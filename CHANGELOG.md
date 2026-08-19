# Journal des versions

Toutes les évolutions notables du projet « Empire : Les Seigneuries » sont consignées ici.

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/) et le versionnage
respecte la convention [SemVer](https://semver.org/lang/fr/) (`MAJEUR.MINEUR.CORRECTIF`).
Chaque version publiée correspond à une étiquette Git (`tag`) et à une note de version (release).

---

## [Non publié]

### Ajouté
- Supervision : point de santé `/up` enrichi (base de données, cache, files d'attente) et
  endpoint d'exploitation `GET /api/v1/admin/monitoring` (indicateurs temps réel).
- Consignation des anomalies : formulaire « Signaler un problème » dans l'application
  (`POST /api/v1/support/reports`) et journalisation structurée des signalements.
- Gestion des dépendances : configuration Dependabot (Composer, npm, Docker, GitHub Actions)
  et audits de sécurité (`composer audit`, `npm audit`) intégrés à la chaîne CI.
- Modèle de fiche de consignation d'anomalie (GitHub Issues).

---

## [1.0.1] — Correctifs de maintenance

### Corrigé
- **#42** — HTTP 419 « CSRF token mismatch » à l'inscription : l'API était traitée comme
  *stateful* (middleware `statefulApi` et cookies de session) alors que l'authentification
  repose sur un jeton Bearer. Le middleware et les cookies ont été retirés : l'API est
  désormais totalement sans état. Test de non-régression ajouté (inscription ? HTTP 201).
- **#43** — Conteneur `laravel` en échec au démarrage (`exec entrypoint: no such file`) :
  fins de ligne CRLF introduites par Windows dans `entrypoint.sh`. Conversion en LF et
  ajout de `sed -i 's/\r$//'` dans le `Dockerfile` pour immuniser l'image.
- **#44** — Image `vue` impossible à construire (`node:22-alpin: not found`) : coquille dans
  le `Dockerfile` du frontend, corrigée en `node:22-alpine`.
- **#45** — Temps de réponse de 3 à 8 s en développement sous Windows : OPcache
  sous-dimensionné face à la lenteur du montage de fichiers. Réglage d'OPcache et du cache
  de chemins réels ; temps ramenés sous la seconde.

### Sécurité
- Blocage de la connexion des comptes suspendus et invalidation immédiate de leurs jetons.

---

## [1.0.0] — Version initiale complète

### Ajouté
- **Jeu** : carte du monde, carte du royaume et vue de ville illustrées ; production de
  ressources (5 actions par jour), construction collective des bâtiments, aventures
  (cartes d'événements), scrutins de lois, chat de royaume en temps réel, fin de saison
  et classement archivé.
- **Temps réel** : diffusion via Laravel Reverb sur les canaux privés `country.{id}` et
  `town.{id}` ; synchronisation instantanée entre les joueurs d'une même ville.
- **Automatisation** : conteneur `scheduler` exécutant les événements du monde, la clôture
  des scrutins, le cycle d'entretien et la purge du chat.
- **Administration** : création de lois, lancement de scrutins, déclenchement d'événements,
  suspension de comptes, clôture de saison.
- **Recherche** : API et écran d'agrégats pseudonymisés, exports CSV compatibles Excel.
- **RGPD** : consentement, export des données personnelles, droit à l'effacement.

---

## [0.7.0] — Ordonnanceur

### Ajouté
- Conteneur `scheduler` (`schedule:work`) et verrous `withoutOverlapping` sur les tâches.

## [0.6.0] — Interface de jeu

### Ajouté
- Écrans du jeu (cartes monde / royaume / ville), panneaux d'action, chat, écrans
  d'administration et de fin de saison.

## [0.5.0] — Cœur du jeu

### Ajouté
- Moteur d'actions (quota journalier Redis), construction, aventure, événements,
  lois et votes, entretien et fin de saison, chat, administration, API recherche.
  

## [0.4.0] — Interface d'authentification

### Ajouté
- Écrans d'accueil et d'authentification (Vue 3, Tailwind CSS).

### Corrigé
- **#41** — La chaîne d'intégration échouait à l'étape `npm ci` sur les agents Linux : le
  fichier `package-lock.json`, généré sous Windows, figeait des dépendances optionnelles
  propres à cette plateforme. Fichier de verrouillage régénéré de façon portable
  (branche `fix/ci-job-names`).
- **Fix-01** — Simplification des noms de travaux de la chaîne CI (`backend`, `frontend`)
  pour rendre les rapports d'exécution lisibles.

## [0.3.0] — Authentification et RGPD

### Ajouté
- Inscription, vérification de courriel, connexion (Sanctum), réinitialisation de mot de
  passe, export et effacement des données personnelles.

## [0.2.0] — Modèle de données

### Ajouté
- Schéma de 22 tables, modèles Eloquent, factories et jeu de données de démonstration.

## [0.1.0] — Socle technique

### Ajouté
- Socle Docker (9 services), Laravel 11, Vue 3, chaîne d'intégration continue GitHub Actions.