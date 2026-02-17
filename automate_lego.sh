#!/bin/bash

PROJECT_ROOT="/var/www/html/SAE_S3_BUT2_INFO/JAVA/legotools" # Assurez-vous que le chemin est bon vers le dossier Java !
JAR_PATH="$PROJECT_ROOT/target/legotools-1.0-SNAPSHOT.jar"

if [ ! -f "$JAR_PATH" ]; then
    echo "erreur : le fichier $JAR_PATH est introuvable."
    exit 1
fi

# On va dans le dossier legotools pour que Java trouve le .env
cd "$PROJECT_ROOT"

echo " --- debut de la maintenance automatique des stocks $(date) ---"

# 1. On mine de l'argent
echo "execution de refill (minage)..."
java -jar "$JAR_PATH" refill
java -jar "$JAR_PATH" refill

# 3. On lance l'analyse intelligente
echo "execution de l'analyse proactive..."
java -jar "$JAR_PATH" proactive

echo "--- maintenance terminee ---"