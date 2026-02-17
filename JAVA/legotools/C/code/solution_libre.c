#include <stdlib.h>
#include <stdio.h>
#include <limits.h>

#include "dependance/structure.h"
#include "dependance/util.h"
#include "dependance/image.h"
#include "dependance/brique.h"
#include "dependance/solution.h"
#include "dependance/solution_libre.h"

// Seuil d'erreur moyen par pixel au-delà duquel on rejette une grosse brique
#define TOLERANCE_LIBRE 700 

/**
 * Algorithme Formes Libres
 * Cet algorithme maximise l'efficacité géométrique en utilisant les plus grandes
 * formes possibles du catalogue.
 */
Solution run_algo_libre(Image* I, BriqueList* B) {
    Solution S;
    init_sol(&S, I);

    if (S.array == NULL && S.length != 0) {
        fprintf(stderr, "Erreur: Solution non initialisée.\n");
        exit(EXIT_FAILURE);
    }

    int npix = I->W * I->H;
    int* couvert = calloc(npix, sizeof(int));
    if (!couvert) {
        perror("malloc couvert");
        exit(EXIT_FAILURE);
    }

    // Etape 1 : Identification de la brique de base 1x1 pour le repli (fallback)
    int shape11 = lookupShape(B, 1, 1);
    if (shape11 == -1) {
        fprintf(stderr, "Erreur critique: Forme 1x1 introuvable.\n");
        exit(EXIT_FAILURE);
    }

    // Etape 2 : Préparation de la liste des formes triées par taille (aire) décroissante
    Dimension* formes = malloc(B->nShape * sizeof(Dimension));
    int nb_formes = 0;
    for (int i = 0; i < B->nShape; i++) {
        if (B->W[i] > 0 && B->H[i] > 0) {
            formes[nb_formes].w = B->W[i];
            formes[nb_formes].h = B->H[i];
            formes[nb_formes].aire = B->W[i] * B->H[i];
            nb_formes++;
        }
    }
    // Tri pour essayer les plus grandes pièces d'abord (optimisation géométrique)
    qsort(formes, nb_formes, sizeof(Dimension), comparer_aire);
    
    // Etape 3 : Pavage glouton
    for (int y = 0; y < I->H; y++) {
        for (int x = 0; x < I->W; x++) {

            if (couvert[getIndex(x, y, I)]) {
                continue;
            }

            int place = 0;

            // On essaie les formes de la plus grande à la plus petite
            for (int k = 0; k < nb_formes && !place; k++) {
                int w_base = formes[k].w;
                int h_base = formes[k].h;

                for (int rot = 0; rot < 2 && !place; rot++) {
                    int w = (rot == 0) ? w_base : h_base;
                    int h = (rot == 0) ? h_base : w_base;

                    // Vérification des limites de l'image et de la zone libre
                    if (x + w > I->W || y + h > I->H) continue;
                    if (!rect_is_uncovered(x, y, w, h, I, couvert)) continue;

                    int id_shape = lookupShape(B, w, h);
                    if (id_shape == -1) continue;

                    // Recherche de la brique la plus proche en couleur pour cette zone
                    int best_brique = -1;
                    long long min_err = LLONG_MAX;

                    for (int i = 0; i < B->nBrique; i++) {
                        if (B->bShape[i] == id_shape) {
                            // Calcul de l'erreur totale de la brique sur la zone
                            long long err = (long long)compute_error_for_shape_at(i, x, y, rot, B, I);
                            if (err < min_err) {
                                min_err = err;
                                best_brique = i;
                            }
                        }
                    }

                    // Validation : on place la brique si l'erreur moyenne par pixel est acceptable
                    if (best_brique != -1 && (min_err / (w * h)) < TOLERANCE_LIBRE) {
                        push_sol_with_error(&S, best_brique, x, y, rot, I, B);
                        mark_rect_covered(x, y, w, h, I, couvert);
                        place = 1;
                    }
                }
            }

            // Etape 4 : Repli sur brique 1x1 si aucune grande forme ne convient
            if (!place) {
                int best_1x1 = -1;
                int min_err_1x1 = INT_MAX;
                RGB pix = *get(I, x, y);

                for (int i = 0; i < B->nBrique; i++) {
                    if (B->bShape[i] == shape11) {
                        int err = colError(B->col[B->bCol[i]], pix);
                        if (err < min_err_1x1) {
                            min_err_1x1 = err;
                            best_1x1 = i;
                        }
                    }
                }

                if (best_1x1 != -1) {
                    push_sol_with_error(&S, best_1x1, x, y, 0, I, B);
                } else {
                    // Cas désespéré : on met la première brique 1x1 trouvée
                    push_sol_with_error(&S, -1, x, y, 0, I, B);
                }
                couvert[getIndex(x, y, I)] = 1;
            }
        }
    }

    fill_sol_stock(&S, B);
    free(couvert);
    free(formes);
    
    return S;
}