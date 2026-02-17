package fr.univ_eiffel.legotools.image;
import java.awt.image.BufferedImage;

/**
 * Interface commune à tous les algorithmes de redimensionnement.
 * <p>
 * Cette interface est le pilier du <b>Patron de Conception Stratégie (Strategy Pattern)</b>.
 * Elle permet à l'application de traiter une image sans savoir quel algorithme mathématique 
 * est utilisé derrière (Bicubique, Bilinéaire, etc.).
 * </p>
 */
public interface ResolutionStrategy {

    /**
     * Méthode principale que chaque algorithme doit implémenter.
     * @param source L'image originale.
     * @param targetWidth Largeur cible.
     * @param targetHeight Hauteur cible.
     * @return Une nouvelle image redimensionnée.
     */
    BufferedImage resize(BufferedImage source, int targetWidth, int targetHeight);
    
    /**
     * Méthode utilitaire (Default) pour extraire les canaux de couleur.
     * @param pixel L'entier représentant la couleur (ARGB).
     * @return Un tableau [Rouge, Vert, Bleu].
     */
    default int[] getRGB(int pixel) {
        int r = (pixel >> 16) & 0xFF;
        int g = (pixel >> 8) & 0xFF;
        int b = pixel & 0xFF;
        return new int[]{r, g, b};
    }
    
    /**
     * Recompose un pixel entier à partir des composantes R, G, B.
     * @param r Composante Rouge.
     * @param g Composante Verte.
     * @param b Composante Bleue.
     * @return L'entier ARGB recomposé.
     */
    default int toRGB(int r, int g, int b) {
        r = Math.min(255, Math.max(0, r));
        g = Math.min(255, Math.max(0, g));
        b = Math.min(255, Math.max(0, b));
        return (0xFF << 24) | (r << 16) | (g << 8) | b;
    }
}