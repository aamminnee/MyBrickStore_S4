package fr.univ_eiffel.legotools.image;

import java.awt.image.BufferedImage;

/**
 * Stratégie de redimensionnement "Plus proche voisin" (Nearest Neighbor).
 * <p>
 * <b>Pourquoi cette classe ?</b><br>
 * C'est l'algorithme le plus rapide et le plus simple qui existe.
 * Il ne crée aucune nouvelle couleur (pas de flou, pas de mélange).
 * </p>
 */
public class NearestNeighborStrategy implements ResolutionStrategy {

    /**
     * Constructeur par défaut.
     */
    public NearestNeighborStrategy() {}

    /**
     * Redimensionne l'image en sélectionnant simplement le pixel source correspondant.
     * @param source Image d'origine.
     * @param targetWidth Largeur cible.
     * @param targetHeight Hauteur cible.
     * @return Une image redimensionnée brute (pixelisée).
     */
    @Override
    public BufferedImage resize(BufferedImage source, int targetWidth, int targetHeight) {

        var output = new BufferedImage(targetWidth, targetHeight, source.getType());
        double xRatio = (double) source.getWidth() / targetWidth;
        double yRatio = (double) source.getHeight() / targetHeight;

        for (int y = 0; y < targetHeight; y++) {
            for (int x = 0; x < targetWidth; x++) {
                
                int srcX = (int) (x * xRatio);
                int srcY = (int) (y * yRatio);
                output.setRGB(x, y, source.getRGB(srcX, srcY));
            }
        }
        return output;
    }
}