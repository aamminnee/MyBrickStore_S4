package fr.univ_eiffel.legotools.paving;

import fr.univ_eiffel.legotools.model.LegoBrick;
import java.awt.Color;
import java.awt.Graphics2D;
import java.awt.image.BufferedImage;
import java.io.*;
import java.nio.file.Files;
import java.nio.file.StandardCopyOption;
import java.util.ArrayList;
import java.util.List;
import javax.imageio.ImageIO;

/**
 * Service assurant l'interopérabilité avec le module C.
 * <p>
 * Cette classe agit comme une passerelle (Bridge) :
 * 1. Elle traduit les données Java (BufferedImage) en format texte brut compréhensible par le C.
 * 2. Elle lance l'exécutable C dans un processus séparé.
 * 3. Elle récupère les résultats (fichiers texte) et les reconvertit en objets Java (LegoBrick) pour l'affichage.
 * </p>
 */
public class PavingService {

    private final String pathToCExecutable;

    // Structure de dossiers fixe requise par l'architecture du projet C
    private final File saeDir = new File("C");
    private final File inputDir = new File(saeDir, "input");
    private final File outputDir = new File(saeDir, "output");

    /**
     * Initialise le service de pavage.
     * @param pathToCExecutable Chemin relatif ou absolu vers l'exécutable C.
     */
    public PavingService(String pathToCExecutable) {
        this.pathToCExecutable = pathToCExecutable;
        if (!inputDir.exists()) inputDir.mkdirs();
        if (!outputDir.exists()) outputDir.mkdirs();
    }

    /**
     * Orchestre le pipeline complet de génération d'un pavage.
     * @param sourceImage L'image pixelisée à traiter.
     * @param algoName Le nom de l'algo C à utiliser.
     * @param destTxtFile Fichier où sauvegarder une copie des instructions textuelles.
     * @return Une image générée représentant le résultat final (vue "Lego").
     * @throws IOException En cas d'erreur E/S.
     * @throws InterruptedException Si le processus est interrompu.
     */
    public BufferedImage generatePaving(BufferedImage sourceImage, String algoName, File destTxtFile) throws IOException, InterruptedException {
        writeImageTxt(sourceImage);

        String inputArg = "input"; 
        String exeCmd = pathToCExecutable;
        if (exeCmd.startsWith("./C/") || exeCmd.startsWith("C/")) {
            exeCmd = exeCmd.replace("C/", "");
            if (exeCmd.startsWith("./")) exeCmd = exeCmd.substring(2);
        }
        if (!exeCmd.startsWith("./") && !exeCmd.startsWith("/")) {
            exeCmd = "./" + exeCmd;
        }

        System.out.println("Lancement C (dir=" + saeDir.getAbsolutePath() + ") : " + exeCmd + " " + inputArg + " " + algoName);
        
        ProcessBuilder pb = new ProcessBuilder(exeCmd, inputArg, algoName);
        pb.directory(saeDir);
        pb.redirectErrorStream(true);
        pb.redirectOutput(ProcessBuilder.Redirect.INHERIT);

        Process process = pb.start();
        int exitCode = process.waitFor();

        if (exitCode != 0) {
            throw new IOException("Le programme C a échoué (Code " + exitCode + ")");
        }

        String suffix = algoName;
        if ("v4_libre".equals(algoName)) {
            suffix = "v4_forme_libre";
        }
        
        String resultFileName = "pavage_" + suffix + ".txt";
        File resultFile = new File(outputDir, resultFileName);

        if (!resultFile.exists()) {
             throw new IOException("Résultat introuvable : " + resultFile.getAbsolutePath());
        }

        if (destTxtFile != null) {
            Files.copy(resultFile.toPath(), destTxtFile.toPath(), StandardCopyOption.REPLACE_EXISTING);
        }

        List<LegoBrick> bricks = parsePavingFile(resultFile);
        return renderPreview(sourceImage.getWidth(), sourceImage.getHeight(), bricks);
    }

    /**
     * Génère uniquement l'image de visualisation.
     * @param inputTxt Fichier texte contenant le pavage.
     * @param outputPng Fichier image de sortie.
     * @throws IOException En cas d'erreur fichier.
     */
    public void createVisualization(File inputTxt, File outputPng) throws IOException {
        List<LegoBrick> bricks = parsePavingFile(inputTxt);
        
        int maxX = 0;
        int maxY = 0;
        for (LegoBrick b : bricks) {
            int right = b.getX() + b.getWidth();
            int bottom = b.getY() + b.getHeight();
            if (right > maxX) maxX = right;
            if (bottom > maxY) maxY = bottom;
        }

        if (maxX == 0) maxX = 100;
        if (maxY == 0) maxY = 100;

        BufferedImage image = renderPreview(maxX, maxY, bricks);
        ImageIO.write(image, "png", outputPng);
    }

    /**
     * Sérialise l'image en un format texte brut très simple.
     */
    private void writeImageTxt(BufferedImage img) throws IOException {
        File file = new File(inputDir, "image.txt");
        try (PrintWriter writer = new PrintWriter(new FileWriter(file))) {
            int w = img.getWidth();
            int h = img.getHeight();
            writer.println(w + " " + h);
            for (int y = 0; y < h; y++) {
                for (int x = 0; x < w; x++) {
                    int rgb = img.getRGB(x, y) & 0xFFFFFF;
                    writer.printf("%06X ", rgb);
                }
                writer.println();
            }
        }
    }

    /**
     * Parseur du fichier de résultat généré par le C.
     * Lit les lignes au format : "2x4/FF0000 X Y ROTATION"
     */
    private List<LegoBrick> parsePavingFile(File file) throws IOException {
        List<LegoBrick> bricks = new ArrayList<>();

        try (BufferedReader br = new BufferedReader(new FileReader(file))) {
            String line = br.readLine();
            if (line != null) {
                System.out.println("Stats pavage : " + line);
            }

            while ((line = br.readLine()) != null) {
                line = line.trim();
                if (line.isEmpty()) continue;

                String[] parts = line.split(" ");
                if (parts.length < 4) continue;

                String[] typeAndColor = parts[0].split("/");
                if (typeAndColor.length < 2) continue; 
                
                String dims = typeAndColor[0];
                String color = typeAndColor[1];
                
                String[] dimParts = dims.split("[-x]");
                if (dimParts.length < 2) continue;
                
                int w = Integer.parseInt(dimParts[0]);
                int h = Integer.parseInt(dimParts[1]);
                
                int x = Integer.parseInt(parts[1]);
                int y = Integer.parseInt(parts[2]);
                int rot = Integer.parseInt(parts[3]);

                if (rot == 1) {
                    int temp = w; w = h; h = temp;
                }

                bricks.add(new LegoBrick(x, y, w, h, "#" + color));
            }
        }
        return bricks;
    }

    /**
     * Dessine le rendu final des briques.
     * Applique un zoom automatique pour que les pavages de petite taille restent visibles.
     */
    private BufferedImage renderPreview(int width, int height, List<LegoBrick> bricks) {
        int scale = 20;
        if (width > 200) scale = 5;
        if (width > 1000) scale = 1;

        System.out.println("Génération image prévisualisation " + (width*scale) + "x" + (height*scale));

        BufferedImage preview = new BufferedImage(width * scale, height * scale, BufferedImage.TYPE_INT_RGB);
        Graphics2D g2d = preview.createGraphics();

        g2d.setColor(Color.BLACK);
        g2d.fillRect(0, 0, width * scale, height * scale);

        for (LegoBrick brick : bricks) {
            try {
                g2d.setColor(Color.decode(brick.getColor()));
            } catch (NumberFormatException e) {
                g2d.setColor(Color.MAGENTA);
            }
            g2d.fillRect(brick.getX() * scale, brick.getY() * scale, 
                         brick.getWidth() * scale, brick.getHeight() * scale);
            
            if (scale > 2) {
                g2d.setColor(Color.DARK_GRAY);
                g2d.drawRect(brick.getX() * scale, brick.getY() * scale, 
                             brick.getWidth() * scale, brick.getHeight() * scale);
            }
        }
        g2d.dispose();
        return preview;
    }
}