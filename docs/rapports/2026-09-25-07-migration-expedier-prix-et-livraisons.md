# Rapport des travaux — Migration Expédier (lot 1) : prix réel et livraisons dans le nouveau backend

| | |
|---|---|
| **Date** | 25/09/2026 |
| **Projet** | MonColis Particulier — backend (API) |
| **Module** | Expédier (livraison de colis) |
| **Statut** | Terminé côté backend — branchement de l'app à suivre (lot E9) |

## 1. Contexte

Le parcours Expédier de l'app s'appuyait sur l'ancienne API et affichait **3 prix fixes**
(moto, tricycle, camion), quels que soient la distance, le poids ou les options. Avant de migrer,
un plan complet a été rédigé : `docs/PLAN_BACKEND.md`. Il reprend toutes les tâches backend
réalisées depuis le matin et découpe la migration en 10 lots, avec les décisions attendues.

Ce rapport couvre les lots **E1 (référentiels)**, **E2 (prix)** et **E3 (création et consultation des livraisons)**.

## 2. Travaux réalisés

### 2.1 Référentiels (E1)
- **7 natures de colis** : documents, colis standard, vêtements, alimentaire, électronique, fragile, volumineux.
- **3 véhicules** avec leur poids maximum et leur **grille tarifaire** : prise en charge, prix au km,
  poids inclus, supplément par kg, minimum.
- **5 options payantes** : express, fragile, assurance, accusé de réception, emballage protecteur.
- Toutes ces valeurs sont **en base de données** : un tarif se modifie sans nouvelle version de l'app ni du serveur.

### 2.2 Prix réel (E2)
- Le prix est calculé **par le serveur** pour chaque véhicule, à partir de :
  - la **distance routière réelle** (même moteur d'itinéraire que la carte de l'app ; repli à vol
    d'oiseau corrigé si ce service ne répond pas) ;
  - le poids total ;
  - les options choisies.
- Chaque prix est un **devis garanti 15 minutes** : la livraison est créée à partir de ce devis, et
  le prix annoncé au client est exactement celui facturé. L'app ne peut pas imposer un autre montant.
- Un véhicule trop petit pour le poids est affiché « indisponible », avec la raison (« Poids maximum 30 kg »).
- Temps estimé pour chaque véhicule : arrivée du coursier + trajet.
- Zone desservie limitée (60 km, réglable).

### 2.3 Livraisons (E3)
- **Création** à partir du devis :
  - adresses avec précisions et consignes pour le coursier ;
  - expéditeur (par défaut le client connecté) et destinataire ;
  - plusieurs colis par envoi (nature, poids, quantité, valeur, dimensions) ;
  - paiement Mobile Money ou espèces.
- **Référence officielle** lisible, du type `MC-250925-7KQX` (sans caractères ambigus 0/O, 1/I),
  qui remplace l'identifiant généré sur le téléphone.
- **Contrôles** :
  - devis expiré, déjà utilisé ou d'un autre client refusé ;
  - livraison différente du devis refusée (adresse déplacée, colis plus lourd, valeur plus élevée).
    Un léger décalage du repère sur la carte reste accepté.
- **Photos et vidéos du colis** (6 photos et 1 vidéo maximum).
- **« Mes livraisons »** : historique paginé, filtré en cours / livrées / annulées.
- **Détail** : adresses, colis, détail du prix, paiement, photos et **chronologie des statuts**.
- **Annulation** jusqu'au ramassage du colis, avec motif.
- **Cycle de vie** : 9 statuts, transitions contrôlées et historisées. C'est la base des lots suivants
  (paiement, coursiers, suivi, réception).

## 3. APIs livrées (9 routes)

| Fonction | Route |
|---|---|
| Natures de colis | `GET /api/configurations/nature-colis` |
| Véhicules, options, moyens de paiement | `GET /api/configurations/expedition` |
| Estimer le prix | `GET /api/tarifs/estimer` |
| Créer une livraison | `POST /api/livraisons` (et `/api/livraisons/create`) |
| Mes livraisons | `GET /api/livraisons/mes-livraisons` |
| Détail | `GET /api/livraisons/{reference}` |
| Photos / vidéos | `POST /api/livraisons/{reference}/medias` |
| Annuler | `POST /api/livraisons/{reference}/annuler` |

Toutes les routes sont documentées dans Swagger, dans trois nouvelles rubriques « Expédier ».

## 4. Tests et vérifications

- **83 tests automatisés au vert**, dont 19 nouveaux :
  - calcul du prix selon la grille, options et arrondi, supplément de poids, véhicule trop petit ;
  - repli si le calcul d'itinéraire échoue, hors zone ;
  - création en espèces et en Mobile Money ;
  - prix imposé par le devis, devis à usage unique, expiré ou d'un autre client, livraison différente du devis ;
  - historique et filtres, confidentialité entre clients, annulation, photos et limite.
- **Vérification réelle** avec l'itinéraire routier Bastos → Essos (3,9 km, 2 kg, express) :
  moto 1 450 F, tricycle 2 100 F, camion 6 500 F.
- Base MySQL mise à jour. Documentation Swagger sans avertissement.

## 5. Limites et décisions attendues

- **Grille tarifaire** : les valeurs de départ sont à valider (tableau dans `docs/PLAN_BACKEND.md` §2.7).
- **Paiement Mobile Money (lot E4)** : la livraison est créée « en attente de paiement », mais aucun
  prélèvement n'a lieu tant que l'agrégateur n'est pas choisi.
- **Coursiers (lot E5)** : l'attribution d'un coursier dépend du mode d'intégration de ClemenTino, à décider.
- L'app mobile utilise encore l'ancienne API pour ce parcours : son branchement est le lot E9.

## 6. Prochaines étapes

1. **E9** : l'app utilise ces APIs (prix réels, référence officielle, historique serveur).
2. **E7 / E8** : confirmation de réception par code, notation et pourboire.
3. **E4 / E5 / E6** : dès les décisions sur le paiement et ClemenTino.
