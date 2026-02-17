#ifndef SOLUTION_LIBRE_H
#define SOLUTION_LIBRE_H

#include "image.h"
#include "brique.h"
#include "structure.h"

/**
 * @brief Exécute l'algorithme de pavage "Forme Libre".
 *
 * Cette stratégie choisit la brique la mieux adapté. Elle va privilégier les 
 * briques de grandes tailles permettant ainsi d'avoir le meilleur rapport
 * qualité/prix
 *
 * @param I L'image source.
 * @param B Le catalogue des briques (prix, formes, couleurs).
 * @return La solution idéale générée.
 */
Solution run_algo_libre(Image* I, BriqueList* B);

#endif