package fr.univ_eiffel.legotools.image;

import java.awt.image.BufferedImage;

/**
 * Stratégie de redimensionnement utilisant le filtre de Lanczos (Lanczos-3).
 * <p>
 * <b>Pourquoi utiliser cette stratégie ?</b><br>
 * C'est l'algorithme qui offre théoriquement la <b>meilleure qualité possible</b> pour la préservation des détails.
 * Il est basé sur une fonction mathématique (Sinc) qui simule la reconstruction parfaite d'un signal analogique.
 * </p>
 * <p>
 * <b>Contrepartie :</b> C'est l'algorithme le plus lent du projet. Il effectue une convolution sur une zone
 * de 6x6 pixels (36 opérations par pixel cible), contre 16 pour le bicubique et 4 pour le bilinéaire.
 * </p>
 */
public class LanczosStrategy implements ResolutionStrategy {
	
	// Définit la "fenêtre" du filtre. A=3 signifie "Lanczos-3".
    // L'algorithme regarde 3 pixels de chaque côté du centre (soit une fenêtre de 6 pixels de large).
	private static final int A = 3;

    /**
     * Constructeur par défaut.
     */
    public LanczosStrategy() {}
	
    /**
     * Redimensionne l'image avec un filtre de convolution Lanczos.
     * @param source L'image d'origine.
     * @param targetWidth La largeur cible.
     * @param targetHeight La hauteur cible.
     * @return L'image traitée, très nette mais coûteuse à calculer.
     */
	@Override
    public BufferedImage resize(BufferedImage source, int targetWidth, int targetHeight) {

        BufferedImage destination = new BufferedImage(targetWidth, targetHeight, BufferedImage.TYPE_INT_RGB);

        double ratioX = (double) source.getWidth() / targetWidth;
        double ratioY = (double) source.getHeight() / targetHeight;

        for (int x = 0; x < targetWidth; x++) {
            for (int y = 0; y < targetHeight; y++) {

                double sx = x * ratioX;
                double sy = y * ratioY;
                
                int ix = (int) Math.floor(sx);
                int iy = (int) Math.floor(sy);

                double dx = sx - ix;
                double dy = sy - iy;

                double r = 0, g = 0, b = 0;
                double totalWeight = 0;
                
                for (int m = -A + 1; m <= A; m++) {

                    int yy = clamp(iy + m, 0, source.getHeight() - 1);
                    double wy = lanczosWeight(m - dy);

                    for (int n = -A + 1; n <= A; n++) {
                    	
                    	int xx = clamp(ix + n, 0, source.getWidth() - 1);
                        double wx = lanczosWeight(n - dx);

                        double w = wx * wy;

                        int rgb = source.getRGB(xx, yy);
                        int rr = (rgb >> 16) & 0xFF;
                        int gg = (rgb >> 8) & 0xFF;
                        int bb = rgb & 0xFF;

                        r += rr * w;
                        g += gg * w;
                        b += bb * w;

                        totalWeight += w;
                    }
                }

                if (totalWeight != 0) {
                    r /= totalWeight;
                    g /= totalWeight;
                    b /= totalWeight;
                }

                destination.setRGB(x, y, toRGB((int) Math.round(r), (int) Math.round(g), (int) Math.round(b)));
            }
        }

        return destination;
    }
	
	/**
     * Calcule le poids du filtre Lanczos L(x).
     * Formule : L(x) = sinc(x) * sinc(x/a) si |x| < a, sinon 0.
     */
	private double lanczosWeight(double x) {
		x = Math.abs(x);
		
		if (x < 1e-5) { 
			return 1.0;
		}
		if (x >= A) {
			return 0.0;
		}
		return sinc(x) * sinc(x / A);
	}
	
	/**
     * Fonction mathématique Sinus Cardinal (sinc).
     * sinc(x) = sin(πx) / (πx)
     */
	private double sinc(double x) {
		if (Math.abs(x) < 1e-5) {
			return 1.0;
		}
		return Math.sin(Math.PI * x) / (Math.PI * x);
	}
	
	// Sécurité pour rester dans les bornes de l'image (coordonnées)
	private int clamp(int v, int min, int max) {
		return Math.max(min, Math.min(max, v));
	}
}