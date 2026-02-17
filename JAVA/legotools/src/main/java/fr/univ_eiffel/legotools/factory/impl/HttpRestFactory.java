package fr.univ_eiffel.legotools.factory.impl;

import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;
import fr.univ_eiffel.legotools.factory.api.ApiSender;
import fr.univ_eiffel.legotools.factory.api.LegoFactory;
import fr.univ_eiffel.legotools.factory.api.PaymentStrategy;
import fr.univ_eiffel.legotools.model.FactoryBrick;

import java.io.*;
import java.lang.reflect.Type;
import java.math.BigInteger;
import java.net.HttpURLConnection;
import java.net.URI;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.security.KeyFactory;
import java.security.PublicKey;
import java.security.Signature;
import java.security.spec.X509EncodedKeySpec;
import java.util.*;

/**
 * Implémentation concrète de l'interface {@link LegoFactory} communiquant via une API REST.
 * <p>
 * Cette classe gère toute le réseau :
 * </p>
 * <ul>
 * <li>Authentification (Headers X-Email, X-Secret-Key).</li>
 * <li>Sérialisation/Désérialisation JSON.</li>
 * <li>Gestion des codes d'erreur HTTP (402 Payment Required, 404 Not Found...).</li>
 * <li>Vérification cryptographique des signatures (Ed25519).</li>
 * </ul>
 */
public class HttpRestFactory implements LegoFactory {

    private final String serverUrl;
    private final String email;
    private final String apiKey;
    private final Gson gson = new Gson();
    
    // Cache pour la clé publique afin d'éviter de la retélécharger à chaque vérification de brique.
    private static PublicKey cachedPublicKey = null;

    // Stratégie de paiement par défaut.
    private PaymentStrategy paymentStrategy = new PoWPaymentStrategy();

    /**
     * Initialise la factory REST.
     * @param serverUrl URL de base.
     * @param email Email client.
     * @param apiKey Clé API.
     */
    public HttpRestFactory(String serverUrl, String email, String apiKey) {
        this.serverUrl = serverUrl;
        this.email = email;
        this.apiKey = apiKey;
    }

    /**
     * Permet de changer l'algorithme de paiement à la volée (Pattern Strategy).
     * @param strategy La nouvelle stratégie (ex: Carte Bancaire, Paypal, etc.).
     */
    public void setPaymentStrategy(PaymentStrategy strategy) {
        this.paymentStrategy = strategy;
    }

    // Interface fonctionnelle interne pour permettre à la stratégie de rappeler sendRequest
    private final ApiSender apiSender = this::sendRequest;

    /**
     * Méthode centrale d'envoi de requêtes HTTP.
     * Gère l'écriture du corps, les headers d'authentification et la lecture des erreurs.
     */
    private String sendRequest(String endpoint, String method, String jsonBody) throws IOException {
        URL url = URI.create(serverUrl + endpoint).toURL();
        HttpURLConnection conn = (HttpURLConnection) url.openConnection();
        conn.setRequestMethod(method);

        // Authentification systématique
        conn.setRequestProperty("X-Email", email);
        conn.setRequestProperty("X-Secret-Key", apiKey);
        
        if (jsonBody != null) {
            conn.setDoOutput(true);
            try (OutputStream os = conn.getOutputStream()) {
                os.write(jsonBody.getBytes(StandardCharsets.UTF_8));
            }
        }

        int code = conn.getResponseCode();

        // Gestion unifiée des erreurs : on lance une IOException contenant le message du serveur
        if (code >= 400) {
            try (InputStream es = conn.getErrorStream()) {
                String errorMsg = (es != null) ? new String(es.readAllBytes(), StandardCharsets.UTF_8) : "";
                throw new IOException("HTTP_" + code + " : " + errorMsg);
            }
        }
        try (InputStream is = conn.getInputStream()) {
            return new String(is.readAllBytes(), StandardCharsets.UTF_8);
        }
    }

    @Override
    public long getBalance() throws IOException {
        String json = sendRequest("/billing/balance", "GET", null);
        Type type = new TypeToken<Map<String, String>>(){}.getType();
        Map<String, String> response = gson.fromJson(json, type);
        
        // Gestion de la compatibilité API (parfois "balance", parfois "amount")
        String balanceStr = response.get("balance");
        if (balanceStr == null) balanceStr = response.get("amount");
        return (long) Double.parseDouble(balanceStr);
    }

    @Override
    public void rechargeAccount(long minAmount) throws IOException {
        long current = getBalance();

        // Délégation complète à la stratégie choisie (ex: minage)
        paymentStrategy.pay(minAmount, current, apiSender);
    }

    @Override
    public LegoFactory.Quote requestQuote(Map<String, Integer> items) throws IOException {
        String body = gson.toJson(items);
        String response = sendRequest("/ordering/quote-request", "POST", body);
        Type type = new TypeToken<Map<String, Object>>(){}.getType();
        Map<String, Object> quoteMap = gson.fromJson(response, type);
        
        String id = (String) quoteMap.get("id");
        Object priceObj = quoteMap.get("price");
        float price = Float.parseFloat(priceObj.toString());
        
        System.out.println("devis reçu : " + price + " crédits (id: " + id + ")");
        return new LegoFactory.Quote(id, price);
    }

    /**
     * Accepte un devis, avec une logique de "Retry" intelligente.
     * Si le serveur répond HTTP 402 (Paiement requis), on tente un rechargement automatique
     * puis on relance la commande.
     */
    @Override
    public void acceptQuote(String quoteId) throws IOException {
        try {
            sendRequest("/ordering/order/" + quoteId, "POST", null);
        } catch (IOException e) {
            if (e.getMessage().contains("HTTP_402")) {
                System.out.println("erreur 402 : solde insuffisant. tentative de rechargement...");
                rechargeAccount(1000); 
                sendRequest("/ordering/order/" + quoteId, "POST", null);
            } else if (e.getMessage().contains("HTTP_404")) {
                throw new IOException("Le devis " + quoteId + " a expiré.");
            } else {
                throw e;
            }
        }
    }

    @Override
    public List<FactoryBrick> retrieveOrder(String quoteId) throws IOException {
        String json = sendRequest("/ordering/deliver/" + quoteId, "GET", null);
        DeliveryResponse dr = gson.fromJson(json, DeliveryResponse.class);
        if (dr.built_blocks() == null) return List.of();
        return dr.built_blocks();
    }

    /**
     * Vérifie l'authenticité d'une brique.
     * Tente d'abord une vérification en ligne, et bascule sur une vérification
     * hors-ligne en cas d'échec réseau.
     */
    @Override
    public boolean verifyBrick(FactoryBrick brick) {
        try {
            String body = gson.toJson(brick);
            sendRequest("/verify", "POST", body);
            return true;
        } catch (IOException e) {
            System.err.println("vérification en ligne échouée (" + e.getMessage() + "), tentative hors-ligne...");
            return verifyBrickOffline(brick);
        }
    }

    /**
     * Vérification cryptographique locale (Ed25519).
     * @param brick La brique à vérifier.
     * @return true si la signature est valide.
     */
    public boolean verifyBrickOffline(FactoryBrick brick) {
        try {
            if (cachedPublicKey == null) {
                fetchPublicKey();
            }

            // Reconstruction des données signées (Format binaire spécifique de l'usine)
            byte[] nameBytes = brick.name().getBytes(StandardCharsets.US_ASCII);
            BigInteger serialBi = new BigInteger(brick.serial(), 16);
            byte[] serialRaw = serialBi.toByteArray();

            // Padding du serial pour qu'il fasse exactement 16 octets
            byte[] serialBytes = new byte[16];
            if (serialRaw.length > 16) {
                System.arraycopy(serialRaw, serialRaw.length - 16, serialBytes, 0, 16);
            } else {
                System.arraycopy(serialRaw, 0, serialBytes, 16 - serialRaw.length, serialRaw.length);
            }

            ByteArrayOutputStream outputStream = new ByteArrayOutputStream();
            outputStream.write(nameBytes);
            outputStream.write(serialBytes);
            byte[] dataToVerify = outputStream.toByteArray();

            // Vérification de la signature
            Signature sig = Signature.getInstance("Ed25519");
            sig.initVerify(cachedPublicKey);
            sig.update(dataToVerify);
            
            byte[] signatureBytes = hexStringToByteArray(brick.certificate());
            return sig.verify(signatureBytes);

        } catch (Exception e) {
            System.err.println("erreur vérification hors-ligne : " + e.getMessage());
            return false;
        }
    }

    // Récupère et met en cache la clé publique de l'autorité de certification (l'usine)
    private void fetchPublicKey() throws Exception {
        String json = sendRequest("/signature-public-key", "GET", null);
        String keyHex = json.replaceAll("[^a-fA-F0-9]", ""); 
        byte[] keyBytes = hexStringToByteArray(keyHex);
        X509EncodedKeySpec spec = new X509EncodedKeySpec(keyBytes);
        KeyFactory kf = KeyFactory.getInstance("Ed25519");
        cachedPublicKey = kf.generatePublic(spec);
    }

    // convertit une chaîne hexadécimale en tableau d'octets
    private byte[] hexStringToByteArray(String s) {
        int len = s.length();
        byte[] data = new byte[len / 2];
        for (int i = 0; i < len; i += 2) {
            data[i / 2] = (byte) ((Character.digit(s.charAt(i), 16) << 4)
                                 + Character.digit(s.charAt(i+1), 16));
        }
        return data;
    }

    private record DeliveryResponse(Boolean completion_date, List<FactoryBrick> built_blocks) {}
}