package fr.univ_eiffel.legotools.model;

/**
 * Représente une brique virtuelle positionnée dans l'image pixelisée (Modèle de Vue).
 * <p>
 * Elle sert principalement au transfert de données (DTO) entre l'algorithme de traitement d'image et l'interface graphique.
 * </p>
 */
public class LegoBrick {
    
    // Coordonnée horizontale (en pixels)
    private int x;
    
    // Coordonnée verticale (en pixels)
    private int y;

    // Largeur de la brique (déterminée par la taille de la grille de pixelisation)
    private int width;

    // Hauteur de la brique
    private int height;

    // Code couleur hexadécimal
    private String color;

    /**
     * Constructeur par défaut (No-Args).
     */
    public LegoBrick() {}

    /**
     * Constructeur complet.
     * @param x Position X.
     * @param y Position Y.
     * @param width Largeur.
     * @param height Hauteur.
     * @param color Couleur hexadécimale.
     */
    public LegoBrick(int x, int y, int width, int height, String color) {
        this.x = x;
        this.y = y;
        this.width = width;
        this.height = height;
        this.color = color;
    }

    /**
     * Retourne la position horizontale
     * @return La position X.
     */
    public int getX() { 
        return x; 
    }

    /**
     * Retourne la position verticale
     * @return La position Y.
     */
    public int getY() { 
        return y; 
    }

    /**
     * Retourne la largeur de la brique
     * @return La largeur de la brique.
     */
    public int getWidth() { 
        return width; 
    }

    /**
     * Retourne la hauteur de la brique
     * @return La hauteur de la brique.
     */
    public int getHeight() { 
        return height; 
    }

    /**
     * Retourne la couleur de la brique
     * @return La couleur hexadécimale.
     */
    public String getColor() { 
        return color; 
    }
}