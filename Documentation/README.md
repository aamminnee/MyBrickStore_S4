# 🧱 MyBrickStore - SAE S3

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php)
![Java](https://img.shields.io/badge/Java-17-ED8B00?logo=openjdk)
![MariaDB](https://img.shields.io/badge/MariaDB-10.6-003545?logo=mariadb)
![License](https://img.shields.io/badge/License-MIT-green.svg)

> **De l'image à la brique.**
> MyBrickStore est une solution e-commerce complète permettant de transformer n'importe quelle image en mosaïque LEGO®, de commander les pièces et de gérer les stocks via une simulation d'usine connectée.

---

## 🚀 Accès Rapide

| Ressource | Lien | Description |
| :--- | :--- | :--- |
| **📘 Documentation** | [**Consulter la Doc Technique**](https://alkzhab.github.io/MyBrickStore-Doc/) | Architecture, Javadoc, PHPDoc, SQL. |
| **🌐 Site Web** | [http://localhost/MyBrickStore](http://localhost/MyBrickStore) | Application principale (PHP MVC). |
| **🗃️ Base de Données** | [http://localhost/phpmyadmin](http://localhost/phpmyadmin) | Administration SQL (User: `root`). |
| **📄 Rapports** | [Voir le dossier /Rapports](/Rapports) | Dossiers techniques et fonctionnels (PDF). |
| **📺 Vidéos** | [Voir le dossier /videos](/videos) | Démonstrations du site et du module Java. |

---

## 🔐 Identifiants de Test (Cheat Sheet)

Pour faciliter la correction et les tests, voici les comptes pré-configurés :

### 👨‍💻 Administrateur (Back-Office)
Accès au tableau de bord complet (Gestion stocks, commandes, statistiques, réapprovisionnement).
* **Login / Email :** `admin` (ou `admin@mybrickstore.com`)
* **Mot de passe :** `123456789aA!`

### 💳 Paiement (PayPal Sandbox)
Pour valider une commande fictive lors du paiement :
* **Email :** `sb-ton-compte@personal.example.com`
* **Mot de passe :** `12345678`

---

## ✨ Fonctionnalités Clés

### 🎨 Expérience Utilisateur (Front-End)
* **Mode Invité :** Possibilité de créer une mosaïque et d'ajouter au panier sans inscription (connexion requise uniquement au paiement).
* **Traitement d'Image :** Upload, recadrage (Cropper.js) et pixelisation en temps réel.
* **Ergonomie :** Sécurisation des formulaires (double confirmation de MDP, bouton "voir le mot de passe").

### ⚙️ Moteur & Algorithmique (Backend)
* **Architecture MVC :** Framework PHP propriétaire (Router, Controllers, Models).
* **Algorithmes de Pavage (C/Java) :**
    * *Mode Rentabilité :* Compromis optimisé entre coût et fidélité.
    * *Mode Forme Libre :* Algorithme glouton priorisant les grandes pièces.
* **Base de Données Intelligente :**
    * Triggers de sécurité (Immuabilité des factures et commandes).
    * Procédures stockées pour le calcul de stock temps réel.

### 🏭 Simulation Usine (Java)
* Gestion des ordres de fabrication.
* Validation des transactions par **Proof of Work** (Minage cryptographique).
* Synchronisation bidirectionnelle avec le site Web.

---

## 📚 Qualité & Documentation

Dans une optique de professionnalisation, le code respecte les standards industriels. Chaque module dispose de sa documentation normative générée automatiquement :

| Module | Standard | Outil |
| :--- | :--- | :--- |
| **☕ Java** | Oracle Javadoc | *Javadoc* |
| **🐘 PHP** | PSR-5 / PSR-19 | *phpDocumentor* |
| **⚙️ C** | Doxygen Style | *Doxygen* |
| **🗃️ SQL** | DBML | *DBDocs* |

🚀 **[Accéder au Portail de Documentation](https://alkzhab.github.io/MyBrickStore-Doc/)**

---

## 🛠️ Installation & Démarrage

### Prérequis
* Serveur Web (Apache/Nginx via XAMPP, WAMP ou MAMP).
* PHP >= 8.0 avec extension GD activée.
* Base de données MariaDB ou MySQL.
* Java Runtime (JRE 17) pour le module usine.

### Procédure
1. **Cloner le projet** dans votre dossier serveur (`htdocs` ou `www`) :
   ```bash
   git clone [https://github.com/aamminnee/SAE_S3_BUT2_INFO.git](https://github.com/aamminnee/SAE_S3_BUT2_INFO.git) MyBrickStore