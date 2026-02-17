package fr.univ_eiffel.legotools.paving;

/**
 * Objet de Transfert de Données (DTO) contenant le contexte de pavage.
 * <p>
 * Cette classe regroupe toutes les informations nécessaires pour transformer une image pixelisée
 * en une liste de briques. Elle permet de passer un seul objet "contexte" aux algorithmes
 * au lieu de multiplier les paramètres de méthode.
 * </p>
 */
public class PavingInput {

    // Largeur de la grille.
    int width;

    // Hauteur de la grille.
    int height;

    // Matrice représentant l'image source.
    int[][] pixels; 

    // Drapeau de contrôle critique :
    // - true : Mode "Réaliste" -> L'algorithme ne pose une brique que si elle est disponible en BDD (Pour le pavage gestion de stock).
    // - false : Mode "Idéal" -> L'algorithme suppose un stock infini (pour générer un devis par exemple) (pour les 3 pavages restants).
    boolean useStock; 
    
    /**
     * Initialise le contexte de pavage avec les paramètres fournis.
     * @param width Largeur de la zone à paver.
     * @param height Hauteur de la zone à paver.
     * @param pixels La grille de couleurs cibles.
     * @param useStock {@code true} pour activer la vérification des stocks en temps réel.
     */
    public PavingInput(int width, int height, int[][] pixels, boolean useStock) {
        this.width = width;
        this.height = height;
        this.pixels = pixels;
        this.useStock = useStock;
    }
}