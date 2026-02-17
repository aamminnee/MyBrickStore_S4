#ifndef MAIN_H
#define MAIN_H

#include "structure.h"
#include "brique.h"
#include "image.h"
#include "solution_stock.h"
#include "solution_libre.h"
#include "solution_minimisation.h"
#include "solution_rentabilite.h"
#include "solution.h"
#include "util.h"

/**
 * @brief Lance séquentiellement les 4 algorithmes de pavage.
 * @param dir Le dossier contenant les fichiers d'entrée (input).
 */
void execute_all(char *dir);

/**
 * @brief Exécute la stratégie "Stock" (Respect strict des quantités).
 * @param dir Dossier d'entrée.
 */
void execute_strategie_stock(char *dir);

/**
 * @brief Exécute la stratégie "Minimisation" (Meilleur rendu visuel possible).
 * @param dir Dossier d'entrée.
 */
void execute_strategie_minimisation(char *dir);

/**
 * @brief Exécute la stratégie "Libre" (Utilisation de toutes les formes notamment les plus grandes).
 * @param dir Dossier d'entrée.
 */
void execute_strategie_libre(char *dir);

/**
 * @brief Exécute la stratégie "Rentabilité" (Compromis coût/qualité).
 * @param dir Dossier d'entrée.
 */
void execute_strategie_rentabilite(char *dir);

#endif