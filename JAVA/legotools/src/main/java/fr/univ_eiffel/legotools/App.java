package fr.univ_eiffel.legotools;

import fr.univ_eiffel.legotools.factory.StockManager;
import fr.univ_eiffel.legotools.factory.api.AccountRefiller;
import fr.univ_eiffel.legotools.factory.api.LegoFactory;
import fr.univ_eiffel.legotools.factory.impl.HttpRestFactory;
import fr.univ_eiffel.legotools.model.FactoryBrick;
import fr.univ_eiffel.legotools.image.*;
import fr.univ_eiffel.legotools.paving.PavingService;
import io.github.cdimascio.dotenv.Dotenv; 

import java.awt.image.BufferedImage;
import java.io.File;
import java.io.IOException;
import java.util.List;
import java.util.ArrayList;
import java.util.Map;
import java.util.HashMap;
import javax.imageio.ImageIO;

/**
 * Point d'entrée principal de l'application en ligne de commande (CLI).
 * <p>
 * Cette classe joue le rôle de <b>Contrôleur</b> :
 * </p>
 * <ul>
 * <li>1. Elle analyse les arguments passés au lancement du JAR.</li>
 * <li>2. Elle charge la configuration sécurisée (.env).</li>
 * <li>3. Elle délègue le travail aux services spécialisés (Traitement d'image, Communication API, Algorithme C).</li>
 * </ul>
 */
public class App {

    // Chargement des variables d'environnement (Clés API, URLs) pour ne rien hardcoder.
    private static final Dotenv dotenv = Dotenv.configure().ignoreIfMissing().load();

    /**
     * Constructeur privé pour empêcher l'instanciation de cette classe utilitaire/principale.
     */
    private App() {
        throw new IllegalStateException("Utility class");
    }

    /**
     * Récupère une configuration du .env ou des variables système.
     * @param key La clé de la variable d'environnement recherchée.
     * @return La valeur de la configuration, ou null si elle n'existe pas.
     */
    private static String getEnv(String key) {
        String value = dotenv.get(key);
        if (value == null) {
            return System.getenv(key);
        }
        return value;
    }

    /**
     * Méthode principale lancée lors de l'exécution du JAR.
     * @param args Les arguments de la ligne de commande (ex: "refill", "pave input.png ...").
     */
    public static void main(String[] args) {
        if (args.length < 1) {
            printUsage();
            return;
        }

        String command = args[0];

        try {
            // Dispatching des commandes (Pattern Command simplifié)
            switch (command) {
                case "refill" -> runRefill();      // Minage de crédits
                case "resize" -> runResize(args);  // Traitement d'image (Pixelisation)
                case "pave" -> runPave(args);      // Pont vers l'algo C
                case "order" -> runOrder();        // Commande de test
                case "proactive" -> runProactiveOrder(); // Commande automatique sur seuil
                case "visualize" -> runVisualize(args); // Génération PNG depuis TXT
                case "restock" -> runFullRestock();     // Remplissage massif du stock
                case "buy" -> runBuy(args);             // Achat manuel ciblé
                default -> {
                    System.err.println("Commande inconnue : " + command);
                    printUsage();
                }
            }
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    /**
     * Affiche l'aide et la liste des commandes disponibles dans la console.
     */
    private static void printUsage() {
        System.out.println("Usage :");
        System.out.println("  1. Recharger le compte : java -jar legotools.jar refill");
        System.out.println("  2. Redimensionner : java -jar legotools.jar resize <input> <output> <WxH> [strategy]");
        System.out.println("  3. Paver : java -jar legotools.jar pave <input> <output_base> <exe_c> [algo|all]");
        System.out.println("  4. Commander : java -jar legotools.jar order");
        System.out.println("  5. Commande proactive : java -jar legotools.jar proactive");
        System.out.println("  6. Visualiser : java -jar legotools.jar visualize <input_txt> <output_png>");
        System.out.println("  7. Restockage complet : java -jar legotools.jar restock");
        System.out.println("  8. Achat ciblé : java -jar legotools.jar buy <ref> <qty>");
    }

    /**
     * Commande 'refill' : Recharge le compte en résolvant un défi cryptographique (Proof of Work).
     * @throws IOException En cas d'erreur lors de la communication avec l'API.
     */
    private static void runRefill() throws IOException {
        var email = getEnv("LEGOFACTORY_EMAIL");
        var key = getEnv("LEGOFACTORY_KEY");
        var url = getEnv("LEGOFACTORY_URL");

        if (email == null || key == null || url == null) {
            System.err.println("Erreur : Variables LEGOFACTORY manquantes (.env).");
            return;
        }
        
        var refiller = new AccountRefiller(url, email, key);
        System.out.println("Nouveau solde : " + refiller.refill());
    }

    /**
     * Commande 'resize' : Applique une stratégie de réduction de résolution.
     * Configure dynamiquement la stratégie (Strategy Pattern) selon l'argument utilisateur.
     * @param args Les arguments de la ligne de commande (fichiers d'entrée/sortie, dimensions).
     * @throws IOException Si le fichier image ne peut pas être lu ou écrit.
     */
    private static void runResize(String[] args) throws IOException {
        if (args.length < 4) {
            System.out.println("Usage: resize <input> <output> <WxH> [strategy]");
            return;
        }
        String input = args[1];
        String output = args[2];
        String[] dims = args[3].split("x");
        int w = Integer.parseInt(dims[0]);
        int h = Integer.parseInt(dims[1]);
        String algo = (args.length > 4) ? args[4].toLowerCase() : "neighbor";

        ImageProcessor processor = new ImageProcessor();
        switch (algo) {
            case "bilinear" -> processor.setStrategy(new BilinearStrategy());
            case "bicubic" -> processor.setStrategy(new BicubicStrategy());
            case "lanczos" -> processor.setStrategy(new LanczosStrategy());
            case "stepwise" -> processor.setStrategy(new StepwiseStrategy(List.of(
                new NearestNeighborStrategy(),
                new BilinearStrategy(),
                new BicubicStrategy(),
                new LanczosStrategy()
            ), 4));
            case "neighbor" -> processor.setStrategy(new NearestNeighborStrategy());
            default -> System.out.println("Stratégie inconnue, utilisation de NearestNeighbor.");
        }
        processor.processImage(input, output, w, h);
    }

    /**
     * Commande 'pave' : Orchestre l'appel au programme C pour générer un pavage.
     * Peut lancer tous les algos à la suite pour comparaison.
     * @param args Les arguments contenant les chemins d'entrée/sortie et l'exécutable.
     * @throws IOException En cas d'erreur de lecture d'image ou d'exécution du processus C.
     * @throws InterruptedException Si le processus C est interrompu en cours d'exécution.
     */
    private static void runPave(String[] args) throws IOException, InterruptedException {
        if (args.length < 4) {
            System.out.println("Usage: pave <input> <output_prefix> <exe_c> [algo|all]");
            return;
        }
        
        String inputPath = args[1];
        String outputBasePath = args[2];
        String exePath = args[3];
        String algoArg = (args.length > 4) ? args[4] : "all";

        BufferedImage source = ImageIO.read(new File(inputPath));
        if (source == null) throw new IOException("Image introuvable : " + inputPath);

        PavingService service = new PavingService(exePath);

        List<String> algos;
        if ("all".equalsIgnoreCase(algoArg)) {
            algos = List.of("stock", "libre", "minimisation", "rentabilite");
        } else {
            algos = List.of(algoArg);
        }

        String basePath = outputBasePath;
        if (basePath.toLowerCase().endsWith(".png")) {
            basePath = basePath.substring(0, basePath.length() - 4);
        } else if (basePath.toLowerCase().endsWith(".jpg")) {
            basePath = basePath.substring(0, basePath.length() - 4);
        }

        for (String algo : algos) {
            System.out.println("\n--- Traitement : " + algo + " ---");
            try {
                String finalNamePng = basePath + "_" + algo + ".png";
                String finalNameTxt = basePath + "_" + algo + ".txt";
                BufferedImage result = service.generatePaving(source, algo, new File(finalNameTxt));
                ImageIO.write(result, "png", new File(finalNamePng));
                System.out.println("Image générée : " + finalNamePng);
            } catch (Exception e) {
                System.err.println("Erreur sur l'algo " + algo + " : " + e.getMessage());
            }
        }
    }

    /**
     * Commande 'order' : Passe une commande simple d'une seule brique pour tester le fonctionnement.
     */
    private static void runOrder() {
        var email = getEnv("LEGOFACTORY_EMAIL");
        var key = getEnv("LEGOFACTORY_KEY");
        var url = getEnv("LEGOFACTORY_URL");

        if (email == null || key == null || url == null) {
            System.err.println("Erreur : Configuration manquante (.env)");
            return;
        }

        HttpRestFactory factory = new HttpRestFactory(url, email, key);
        StockManager stock = new StockManager();
        stock.showStock();

        try {
            long balance = factory.getBalance();
            System.out.println("Solde : " + balance);

            Map<String, Integer> panier = Map.of("2-2/c9cae2", 1);
            
            LegoFactory.Quote quote = factory.requestQuote(panier);
            
            factory.acceptQuote(quote.id());
            
            stock.recordFactoryOrder(quote.id(), quote.price(), panier);
            
            System.out.println("Attente livraison...");
            processDelivery(factory, stock, quote.id(), 1);

        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    /**
     * Commande 'proactive' : Analyse la vue SQL 'View_LowStockDetails'
     * et commande automatiquement les pièces manquantes pour atteindre un seuil de sécurité.
     */
    private static void runProactiveOrder() {
        var email = getEnv("LEGOFACTORY_EMAIL");
        var key = getEnv("LEGOFACTORY_KEY");
        var url = getEnv("LEGOFACTORY_URL");

        if (email == null || key == null || url == null) {
             System.err.println("Variables d'environnement manquantes");
             return;
        }

        StockManager stockManager = new StockManager();
        HttpRestFactory factory = new HttpRestFactory(url, email, key);
        
        System.out.println("Interrogation de la vue 'View_LowStockDetails'...");
        
        Map<String, Integer> alerts = stockManager.getLowStockItems();
        
        if (alerts.isEmpty()) {
            System.out.println("Aucune alerte. Tout le stock est superieur à 50.");
            return;
        }

        Map<String, Integer> toOrder = new HashMap<>();
        int TARGET_BUFFER = 75; 
        
        for (Map.Entry<String, Integer> entry : alerts.entrySet()) {
            String itemKey = entry.getKey();
            int current = entry.getValue();
            
            int needed = TARGET_BUFFER - current;
            
            if (needed > 0) {
                toOrder.put(itemKey, needed);
                System.out.println(" > ALERTE : " + itemKey + " (Stock: " + current + " -> Commande: " + needed + ")");
            }
        }
        
        try {
            System.out.println("Envoi commande pour " + toOrder.size() + " references...");
            LegoFactory.Quote quote = factory.requestQuote(toOrder);
            
            long balance = factory.getBalance();
            if (balance < quote.price()) {
                long missing = (long)quote.price() - balance + 500;
                System.out.println(" ! Solde insuffisant. Minage automatique (" + missing + ")...");
                factory.rechargeAccount(missing);
            }

            factory.acceptQuote(quote.id());
            stockManager.recordFactoryOrder(quote.id(), quote.price(), toOrder);
            
            int totalExpected = toOrder.values().stream().mapToInt(Integer::intValue).sum();
            processDelivery(factory, stockManager, quote.id(), totalExpected);
            
        } catch (Exception e) {
            System.err.println("Erreur proactive : " + e.getMessage());
        }
    }
    
    /**
     * Logique de Polling (Attente active) pour la réception de commande.
     * <p>
     * L'usine met du temps à fabriquer les pièces. On boucle tant qu'on n'a pas tout reçu.
     * Une fois reçues, on vérifie cryptographiquement chaque brique avant de l'ajouter au stock.
     * </p>
     * @param factory La façade de communication avec l'usine.
     * @param stock Le gestionnaire de stock local.
     * @param quoteId L'identifiant de la commande à suivre.
     * @param expectedQuantity Le nombre de briques attendues.
     * @throws IOException En cas d'erreur réseau.
     * @throws InterruptedException Si l'attente est interrompue.
     */
    private static void processDelivery(HttpRestFactory factory, StockManager stock, String quoteId, int expectedQuantity) throws IOException, InterruptedException {
        List<FactoryBrick> briques = new ArrayList<>();
        
        while (briques.size() < expectedQuantity) {
            briques = factory.retrieveOrder(quoteId);
            
            if (briques.size() < expectedQuantity) {
                System.out.print("\rAttente livraison... (" + briques.size() + "/" + expectedQuantity + ")");
                Thread.sleep(1000);
            }
        }
        System.out.println("\nReception complete de " + briques.size() + " briques.");
        
        List<FactoryBrick> verifiedBricks = new ArrayList<>();
        for (FactoryBrick b : briques) {
            if (factory.verifyBrick(b)) {
                verifiedBricks.add(b);
            } else {
                System.err.println("ALERTE : Brique rejetee (signature invalide) : " + b.serial());
            }
        }
        
        if (!verifiedBricks.isEmpty()) {
            stock.addBricks(verifiedBricks);
        }
    }

    /**
     * Commande 'visualize' : Génère une représentation graphique (PNG) d'un fichier de pavage texte.
     * @param args Les arguments contenant le fichier texte source et la destination PNG.
     * @throws IOException En cas d'erreur de lecture ou d'écriture.
     */
    private static void runVisualize(String[] args) throws IOException {
        if (args.length < 3) {
            System.out.println("Usage: visualize <input_txt> <output_png>");
            return;
        }
        String inputPath = args[1];
        String outputPath = args[2];

        PavingService service = new PavingService("dummy");
        service.createVisualization(new File(inputPath), new File(outputPath));
        System.out.println("Visualisation generee : " + outputPath);
    }

    /**
     * Commande 'restock' : Lance un réapprovisionnement massif de toutes les références connues.
     * Tente de commander 75 unités de chaque type de brique existant en base.
     */
    private static void runFullRestock() {
        var email = getEnv("LEGOFACTORY_EMAIL");
        var key = getEnv("LEGOFACTORY_KEY");
        var url = getEnv("LEGOFACTORY_URL");

        if (email == null || key == null || url == null) {
            System.err.println("Erreur config (.env)");
            return;
        }

        StockManager stock = new StockManager();
        HttpRestFactory factory = new HttpRestFactory(url, email, key);

        System.out.println("Preparation de la commande massive (75 unités par brique)...");

        List<String> allTypes = stock.getAllBrickTypes();
        
        if (allTypes.isEmpty()) {
            System.out.println("Aucune brique trouvée en base.");
            return;
        }

        int BATCH_SIZE = 50;
        Map<String, Integer> currentBatch = new HashMap<>();
        
        System.out.println("Traitement de " + allTypes.size() + " references...");

        for (String type : allTypes) {
            currentBatch.put(type, 75);

            if (currentBatch.size() >= BATCH_SIZE) {
                processBatch(factory, stock, currentBatch);
                currentBatch.clear();
            }
        }
        
        if (!currentBatch.isEmpty()) {
            processBatch(factory, stock, currentBatch);
        }
        
        System.out.println("Restockage terminé.");
    }

    /**
     * Traite un lot de commande avec gestion d'erreur (Fail-Safe).
     * Si une commande de groupe échoue (ex: 1 référence invalide parmi 50),
     * on tente de ré-identifier les éléments valides un par un pour ne pas tout annuler.
     * @param factory L'interface vers l'API de l'usine.
     * @param stock Le gestionnaire de stock pour l'enregistrement.
     * @param batch La map des articles à commander (Référence -> Quantité).
     */
    private static void processBatch(HttpRestFactory factory, StockManager stock, Map<String, Integer> batch) {
        try {
            LegoFactory.Quote quote = factory.requestQuote(batch);
            factory.acceptQuote(quote.id());
            stock.recordFactoryOrder(quote.id(), quote.price(), batch);
            System.out.println("// Lot de " + batch.size() + " références commandé avec succès.");
            
            int total = batch.values().stream().mapToInt(Integer::intValue).sum();
            processDelivery(factory, stock, quote.id(), total);
            
        } catch (Exception e) {
            System.err.println("// Erreur sur le lot (" + e.getMessage() + "). Filtrage des items invalides...");
            
            Map<String, Integer> safeBatch = new HashMap<>();
            
            for (Map.Entry<String, Integer> entry : batch.entrySet()) {
                try {
                    factory.requestQuote(Map.of(entry.getKey(), entry.getValue()));
                    safeBatch.put(entry.getKey(), entry.getValue());
                } catch (Exception ex) {
                    System.err.println("// Item invalide retiré de la commande : " + entry.getKey());
                }
            }
            
            if (!safeBatch.isEmpty()) {
                try {
                    LegoFactory.Quote quote = factory.requestQuote(safeBatch);
                    factory.acceptQuote(quote.id());
                    stock.recordFactoryOrder(quote.id(), quote.price(), safeBatch);
                    System.out.println("// Lot corrigé (" + safeBatch.size() + " références) commandé.");
                    
                    int totalSafe = safeBatch.values().stream().mapToInt(Integer::intValue).sum();
                    processDelivery(factory, stock, quote.id(), totalSafe);
                    
                } catch (Exception ex) {
                    ex.printStackTrace();
                }
            }
        }
    }

    /**
     * Commande 'buy' : Effectue un achat manuel ciblé d'une référence spécifique.
     * @param args Les arguments contenant la référence et la quantité.
     */
    private static void runBuy(String[] args) {
        if (args.length < 3) {
            System.out.println("Usage: buy <reference> <quantité>");
            System.out.println("Exemple: buy 2-2/c9cae2 50");
            return;
        }
        
        String reference = args[1];
        int quantity;
        
        try {
            quantity = Integer.parseInt(args[2]);
        } catch (NumberFormatException e) {
            System.err.println("Erreur : La quantité doit être un nombre entier.");
            return;
        }
        
        var email = getEnv("LEGOFACTORY_EMAIL");
        var key = getEnv("LEGOFACTORY_KEY");
        var url = getEnv("LEGOFACTORY_URL");

        if (email == null || key == null || url == null) {
            System.err.println("Erreur config (.env)");
            return;
        }

        StockManager stock = new StockManager();
        HttpRestFactory factory = new HttpRestFactory(url, email, key);

        try {
            System.out.println("Preparation de la commande : " + quantity + " x " + reference);
            
            Map<String, Integer> itemToOrder = Map.of(reference, quantity);
            
            LegoFactory.Quote quote = factory.requestQuote(itemToOrder);
            System.out.println("Devis reçu : " + quote.price() + " credits");
            
            factory.acceptQuote(quote.id());
            
            stock.recordFactoryOrder(quote.id(), quote.price(), itemToOrder);
            
            System.out.println("Commande validee. En attente de livraison...");
            
            processDelivery(factory, stock, quote.id(), quantity);
            
        } catch (Exception e) {
            System.err.println("Echec de la commande (" + e.getMessage() + ")");
        }
    }
}