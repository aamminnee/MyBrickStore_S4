package fr.univ_eiffel.legotools.scripts;

import java.io.BufferedReader;
import java.io.FileReader;
import java.io.BufferedWriter;
import java.io.FileWriter;
import java.io.IOException;
import java.sql.*;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;

/**
 * Script d'exportation des données de stock vers le module C.
 * <p>
 * Ce programme autonome (Main) sert de passerelle entre la Base de Données SQL et l'Algorithme C.
 * Il génère le fichier "briques.txt" qui sert de catalogue de référence pour le pavage.
 * </p>
 */
public class GenerationItem {

    private static final Map<String, String> ENV = new HashMap<>();

    /**
     * Constructeur par défaut.
     */
    public GenerationItem() {
        // Constructeur vide explicite pour la Javadoc
    }

    /**
     * Point d'entrée principal du script.
     * @param args Arguments de la ligne de commande.
     */
    public static void main(String[] args) {

        // chargement des variables de configuration depuis le fichier .env
        loadEnv(".env");

        String host = ENV.get("DB_HOST");
        String dbName = ENV.get("DB_NAME");
        String user = ENV.get("DB_USER");
        String password = ENV.get("DB_PASSWORD");

        // Validation stricte des paramètres avant de tenter la connexion
        if (host == null) {
            System.err.println("Erreur : DB_HOST manquant dans le fichier .env");
            return;
        }

        if (dbName == null) {
            System.err.println("Erreur : DB_NAME manquant dans le fichier .env");
            return;
        }

        if (user == null) {
            System.err.println("Erreur : DB_USER manquant dans le fichier .env");
            return;
        }

        if (password == null) password = "";

        String url = "jdbc:mysql://" + host + ":3306/" + dbName;
        
        System.out.println("Connexion à la BDD : " + url + " (User: " + user + ")");

        List<String> shapesList = new ArrayList<>();
        Map<Integer, Integer> shapeIdToIndex = new HashMap<>();

        List<String> colorsList = new ArrayList<>();
        Map<Integer, Integer> colorIdToIndex = new HashMap<>();

        List<String> piecesLines = new ArrayList<>();

        // Connexion et extraction des données via les procédures stockées
        try (Connection conn = DriverManager.getConnection(url, user, password)) {
            
            // Récupération et indexation des formes de briques
            try (CallableStatement csShapes = conn.prepareCall("{call get_export_shapes()}");
                 ResultSet rsShapes = csShapes.executeQuery()) {
                
                int idxS = 0;
                while (rsShapes.next()) {
                    shapeIdToIndex.put(rsShapes.getInt("id_shape"), idxS++);
                    shapesList.add(rsShapes.getInt("width") + "-" + rsShapes.getInt("length"));
                }
            }
            
            // Récupération et indexation des couleurs disponibles
            try (CallableStatement csColors = conn.prepareCall("{call get_export_colors()}");
                 ResultSet rsColors = csColors.executeQuery()) {
                
                int idxC = 0;
                while (rsColors.next()) {
                    colorIdToIndex.put(rsColors.getInt("id_color"), idxC++);
                    colorsList.add(rsColors.getString("hex_color"));
                }
            }

            // Extraction des items en stock avec leurs prix
            try (CallableStatement csItems = conn.prepareCall("{call get_export_items_stock()}");
                 ResultSet rsItems = csItems.executeQuery()) {
                
                while (rsItems.next()) {
                    int sId = rsItems.getInt("shape_id");
                    int cId = rsItems.getInt("color_id");
                    double price = rsItems.getDouble("price");
                    int stock = rsItems.getInt("current_stock");

                    Integer shapeIdx = shapeIdToIndex.get(sId);
                    Integer colorIdx = colorIdToIndex.get(cId);

                    if (shapeIdx != null && colorIdx != null) {
                        String formattedPrice = String.format(Locale.US, "%.2f", price);
                        piecesLines.add(shapeIdx + "/" + colorIdx + " " + formattedPrice + " " + stock);
                    }
                }
            }

            // Génération du fichier briques.txt pour le module de pavage en c
            try (BufferedWriter writer = new BufferedWriter(new FileWriter("C/input/briques.txt"))) {
                writer.write(shapesList.size() + " " + colorsList.size() + " " + piecesLines.size());
                writer.newLine();

                for (String s : shapesList) {
                    writer.write(s);
                    writer.newLine();
                }

                for (String c : colorsList) {
                    writer.write(c);
                    writer.newLine();
                }

                for (String p : piecesLines) {
                    writer.write(p);
                    writer.newLine();
                }
            }

            System.out.println("Succès : briques.txt généré avec les procédures stockées !");

        } catch (SQLException | IOException e) {
            e.printStackTrace();
        }
    }

    /**
     * Chargeur manuel de fichier .env.
     * Permet de lire les configurations locales sans dépendre d'une librairie externe lourde.
     */
    private static void loadEnv(String filePath) {
        try (BufferedReader reader = new BufferedReader(new FileReader(filePath))) {
            String line;
            while ((line = reader.readLine()) != null) {
                line = line.trim();
                if (line.isEmpty() || line.startsWith("#")) continue;
                
                String[] parts = line.split("=", 2);
                if (parts.length >= 2) {
                    ENV.put(parts[0].trim(), parts[1].trim());
                } else if (parts.length == 1) {
                    ENV.put(parts[0].trim(), "");
                }
            }
        } catch (IOException e) {
            System.err.println("Erreur : Impossible de lire le fichier " + filePath);
        }
    }
}