# Rapport des travaux — Documentation interactive de l'API (Swagger)

| | |
|---|---|
| **Date** | 25/09/2026 |
| **Projet** | MonColis Particulier — backend (API) |
| **Module** | Socle technique — documentation API |
| **Statut** | Terminé |

## 1. Contexte

Les développeurs de l'app mobile (et tout futur intégrateur) ont besoin d'une documentation fiable de
l'API, à jour à chaque évolution, et permettant de **tester les appels directement depuis le navigateur**.

## 2. Travaux réalisés

- Mise en place d'une documentation **OpenAPI 3.1 générée automatiquement à partir du code**
  (outil Scramble) : les champs attendus, les règles de validation et les formats de réponse sont lus
  directement dans le code. La documentation ne peut donc pas diverger de ce que fait réellement l'API.
- Trois points d'accès :

  | Adresse | Contenu |
  |---|---|
  | `/docs/swagger` | **Swagger UI** : liste des APIs, bouton « Try it out » pour tester un appel |
  | `/docs/api` | Même documentation, présentation alternative plus lisible |
  | `/docs/api.json` | Document OpenAPI brut (import dans Postman, génération de code client) |

- Documentation rédigée **en français**, classée en 5 rubriques : Authentification, Profil,
  Sécurité du compte, Préférences, Mes adresses. Chaque API a un titre, une description et la liste
  de ses codes d'erreur métier (`INVALID_CREDENTIALS`, `OTP_EXPIRED`, `SAVED_PLACE_TYPE_TAKEN`...).
- Sécurité documentée : jeton `Bearer` (bouton « Authorize » de Swagger) et en-tête `client-app-code` ;
  les APIs publiques (connexion, inscription) sont signalées comme telles.
- Codes de réponse exacts (201 à la création, 202 à l'envoi d'un code SMS, 204 sans contenu...).
- **Accès protégé** : documentation ouverte en développement ; sur un serveur, elle reste fermée
  sauf activation explicite (`API_DOCS_PUBLIC=true`, par exemple sur un serveur de recette).
- Document versionné dans le dépôt (`docs/openapi.json`), régénéré à chaque réalisation.

## 3. Tests et vérifications

- 2 tests automatisés ajoutés (accès fermé par défaut, ouverture par configuration + contenu de la
  documentation) → **48 tests au vert** au total.
- Analyse de la documentation : **19 APIs documentées, 0 avertissement**.
- Vérification sur le serveur local : les 3 pages répondent correctement.

## 4. Utilisation

```text
php artisan serve
→ http://localhost:8000/docs/swagger
1. POST /login_check (Try it out) avec demo / password → copier le token
2. Bouton « Authorize » → coller le token dans bearerAuth
3. Tester les autres APIs (GET /profile, /profile/places...)
```

## 5. Prochaines étapes

Chaque nouveau module (Expédier, paiements...) apparaîtra automatiquement dans la documentation,
complété par ses descriptions en français.
