package fr.univ_eiffel.legotools.factory;

import fr.univ_eiffel.legotools.model.FactoryBrick;
import io.github.cdimascio.dotenv.Dotenv;

import java.sql.*;
import java.util.*;

/**
 * Gestionnaire de persistance (DAO) pour le stock de briques.
 * <p>
 * Cette classe fait le lien entre l'application Java et la base de données MySQL.
 * Elle est responsable de :
 * </p>
 * <ul>
 * <li>Suivre l'état du stock en temps réel (Entrées - Sorties).</li>
 * <li>Enregistrer les commandes passées à l'usine.</li>
 * <li>Stocker les preuves d'authenticité (certificats) des briques reçues.</li>
 * </ul>
 */
public class StockManager {

    // Configuration de la connexion bdd
    private final String url;
    private final String user;
    private final String password;

    /**
     * Initialise la connexion en chargeant les identifiants depuis le fichier .env.
     */
    public StockManager() {
        Dotenv dotenv = Dotenv.configure().ignoreIfMissing().load();

        // 1. Récupération des variables
        String host = dotenv.get("DB_HOST");
        String port = dotenv.get("DB_PORT");
        String dbName = dotenv.get("DB_NAME");
        String u = dotenv.get("DB_USER");
        String p = dotenv.get("DB_PASS");

        // 2. Vérification
        if (host == null || host.isEmpty()) {
            throw new RuntimeException("Erreur: DB_HOST est vide ou manquant dans le fichier .env");
        }
        if (port == null || port.isEmpty()) {
            port = "3306"; 
        }
        if (dbName == null || dbName.isEmpty()) {
            throw new RuntimeException("Erreur: DB_NAME est manquant dans le fichier .env");
        }
        if (u == null || u.isEmpty()) {
            throw new RuntimeException("Erreur: DB_USER est manquant dans le fichier .env");
        }
        if (p == null) {
            p = ""; 
        }

        // 3. Construction de l'URL JDBC
        this.url = "jdbc:mysql://" + host + ":" + port + "/" + dbName;
        this.user = u;
        this.password = p;
        
        System.out.println("// Connexion BDD initiee sur " + host + ":" + port);

        initTables();
    }

    /**
     * Crée les tables spécifiques au module Java si elles n'existent pas.
     */
    private void initTables() {
        // Table stockant les numéros de série uniques et certificats cryptographiques
        String sql = """
            CREATE TABLE IF NOT EXISTS FactoryBrick (
                serial VARCHAR(32) PRIMARY KEY,
                certificate TEXT,
                shape_id INT,
                color_id INT,
                purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """;
        try (Connection conn = getConnection(); Statement stmt = conn.createStatement()) {
            stmt.execute(sql);
        } catch (SQLException e) {
            System.err.println("// erreur init table factorybrick : " + e.getMessage());
        }
    }

    private Connection getConnection() throws SQLException {
        return DriverManager.getConnection(url, user, password);
    }

    /**
     * Calcule le stock courant pour chaque référence.
     * @return Une Map associant "Reference" (ex: "2-2/red") à la quantité disponible.
     */
    public Map<String, Integer> getStockCounts() {
        Map<String, Integer> counts = new HashMap<>();
        
        String sql = """
            SELECT 
                s.name AS shape_name, 
                c.hex_color,
                (IFNULL(entries.total_in, 0) - IFNULL(sales.total_out, 0)) AS quantity
            FROM Item i
            JOIN Shapes s ON i.shape_id = s.id_shape
            JOIN Colors c ON i.color_id = c.id_color
            LEFT JOIN (SELECT id_Item, SUM(quantity) AS total_in FROM StockEntry GROUP BY id_Item) entries ON i.id_Item = entries.id_Item
            LEFT JOIN (SELECT id_Item, SUM(quantity) AS total_out FROM OrderItem GROUP BY id_Item) sales ON i.id_Item = sales.id_Item
        """;

        try (Connection conn = getConnection(); 
             Statement stmt = conn.createStatement(); 
             ResultSet rs = stmt.executeQuery(sql)) {
            
            while (rs.next()) {
                String key = rs.getString("shape_name") + "/" + rs.getString("hex_color").toLowerCase();
                counts.put(key, rs.getInt("quantity"));
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return counts;
    }

    /**
     * Interroge une Vue SQL pour identifier les produits en rupture ou stock critique.
     * @return Une map associant la référence de la brique à son niveau de stock actuel.
     */
    public Map<String, Integer> getLowStockItems() {
        Map<String, Integer> alerts = new HashMap<>();
        
        String sql = "SELECT shape_name, hex_color, current_stock FROM View_LowStockDetails";

        try (Connection conn = getConnection(); 
             Statement stmt = conn.createStatement(); 
             ResultSet rs = stmt.executeQuery(sql)) {
            
            while (rs.next()) {
                // Construction de la clé "2-2/c9cae2"
                String key = rs.getString("shape_name") + "/" + rs.getString("hex_color").toLowerCase();
                alerts.put(key, rs.getInt("current_stock"));
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return alerts;
    }

    /**
     * Enregistre un lot de nouvelles briques reçues de l'usine.
     * <p>
     * Soit toutes les briques sont ajoutées (Stock + Métadonnées), soit aucune (Rollback).
     * Cela garantit qu'on ne perd jamais la traçabilité d'une brique (Serial) si le stock plante.
     * </p>
     * @param bricks La liste des objets briques validés.
     */
    public void addBricks(List<FactoryBrick> bricks) {
        if (bricks.isEmpty()) return;

        String insertBrick = "INSERT IGNORE INTO FactoryBrick (serial, certificate, shape_id, color_id) VALUES (?, ?, ?, ?)";
        // Ajout dans StockEntry pour incrémenter le stock physique disponible à la vente
        String insertStock = "INSERT INTO StockEntry (id_Item, quantity, date_import) VALUES (?, 1, NOW())";
        
        try (Connection conn = getConnection()) {
            conn.setAutoCommit(false);
            try (PreparedStatement psBrick = conn.prepareStatement(insertBrick);
                 PreparedStatement psStock = conn.prepareStatement(insertStock)) {
                
                int count = 0;
                for (FactoryBrick b : bricks) {
                    
                    // Traduction Forme/Couleur (API) -> IDs (BDD)
                    int[] ids = findItemIds(conn, b.shapeName(), b.color());
                    
                    if (ids == null) {
                        System.err.println("// item inconnu en base : " + b.shapeName() + " / " + b.color());
                        continue; 
                    }

                    // Sauvegarde des métadonnées usine
                    psBrick.setString(1, b.serial());
                    psBrick.setString(2, b.certificate());
                    psBrick.setInt(3, ids[1]); // shape_id
                    psBrick.setInt(4, ids[2]); // color_id
                    psBrick.executeUpdate();

                    // Mise à jour du stock quantité
                    psStock.setInt(1, ids[0]); // id_item
                    psStock.executeUpdate();
                    
                    count++;
                }
                conn.commit();
                System.out.println("// " + count + " briques ajoutées en base de données.");
            } catch (SQLException e) {
                conn.rollback();
                throw e;
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    // Utilitaire pour normaliser les données API (String) vers les clés étrangères BDD (Int)
    private int[] findItemIds(Connection conn, String shapeName, String hexColor) throws SQLException {
        // // on cherche la brique correspondante
        String sql = """
            SELECT i.id_Item, s.id_shape, c.id_color 
            FROM Item i 
            JOIN Shapes s ON i.shape_id = s.id_shape 
            JOIN Colors c ON i.color_id = c.id_color 
            WHERE s.name = ? AND (c.hex_color = ? OR c.hex_color = ?)
        """;
        try (PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setString(1, shapeName); 
            // Gestion de la tolérance aux formats de couleur (#ABC vs abc)
            ps.setString(2, hexColor.replace("#", "").toUpperCase());
            ps.setString(3, hexColor.replace("#", "").toLowerCase());
            
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    return new int[]{rs.getInt(1), rs.getInt(2), rs.getInt(3)};
                }
            }
        }
        return null;
    }
    
    /**
     * Analyse les commandes passées pour déterminer les tendances d'achat.
     * @param limit Le nombre maximum d'articles à retourner.
     * @return Une Map associant l'article (Forme/Couleur) au volume total des ventes.
     */
    public Map<String, Integer> getPopularItems(int limit) {
        Map<String, Integer> popular = new HashMap<>();
        String sql = """
            SELECT s.name, c.hex_color, SUM(oi.quantity) as total 
            FROM OrderItem oi
            JOIN Item i ON oi.id_Item = i.id_Item
            JOIN Shapes s ON i.shape_id = s.id_shape
            JOIN Colors c ON i.color_id = c.id_color
            GROUP BY oi.id_Item
            ORDER BY total DESC
            LIMIT ?
        """;
        try (Connection conn = getConnection(); PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setInt(1, limit);
            ResultSet rs = ps.executeQuery();
            while(rs.next()) {
                String key = rs.getString(1) + "/" + rs.getString(2).toLowerCase();
                popular.put(key, rs.getInt(3));
            }
        } catch (SQLException e) { 
            System.err.println("// erreur lecture populaires : " + e.getMessage());
        }
        return popular;
    }
    
    /**
     * Affiche l'état actuel du stock dans la console.
     */
    public void showStock() {
        Map<String, Integer> stock = getStockCounts();
        System.out.println("\n--- ÉTAT DU STOCK (SQL) ---");
        if (stock.isEmpty()) {
            System.out.println("(Vide ou erreur connexion)");
        } else {
            stock.forEach((k, v) -> System.out.println("- " + k + " : " + v));
        }
        System.out.println("---------------------------");
    }

    /**
     * Archive une commande fournisseur (achat usine) en base de données.
     * @param quoteId Identifiant unique du devis.
     * @param totalPrice Prix total de la commande.
     * @param items Map des articles commandés (Référence -> Quantité).
     */
    public void recordFactoryOrder(String quoteId, float totalPrice, Map<String, Integer> items) {
        if (items.isEmpty()) return;

        // Insertion de l'en-tête de commande (Prix total connu ici)
        String sqlHeader = "INSERT INTO FactoryOrder (id_FactoryOrder, total_price, order_date) VALUES (?, ?, CURDATE())";
        
        // Insertion des détails (Quantités uniquement, pas de prix unitaire)
        String sqlDetail = "INSERT INTO FactoryOrderDetails (id_FactoryOrder, id_Item, quantity) VALUES (?, ?, ?)";

        try (Connection conn = getConnection()) {
            conn.setAutoCommit(false);
            try {
                // Enregistrer la commande globale
                try (PreparedStatement psHead = conn.prepareStatement(sqlHeader)) {
                    psHead.setString(1, quoteId);
                    psHead.setFloat(2, totalPrice);
                    psHead.executeUpdate();
                }

                // Enregistrer chaque ligne d'article
                try (PreparedStatement psDet = conn.prepareStatement(sqlDetail)) {
                    for (Map.Entry<String, Integer> entry : items.entrySet()) {
                        String key = entry.getKey();
                        int quantity = entry.getValue();

                        // Récupération des IDs (Item/Forme/Couleur)
                        String shape = key.contains("/") ? key.substring(0, key.lastIndexOf('/')) : key;
                        String color = key.contains("/") ? key.substring(key.lastIndexOf('/') + 1) : "000000";

                        int[] ids = findItemIds(conn, shape, color);
                        if (ids == null) {
                            System.err.println("// Item inconnu (non enregistré) : " + key);
                            continue;
                        }

                        psDet.setString(1, quoteId); // Lien FK
                        psDet.setInt(2, ids[0]);     // Id_Item
                        psDet.setInt(3, quantity);   // Quantité
                        psDet.addBatch();            // Mise en file d'attente
                    }
                    psDet.executeBatch();
                }
                
                conn.commit();
                System.out.println("// Commande " + quoteId + " enregistrée (Prix: " + totalPrice + ")");
                
            } catch (SQLException e) {
                conn.rollback();
                System.err.println("// Erreur transaction FactoryOrder : " + e.getMessage());
                throw e;
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    /**
     * Récupère la liste de tous les types de briques référencés.
     * @return Liste de chaînes formatées "Forme/Couleur".
     */
    public List<String> getAllBrickTypes() {
        List<String> types = new ArrayList<>();
        String sql = """
            SELECT s.name, c.hex_color 
            FROM Item i
            JOIN Shapes s ON i.shape_id = s.id_shape
            JOIN Colors c ON i.color_id = c.id_color
        """;
        
        try (Connection conn = getConnection(); 
             Statement stmt = conn.createStatement(); 
             ResultSet rs = stmt.executeQuery(sql)) {
            
            while (rs.next()) {
                // // construction de la clé unique type "2-2/c9cae2"
                String shape = rs.getString("name");
                String color = rs.getString("hex_color").replace("#", "").toLowerCase();
                types.add(shape + "/" + color);
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return types;
    }

    /**
     * Récupère le mapping complet ID &lt;-&gt; Référence pour tous les items.
     * @return Map liant l'ID BDD (Integer) à la référence API (String).
     */
    public Map<Integer, String> getAllItemsRef() {
        Map<Integer, String> items = new HashMap<>();
        String sql = "SELECT i.id_Item, CONCAT(s.name, '/', LOWER(c.hex_color)) AS ref " +
                     "FROM Item i " +
                     "JOIN Shapes s ON i.shape_id = s.id_shape " +
                     "JOIN Colors c ON i.color_id = c.id_color";

        try (Connection conn = getConnection();
             Statement stmt = conn.createStatement();
             ResultSet rs = stmt.executeQuery(sql)) {
            while (rs.next()) {
                items.put(rs.getInt("id_Item"), rs.getString("ref"));
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return items;
    }

    /**
     * Met à jour le prix unitaire d'un item.
     * @param idItem L'ID de l'item en base.
     * @param price Le nouveau prix.
     */
    public void updateItemPrice(int idItem, double price) {
        String sql = "UPDATE Item SET price = ? WHERE id_Item = ?";
        try (Connection conn = getConnection();
             PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setDouble(1, price);
            pstmt.setInt(2, idItem);
            pstmt.executeUpdate();
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }
}