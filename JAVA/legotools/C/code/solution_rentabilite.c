#include <stdlib.h>
#include <stdio.h>
#include <limits.h>

#include "dependance/structure.h"
#include "dependance/util.h"
#include "dependance/image.h"
#include "dependance/brique.h"
#include "dependance/solution.h"
#include "dependance/solution_rentabilite.h"

// Seuils pour équilibrer qualité et économie
#define TOLERANCE_FAMILLE 2000      // Erreur max tolérée pour considérer qu'une couleur est "proche"
#define TOLERANCE_HOMOGENEITE 1500  // Erreur max pour la zone entière
#define MAX_FLOAT 9999999.0f

/**
 * Algorithme de Rentabilité :
 * Cet algorithme vise à minimiser le coût total du pavage en comparant le prix 
 * de chaque grande brique avec le coût cumulé des briques 1x1 nécessaires 
 * pour couvrir la même surface.
 */
Solution run_algo_rentabilite(Image* I, BriqueList* B) {
    Solution S;
    init_sol(&S, I);

    int npix = I->W * I->H;
    int* couvert = calloc(npix, sizeof(int));
    float* cout_ref_1x1 = malloc(npix * sizeof(float));

    if (!couvert || !cout_ref_1x1) {
        perror("malloc");
        exit(EXIT_FAILURE);
    }

    int shape11 = lookupShape(B, 1, 1);

    // Etape 1 : Calcul de la référence de prix en 1x1 (Respect strict de la couleur)
    for (int y = 0; y < I->H; y++) {
        for (int x = 0; x < I->W; x++) {
            RGB pix = *get(I, x, y);
            float min_prix = MAX_FLOAT;
            int best_err_idx = -1;
            int min_err = INT_MAX;

            for (int i = 0; i < B->nBrique; i++) {
                if (B->bShape[i] == shape11) {
                    int err = colError(B->col[B->bCol[i]], pix);

                    if (err < min_err) {
                        min_err = err;
                        best_err_idx = i;
                    }
    
                    if (err < TOLERANCE_FAMILLE) {
                        if (B->bPrix[i] < min_prix) {
                            min_prix = B->bPrix[i];
                        }
                    }
                }
            }
            if (min_prix == MAX_FLOAT && best_err_idx != -1) {
                min_prix = B->bPrix[best_err_idx];
            }
            cout_ref_1x1[getIndex(x, y, I)] = min_prix;
        }
    }

    // Etape 2 : Préparation des formes
    Dimension* formes = malloc(B->nShape * sizeof(Dimension));
    int nb_formes = 0;
    for (int i = 0; i < B->nShape; i++) {
        if (B->W[i]*B->H[i] > 1) {
            formes[nb_formes].w = B->W[i];
            formes[nb_formes].h = B->H[i];
            formes[nb_formes].aire = B->W[i] * B->H[i];
            nb_formes++;
        }
    }
    qsort(formes, nb_formes, sizeof(Dimension), comparer_aire);

    // Etape 3 : Placement glouton basé sur l'économie
    for (int y = 0; y < I->H; y++) {
        for (int x = 0; x < I->W; x++) {
            if (couvert[getIndex(x, y, I)]) continue;

            int place = 0;
            RGB pix_ref = *get(I, x, y);

            for (int k = 0; k < nb_formes && !place; k++) {
                for (int rot = 0; rot < 2 && !place; rot++) {
                    int w = (rot == 0) ? formes[k].w : formes[k].h;
                    int h = (rot == 0) ? formes[k].h : formes[k].w;

                    if (x + w > I->W || y + h > I->H) continue;
                    if (!rect_is_uncovered(x, y, w, h, I, couvert)) continue;
                    if (!is_area_compatible(I, x, y, w, h, pix_ref, TOLERANCE_HOMOGENEITE)) continue;

                    int id_shape = lookupShape(B, w, h);
                    if (id_shape == -1) continue;

                    int best_brique = -1;
                    float min_prix_brique = MAX_FLOAT;

                    for (int i = 0; i < B->nBrique; i++) {
                        if (B->bShape[i] == id_shape) {
                            if (colError(B->col[B->bCol[i]], pix_ref) < TOLERANCE_FAMILLE) {
                                if (B->bPrix[i] < min_prix_brique) {
                                    min_prix_brique = B->bPrix[i];
                                    best_brique = i;
                                }
                            }
                        }
                    }

                    if (best_brique != -1) {
                        float somme_petites = 0.0f;
                        for (int dy = 0; dy < h; dy++)
                            for (int dx = 0; dx < w; dx++)
                                somme_petites += cout_ref_1x1[getIndex(x + dx, y + dy, I)];

                        if (min_prix_brique < somme_petites - 0.001f) {
                            push_sol_with_error(&S, best_brique, x, y, rot, I, B);
                            mark_rect_covered(x, y, w, h, I, couvert);
                            place = 1;
                        }
                    }
                }
            }
            
            // Etape 4 : Fallback brique 1x1 (Respect de la couleur)
            if (!place) {
                int best_1x1 = -1;
                float min_p = MAX_FLOAT;
                int best_color_idx = -1;
                int min_err = INT_MAX;

                for (int i = 0; i < B->nBrique; i++) {
                    if (B->bShape[i] == shape11) {
                        int err = colError(B->col[B->bCol[i]], pix_ref);
                        if (err < min_err) { min_err = err; best_color_idx = i; }
                        if (err < TOLERANCE_FAMILLE && B->bPrix[i] < min_p) {
                            min_p = B->bPrix[i];
                            best_1x1 = i;
                        }
                    }
                }
                
                if (best_1x1 == -1) best_1x1 = best_color_idx;

                push_sol_with_error(&S, best_1x1, x, y, 0, I, B);
                couvert[getIndex(x, y, I)] = 1;
            }
        }
    }

    fill_sol_stock(&S, B);
    free(couvert); free(cout_ref_1x1); free(formes);
    return S;
}