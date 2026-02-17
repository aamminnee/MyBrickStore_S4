#include <stdlib.h>
#include <stdio.h>
#include <limits.h>

#include "dependance/structure.h"
#include "dependance/util.h"
#include "dependance/image.h"
#include "dependance/brique.h"
#include "dependance/solution.h"
#include "dependance/solution_stock.h"

/**
 * Algorithme de Gestion de Stock :
 * Cette stratégie tente de paver l'image en utilisant prioritairement les briques 
 * disponibles en stock. Elle évite de poser des briques dont la quantité est épuisée.
 */

Solution run_algo_stock(Image* I, BriqueList* B) {
    Solution S;
    init_sol(&S, I);

    int npix = I->W * I->H;
    int* couvert = calloc(npix, sizeof(int));

    if (!couvert) { 
        perror("calloc"); 
        exit(EXIT_FAILURE); 
    }

    int shape11 = lookupShape(B, 1, 1);
    int* idxs_1x1 = malloc(B->nBrique * sizeof(int));
    int nb_1x1 = 0;
    
    int* couleur_proche = malloc(npix * sizeof(int));
    if (!idxs_1x1 || !couleur_proche) { 
        perror("malloc"); exit(EXIT_FAILURE); 
    }

    for (int i = 0; i < B->nBrique; i++) {
        if (B->bShape[i] == shape11) {
            idxs_1x1[nb_1x1++] = i;
        }
    }

    // Calcul de la carte de couleur idéale
    for (int y = 0; y < I->H; y++) {
        for (int x = 0; x < I->W; x++) {
            int meilleur_col = -1;
            int min_err = INT_MAX;
            RGB pix = *get(I, x, y);
            for (int c = 0; c < B->nCol; c++) {
                int err = colError(B->col[c], pix);
                if (err < min_err) {
                    min_err = err;
                    meilleur_col = c;
                }
            }
            couleur_proche[getIndex(x, y, I)] = meilleur_col;
        }
    }

    Dimension* formes = malloc(B->nShape * sizeof(Dimension));
    int nb_formes = 0;
    for (int i = 0; i < B->nShape; i++) {
        int w = B->W[i];
        int h = B->H[i];

        if (w <= 0 || h <= 0) {
            continue;
        }

        formes[nb_formes].w = w;
        formes[nb_formes].h = h;
        formes[nb_formes].aire = w * h;
        nb_formes++;
    }

    qsort(formes, nb_formes, sizeof(Dimension), comparer_aire);

    for (int y = 0; y < I->H; y++) {
        for (int x = 0; x < I->W; x++) {

            if (couvert[getIndex(x, y, I)]) {
                continue;
            }

            int col_cible = couleur_proche[getIndex(x, y, I)];
            int place = 0;

            for (int k = 0; k < nb_formes && !place; k++) {
                int w_base = formes[k].w;
                int h_base = formes[k].h;

                for (int rot = 0; rot < 2 && !place; rot++) {
                    int w = (rot == 0) ? w_base : h_base;
                    int h = (rot == 0) ? h_base : w_base;

                    if (x + w > I->W || y + h > I->H) {
                        continue;
                    }
                    if (!rect_is_uncovered(x, y, w, h, I, couvert)) {
                        continue;
                    }

                    // On vérifie si la zone correspond bien à la couleur cible
                    if (!rect_has_uniform_closest(x, y, w, h, I, couleur_proche, col_cible)) {
                        continue;
                    }

                    int id_shape = lookupShape(B, w, h);
                    if (id_shape == -1) {
                        continue;
                    }

                    int id_brique = getBriqueWithColor(B, id_shape, col_cible);

                    // Vérification du stock
                    if (id_brique != -1 && B->bStock[id_brique] > 0) {
                        push_sol_with_error(&S, id_brique, x, y, 0, I, B);
                        mark_rect_covered(x, y, w, h, I, couvert);
                        B->bStock[id_brique]--;
                        place = 1;
                    }
                }
            }

            if (!place) {
                int best_brique = -1;
                long long min_err_fallback = LLONG_MAX;
                RGB pix_actuel = *get(I, x, y);

                for (int i = 0; i < nb_1x1; i++) {
                    int idx = idxs_1x1[i];
                    if (B->bStock[idx] > 0) {

                        // On calcule l'erreur entre la couleur du pixel et cette brique
                        long long err = (long long)colError(B->col[B->bCol[idx]], pix_actuel);
                        if (err < min_err_fallback) {
                            min_err_fallback = err;
                            best_brique = idx;
                        }
                    }
                }

                if (best_brique != -1) {
                    push_sol_with_error(&S, best_brique, x, y, 0, I, B);
                    B->bStock[best_brique]--;
                } else {
                    push_sol_with_error(&S, -1, x, y, 0, I, B);
                }
                couvert[getIndex(x, y, I)] = 1;
            }
        }
    }

    fill_sol_stock(&S, B);
    free(couvert);
    free(couleur_proche);
    free(formes);
    free(idxs_1x1);
    
    return S;
}
