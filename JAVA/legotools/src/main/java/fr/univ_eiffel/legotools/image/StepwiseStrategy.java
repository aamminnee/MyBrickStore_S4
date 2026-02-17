package fr.univ_eiffel.legotools.image;

import java.awt.image.BufferedImage;
import java.util.List;

/**
 * Stratégie de redimensionnement par étapes (Iterative Resizing).
 * <p>
 * <b>Pourquoi cette classe ?</b><br>
 * Réduire une image de très haute définition vers une petite icône
 * en une seule opération provoque souvent une perte massive de détails (Aliasing).
 * </p>
 * <p>
 * <b>Principe :</b> Cette stratégie découpe le redimensionnement en plusieurs petites étapes intermédiaires 
 * (ex: 4000px -> 2000px -> 1000px -> 500px -> 64px) et peut même alterner les algorithmes 
 * (ex: utiliser Lanczos pour le début et Bilinéaire pour la fin).
 * </p>
 */
public class StepwiseStrategy implements ResolutionStrategy {

    // Liste des stratégies à alterner (Round-Robin)
    private final List<ResolutionStrategy> strategies;
    private final int steps;

    /**
     * Crée une nouvelle stratégie de redimensionnement par étapes.
     * @param strategies La liste des algorithmes à utiliser.
     * @param numberOfSteps Le nombre d'itérations intermédiaires. Plus ce nombre est élevé, plus la réduction est douce.
     */
    public StepwiseStrategy(List<ResolutionStrategy> strategies, int numberOfSteps) {
        this.strategies = strategies;
        this.steps = numberOfSteps;
    }

    /**
     * Exécute le redimensionnement progressif.
     * <p>
     * L'algorithme calcule des étapes intermédiaires selon une progression 
     * géométrique pour lisser la réduction et éviter la perte brutale de détails (aliasing).
     * </p>
     * @param source L'image initiale à traiter.
     * @param targetWidth La largeur finale souhaitée après la dernière étape.
     * @param targetHeight La hauteur finale souhaitée après la dernière étape.
     * @return L'image finale, résultat de l'application successive des stratégies.
     */
    @Override
    public BufferedImage resize(BufferedImage source, int targetWidth, int targetHeight) {

        BufferedImage currentImage = source;

        int w = source.getWidth();
        int h = source.getHeight();

        double wStep = Math.pow((double)targetWidth / w, 1.0 / steps);
        double hStep = Math.pow((double)targetHeight / h, 1.0 / steps);

        for (int i = 0; i < steps; i++) {
            
            int nextW = (int) (w * Math.pow(wStep, i + 1));
            int nextH = (int) (h * Math.pow(hStep, i + 1));

            if (i == steps - 1) {
                nextW = targetWidth;
                nextH = targetHeight;
            }

            ResolutionStrategy strategyToUse = strategies.get(i % strategies.size());
            System.out.println("Étape " + (i+1) + "/" + steps + " : Redimensionnement vers " + nextW + "x" + nextH + " avec " + strategyToUse.getClass().getSimpleName());
            currentImage = strategyToUse.resize(currentImage, nextW, nextH);
        }
        
        return currentImage;
    }
}