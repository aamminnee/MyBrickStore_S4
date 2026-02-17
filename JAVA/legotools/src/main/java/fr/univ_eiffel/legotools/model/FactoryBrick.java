package fr.univ_eiffel.legotools.model;

import java.time.LocalDate;
import java.time.LocalTime;
import java.util.HexFormat;

/**
 * Représente un actif numérique "Brique" tel que livré par l'usine.
 * <p>
 * Garantit que les données sont immuables (inchangeables).
 * Cette classe représente la forme physique, mais aussi son identité unique
 * (numéro de série) et sa preuve d'authenticité (certificat cryptographique).
 * </p>
 * @param name Le nom technique composite (ex: "2-2/c9cae2").
 * @param serial Le numéro de série unique (encodant la date de fabrication).
 * @param certificate La signature numérique (Ed25519) prouvant que la brique vient bien de l'usine.
 */
public record FactoryBrick(String name, String serial, String certificate) {

    /**
     * Extrait le code couleur hexadécimal du nom composite.
     * <p>
     * Le format attendu de l'API est "Forme/Couleur" (ex: "2x4_Plate/FF0000").
     * </p>
     * @return Le code hexadécimal (ex: "FF0000") ou "000000" (Noir) par défaut si le format est invalide.
     */
    public String color() {
        if (name != null && name.contains("/")) {
            return name.substring(name.lastIndexOf('/') + 1);
        }
        return "000000"; 
    }

    /**
     * Extrait l'identifiant de la forme (Shape) du nom composite.
     * @return Le nom de la forme sans la couleur.
     */
    public String shapeName() {
        if (name != null && name.contains("/")) {
            return name.substring(0, name.lastIndexOf('/'));
        }
        return name;
    }

    /**
     * Décode la date de fabrication cachée dans le numéro de série.
     * <p>
     * Le numéro de série n'est pas aléatoire, ses 5 premiers octets suivent un protocole précis :
     * </p>
     * <ul>
     * <li><b>Octets 0-1 (16 bits) :</b> Nombre de jours écoulés depuis le 01/01/2000.</li>
     * <li><b>Octets 2-4 (24 bits) :</b> Nombre de millisecondes écoulées depuis minuit.</li>
     * </ul>
     * <p>
     * On utilise des opérations bit à bit (shifters) pour reconstruire ces nombres.
     * </p>
     * @return Une chaîne lisible (ex: "2023-10-15 à 14:30:00") ou "Date invalide".
     */
    public String getManufacturingDateInfo() {

        // vérifie si le numéro de série est présent et assez long
        if (serial == null || serial.length() < 10) return "Date inconnue";

        try {
            byte[] bytes = HexFormat.of().parseHex(serial);
            
            // récupère le nombre de jours écoulés depuis le premier janvier deux mille
            int daysSince2000 = ((bytes[0] & 0xFF) << 8) | (bytes[1] & 0xFF);
            
            // récupère le nombre de millisecondes écoulées dans la journée
            long msInDay = ((bytes[2] & 0xFF) << 16) | ((bytes[3] & 0xFF) << 8) | (bytes[4] & 0xFF);

            LocalDate baseDate = LocalDate.of(2000, 1, 1);
            LocalDate date = baseDate.plusDays(daysSince2000);
            LocalTime time = LocalTime.ofNanoOfDay(msInDay * 1_000_000);

            return date.toString() + " à " + time.toString();
        } catch (Exception e) {
            return "Date invalide";
        }
    }
}