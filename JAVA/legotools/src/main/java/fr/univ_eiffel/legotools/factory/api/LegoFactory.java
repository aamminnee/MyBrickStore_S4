package fr.univ_eiffel.legotools.factory.api;

import fr.univ_eiffel.legotools.model.FactoryBrick;
import java.io.IOException;
import java.util.List;
import java.util.Map;

/**
 * Interface principale définissant les interactions possibles avec l'usine de briques.
 * <p>
 * Cette interface suit le patron de conception <b>Façade</b> : elle masque la complexité des appels HTTP
 * (authentification, JSON, gestion des erreurs) derrière des méthodes métier simples.
 * <p>
 * <b>Cycle de vie typique d'un achat :</b>
 * <ol>
 * <li>{@link #getBalance()} : Vérifier si on a assez de crédits.</li>
 * <li>{@link #rechargeAccount(long)} : "Miner" des crédits si nécessaire.</li>
 * <li>{@link #requestQuote(Map)} : Demander un devis pour un lot de pièces.</li>
 * <li>{@link #acceptQuote(String)} : Valider et payer ce devis.</li>
 * <li>{@link #retrieveOrder(String)} : Télécharger les briques générées.</li>
 * <li>{@link #verifyBrick(FactoryBrick)} : Vérifier la signature cryptographique des pièces reçues.</li>
 * </ol>
 */
public interface LegoFactory {
    
    /**
     * Récupère le solde actuel du portefeuille client auprès de l'usine.
     * @return Le nombre de crédits disponibles.
     * @throws IOException En cas d'erreur de communication avec le serveur.
     */
    long getBalance() throws IOException;

    /**
     * Lance le processus de rechargement du compte (Proof-of-Work).
     * Cette méthode peut prendre du temps
     * pour résoudre les défis cryptographiques nécessaires à l'obtention de crédits.
     * @param amountNeeded Le montant approximatif souhaité.
     * @throws IOException Si le serveur refuse le rechargement ou est inaccessible.
     */
    void rechargeAccount(long amountNeeded) throws IOException;
    
    /**
     * Demande de chiffrage.
     * L'usine calcule le prix total en fonction des stocks et de la rareté, et bloque ce prix temporairement.
     * @param items Une map associant la référence de la brique (ex: "2-2/c9cae2") à la quantité désirée.
     * @return Un objet contenant l'ID du devis et le prix total.
     * @throws IOException Si une référence est invalide ou le stock insuffisant.
     */
    Quote requestQuote(Map<String, Integer> items) throws IOException;
    
    /**
     * Validation et Paiement.
     * Transforme le devis temporaire en commande ferme. Le montant est débité immédiatement.
     * @param quoteId L'identifiant du devis reçu via {@link #requestQuote(Map)}.
     * @throws IOException Si le solde est insuffisant ou si le devis a expiré.
     */
    void acceptQuote(String quoteId) throws IOException;

    /**
     * Réception.
     * Récupère les actifs numériques associés à une commande payée.
     * @param quoteId L'identifiant de la commande validée.
     * @return La liste des briques avec leurs métadonnées (numéro de série, certificat...).
     * @throws IOException Si la commande n'est pas encore prête ou n'existe pas.
     */
    List<FactoryBrick> retrieveOrder(String quoteId) throws IOException;

    /**
     * Contrôle Qualité.
     * Vérifie mathématiquement que la brique possède une signature valide émise par l'usine.
     * Permet d'éviter l'injection de fausses briques dans le stock.
     * @param brick La brique à analyser.
     * @return {@code true} si la signature correspond aux données et à la clé publique de l'usine.
     */
    boolean verifyBrick(FactoryBrick brick);

    /**
     * Objet immuable (Record) représentant la réponse à une demande de prix.
     * @param id L'identifiant unique du devis.
     * @param price Le montant total en crédits.
     */
    record Quote(String id, float price) {}
}