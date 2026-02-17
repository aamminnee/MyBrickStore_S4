package fr.univ_eiffel.legotools.image;

import java.awt.image.BufferedImage;
import javax.imageio.ImageIO;
import java.io.File;
import java.io.IOException;

/**
 * Chef d'orchestre du traitement d'image.
 * <p>
 * Cette classe joue le rôle de <b>Contexte</b> dans le patron de conception <b>Stratégie</b>.
 * Elle isole la complexité des entrées/sorties (lecture de fichier, format, sauvegarde)
 * de la complexité algorithmique (calcul des pixels).
 * </p>
 */
public class ImageProcessor {

    private ResolutionStrategy strategy;

    /**
     * Par défaut, on utilise la stratégie la plus rapide ("Plus proche voisin")
     * pour éviter un NullPointerException si l'utilisateur oublie de configurer.
     */ 
    public ImageProcessor() {
        this.strategy = new NearestNeighborStrategy();
    }

    /**
     * Permet de changer l'algorithme de redimensionnement à la volée (Runtime).
     * @param strategy Une instance concrète (Bilinear, Bicubic, etc.).
     */
    public void setStrategy(ResolutionStrategy strategy) {
        this.strategy = strategy;
    }

    /**
     * Exécute le pipeline complet de traitement.
     * <p>
     * 1. Charge l'image depuis le disque (I/O).<br>
     * 2. Délègue le redimensionnement à la stratégie active (Logique métier).<br>
     * 3. Sauvegarde le résultat au bon format (I/O).
     * </p>
     * @param inputPath Chemin vers l'image source.
     * @param outputPath Chemin de destination.
     * @param targetWidth Largeur souhaitée.
     * @param targetHeight Hauteur souhaitée.
     * @throws IOException Si le fichier est introuvable ou illisible.
     */
    public void processImage(String inputPath, String outputPath, int targetWidth, int targetHeight) throws IOException {

        File inputFile = new File(inputPath);
        BufferedImage source = ImageIO.read(inputFile);

        if (source == null) {
            throw new IOException("Impossible de lire le fichier image : " + inputPath);
        }

        System.out.println("Traitement de l'image avec la stratégie : " + strategy.getClass().getSimpleName());
        
        BufferedImage result = strategy.resize(source, targetWidth, targetHeight);
        File outputFile = new File(outputPath);
        String fileName = outputFile.getName();
        String formatName = fileName.substring(fileName.lastIndexOf('.') + 1);
        
        ImageIO.write(result, formatName, outputFile);
        System.out.println("Image sauvegardée dans : " + outputPath);
    }
}