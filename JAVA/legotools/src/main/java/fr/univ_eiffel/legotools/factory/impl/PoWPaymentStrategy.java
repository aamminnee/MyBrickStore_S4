package fr.univ_eiffel.legotools.factory.impl;

import com.google.gson.Gson;
import fr.univ_eiffel.legotools.factory.api.ApiSender;
import fr.univ_eiffel.legotools.factory.api.PaymentStrategy;
import fr.univ_eiffel.legotools.factory.security.ProofOfWorkSolver;
import java.io.IOException;
import java.util.HexFormat;

/**
 * Implémentation de la stratégie de paiement par "Minage" (Proof-of-Work).
 * <p>
 * Cette classe réalise l'échange de temps de calcul contre des crédits :
 * tant que le solde est insuffisant, elle demande des problèmes mathématiques
 * complexes au serveur et les résout.
 * </p>
 */
public class PoWPaymentStrategy implements PaymentStrategy {

    private final Gson gson = new Gson();

    // Le résolveur contient l'algorithme de hachage (SHA-256) optimisé.
    private final ProofOfWorkSolver powSolver = new ProofOfWorkSolver("SHA-256");

    /**
     * DTO (Data Transfer Object) représentant le défi envoyé par l'API.
     * Contient les préfixes hexadécimaux et la récompense promise.
     */
    private record Challenge(String data_prefix, String hash_prefix, String reward) {}

    /**
     * DTO représentant la réponse à soumettre à l'API.
     */
    private record ChallengeAnswer(String data_prefix, String hash_prefix, String answer) {}

    /**
     * Constructeur par défaut.
     */
    public PoWPaymentStrategy() {}

    /**
     * Exécute le paiement. Si le solde est insuffisant, déclenche le minage.
     * @param amountNeeded Montant nécessaire pour la transaction.
     * @param currentBalance Solde actuel du compte.
     * @param api Interface permettant de rappeler les méthodes de rechargement.
     * @throws IOException En cas d'erreur de communication avec l'API.
     */
    @Override
    public void pay(long amountNeeded, long currentBalance, ApiSender api) throws IOException {

        // Boucle tant que le solde est insuffisant pour payer la commande
        while (currentBalance < amountNeeded) {
            System.out.println("Stratégie PoW : Minage en cours... (Solde: " + currentBalance + ")");
            
            // On utilise l'interface ApiSender pour ne pas dépendre de HttpURLConnection ici
            String json = api.send("/billing/challenge", "GET", null);
            Challenge challenge = gson.fromJson(json, Challenge.class);
            
            // Conversion des chaînes hexadécimales en octets bruts pour le calcul
            byte[] solution = powSolver.solve(
                HexFormat.of().parseHex(challenge.data_prefix()), 
                HexFormat.of().parseHex(challenge.hash_prefix())
            );
            
            // Soumission de la solution
            String answerHex = HexFormat.of().formatHex(solution);
            ChallengeAnswer answer = new ChallengeAnswer(challenge.data_prefix(), challenge.hash_prefix(), answerHex);    
            api.send("/billing/challenge-answer", "POST", gson.toJson(answer));
            
            // Mise à jour optimiste du solde local
            double rewardVal = Double.parseDouble(challenge.reward());
            currentBalance += (long) rewardVal;
            
            System.out.println("Gagné ! Nouveau solde estimé : " + currentBalance);
        }
        System.out.println("Fonds suffisants atteints !");
    }
}