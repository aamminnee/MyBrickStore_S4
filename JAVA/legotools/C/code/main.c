#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#include "dependance/main.h"

// Exécute tous les algorithmes
void execute_all(char * dir) {  
    execute_strategie_stock(dir);
    execute_strategie_libre(dir);
    execute_strategie_minimisation(dir);
    execute_strategie_rentabilite(dir);
}

// Exécute l'algorithme de gestion de stock
void execute_strategie_stock(char * dir) {  

    // Charger l'image et la liste de briques
    Image I;
    BriqueList B;
    load_image(dir, &I);
    load_brique(dir, &B);

    // =================== Algo stock ===================
    printf("\nExécution de la version 4 algo gestion de stock...\n");
    Solution S = run_algo_stock(&I, &B);
    print_sol(&S, "output", "pavage_stock.txt", &B);

    // Libération mémoire
    freeSolution(S);
    freeImage(I);
    freeBrique(B);
}

void execute_strategie_libre(char * dir) {

    // Charger l'image et la liste de briques
    Image I;
    BriqueList B;
    load_image(dir, &I);
    load_brique(dir, &B);

    // =================== Algo forme libre ===================
    printf("\nExécution de l'algo v4 forme libre (toutes pièces)...\n");
    Solution S = run_algo_libre(&I, &B);
    print_sol(&S, "output", "pavage_libre.txt", &B);

    // Libération mémoire
    freeSolution(S);
    freeImage(I);
    freeBrique(B);
}

void execute_strategie_minimisation(char * dir) {  
    // Charger l'image et la liste de briques
    Image I;
    BriqueList B;
    load_image(dir, &I);
    load_brique(dir, &B);

    // =================== Algo v4 rupture ===================
    printf("\nExécution de la version 4 algo minimalisation du l'erreur...\n");
    Solution S = run_algo_minimisation(&I, &B);
    print_sol(&S, "output", "pavage_minimisation.txt", &B);

    // Libération mémoire
    freeSolution(S);
    freeImage(I);
    freeBrique(B);
}

void execute_strategie_rentabilite(char * dir) {

    // Charger l'image et la liste de briques
    Image I;
    BriqueList B;
    load_image(dir, &I);
    load_brique(dir, &B);

    // =================== Algo v4 rentabilité ===================
    printf("\nExécution de la version 4 algo rentabilité...\n");
    Solution S = run_algo_rentabilite(&I, &B);
    print_sol(&S, "output", "pavage_rentabilite.txt", &B);

    // Libération mémoire
    freeSolution(S);
    freeImage(I);
    freeBrique(B);
}

int main(int argc, char** argv) {
    char* dir = "input";
    printf("\n");
    if (argc > 1) {
        dir = argv[1]; 
    }

    // Vérification de l'existence du dossier
    FILE* test = fopen(dir, "r");
    if (!test) {
        printf("Erreur : le dossier '%s' n'existe pas ou n'est pas accessible.\n", dir);
        return EXIT_FAILURE;
    }
    fclose(test);

    // Exécution des algorithmes demandés
    if (argc == 2) {
        execute_all(dir);
        return EXIT_SUCCESS;
    }

    // Gestion des arguments pour choisir les algorithmes
    for (int i = 2; i < argc; i++) {
        char* algo = argv[i];
        if (strcmp(algo, "all") == 0) {
            execute_all(dir);
        } else if (strcmp(algo, "stock") == 0) {
            execute_strategie_stock(dir);
        } else if (strcmp(algo, "libre") == 0) {
            execute_strategie_libre(dir);
        } else if (strcmp(algo, "minimisation") == 0) {
            execute_strategie_minimisation(dir);
        } else if (strcmp(algo, "rentabilite") == 0) {
            execute_strategie_rentabilite(dir);
        } else {
            printf("Erreur : option inconnue '%s'.\n", algo);
            printf("Options : all, stock, libre, minimisation, rentabilite\n");
        }
    }
    return EXIT_SUCCESS;
}
