#ifndef STRUCTURE_H
#define STRUCTURE_H

#include <stdlib.h>
#include <stdio.h>
#include <assert.h>
#include <limits.h>

/** @brief Active les messages de debug lors du chargement. */
#define DEBUG_LOAD 1
/** @brief Nombre maximum de couleurs supportées. */
#define MAX_COLORS 275
/** @brief Valeur sentinelle pour indiquer qu'aucun élément n'a été trouvé. */
#define UNMATCHED -1

/**
 * @struct RGB
 * @brief Représente un pixel avec ses composantes Rouge, Vert, Bleu.
 */
typedef struct {
    int R; /**< Composante Rouge (0-255) */
    int G; /**< Composante Verte (0-255) */
    int B; /**< Composante Bleue (0-255) */
} RGB;

/**
 * @struct Image
 * @brief Structure représentant l'image source pixelisée.
 *
 * Les pixels sont stockés dans un tableau 1D unique pour optimiser le cache.
 * L'accès se fait via la formule : y * W + x.
 */
typedef struct {
    int W;      /**< Largeur de l'image */
    int H;      /**< Hauteur de l'image */
    RGB* rgb;   /**< Tableau des pixels */
} Image;

/**
 * @struct BriqueList
 * @brief Catalogue complet des briques disponibles (chargé depuis briques.txt).
 *
 * Utilise une architecture "Structure of Arrays" (SoA) : 
 * plusieurs tableaux parallèles au lieu d'un tableau de structures.
 */
typedef struct {
    int nShape;   /**< Nombre de formes uniques définies */
    int nCol;     /**< Nombre de couleurs uniques définies */
    int nBrique;  /**< Nombre total de références (combinaisons forme/couleur) */
    
    int* W;       /**< Largeur des formes (taille nShape) */
    int* H;       /**< Hauteur des formes (taille nShape) */
    int* T;       /**< Configuration des trous (ID) */
    
    RGB* col;     /**< Palette des couleurs (taille nCol) */
    
    int* bCol;    /**< ID de la couleur pour chaque brique (taille nBrique) */
    int* bShape;  /**< ID de la forme pour chaque brique (taille nBrique) */
    float* bPrix; /**< Prix unitaire pour chaque brique (taille nBrique) */
    int* bStock;  /**< Stock disponible pour chaque brique (taille nBrique) */
} BriqueList;

/**
 * @struct SolItem
 * @brief Représente une brique posée dans la solution finale.
 */
typedef struct {
    int iBrique; /**< Index de la brique dans BriqueList (-1 si vide/noir) */
    int x;       /**< Position X du coin haut-gauche */
    int y;       /**< Position Y du coin haut-gauche */
    int rot;     /**< Rotation (0: normale, 1: tournée de 90 degrés) */
} SolItem;

/**
 * @struct Solution
 * @brief Contient le résultat complet d'un algorithme de pavage.
 */
typedef struct {
    long int length;      /**< Nombre de briques posées */
    long int totalError;  /**< Somme des distances de couleur (Qualité visuelle) */
    double cost;          /**< Coût financier total du pavage */
    long int stock;       /**< Nombre de briques manquantes (si stock insuffisant) */
    SolItem* array;       /**< Liste dynamique des briques posées */
} Solution;

/**
 * @struct ShapeWH
 * @brief Structure utilitaire simple pour stocker des dimensions.
 */
typedef struct { 
    int w; 
    int h; 
} ShapeWH;

/**
 * @struct Dimension
 * @brief Structure étendue avec calcul d'aire pré-calculé.
 */
typedef struct {
    int w;
    int h;
    int aire;
} Dimension;

#endif