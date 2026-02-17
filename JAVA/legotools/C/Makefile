# Compilateur
CC=gcc

# Options de compilation :
# -Icode : Permet de trouver les headers correctement
# -g : Debug
# -Wall -Wextra : Affiche tous les warnings (Qualité Pro)
CFLAGS=-g -Wall -Wextra -Icode

# Options d'édition de liens :
# -lm : Indispensable pour lier la bibliothèque mathématique standard
LDFLAGS=-lm

# Dossiers
SRC_DIR=code
OBJ_DIR=code/exec
DEST_FILE=output

# Chemin final de l'exécutable
BIN=$(OBJ_DIR)/pavage

# Liste explicite des sources
SRC=$(SRC_DIR)/main.c \
    $(SRC_DIR)/image.c \
    $(SRC_DIR)/brique.c \
    $(SRC_DIR)/util.c \
    $(SRC_DIR)/solution.c \
    $(SRC_DIR)/solution_stock.c \
    $(SRC_DIR)/solution_libre.c \
    $(SRC_DIR)/solution_minimisation.c \
    $(SRC_DIR)/solution_rentabilite.c

# Transformation automatique des .c en .o pour la liste des objets
OBJ=$(patsubst $(SRC_DIR)/%.c, $(OBJ_DIR)/%.o, $(SRC))

# Fichiers de sortie à nettoyer
OUT=$(DEST_FILE)/pavage_stock.txt \
    $(DEST_FILE)/pavage_libre.txt \
    $(DEST_FILE)/pavage_minimisation.txt \
    $(DEST_FILE)/pavage_rentabilite.txt

# Variable pour choisir l'algo via "make run ARGO=stock" (par défaut: all)
ARGO ?= all

# Cible par défaut
all: $(BIN)

# Règle de compilation des .c en .o
# Le @mkdir -p assure que le dossier code/exec existe avant de compiler
$(OBJ_DIR)/%.o: $(SRC_DIR)/%.c
	@mkdir -p $(OBJ_DIR)
	$(CC) $(CFLAGS) -c $< -o $@

# Règle de création de l'exécutable (Edition de liens)
$(BIN): $(OBJ)
	$(CC) $(OBJ) -o $(BIN) $(LDFLAGS)
	@echo "Succès : Exécutable généré dans $(BIN)"

# Nettoyage complet
clean:
	rm -f $(OBJ) $(BIN) $(OUT)
	rm -rf $(OBJ_DIR)

# Exécution rapide
# Exemple : make run ARGO=rentabilite
run: $(BIN)
	./$(BIN) input $(ARGO)

# Debug
debug: $(BIN)
	@gdb ./$(BIN)

.PHONY: all clean run debug