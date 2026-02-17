package fr.univ_eiffel.legotools.image;
import java.awt.image.BufferedImage;

/**
 * Stratégie de redimensionnement utilisant l'interpolation Bicubique.
 * <p>
 * <b>Pourquoi cette classe ?</b><br>
 * C'est l'algorithme offrant la <b>meilleure qualité visuelle</b> parmi les méthodes standard.
 * Il préserve la netteté des bords et produit des dégradés très lisses (moins d'effet d'escalier).
 * <p>
 * <b>Coût :</b> C'est aussi la méthode la plus lente (complexité de calcul élevée) car elle
 * nécessite de traiter une matrice de 16 pixels voisins pour chaque pixel généré.
 * </p>
 */
public class BicubicStrategy implements ResolutionStrategy {

    // Coefficient de "tension" pour le spline de Catmull-Rom.
    // La valeur -0.5 est le standard industriel pour le redimensionnement d'image
    private static final double A = -0.5;

    /**
     * Constructeur par défaut.
     */
    public BicubicStrategy() {}

    /**
     * Redimensionne l'image en utilisant l'algorithme Bicubique.
     * <p>
     * Cet algorithme parcourt les pixels de l'image cible et calcule leur couleur
     * en interpolant les 16 pixels voisins dans l'image source.
     * </p>
     * @param source L'image d'origine à redimensionner.
     * @param targetWidth La largeur souhaitée en pixels.
     * @param targetHeight La hauteur souhaitée en pixels.
     * @return Une nouvelle image redimensionnée avec un rendu lissé de haute qualité.
     */
    @Override
    public BufferedImage resize(BufferedImage source, int targetWidth, int targetHeight) {

        BufferedImage output = new BufferedImage(targetWidth, targetHeight, BufferedImage.TYPE_INT_RGB);
        double xRatio = (double) source.getWidth() / targetWidth;
        double yRatio = (double) source.getHeight() / targetHeight;

        for (int y = 0; y < targetHeight; y++) {
            for (int x = 0; x < targetWidth; x++) {

                double srcX = x * xRatio;
                double srcY = y * yRatio;

                int xInt = (int) srcX;
                int yInt = (int) srcY;

                int pixel = getBicubicPixel(source, xInt, yInt, srcX - xInt, srcY - yInt);
                output.setRGB(x, y, pixel);
            }
        }
        return output;
    }

    /**
     * Fonction de pondération (Kernel) cubique.
     * Elle détermine l'influence d'un pixel voisin en fonction de sa distance 't'.
     * Plus le pixel est proche, plus son poids est élevé.
     */
    private double cubicKernel(double t) {
        t = Math.abs(t);
        if (t <= 1) {
            // Formule pour les voisins immédiats (0 à 1 de distance)
            return (A + 2) * t * t * t - (A + 3) * t * t + 1;
        } else if (t < 2) {
            // Formule pour les voisins éloignés (1 à 2 de distance)
            return A * t * t * t - 5 * A * t * t + 8 * A * t - 4 * A;
        }
        return 0;
    }

    /**
     * Convolution sur une grille 4x4.
     * On somme les contributions pondérées
     */
    private int getBicubicPixel(BufferedImage img, int xBase, int yBase, double dx, double dy) {

        double r = 0, g = 0, b = 0;

        // Double boucle pour parcourir la matrice 4x4 autour du pixel
        for (int m = -1; m <= 2; m++) {
            for (int n = -1; n <= 2; n++) {

                int px = Math.min(Math.max(xBase + m, 0), img.getWidth() - 1);
                int py = Math.min(Math.max(yBase + n, 0), img.getHeight() - 1);

                int pixel = img.getRGB(px, py);
                int[] rgb = getRGB(pixel);

                double weight = cubicKernel(m - dx) * cubicKernel(n - dy);

                r += rgb[0] * weight;
                g += rgb[1] * weight;
                b += rgb[2] * weight;
            }
        }
        return toRGB((int) r, (int) g, (int) b);
    }
}