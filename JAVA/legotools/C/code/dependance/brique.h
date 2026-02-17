#ifndef BRIQUE_H
#define BRIQUE_H

#include "structure.h"

/**
 * @brief Charge le catalogue de briques depuis le fichier 'briques.txt'.
 * * Remplit la structure BriqueList avec les formes, couleurs, prix et stocks.
 * @param dir Dossier contenant 'briques.txt'.
 * @param B Pointeur vers la structure BriqueList à initialiser.
 */
void load_brique(char* dir, BriqueList* B);

/**
 * @brief Libère toute la mémoire allouée dynamiquement dans la BriqueList.
 * @param B La liste de briques à nettoyer.
 */
void freeBrique(BriqueList B);

/**
 * @brief Cherche l'index d'une forme correspondant aux dimensions données.
 * @param B Le catalogue.
 * @param W Largeur cherchée.
 * @param H Hauteur cherchée.
 * @return L'index de la forme (0 à nShape-1), ou UNMATCHED si non trouvée.
 */
int lookupShape(BriqueList* B, int W, int H);

/**
 * @brief Trouve la brique précise correspondant à une forme et une couleur.
 * @param B Le catalogue.
 * @param shape Index de la forme.
 * @param col Index de la couleur.
 * @return L'index de la brique (0 à nBrique-1), ou UNMATCHED.
 */
int getBriqueWithColor(BriqueList* B, int shape, int col); 

#endif
