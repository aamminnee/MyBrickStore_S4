#include <stdlib.h>
#include <stdio.h>
#include <limits.h>
#include <string.h>
#include <assert.h>

#include "dependance/structure.h"
#include "dependance/util.h"
#include "dependance/image.h"
#include "dependance/brique.h"


// Initialise une solution vide
void init_sol(Solution* sol, Image* I) {
    sol->length = 0;
    sol->totalError = 0;
    sol->cost = 0.0;
    sol->stock = 0;
    sol->array = malloc(I->W * I->H * 2 * sizeof(SolItem));
    if (!sol->array) { 
        perror("malloc"); 
        exit(EXIT_FAILURE); 
    }
}

// Ajoute un élément à la solution
void push_sol(Solution* sol, int iBrique, int x, int y, int rot, Image* I, BriqueList* B) {
    (void)I;
    sol->array[sol->length].iBrique = iBrique;
    sol->array[sol->length].x = x;
    sol->array[sol->length].y = y;
    sol->array[sol->length].rot = rot;
    sol->length++;

    // Mise à jour du coût
    if (iBrique >= 0) { 
        sol->cost += B->bPrix[iBrique];
    }
}

// Ajoute un élément à la solution et met à jour l'erreur totale
void push_sol_with_error(Solution* sol, int iBrique, int x, int y, int rot, Image* I, BriqueList* B) {
    push_sol(sol, iBrique, x, y, rot, I, B);
    if (iBrique >= 0) { 
        sol->totalError += compute_error_for_shape_at(iBrique, x, y, rot, B, I);
    }
}

// Enregistre la solution dans un fichier et affiche dans la console
void print_sol(Solution* sol, char* dir, char* name, BriqueList* B) {
    if (!sol) { 
        return;
    }

    // Affichage console
    printf("%s/%s %ld %.2f %ld %ld\n", dir, name, sol->length, sol->cost, sol->totalError, sol->stock); 
    FILE* fptr = open_with_dir(dir, name, "w"); 
    if (!fptr) { 
        perror("open_with_dir"); 
        return; 
    } 

    // Écriture dans le fichier
    fprintf(fptr, "%ld %.2f %ld %ld\n", sol->length, sol->cost, sol->totalError, sol->stock);

    for (int i = 0; i < sol->length; i++) { 

        int ib = sol->array[i].iBrique; 
        int shape = (ib >= 0) ? B->bShape[ib] : 0; 
        int col = (ib >= 0) ? B->bCol[ib] : 0; 

        if (ib >= 0) { 
            if (B->T[shape] == 0) { 
                // Pas de trou
                fprintf(fptr, "%dx%d/%02x%02x%02x %d %d %d\n",
                        B->W[shape], B->H[shape],
                        B->col[col].R, B->col[col].G, B->col[col].B,
                        sol->array[i].x, sol->array[i].y, sol->array[i].rot);
                        
            } else { 
                // Avec trou
                char buffer[64]; 
                trou_int_to_str(B->T[shape], buffer); 
                fprintf(fptr, "%dx%d-%s/%02x%02x%02x %d %d %d\n",
                        B->W[shape], B->H[shape], buffer,
                        B->col[col].R, B->col[col].G, B->col[col].B,
                        sol->array[i].x, sol->array[i].y, sol->array[i].rot); 
            } 
        } else { 
            // Brique 1x1 noire pour pixel non couvert
            fprintf(fptr, "1x1/000000 %d %d %d\n",
                    sol->array[i].x, sol->array[i].y, sol->array[i].rot); 
        } 
    } 
    fclose(fptr); 
}

// Calcule le nombre de briques manquantes par rapport au stock
void fill_sol_stock(Solution* sol, BriqueList* B) {
    int* used = calloc(B->nBrique, sizeof(int));

    for (int i = 0; i < sol->length; i++) {
        int ib = sol->array[i].iBrique;
        if (ib >= 0) used[ib]++;
    }

    sol->stock = 0;
    for (int i = 0; i < B->nBrique; i++) {
        if (used[i] > B->bStock[i]) sol->stock += (used[i] - B->bStock[i]);
    }

    free(used);
}

// Libère la mémoire allouée pour la solution
void freeSolution(Solution S) {
    free(S.array);
}

