package fr.univ_eiffel.legotools.scripts;

import java.io.*;
import java.util.Map;
import java.util.TreeMap;

/**
 * Script utilitaire générant une "Liste de courses".
 */
public class PavingInventory {

    /**
     * Constructeur privé.
     */
    private PavingInventory() {
        throw new IllegalStateException("Utility class");
    }

    /**
     * Point d'entrée.
     * @param args Arguments CLI (0: fichier entrée).
     */
    public static void main(String[] args) {
        String inputPath = (args.length > 0) ? args[0] : "C/output/pavage_v4_stock.txt";
        String outputPath = "inventory.txt";
        
        try {
            int total = createInventory(inputPath, outputPath);
            System.out.println("Total de briques : " + total);
        } catch (IOException e) {
            e.printStackTrace();
        }
    }

    /**
     * Génère un fichier d'inventaire.
     * @param inputPath Le fichier de pavage brut.
     * @param outputPath Le fichier de destination.
     * @return Le nombre total de briques.
     * @throws IOException Si les fichiers ne sont pas accessibles.
     */
    public static int createInventory(String inputPath, String outputPath) throws IOException {
        System.out.println("Génération inventaire : " + inputPath + " -> " + outputPath);

        // Utilisation d'un TreeMap pour trier automatiquement les briques par nom
        Map<String, Integer> inventory = new TreeMap<>();
        int totalBricks = 0;

        // Phase de Lecture et d'Agrégation
        try (BufferedReader br = new BufferedReader(new FileReader(inputPath))) {
            String line = br.readLine();
            
            while ((line = br.readLine()) != null) {
                line = line.trim();
                if (line.isEmpty()) continue;

                String[] parts = line.split(" ");
                if (parts.length < 1) continue;

                String brickKey = parts[0]; 
                if (!brickKey.contains("/")) continue;

                inventory.put(brickKey, inventory.getOrDefault(brickKey, 0) + 1);
            }
        }

        // Phase d'Écriture du Rapport
        try (BufferedWriter writer = new BufferedWriter(new FileWriter(outputPath))) {
            writer.write(String.format("%-15s | %-10s | %s", "Dimension", "Couleur", "Quantité"));
            writer.newLine();
            writer.write("----------------|------------|----------");
            writer.newLine();

            for (Map.Entry<String, Integer> entry : inventory.entrySet()) {
                String key = entry.getKey();
                int count = entry.getValue();

                String[] brickInfo = key.split("/");
                String dim = brickInfo[0];
                String hexColor = brickInfo[1];

                if (!hexColor.startsWith("#")) {
                    hexColor = "#" + hexColor;
                }

                writer.write(String.format("%-15s | %-10s | %d", dim, hexColor, count));
                writer.newLine();
                
                totalBricks += count;
            }

            writer.write("--------------------------------------------------");
            writer.newLine();
            writer.write("Total de briques : " + totalBricks);
            writer.newLine();
        }

        return totalBricks;
    }
}