package fr.univ_eiffel.legotools.factory.api;

import java.io.IOException;

/**
 * Interface pour le patron de conception Stratégie (Payment Strategy).
 * Permet de définir différents moyens de financer les commandes (Minage, Carte Bleue, PayPal...).
 */
public interface PaymentStrategy {

    /**
     * Méthode appelée lorsqu'un paiement ou une vérification de solde est nécessaire.
     * @param amountNeeded Le montant total requis pour l'opération.
     * @param currentBalance Le solde actuel disponible sur le compte.
     * @param api Une référence vers l'API (Callback) pour effectuer des actions (ex: recharge).
     * @throws IOException Si une opération réseau échoue.
     */
    void pay(long amountNeeded, long currentBalance, ApiSender api) throws IOException;
}