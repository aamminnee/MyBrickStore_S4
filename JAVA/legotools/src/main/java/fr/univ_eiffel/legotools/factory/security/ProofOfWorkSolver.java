package fr.univ_eiffel.legotools.factory.security;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.util.Arrays;

/**
 * Moteur de résolution de "Preuve de Travail" (Proof of Work).
 * <p>
 * Cette classe effectue une recherche par force brute
 * pour trouver une donnée dont le hachage (SHA-256) commence par une séquence précise.
 * C'est ce calcul intensif qui prouve à l'usine que nous avons travaillé pour mériter des crédits.
 * </p>
 */
public class ProofOfWorkSolver {

    // Instance réutilisable pour éviter la surcharge d'initialisation à chaque hash
    private final MessageDigest messageDigest;

    /**
     * Initialise le solveur avec l'algorithme spécifié.
     * @param hashAlgorithm L'algorithme de hachage imposé par l'usine (généralement "SHA-256").
     */
    public ProofOfWorkSolver(String hashAlgorithm) {
        try {
            this.messageDigest = MessageDigest.getInstance(hashAlgorithm);
        } catch (NoSuchAlgorithmException e) {
            throw new RuntimeException(e);
        }
    }

    /**
     * Incrémente un tableau d'octets comme s'il s'agissait d'un grand nombre entier.
     * <p>
     * Cela évite d'allouer de la mémoire à chaque itération de la boucle de minage,
     * ce qui aurait ruiné les performances à cause du Garbage Collector.
     * </p>
     * @param data Le tableau à incrémenter.
     */
    public static void incrementByteArray(byte[] data) {

        // On parcourt le tableau de la fin vers le début (Little Endian logique)
        for (int i = 0; i < data.length; i++) {
            byte value = data[data.length-1-i];
            // Si l'octet vaut 255 (0xFF ou -1 en Java signé), on le remet à 0 et on retient 1
            if (value == -1) {
                data[data.length-1-i] = 0;
            } else {
                // Sinon on incrémente juste cet octet et on a fini
                data[data.length-1-i]++;
                break;
            }
        }
    }

    /**
     * Boucle de recherche par force brute (Minage).
     * <p>
     * Teste des millions de combinaisons jusqu'à trouver celle qui satisfait la condition du défi.
     * </p>
     * @param dataPrefix Les données fixes imposées par le serveur.
     * @param hashPrefix Le préfixe hexadécimal que doit avoir le hash final.
     * @return Le tableau complet (Prefixe + Nonce trouvé) qui valide le défi.
     */
    public byte[] solve(byte[] dataPrefix, byte[] hashPrefix) {
        // Copie le préfixe et ajoute 16 octets vides à la fin pour le "nonce" (le compteur)
        byte[] content = Arrays.copyOf(dataPrefix, dataPrefix.length+16);
        while (true) {
            // Calcul du hash de la tentative actuelle
            messageDigest.reset();
            byte[] digest = messageDigest.digest(content);

            // Vérification du début du hash
            if (Arrays.mismatch(digest, hashPrefix) == hashPrefix.length)
                return content;

            // On incrémente le nonce et on recommence
            incrementByteArray(content);
        }
    }    
}