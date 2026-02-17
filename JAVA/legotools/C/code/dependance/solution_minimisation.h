#ifndef SOLUTION_MINIMISATION_H
#define SOLUTION_MINIMISATION_H

#include "image.h"
#include "brique.h"
#include "structure.h"

/**
 * @brief Exécute l'algorithme de "Minimisation de l'erreur".
 *
 * Cette stratégie priorise la qualité visuelle absolue. Elle cherche
 * la brique dont la couleur moyenne est la plus proche possible de la
 * zone de l'image correspondante, quitte à utiliser des briques chères.
 *
 * @param I L'image source.
 * @param B Le catalogue.
 * @return La solution offrant le meilleur rendu visuel.
 */
Solution run_algo_minimisation(Image* I, BriqueList* B);

#endif