package fr.univ_eiffel.legotools.factory.api;

import fr.univ_eiffel.legotools.factory.security.ProofOfWorkSolver;
import java.io.IOException;
import java.net.HttpURLConnection;
import java.net.URI;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.util.HexFormat;

import com.google.gson.Gson;

/**
 * Gère le système de "minage" pour recharger le solde du compte client.
 * <p>
 * Cette classe implémente le protocole Proof-of-Work (Preuve de Travail) exigé par l'API de l'usine :
 * </p>
 * <ol>
 * <li>1. Demande d'un défi cryptographique (Challenge).</li>
 * <li>2. Résolution locale du défi (calcul intensif CPU).</li>
 * <li>3. Envoi de la solution pour obtenir des crédits.</li>
 * </ol>
 */
public class AccountRefiller {
    
    /**
     * Solveur de preuve de travail utilisant SHA-256.
     */
    public static final ProofOfWorkSolver POW_SOLVER = new ProofOfWorkSolver("SHA-256");

    private final String serverUrl;
    private final String email;
    private final String apiKey;

    private Gson gson = new Gson();

    /**
     * Initialise le rechargeur de compte avec les identifiants API.
     * @param serverUrl L'URL de base de l'usine
     * @param email L'email du compte client.
     * @param apiKey La clé secrète pour signer les requêtes.
     */
    public AccountRefiller(String serverUrl, String email, String apiKey) {
        this.serverUrl = serverUrl;
        this.email = email;
        this.apiKey = apiKey;
    }

    /**
     * Structure représentant un défi cryptographique reçu du serveur.
     * @param data_prefix Préfixe des données à utiliser.
     * @param hash_prefix Préfixe que le hash résultant doit matcher.
     */
    public record Challenge(String data_prefix, String hash_prefix) {}

    /**
     * Récupère un nouveau défi auprès du serveur.
     * Le serveur envoie un préfixe de données et un préfixe de hash cible.
     * @return L'objet Challenge contenant les contraintes à résoudre.
     * @throws IOException Si l'API est inaccessible ou rejette les identifiants.
     */
    public Challenge fetchChallenge() throws IOException {
        @SuppressWarnings("deprecation")
        var connection = (HttpURLConnection) URI.create(serverUrl + "/billing/challenge").toURL().openConnection();

        // Authentification via headers personnalisés
        connection.addRequestProperty("X-Email", email);
        connection.addRequestProperty("X-Secret-Key", apiKey);
        
        int status = connection.getResponseCode();
        if (status != 200)
            throw new IOException("Cannot get the challenge: status code is " + status);

        var answer = new String(connection.getInputStream().readAllBytes(), StandardCharsets.UTF_8);
        return gson.fromJson(answer, Challenge.class);
    }

    /**
     * Résout le défi cryptographique localement.
     * @param challenge Le défi à résoudre.
     * @return Un tableau d'octets représentant la solution trouvée (nonce).
     */
    public byte[] solveChallenge(Challenge challenge) {
        var startTime = System.nanoTime();

        // Conversion des chaînes hexadécimales en tableaux d'octets pour le traitement
        byte[] dataPrefix = HexFormat.of().parseHex(challenge.data_prefix());
        byte[] hashPrefix = HexFormat.of().parseHex(challenge.hash_prefix());

        // Délégation du calcul lourd au Solver
        byte[] solved = POW_SOLVER.solve(dataPrefix, hashPrefix);
        System.err.println("Challenge solved in " + (System.nanoTime() - startTime)/1e9 + " seconds");
        return solved;
    }

    /**
     * Structure représentant la réponse à envoyer au serveur.
     * @param data_prefix Le préfixe de données original.
     * @param hash_prefix Le préfixe de hash original.
     * @param answer La solution trouvée (Hexadécimal).
     */
    public record ChallengeAnswer(String data_prefix, String hash_prefix, String answer) {}

    /**
     * Envoie la solution trouvée au serveur pour validation.
     * @param challengeAnswer La réponse formatée contenant la solution.
     * @throws IOException Si la connexion échoue ou si la réponse est rejetée (Status != 200).
     */
    public void submitChallengeAnswer(ChallengeAnswer challengeAnswer) throws IOException {
        @SuppressWarnings("deprecation")
        var connection = (HttpURLConnection) URI.create(serverUrl + "/billing/challenge-answer").toURL().openConnection();

        connection.setRequestMethod("POST");
        connection.setDoOutput(true);

        connection.addRequestProperty("X-Email", email);
        connection.addRequestProperty("X-Secret-Key", apiKey);

        String body = gson.toJson(challengeAnswer);
        connection.getOutputStream().write(body.getBytes(StandardCharsets.UTF_8));

        int statusCode = connection.getResponseCode();
        if (statusCode != 200)
            throw new IOException("Status code is " + statusCode + " message:" + connection.getResponseMessage());
    }

    /**
     * Structure encapsulant la réponse de solde.
     * @param balance Le solde actuel sous forme de chaîne.
     */
    public record AccountBalance(String balance) {};

    /**
     * Récupère le solde actuel pour confirmer le rechargement.
     * @return Le solde du compte sous forme de chaîne.
     * @throws IOException Si la requête échoue.
     */
    public String fetchAccountBalance() throws IOException {
        @SuppressWarnings("deprecation")
        var connection = (HttpURLConnection) URI.create(serverUrl + "/billing/balance").toURL().openConnection();

        connection.addRequestProperty("X-Email", email);
        connection.addRequestProperty("X-Secret-Key", apiKey);

        int status = connection.getResponseCode();
        if (status != 200)
            throw new IOException("Cannot get the balance, status code: " + status);

        var answer = new String(connection.getInputStream().readAllBytes(), StandardCharsets.UTF_8);
        return gson.fromJson(answer, AccountBalance.class).balance();
    }

    /**
     * Exécute le processus complet de rechargement (Minage).
     * @return Le nouveau solde après rechargement.
     * @throws IOException En cas d'erreur réseau ou de validation.
     */
    public String refill() throws IOException {
        var challenge = fetchChallenge();
        System.err.println("Received PoW challenge: " + challenge);

        var solved = solveChallenge(challenge);
        var challengeAnswer = new ChallengeAnswer(challenge.data_prefix(), challenge.hash_prefix(), HexFormat.of().formatHex(solved));

        submitChallengeAnswer(challengeAnswer);
        return fetchAccountBalance();
    }
}