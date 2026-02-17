Table "BankDetails" {
  "id_Bank_Details" int [pk, not null, increment]
  "id_Customer" int [not null, note: 'Propriétaire du moyen de paiement']
  "bank_name" varchar(150) [not null, note: 'Nom de la banque émettrice']
  "last_four" varchar(4) [not null, note: '4 derniers chiffres']
  "expire_at" date [not null]
  "payment_token" varchar(255) [not null, note: 'Jeton sécurisé (PayPal)']
  "card_brand" varchar(50) [not null, note: 'Réseau (Visa, Mastercard, Amex)']

  Indexes {
    id_Customer [name: "id_Customer"]
  }
  Note: 'Portefeuille numérique : Stocke les méthodes de paiement tokenisées'
}

Table "Colors" {
  "id_color" int(4) [pk, not null, increment]
  "name" varchar(28) [default: NULL, note: 'Nom commercial officiel (ex: Bright Red)']
  "hex_color" varchar(6) [default: NULL, note: 'Code hexadécimal']
  "is_trans" varchar(5) [default: NULL, note: 'Transparence du plastique']
  Note: 'Liste des couleurs disponibles'
}

Table "Customer" {
  "id_Customer" int [pk, not null, increment]
  "password" varchar(255) [not null, note: 'Hashage du mot de passage']
  "phone" char(10) [default: NULL]
  "id_SaveCustomer" int [default: NULL, note: '''Lien vers l\\\'identité civile''']
  "etat" varchar(20) [default: 'invalide', note: 'Cycle de vie : invalide -> valide -> banni']
  "mode" varchar(50) [default: NULL, note: 'Configuration sécurité : 2FA activé ou désactivé']
  "role" varchar(20) [not null, default: 'user', note: 'ACL : user ou admin']

  Indexes {
    id_SaveCustomer [name: "id_SaveCustomer"]
  }
  Note: 'Authentification : Gestion des accès et sécurité des comptes'
}

Table "CustomerImage" {
  "id_Image" int [pk, not null, increment]
  "upload_date" datetime [not null, default: `CURRENT_TIMESTAMP`]
  "file" longblob [not null, note: '''Blob binaire de l\\\'image source''']
  "file_type" varchar(255) [not null, note: 'MIME Type pour les headers HTTP']
  Note: 'Stockage Fichiers : Images brutes uploadées par les clients'
}

Table "CustomerOrder" {
  "id_Order" int [pk, not null, increment]
  "order_date" datetime [not null, default: `CURRENT_TIMESTAMP`]
  "status" varchar(20) [not null, note: 'En cours, Payée, Expédiée...']
  "total_amount" decimal(10,2) [not null]
  "id_Customer" int [not null]
  "id_Image" int [default: NULL, note: '''Lien vers l\\\'image source de la mosaïque''']

  Indexes {
    id_Customer [name: "id_Customer"]
    id_Image [name: "id_Image"]
  }
  Note: 'Commandes Clients : Historique des achats effectués'
}

Table "FactoryBrick" {
  "serial" varchar(32) [pk, not null, note: 'Numéro de série unique de la brique']
  "certificate" TEXT [note: '''Certificat d\\\'authenticité JSON/Text''']
  "shape_id" INT [note: 'Forme de la brique commandée']
  "color_id" INT [note: 'Couleur de la brique commandée']
  "purchase_date" TIMESTAMP [default: `CURRENT_TIMESTAMP`]

  Indexes {
    shape_id [name: "shape_id"]
    color_id [name: "color_id"]
  }
  Note: 'Traçabilité Unitaire : Suivi individuel des pièces commandées'
}

Table "FactoryOrder" {
  "id_FactoryOrder" varchar(64) [pk, not null, note: '''ID unique généré par l\\\'API fournisseur''']
  "total_price" decimal(10,2) [not null]
  "order_date" date [not null, default: `curdate()`]
  Note: 'Achat Fournisseur : Entêtes des commandes de réapprovisionnement'
}

Table "FactoryOrderDetails" {
  "id_Detail" int [pk, not null, increment]
  "id_FactoryOrder" varchar(64) [not null]
  "id_Item" int [not null, note: 'Brique commandée']
  "quantity" int [not null, note: 'Quantité commandée']

  Indexes {
    id_FactoryOrder [name: "id_FactoryOrder"]
    id_Item [name: "id_Item"]
  }
  Note: 'Détail Achat : Contenu ligne par ligne des commandes fournisseurs'
}

Table "Image" {
  "id_Image" int [pk, not null, increment]
  "filename" varchar(255) [not null, note: 'Nom du fichier']
  "id_Customer" int [not null]

  Indexes {
    id_Customer [name: "id_Customer"]
  }
  Note: 'Métadonnées Images : Lien entre le fichier physique et le client'
}

Table "Invoice" {
  "id_Invoice" int [pk, not null, increment]
  "issue_date" datetime [not null, default: `CURRENT_TIMESTAMP`]
  "payment_date" datetime [default: NULL]
  "total_amount" decimal(10,2) [not null]
  "order_status" varchar(50) [default: 'Pending']
  "invoice_number" varchar(50) [default: NULL, note: 'Numéro unique séquentiel']
  "adress" varchar(255) [not null, note: 'Adresse de facturation figée']
  "id_Order" int [not null]
  "order_date" datetime [not null, default: `CURRENT_TIMESTAMP`]
  "id_Bank_Details" int [default: NULL]
  "id_SaveCustomer" int [not null, note: 'Snapshot des infos client au moment de la facture']

  Indexes {
    invoice_number [unique, name: "invoice_number"]
    id_Order [name: "id_Order"]
    id_Bank_Details [name: "id_Bank_Details"]
    id_SaveCustomer [name: "id_SaveCustomer"]
  }
  Note: 'Comptabilité : Factures émises (Document légal immuable)'
}

Table "Item" {
  "id_Item" int [pk, not null, increment]
  "shape_id" int(11) [not null]
  "color_id" int(11) [not null]
  "price" decimal(6,2) [not null, default: 0.00, note: 'Prix unitaire de la pièce']

  Indexes {
    (shape_id, color_id) [unique, name: "shape_id"]
    color_id [name: "color_id"]
  }
  Note: 'Catalogue Produits (SKU) : Croisement unique Forme + Couleur'
}

Table "Mosaic" {
  "id_Mosaic" int [pk, not null, increment]
  "pavage" longblob [not null, note: 'Données du plan de montage']
  "generation_date" datetime [not null, default: `CURRENT_TIMESTAMP`]
  "id_Image" int [default: NULL]
  "id_Order" int [default: NULL]

  Indexes {
    id_Image [name: "fk_mosaic_image"]
    id_Order [name: "fk_mosaic_order"]
  }
  Note: '''Moteur Logique : Résultat de l\\\'algorithme de traitement d\\\'image'''
}

Table "MosaicComposition" {
  "id_Mosaic" int [not null]
  "id_Item" int [not null]
  "quantity_needed" int [not null, default: 1, note: 'Quantité requise pour ce type de brique pour former la mosaïque']

  Indexes {
    (id_Mosaic, id_Item) [pk]
    id_Item [name: "fk_composition_item"]
  }
  Note: 'Nomenclature (BOM) : Liste des pièces nécessaires pour une mosaïque'
}

Table "SaveCustomer" {
  "id_SaveCustomer" int [pk, not null, increment]
  "first_name" varchar(255) [not null]
  "last_name" varchar(255) [not null]
  "email" varchar(255) [not null]
  "phone" varchar(20) [default: NULL]
  "created_at" datetime [default: `CURRENT_TIMESTAMP`]
  Note: 'Profil Civil : Données personnelles et identité'
}

Table "Shapes" {
  "id_shape" int(11) [pk, not null, increment]
  "width" tinyint(4) [not null, note: 'Largeur en tenons']
  "length" tinyint(4) [not null, note: 'Longueur en tenons']
  "hole" varchar(10) [default: NULL, note: 'Spécificité (forme non-rectangulaire)']
  "name" varchar(50) [not null, note: 'Nom technique (ex: 2x4)']

  Indexes {
    (width, length, hole) [unique, name: "width"]
  }
  Note: 'Liste des Formes : Dimensions physiques des briques'
}

Table "StockEntry" {
  "id_Stock" int [pk, not null, increment]
  "id_Item" int [not null]
  "date_import" date [not null, default: `curdate()`]
  "quantity" int [not null, note: 'Positif pour ajout, Négatif pour retrait']

  Indexes {
    id_Item [name: "fk_stock_item"]
  }
  Note: 'Journal des Stocks : Historique complet des mouvements (Ledger)'
}

Table "Tokens" {
  "id_Token" int [pk, not null, increment]
  "id_Customer" int [not null]
  "token" varchar(10) [not null, note: 'Code alphanumérique court']
  "types" varchar(50) [not null, note: 'Type: validation_email, reset_password, 2fa']
  "expires_at" datetime [not null]

  Indexes {
    id_Customer [name: "id_Customer"]
  }
  Note: 'Sécurité Temporaire : Gestion des codes à durée de vie limitée'
}

Table "Translations" {
  "id" int [pk, not null, increment]
  "key_name" varchar(100) [not null, note: 'Clé de traduction (ex: home_title)']
  "lang" varchar(5) [not null, note: 'Code langue (fr, en)']
  "texte" varchar(255) [not null, note: 'Texte traduit']
  Note: 'i18n : Base de données des traductions dynamiques'
}

Ref "BankDetails_ibfk_1":"Customer"."id_Customer" < "BankDetails"."id_Customer"

Ref "Customer_ibfk_1":"SaveCustomer"."id_SaveCustomer" < "Customer"."id_SaveCustomer"

Ref "CustomerOrder_ibfk_1":"Customer"."id_Customer" < "CustomerOrder"."id_Customer"

Ref "CustomerOrder_ibfk_2":"CustomerImage"."id_Image" < "CustomerOrder"."id_Image"

Ref "fk_fb_shape":"Shapes"."id_shape" < "FactoryBrick"."shape_id" [delete: restrict]

Ref "fk_fb_color":"Colors"."id_color" < "FactoryBrick"."color_id" [delete: restrict]

Ref "FactoryOrderDetails_ibfk_1":"FactoryOrder"."id_FactoryOrder" < "FactoryOrderDetails"."id_FactoryOrder" [delete: cascade]

Ref "FactoryOrderDetails_ibfk_2":"Item"."id_Item" < "FactoryOrderDetails"."id_Item" [delete: cascade]

Ref "Image_ibfk_1":"Customer"."id_Customer" < "Image"."id_Customer" [delete: cascade]

Ref "Invoice_ibfk_1":"CustomerOrder"."id_Order" < "Invoice"."id_Order"

Ref "Invoice_ibfk_2":"BankDetails"."id_Bank_Details" < "Invoice"."id_Bank_Details"

Ref "Invoice_ibfk_3":"SaveCustomer"."id_SaveCustomer" < "Invoice"."id_SaveCustomer"

Ref "pieces_ibfk_1":"Shapes"."id_shape" < "Item"."shape_id" [update: cascade]

Ref "pieces_ibfk_2":"Colors"."id_color" < "Item"."color_id" [update: cascade]

Ref "fk_mosaic_image":"Image"."id_Image" < "Mosaic"."id_Image" [delete: cascade]

Ref "fk_mosaic_order":"CustomerOrder"."id_Order" < "Mosaic"."id_Order" [delete: cascade]

Ref "fk_composition_mosaic":"Mosaic"."id_Mosaic" < "MosaicComposition"."id_Mosaic" [delete: cascade]

Ref "fk_composition_item":"Item"."id_Item" < "MosaicComposition"."id_Item" [delete: cascade]

Ref "fk_stock_item":"Item"."id_Item" < "StockEntry"."id_Item"

Ref "Tokens_ibfk_1":"Customer"."id_Customer" < "Tokens"."id_Customer" [delete: cascade]
