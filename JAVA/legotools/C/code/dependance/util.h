#ifndef UTIL_H
#define UTIL_H

#include "structure.h"

/**
 * @brief Ouvre un fichier en gérant la concaténation du chemin et du nom.
 * @param dir Dossier.
 * @param name Nom de fichier.
 * @param mode Mode d'ouverture ("r", "w", etc.).
 * @return Pointeur FILE* ou NULL en cas d'échec.
 */
FILE* open_with_dir(char* dir, char* name, char* mode);

/** @brief Convertit un caractère hexadécimal en entier/masque binaire. */
int charToMask(char c);

/** @brief Calcule un masque 1D à partir de coordonnées 2D. */
int coordToMask(int dx, int dy, int W);

/** @brief Parse une chaîne représentant un trou (ex: "1-1") vers un entier. */
int trou_str_to_int(char* buffer);

/** @brief Convertit un entier représentant un trou vers une chaîne. */
void trou_int_to_str(int T, char* buffer);

/** @brief Calcule l'index linéaire (1D) d'un pixel (x,y). */
int getIndex(int x, int y, Image* I);

/** @brief Helper pour trouver une brique spécifique dans le catalogue. */
int getBrickFor(BriqueList* B, int shape, int col);

/**
 * @brief Calcule l'erreur chromatique totale si on place une brique donnée à une position.
 * Somme les distances entre les pixels de l'image et la couleur de la brique.
 */
int compute_error_for_shape_at(int iBrique, int x0, int y0, int rot, BriqueList* B, Image* I);

/**
 * @brief Vérifie si une zone rectangulaire est libre (non couverte).
 * @param covered Tableau indiquant les pixels déjà occupés (1) ou libres (0).
 * @return 1 si la zone est libre, 0 si elle chevauche une brique existante.
 */
int rect_is_uncovered(int x0, int y0, int w, int h, Image* I, int* covered);

/** @brief Marque une zone rectangulaire comme occupée dans le tableau 'covered'. */
void mark_rect_covered(int x0, int y0, int w, int h, Image* I, int* covered);

/** @brief Cherche une brique 1x1 disponible de la couleur donnée (Gestion de stock). */
int choisir_brique1x1_disponible(BriqueList* B, int col, int* used);

/**
 * @brief Analyse une zone pour voir si elle est uniforme (même couleur dominante).
 * @param[out] closestColor Stocke l'index de la couleur la plus proche trouvée.
 * @return 1 si la zone est assez uniforme, 0 sinon.
 */
int rect_has_uniform_closest(int x0, int y0, int w, int h, Image* I, int* closestColor, int colTarget);

/** @brief Fonction de comparaison pour qsort (tri par aire décroissante). */
int comparer_aire(const void* a, const void* b);

/** @brief Vérifie la compatibilité visuelle d'une zone avec une couleur donnée (seuil de tolérance). */
int is_area_compatible(Image* I, int x, int y, int w, int h, RGB bColor, int tolerance);

#endif