# Plan du backend MonColis Particulier

> Document de pilotage du backend (`moncolis-particulier-api`) : ce qui est **traité** et ce qui reste
> à faire, lot par lot. Mis à jour à chaque réalisation. Le détail de chaque réalisation se trouve dans
> `docs/rapports/`.
>
> Statuts : **Traité** · **En cours** · **À faire** · **Bloqué** (décision attendue)

---

## 1. Tâches traitées (25/09/2026)

| # | Tâche | Module | Statut | Rapport |
|---|---|---|---|---|
| T1 | Création du projet Laravel 12 (PHP 8.2, MySQL, Sanctum), dépôt séparé de l'app mobile | Socle | **Traité** | 01 |
| T2 | Architecture modulaire `app/Modules/<Module>` calquée sur les modules de l'app, chargement automatique des routes et migrations de chaque module | Socle | **Traité** | 01 |
| T3 | Briques communes : erreurs JSON `{message, code}`, réponses JSON forcées, contrôle de l'en-tête `client-app-code`, normalisation des numéros (+237…), passerelle SMS (journal en attendant un fournisseur) | Socle | **Traité** | 01 |
| T4 | Connexion compatible avec l'app existante (`POST /api/login_check`, réponse `{token, data}`) par nom d'utilisateur, e-mail ou numéro ; inscription ; déconnexion ; limitation des tentatives | Authentification | **Traité** | 01 |
| T5 | Profil : lecture et modification, taux de complétion, photo de profil | Profil | **Traité** | 01 |
| T6 | Changement de numéro vérifié par code SMS (6 chiffres, 5 min, 5 essais, délai de renvoi, code stocké chiffré) | Profil | **Traité** | 01 |
| T7 | Mot de passe (changement + déconnexion des autres appareils), préférences (langue, thème, notifications) | Profil | **Traité** | 01 |
| T8 | Adresses enregistrées Maison / Travail / favoris (coordonnées GPS, précisions, consignes coursier) | Profil | **Traité** | 01 |
| T9 | Suppression du compte avec anonymisation (exigence Play Store / App Store) | Profil | **Traité** | 01 |
| T10 | Installation de l'environnement (PHP XAMPP, Composer, MySQL), base `moncolis_particulier` + compte démo, 2 anomalies corrigées, 46 tests au vert, vérification en conditions réelles | Socle | **Traité** | 01 |
| T11 | Mise en ligne du code sur GitHub (`theoleprince/mon-colis-particulier-api`) | Socle | **Traité** | — |
| T12 | Documentation interactive Swagger générée depuis le code (`/docs/swagger`, `/docs/api`, `/docs/api.json`), en français, accès protégé hors développement | Socle | **Traité** | 02 |
| T13 | Rapport des travaux après chaque réalisation (`docs/rapports/`), liste de contrôle de fin de tâche (`CLAUDE.md`) | Socle | **Traité** | 02 |
| T14 | Contrat d'API vérifié depuis l'app mobile contre le vrai backend (tests de contrat Flutter) | Profil | **Traité** | 03 |
| T15 | Connexion et inscription par code SMS façon Yango (compte créé au premier code, sans mot de passe, sans révéler l'existence d'un compte) | Authentification | **Traité** | 05 |
| T16 | Mot de passe oublié par code SMS ou e-mail, connexion directe et fermeture des autres sessions | Authentification | **Traité** | 05 |
| T17 | Vérification de l'adresse e-mail par code ; codes à usage unique par SMS **et** par e-mail | Profil | **Traité** | 05 |
| T18 | Mot de passe facultatif (premier mot de passe, suppression de compte sans mot de passe), 64 tests au vert | Authentification / Profil | **Traité** | 05 |
| T19 | Plan du backend et de la migration Expédier (ce document) | Pilotage | **Traité** | 07 |
| T20 | Expédier E1 — référentiels en base : 7 natures de colis, 3 véhicules avec leur grille tarifaire, 5 options payantes | Expédier | **Traité** | 07 |
| T21 | Expédier E2 — estimation du prix par véhicule sur la distance routière réelle (OSRM, repli à vol d'oiseau), devis garanti 15 min | Expédier | **Traité** | 07 |
| T22 | Expédier E3 — création d'une livraison à partir d'un devis (référence officielle, colis multiples, consignes coursier), photos / vidéos, historique paginé, détail avec chronologie, annulation ; 83 tests au vert | Expédier | **Traité** | 07 |

Bilan au 25/09/2026 : **33 routes d'API** en service (24 compte / profil + 9 Expédier), **83 tests automatisés** au vert, documentation Swagger à jour.

---

## 2. Migration du module Expédier

### 2.1 Point de départ

Aujourd'hui, le parcours Expédier de l'app (10 écrans façon Yango) repose sur l'**ancienne API**
(`moncolis-api.kutiwa.com`), et une grande partie reste de la démonstration côté app :

| Élément | Situation actuelle |
|---|---|
| Natures de colis | Ancienne API (`/configurations/nature-colis`) |
| Création de la livraison | Ancienne API (`/expeditions/create`), jeton de l'ancienne API récupéré au mieux |
| Prix | 3 tarifs **fixes** dans l'app (pas de calcul selon la distance, le poids ou les options) |
| Paiement | Choix affiché seulement, **aucun prélèvement** |
| Recherche du coursier | **Mise en scène** (délai fixe), coursier tiré d'un petit répertoire de démonstration |
| Suivi | Position du livreur en direct via Firebase (réel) ; statut de la livraison non suivi |
| Confirmation de réception | Code et signature **non vérifiés** côté serveur |
| Historique « Mes livraisons » | Local au téléphone, pas rechargé depuis un serveur |
| Notation / pourboire | Non enregistrés |

Objectif : un module **`Delivery`** complet dans le nouveau backend, qui remplace l'ancienne API et
les données de démonstration. Le prix doit être **calculé et garanti par le serveur**, jamais fourni par l'app.

### 2.2 Cycle de vie d'une livraison (cible)

```
BROUILLON ─▶ EN_ATTENTE_PAIEMENT ─▶ RECHERCHE_COURSIER ─▶ COURSIER_ASSIGNE ─▶ RAMASSAGE_EN_COURS
                                                                                     │
      LIVRE_CONFIRME ◀─ LIVRE ◀─ EN_LIVRAISON ◀─ COLIS_RECUPERE ◀────────────────────┘
      (+ ANNULEE possible jusqu'au ramassage, avec règles de frais)
```

- Chaque changement de statut est historisé (date, auteur, position) et notifié : push FCM au client,
  SMS au destinataire aux étapes clés.
- En paiement à la livraison, l'étape `EN_ATTENTE_PAIEMENT` est sautée.

### 2.3 Lots

| Lot | Contenu | Statut |
|---|---|---|
| **E1 — Référentiels** | Natures de colis, véhicules (moto, tricycle, camion : capacité, poids max), options (fragile, express, assurance, accusé de réception, emballage), grille tarifaire en base (modifiable sans redéployer). APIs de lecture pour l'app | **Traité** |
| **E2 — Estimation du prix** | `GET /tarifs/estimer` : distance réelle (itinéraire routier OSRM, repli à vol d'oiseau × coefficient), un devis par véhicule (prix, distance, temps estimé). Devis enregistré et valable 15 min, pour que le prix annoncé soit celui facturé | **Traité** |
| **E3 — Création et consultation** | Création d'une livraison à partir d'un devis (adresses, adresse enregistrée, consignes coursier, expéditeur, destinataire, colis multiples avec options), référence officielle `MC-AAMMJJ-XXXX`, photos et vidéos du colis, détail, historique « Mes livraisons » paginé, annulation | **Traité** |
| **E4 — Paiement** | Mobile money (MTN MoMo, Orange Money) via un agrégateur, paiement à la livraison, statut de paiement, notification de paiement (webhook), remboursement en cas d'annulation | **Bloqué** — choix de l'agrégateur |
| **E5 — Coursiers et attribution** | Comptes coursiers, disponibilité, position, attribution au plus proche du véhicule demandé, acceptation / refus, délai de réponse et réattribution, APIs pour l'app coursier (ClemenTino) | **Bloqué** — mode d'intégration ClemenTino |
| **E6 — Suivi en direct** | Statut + chronologie + coursier + temps estimé ; position du coursier (Firebase conservé pour le temps réel, dernière position enregistrée côté serveur) ; notifications push à chaque étape | À faire |
| **E7 — Confirmation de réception** | Code à 4 chiffres généré par le serveur et envoyé par SMS au destinataire, vérifié à la remise ; signature enregistrée ; liste « Colis à confirmer » pour le destinataire | À faire |
| **E8 — Après livraison** | Notation du coursier, pourboire, reçu (PDF) ; notice de ramassage avec la référence officielle et le QR code | À faire |
| **E9 — Branchement de l'app** | L'app appelle le nouveau backend pour tout le parcours (fin de l'ancienne API et du jeton `legacy_token`), prix réels, statuts réels, historique serveur, file hors ligne adaptée | À faire |
| **E10 — Qualité** | Tests automatisés par lot, documentation Swagger, rapport de travaux par lot, mise à jour du manuel utilisateur Expédier | En continu |

### 2.4 APIs prévues (contrat)

Les chemins reprennent ceux déjà proposés à l'app (`docs/DEMANDES-API-authentification-et-expedier-2026-09-09.md`).

| Lot | Méthode | Route | Rôle |
|---|---|---|---|
| E1 | GET | `/api/configurations/nature-colis` | Natures de colis (chemin identique à l'ancienne API) |
| E1 | GET | `/api/configurations/expedition` | Véhicules, options et leurs tarifs affichables |
| E2 | GET | `/api/tarifs/estimer` | Devis par véhicule pour un trajet et un colis |
| E3 | POST | `/api/livraisons` | Créer une livraison à partir d'un devis |
| E3 | POST | `/api/livraisons/{reference}/medias` | Photos / vidéos du colis |
| E3 | GET | `/api/livraisons/mes-livraisons` | Historique paginé |
| E3 | GET | `/api/livraisons/{reference}` | Détail + chronologie |
| E3 | POST | `/api/livraisons/{reference}/annuler` | Annulation |
| E4 | POST | `/api/payments/initiate` · GET `/api/payments/{reference}/status` | Paiement mobile money |
| E6 | GET | `/api/livraisons/{reference}/suivi` | Statut, coursier, position, temps estimé |
| E7 | GET | `/api/livraisons/a-confirmer` · POST `/api/livraisons/{reference}/confirmer-reception` | Réception |
| E8 | POST | `/api/livraisons/{reference}/evaluation` | Note + pourboire |

### 2.5 Décisions attendues

| # | Question | Impact | Proposition par défaut |
|---|---|---|---|
| D1 | Grille tarifaire réelle (prise en charge, prix au km, supplément par kg, prix des options) par véhicule | E2 | **Grille de départ en place, à valider** (voir 2.7), modifiable en base sans redéployer |
| D2 | Agrégateur de paiement mobile money (CinetPay, Notch Pay, Campay, Monetbil…) | E4 | Prévoir un connecteur interchangeable, démarrer avec un mode « bac à sable » |
| D3 | Intégration de ClemenTino (app des coursiers) : appelle-t-elle ce backend, ou reste-t-elle sur son propre système / Firebase ? | E5, E6 | APIs coursier dans ce backend + Firebase conservé pour la position en direct |
| D4 | Fournisseur SMS (codes, notifications destinataire) | E7 + authentification | Connecteur interchangeable, journal en attendant |
| D5 | Zones desservies (Yaoundé, Douala…) et livraison interurbaine | E2, E3 | Zone urbaine uniquement au départ, limite de distance configurable |

### 2.6 Ordre de réalisation

1. **E1 + E2 + E3** : un prix réel et une livraison réellement enregistrée dans le nouveau backend,
   sans dépendre des décisions en attente.
2. **E9 (partiel)** : l'app utilise ces APIs (fin des prix fixes et de l'ancienne API pour la création).
3. **E7 + E8** : confirmation de réception et après livraison.
4. **E4, E5, E6** : dès que les décisions D2 et D3 sont prises.

### 2.7 Grille tarifaire de départ (à valider — décision D1)

| Véhicule | Prise en charge | Prix / km | Poids inclus | Supplément / kg | Minimum | Poids max |
|---|---|---|---|---|---|---|
| Moto | 500 F | 150 F | 5 kg | 50 F | 1 000 F | 30 kg |
| Tricycle | 800 F | 200 F | 20 kg | 20 F | 1 500 F | 300 kg |
| Camion | 3 000 F | 400 F | 500 kg | 5 F | 5 000 F | 3 000 kg |

| Option | Prix |
|---|---|
| Livraison express | + 30 % de la course |
| Colis fragile | 300 F |
| Assurance | 2 % de la valeur déclarée (200 F minimum) |
| Accusé de réception | 200 F |
| Emballage protecteur | 500 F |

Total arrondi aux 50 F supérieurs. Exemple réel (itinéraire routier Bastos → Essos, 3,9 km, 2 kg,
express) : moto 1 450 F · tricycle 2 100 F · camion 6 500 F.
