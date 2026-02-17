#ifndef SOLUTION_STOCK_H
#define SOLUTION_STOCK_H

#include "image.h"
#include "brique.h"
#include "structure.h"

/**
 * @brief Exécute l'algorithme de pavage "Gestion de Stock".
 *
 * Cette stratégie tente de recouvrir l'image en utilisant uniquement
 * les briques réellement disponibles dans l'inventaire (B->bStock).
 * Si une brique n'est plus en stock, elle ne peut pas être posée.
 *
 * @param I L'image source à paver.
 * @param B Le catalogue contenant l'état du stock.
 * @return La solution générée (liste des briques posées).
 */
Solution run_algo_stock(Image* I, BriqueList* B);

#endif