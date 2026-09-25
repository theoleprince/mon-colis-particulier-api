# API — Authentification & Profil utilisateur

Contrat pour l'app Flutter. Base : `/api`. Headers : `Authorization: Bearer {token}`, `client-app-code: {code}`.
JSON en camelCase. Erreurs métier : `{ "message": "...", "code": "..." }`.

## Résumé

| # | Endpoint | Méthode | Auth |
|---|---|---|---|
| A1 | `/login_check` | POST | — |
| A2 | `/register` | POST | — |
| A3 | `/logout` | POST | oui |
| P1 | `/profile` | GET | oui |
| P2 | `/profile` | PATCH | oui |
| P3 | `/profile/avatar` | POST (multipart) | oui |
| P4 | `/profile/avatar` | DELETE | oui |
| P5 | `/profile/password` | PUT | oui |
| P6 | `/profile/phone/otp` | POST | oui |
| P7 | `/profile/phone/verify` | POST | oui |
| P8 | `/profile/preferences` | GET / PATCH | oui |
| P9 | `/profile/places` | GET / POST | oui |
| P10 | `/profile/places/{id}` | GET / PATCH / DELETE | oui |
| P11 | `/profile` | DELETE | oui |

---

## A1. Connexion — `POST /login_check`

`username` accepte un nom d'utilisateur, un e-mail **ou un numéro** (`677897012`, `+237 677 89 70 12`...).

```json
{ "username": "677897012", "password": "secret123", "deviceName": "Pixel 7" }
```
Réponse 200 :
```json
{
  "token": "1|Xy...",
  "tokenType": "Bearer",
  "expiresAt": "2026-10-25T10:00:00+00:00",
  "data": { "...": "profil, voir P1" }
}
```
Erreurs : `401 INVALID_CREDENTIALS`, `403 ACCOUNT_SUSPENDED`, `429` (5 essais / min).

## A2. Inscription — `POST /register`

```json
{ "firstName": "Serge", "lastName": "Fotso", "username": "serge",
  "phone": "699451230", "email": "serge@example.com", "password": "secret123" }
```
`phone` **ou** `email` obligatoire ; `username`, `firstName`, `lastName` facultatifs. Réponse 201 : même forme que A1.

## A3. Déconnexion — `POST /logout` → 204 (révoque le jeton courant)

---

## P1. Mon profil — `GET /profile`

```json
{
  "data": {
    "id": 1,
    "username": "demo",
    "firstName": "Awa",
    "lastName": "Ngo Bikoe",
    "fullName": "Awa Ngo Bikoe",
    "displayName": "Awa",
    "phone": "+237677897012",
    "phoneVerified": true,
    "email": "demo@moncolis.test",
    "emailVerified": true,
    "avatarUrl": "http://.../storage/avatars/1/uuid.jpg",
    "gender": "female",
    "birthDate": "1995-04-12",
    "countryCode": "CM",
    "profile": "PARTICULIER",
    "memberSince": "2026-09-25T08:00:00+00:00",
    "completion": { "percent": 80, "missing": ["avatar"] },
    "preferences": { "language": "fr", "theme": "orange", "notifyPush": true,
                     "notifySms": true, "notifyEmail": false, "marketingOptIn": false }
  }
}
```
`completion.missing` ∈ `firstName, lastName, phoneVerified, email, avatar` → bandeau « Complétez votre profil ».

## P2. Modifier — `PATCH /profile`

Champs facultatifs (seuls ceux envoyés sont modifiés) : `firstName`, `lastName`, `username`, `email`,
`gender` (`male|female|other`), `birthDate` (`AAAA-MM-JJ`), `countryCode` (`CM`, `TG`...).
Changer l'e-mail remet `emailVerified` à `false`. `phone` est **refusé** ici → passer par P6/P7. Réponse : profil (P1).

## P3 / P4. Photo — `POST /profile/avatar` (multipart, champ `avatar`, jpg/png/webp, ≤ 5 Mo, ≥ 100×100) / `DELETE /profile/avatar`

Réponse : profil (P1) avec le nouvel `avatarUrl`.

## P5. Mot de passe — `PUT /profile/password`

```json
{ "currentPassword": "old", "newPassword": "new12345", "newPasswordConfirmation": "new12345",
  "logoutOtherDevices": true }
```
204. Par défaut les autres appareils sont déconnectés (le jeton courant reste valide).

## P6 / P7. Changer de numéro (code SMS)

1. `POST /profile/phone/otp` `{ "phone": "699451230" }` → 202
   ```json
   { "data": { "destination": "+237 •••• ••30", "expiresIn": 300, "resendIn": 60 } }
   ```
   (`devCode` ajouté en environnement local uniquement.)
2. `POST /profile/phone/verify` `{ "phone": "699451230", "code": "123456" }` → profil (P1), `phoneVerified: true`.

Erreurs : `429 OTP_COOLDOWN` (renvoi < 60 s), `422 OTP_INVALID`, `422 OTP_EXPIRED`,
`429 OTP_TOO_MANY_ATTEMPTS` (5 essais), `422 SAME_PHONE`, `422` validation si numéro déjà utilisé.

## P8. Préférences — `GET|PATCH /profile/preferences`

```json
{ "language": "en", "theme": "green", "notifyPush": true, "notifySms": false,
  "notifyEmail": false, "marketingOptIn": false }
```
`language` ∈ `fr, en`. PATCH partiel.

## P9 / P10. Mes adresses (Maison, Travail, favoris)

`POST /profile/places`
```json
{ "type": "home", "label": "Maison", "address": "Bastos, Yaoundé",
  "addressDetails": "Immeuble bleu, 2e étage", "instructions": "Sonner au portail",
  "latitude": 3.878, "longitude": 11.517 }
```
- `type` ∈ `home, work, other`. `label` facultatif (défaut « Maison » / « Travail » / « Adresse favorite »).
- Un seul `home` et un seul `work` : les renvoyer **remplace** l'existant (200 au lieu de 201).
- Transformer un favori en `home` alors qu'il en existe déjà un → `409 SAVED_PLACE_TYPE_TAKEN`.
- Max 20 adresses → `422 SAVED_PLACES_LIMIT`.
- `GET /profile/places` : Maison, puis Travail, puis favoris (plus récemment utilisés d'abord).
- L'adresse d'un autre utilisateur répond `404`.

## P11. Supprimer mon compte — `DELETE /profile`

```json
{ "password": "secret123", "reason": "facultatif" }
```
204. Données personnelles effacées, jetons révoqués, numéro/e-mail réutilisables ; l'historique
des livraisons est conservé de façon anonyme (exigence Play Store / App Store).
