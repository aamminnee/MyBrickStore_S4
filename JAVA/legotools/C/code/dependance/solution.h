#ifndef SOLUTION_H
#define SOLUTION_H

#include "structure.h"
#include "image.h"
#include "brique.h"

/**
 * @brief Initialise une solution vide (taille basée sur l'image).
 * @param sol Pointeur vers la solution.
 * @param I Pointeur vers l'image (pour dimensionner l'allocation).
 */
void init_sol(Solution* sol, Image* I);

/**
 * @brief Ajoute une brique à la solution et met à jour le coût total.
 * @param sol La solution.
 * @param iBrique Index de la brique utilisée.
 * @param x Position X.
 * @param y Position Y.
 * @param rot Rotation (0 ou 1).
 * @param I Image (inutilisé ici mais gardé pour signature uniforme).
 * @param B Catalogue (pour récupérer le prix).
 */
void push_sol(Solution* sol, int iBrique, int x, int y, int rot, Image* I, BriqueList* B);

/**
 * @brief Version étendue de push_sol qui calcule aussi l'erreur visuelle générée.
 * @see push_sol
 */
void push_sol_with_error(Solution* sol, int iBrique, int x, int y, int rot, Image* I, BriqueList* B);

/**
 * @brief Exporte la solution au format texte lisible par l'application Java.
 * Affiche également le résumé dans la console.
 * @param sol La solution à exporter.
 * @param dir Dossier de sortie.
 * @param name Nom du fichier de sortie.
 * @param B Catalogue pour retrouver les noms et couleurs.
 */
void print_sol(Solution* sol, char* dir, char* name, BriqueList* B);

/**
 * @brief Calcule le déficit de stock (briques utilisées vs briques possédées).
 * Met à jour le champ sol->stock.
 * @param sol La solution à analyser.
 * @param B Le catalogue contenant les stocks initiaux.
 */
void fill_sol_stock(Solution* sol, BriqueList* B);

/**
 * @brief Libère la mémoire de la solution (le tableau de SolItems).
 * @param S La solution à détruire.
 */
void freeSolution(Solution S);

#endif