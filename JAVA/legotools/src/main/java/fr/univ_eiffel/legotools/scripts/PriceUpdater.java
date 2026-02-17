package fr.univ_eiffel.legotools.scripts;

import fr.univ_eiffel.legotools.factory.impl.HttpRestFactory;
import fr.univ_eiffel.legotools.factory.api.LegoFactory;
import fr.univ_eiffel.legotools.factory.StockManager;

import java.util.HashMap;
import java.util.Map;

/**
 * Script de synchronisation des prix catalogue.
 * <p>
 * Ce script interroge l'API de l'usine pour mettre à jour les coûts dans la base de données locale.
 * Il implémente une stratégie de <b>Lissage par lot</b> pour contourner les problèmes d'arrondi de l'API.
 * </p>
 */
public class PriceUpdater {

    /**
     * Taille du lot pour la demande de devis.
     * <p>
     * <b>Pourquoi 10 ?</b><br>
     * L'API de l'usine arrondit souvent les prix au centime le plus proche.
     * Pour une pièce coûtant 0.005 crédits :
     * Cela permet d'obtenir une précision "sous-centime".
     * </p>
     */
    private static final int BATCH_SIZE = 10;

    /**
     * Constructeur privé.
     */
    private PriceUpdater() {
        throw new IllegalStateException("Utility class");
    }

    /**
     * Exécute la mise à jour globale des prix.
     * @param url L'adresse de l'usine.
     * @param email Identifiant API.
     * @param key Clé secrète API.
     */
    public static void run(String url, String email, String key) {
        System.out.println("--- DÉMARRAGE DE LA MISE À JOUR DES PRIX (LISSAGE PAR " + BATCH_SIZE + ") ---");

        StockManager stockManager = new StockManager();
        HttpRestFactory factory = new HttpRestFactory(url, email, key);

        Map<Integer, String> allItems = stockManager.getAllItemsRef();
        System.out.println(allItems.size() + " articles trouvés en base.");

        int count = 0;

        for (Map.Entry<Integer, String> entry : allItems.entrySet()) {
            int id = entry.getKey();
            String ref = entry.getValue();

            try {
                // Création d'un panier "Batch"
                Map<String, Integer> cart = new HashMap<>();
                cart.put(ref, BATCH_SIZE);

                // Demande de devis à l'usine
                LegoFactory.Quote quote = factory.requestQuote(cart);
                
                // Extraction du prix unitaire réel
                double totalPrice = quote.price();
                double unitPrice = totalPrice / BATCH_SIZE;

                // Persistance du nouveau prix en BDD
                stockManager.updateItemPrice(id, unitPrice);
                
                System.out.printf("[%d/%d] %s : Total %.2f pour %d => Unité %.3f €%n", 
                        ++count, allItems.size(), ref, totalPrice, BATCH_SIZE, unitPrice);

                Thread.sleep(100); 

            } catch (Exception e) {
                System.err.println("Erreur sur l'article " + ref + " : " + e.getMessage());
            }
        }

        System.out.println("--- MISE À JOUR TERMINÉE ---");
    }
}