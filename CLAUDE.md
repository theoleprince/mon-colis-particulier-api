# MonColis Particulier — backend Laravel

Backend de l'app mobile Flutter `moncolis-particulier-go` (Bitbucket). Objectif produit : expérience **type Yango**.
Architecture et conventions : voir `README.md`.

## Environnement local (Windows)

PHP et MySQL viennent de XAMPP et ne sont pas dans le PATH :

```bash
export PATH="/c/xampp/php:/c/xampp/mysql/bin:/c/Users/ADVIT10/bin:$PATH"
php artisan test        # SQLite en mémoire
php artisan serve       # Apache XAMPP inutilisable (port 80 pris par Windows) et inutile
```

## À faire à la fin de CHAQUE réalisation

1. `php artisan test` au vert.
2. Documentation API à jour : PHPDoc en français sur chaque méthode de contrôleur (1re ligne = titre,
   puis description et codes d'erreur métier), `#[Group(...)]` sur chaque contrôleur, `@unauthenticated`
   sur les routes publiques. Vérifier `php artisan scramble:analyze` (0 avertissement), puis régénérer
   le document versionné : `php artisan scramble:export --path=docs/openapi.json`.
3. **Rapport des travaux** dans `docs/rapports/AAAA-MM-JJ-NN-sujet.md` (NN = numéro d'ordre du jour),
   en français, lisible par un non-développeur, sur le modèle des rapports existants :
   contexte, travaux réalisés, APIs livrées, tests et vérifications, limites connues, prochaines étapes.
   Ajouter la ligne correspondante dans `docs/rapports/README.md`.
4. Ligne dans `../moncolis-particulier-go/docs/TACHES_REALISEES.md` (suivi de facturation, tableau
   append-only, format défini dans le CLAUDE.md du projet Flutter).
5. Commit, puis push sur `origin` (GitHub `theoleprince/mon-colis-particulier-api`) quand l'utilisateur le demande.
