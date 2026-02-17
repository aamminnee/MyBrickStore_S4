#!/bin/bash
PROJECT_ROOT="/var/www/html/SAE_S3_BUT2_INFO/JAVA/legotools"
JAR_PATH="$PROJECT_ROOT/target/legotools-1.0-SNAPSHOT.jar"

echo "--- Démarrage du Mineur de Crédits ---"
echo "Appuyez sur CTRL+C pour arrêter."

cd "$PROJECT_ROOT"

while true
do
    java -jar "$JAR_PATH" refill
    echo "Pause de 1 seconde..."
    sleep 1
done