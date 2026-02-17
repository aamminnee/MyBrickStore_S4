package fr.univ_eiffel.legotools.factory.api;

import java.io.IOException;

/**
 * Interface fonctionnelle définissant le contrat d'envoi de requêtes HTTP.
 * L'interface permet de découpler la logique métier (LegoFactory) de l'implémentation technique réseau (HttpURLConnection).
 */

@FunctionalInterface
public interface ApiSender {

    /**
     * Envoie une requête brute vers l'API.
     * @param endpoint L'URL complète ou le chemin de la ressource cible.
     * @param method Le verbe HTTP à utiliser ("GET", "POST", "DELETE"...).
     * @param body Le corps de la requête.
     * @return La réponse brute du serveur sous forme de chaîne de caractères.
     * @throws IOException Si une erreur réseau survient (timeout, DNS, serveur injoignable...).
     */
    String send(String endpoint, String method, String body) throws IOException;
}