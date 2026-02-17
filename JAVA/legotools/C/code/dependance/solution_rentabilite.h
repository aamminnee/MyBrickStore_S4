#ifndef SOLUTION_RENTABILITE_H
#define SOLUTION_RENTABILITE_H

#include "image.h"
#include "brique.h"
#include "structure.h"

/**
 * @brief Exécute l'algorithme de "Rentabilité".
 *
 * Cette stratégie cherche le meilleur compromis entre la couverture de la surface
 * et le coût financier. Elle privilégie les briques qui couvrent beaucoup de pixels
 * pour un prix faible (ratio surface/prix).
 *
 * @param I L'image source.
 * @param B Le catalogue contenant les prix.
 * @return La solution la plus économique.
 */
Solution run_algo_rentabilite(Image* I, BriqueList* B);

#endif