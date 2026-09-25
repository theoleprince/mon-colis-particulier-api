# Rapport des travaux — Création du backend et du module Profil utilisateur

| | |
|---|---|
| **Date** | 25/09/2026 |
| **Projet** | MonColis Particulier — backend (API) |
| **Modules** | Socle technique, Authentification, Profil utilisateur |
| **Statut** | Terminé — code en ligne sur GitHub (`theoleprince/mon-colis-particulier-api`) |

## 1. Contexte

L'application mobile MonColis Particulier s'appuyait sur une API existante (`moncolis-api.kutiwa.com`)
qui ne proposait que la connexion. Aucune gestion de profil n'existait : l'app affichait un nom fictif,
ne permettait ni de modifier ses informations ni d'enregistrer ses adresses.

Objectif : créer un **backend dédié**, organisé pour accueillir tous les modules de l'application
(Expédier, Course, Voyager, Commander, Portefeuille...), et livrer en premier la **gestion du profil
utilisateur**, avec une expérience inspirée de **Yango** (identité basée sur le numéro de téléphone,
vérification par SMS, adresses Maison / Travail).

## 2. Travaux réalisés

### 2.1 Socle technique

- Projet **Laravel 12** (PHP), base de données **MySQL**, authentification par jeton (Laravel Sanctum).
- **Architecture modulaire** : un module backend par module de l'app mobile. Chaque module regroupe
  ses routes, sa logique et ses tables ; ajouter un module (ex. Expédier) ne touche pas aux autres.
- Briques communes à tous les modules :
  - format de réponse unique (JSON, messages d'erreur en français avec un **code stable** exploitable par l'app) ;
  - normalisation des numéros de téléphone (`677 89 70 12`, `00237...`, `+237...` → même compte) ;
  - contrôle de l'en-tête `client-app-code` déjà envoyé par l'app mobile ;
  - passerelle d'envoi de SMS (en attente du choix d'un fournisseur).
- **Compatibilité** avec l'app existante : l'adresse de connexion (`/api/login_check`) et le format de
  réponse sont identiques à l'ancienne API → l'app n'aura qu'à changer d'adresse de serveur.

### 2.2 Authentification

- Connexion avec **nom d'utilisateur, e-mail ou numéro de téléphone**.
- Inscription (numéro ou e-mail), déconnexion.
- Protection contre les tentatives répétées (5 essais par minute), compte suspendu refusé.

### 2.3 Profil utilisateur (façon Yango)

| Fonctionnalité | Détail |
|---|---|
| Mon profil | Nom, prénom, e-mail, téléphone, genre, date de naissance, pays, date d'inscription |
| Complétion du profil | Pourcentage + éléments manquants (pour un bandeau « Complétez votre profil — 80 % ») |
| Photo de profil | Envoi (jpg/png/webp, 5 Mo max) et suppression |
| Changement de numéro | Sécurisé par **code SMS à 6 chiffres** : validité 5 min, renvoi après 60 s, 5 essais max, code stocké chiffré |
| Mot de passe | Changement avec déconnexion des autres appareils |
| Préférences | Langue (fr/en), thème de couleur, notifications push / SMS / e-mail, offres promotionnelles |
| Mes adresses | Maison, Travail (une seule de chaque) et adresses favorites, avec coordonnées GPS, précisions (étage, porte) et consignes pour le coursier — réutilisables plus tard comme points de départ / d'arrivée |
| Suppression du compte | Données personnelles effacées, numéro et e-mail libérés, historique conservé anonymement (exigence Play Store / App Store) |

## 3. APIs livrées (19)

| Fonction | Route |
|---|---|
| Connexion | `POST /api/login_check` |
| Inscription / Déconnexion | `POST /api/register` · `POST /api/logout` |
| Profil | `GET` · `PATCH` · `DELETE /api/profile` |
| Photo | `POST` · `DELETE /api/profile/avatar` |
| Mot de passe | `PUT /api/profile/password` |
| Changement de numéro | `POST /api/profile/phone/otp` puis `POST /api/profile/phone/verify` |
| Préférences | `GET` · `PATCH /api/profile/preferences` |
| Adresses | `GET` · `POST /api/profile/places` · `GET` · `PATCH` · `DELETE /api/profile/places/{id}` |

Contrat détaillé : `docs/API-PROFIL.md` et documentation interactive (voir rapport n° 02).

## 4. Tests et vérifications

- **46 tests automatisés** au vert (connexion, inscription, profil, photo, mot de passe, code SMS,
  préférences, adresses, suppression de compte, sécurité d'accès).
- 2 anomalies détectées par les tests et corrigées avant livraison (profil inaccessible juste après la
  création d'un compte ; code de réponse erroné sur les préférences).
- Vérification sur la vraie base MySQL : connexion avec un numéro local puis lecture des adresses enregistrées.
- Compte de démonstration : `demo` / `password`.

## 5. Limites connues

- Les **SMS ne sont pas encore envoyés** : les codes sont écrits dans le journal du serveur en attendant
  le choix d'un fournisseur (Orange, MTN, Twilio...).
- Pas encore de « mot de passe oublié », de vérification de l'e-mail, ni de connexion par code SMS sans mot de passe.
- L'app mobile n'est pas encore branchée sur ce backend.

## 6. Prochaines étapes proposées

1. Brancher les écrans profil de l'app Flutter (Mon compte, édition, Mes adresses, changement de numéro).
2. Compléter l'authentification (mot de passe oublié, connexion par code SMS).
3. Module Expédier côté backend (estimation du tarif, création de livraison, suivi, confirmation de réception).
