# Rapport des travaux — Connexion par SMS, mot de passe oublié et vérification d'e-mail (backend)

| | |
|---|---|
| **Date** | 25/09/2026 |
| **Projet** | MonColis Particulier — backend (API) |
| **Modules** | Authentification, Profil utilisateur |
| **Statut** | Terminé |

## 1. Contexte

Jusqu'ici, on se connectait avec un identifiant et un mot de passe, comme sur un site web. Sur
Yango, on donne son numéro de téléphone, on reçoit un code par SMS et on est connecté, sans
mot de passe à retenir. Un compte est créé automatiquement la première fois. Il manquait aussi
le « mot de passe oublié » et la vérification de l'adresse e-mail.

## 2. Travaux réalisés

### 2.1 Connexion et inscription par code SMS
- Deux APIs : « recevoir un code » puis « se connecter avec le code ».
- Si le numéro n'a pas encore de compte, il est créé au moment où le code est validé, avec un
  numéro vérifié d'office. L'app demande ensuite le prénom et le nom.
- Avant la saisie du code, le serveur ne révèle jamais si un numéro possède déjà un compte : la
  réponse est la même dans les deux cas.
- Les comptes créés ainsi n'ont **pas de mot de passe**. L'utilisateur peut en créer un plus tard,
  s'il le souhaite, depuis « Sécurité ». S'il tente la connexion par mot de passe, un message clair
  l'oriente vers le code SMS.
- Suppression de compte possible sans mot de passe, par une confirmation explicite.

### 2.2 Mot de passe oublié
- L'utilisateur saisit **son numéro (code par SMS) ou son e-mail (code par e-mail)**.
- Il saisit ensuite le code et son nouveau mot de passe : il est **connecté directement**, et
  toutes ses autres sessions sont fermées (utile en cas de vol de téléphone ou de mot de passe).
- Le numéro ou l'e-mail utilisé est marqué comme vérifié, puisque le code y a bien été reçu.
- Réponse identique que le compte existe ou non, ce qui ne donne aucune information à un tiers.

### 2.3 Vérification de l'adresse e-mail
- Code envoyé par e-mail (message aux couleurs MonColis), puis validation : le badge « Non vérifié »
  du profil a enfin son parcours.

### 2.4 Socle
- Le service de codes à usage unique gère désormais **deux canaux, SMS et e-mail**.
- Les destinations sont masquées dans les réponses (`+237 •••• ••30`, `aw•••@gmail.com`).
- Protection contre les abus : délai minimum entre deux envois, 5 essais par code, limitation par
  adresse IP.
- 6 nouvelles APIs documentées dans Swagger, en français, avec deux nouvelles rubriques
  (« Connexion par SMS », « Mot de passe oublié »), et contrat mis à jour dans `docs/API-PROFIL.md`.

## 3. APIs livrées

| Fonction | Route |
|---|---|
| Recevoir un code de connexion | `POST /api/auth/otp/request` |
| Se connecter / s'inscrire avec le code | `POST /api/auth/otp/verify` |
| Mot de passe oublié : recevoir un code | `POST /api/password/forgot` |
| Mot de passe oublié : nouveau mot de passe | `POST /api/password/reset` |
| Vérifier mon e-mail (envoi, validation) | `POST /api/profile/email/otp` · `POST /api/profile/email/verify` |

## 4. Tests et vérifications

- **64 tests automatisés au vert** (18 nouveaux) : inscription par SMS, connexion d'un compte
  existant, non-divulgation, mauvais code, compte suspendu, premier mot de passe, suppression sans
  mot de passe, réinitialisation par SMS et par e-mail, identifiant inconnu, vérification d'e-mail.
- Joué aussi depuis l'app mobile contre ce backend (voir rapport n° 06).
- Base MySQL mise à jour (mot de passe devenu facultatif).

## 5. Limites

- SMS et e-mails ne partent pas encore réellement : les SMS sont écrits dans le journal du serveur,
  les e-mails aussi en configuration actuelle. Il reste à choisir un fournisseur SMS et à configurer
  un serveur d'envoi d'e-mails.
