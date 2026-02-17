#ifndef IMAGE_H
#define IMAGE_H

#include "structure.h"

/**
 * @brief Récupère un pointeur vers le pixel aux coordonnées (x,y).
 * @param I Pointeur vers l'image.
 * @param x Coordonnée horizontale.
 * @param y Coordonnée verticale.
 * @return Un pointeur vers la structure RGB correspondante.
 */
RGB* get(Image* I, int x, int y);

/**
 * @brief Réinitialise une couleur à noir (0,0,0).
 * @param col Pointeur vers la couleur à effacer.
 */
void reset(RGB* col);

/**
 * @brief Calcule la distance au carré entre deux couleurs (Distance Euclidienne).
 * * Formule : (R1-R2)² + (G1-G2)² + (B1-B2)²
 * @param c1 Première couleur.
 * @param c2 Deuxième couleur.
 * @return La distance au carré (plus c'est petit, plus les couleurs sont proches).
 */
int colError(RGB c1, RGB c2);

/**
 * @brief Charge une image depuis le format texte personnalisé du projet.
 * @param dir Dossier contenant le fichier 'image.txt'.
 * @param I Pointeur vers la structure Image à remplir (allocation interne).
 */
void load_image(char* dir, Image* I);

/**
 * @brief Libère la mémoire allouée pour les pixels de l'image.
 * @param I L'image à détruire.
 */
void freeImage(Image I);

#endif
