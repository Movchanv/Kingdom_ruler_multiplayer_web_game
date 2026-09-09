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
- **Mise en ligne** : chiffrement HTTPS de la production via l'overlay `docker-compose.ssl.yml`
  (Nginx en TLS 1.2/1.3, redirection permanente depuis HTTP) et service `certbot` chargé de
  l'émission puis du renouvellement automatique des certificats Let's Encrypt toutes les 12 h.
- Modèle `.env.example` à la racine, regroupant les variables lues par Docker Compose
  (`DB_PASSWORD`, `REDIS_PASSWORD`, `DOMAIN`, `CERTBOT_EMAIL`).
- Procédure de déploiement détaillée (README, section 7) : préparation du serveur, mise en
  ligne en HTTP, bascule vers HTTPS et mise à jour du code.
- Sondes de disponibilité sur les services `laravel` (port PHP-FPM) et `vue`.
- Nom de domaine de production renseigné (`lesseigneuriesfr.fr`) dans `frontend/.env.production`
  et dans le modèle `backend/.env.production.example`.
- L'application s'intitule désormais « Les Seigneuries » dans l'interface (titre de l'onglet,
  page d'accueil, en-tête et pied de page), en cohérence avec le nom de domaine.

### Ajouté
- **Création d'événements depuis le panneau d'administration** : `GET /api/v1/admin/events` liste
  le catalogue et `POST /api/v1/admin/events` crée un événement (nom, description, type,
  difficulté, un ou deux effets). Jusqu'ici un administrateur ne pouvait que déclencher un
  événement existant, et seules les lois étaient créables. La cible de chaque effet est validée
  contre une liste fermée (ressources du jeu, loyauté, action offerte) et l'auteur est enregistré.

### Ajouté
- Nouveau type d'événement **« Manuel »**, réservé au déclenchement par un administrateur. Les
  deux chemins automatiques tirant explicitement leur type (`Adventure` pour les aventures,
  `World` pour l'ordonnanceur), un événement manuel n'est jamais sélectionné tout seul.
- **Déclenchement depuis le panneau d'administration** : le catalogue affiche un bouton par
  événement et un sélecteur de ville cible. `GET /api/v1/admin/games` expose désormais les villes
  vivantes de chaque saison active pour alimenter ce choix.

### Modifié
- Les événements mondiaux ne sont plus rangés dans un palier fixe. Un **score de pression**
  combine l'ancienneté de la saison (poids 0,35) et **le nombre d'actions jouées depuis le
  dernier événement** (poids 0,65) : l'activité des joueurs prime sur le calendrier.
  L'activité est rapportée à une fréquentation soutenue, déduite du quota d'actions journalier,
  du nombre de joueurs et de l'intervalle courant.
- Ce score alimente désormais des **probabilités de tirage** plutôt qu'un palier tranché
  (polynômes de Bernstein de degré 2, dont les trois poids somment toujours à 1) : la
  probabilité des événements faciles décroît continûment au profit des difficiles à mesure
  que la pression monte.
- Un **plancher de 5 %** est garanti à chaque palier : aucun n'est jamais totalement exclu.
  Une accalmie reste possible sous forte pression, et un coup dur peut survenir dès le
  premier jour.
- Les effets sont **multipliés par une intensité** croissante avec la pression, de 1,0 à 2,0 :
  un événement `food -20` retire 40 unités à pression maximale. Les aventures et les
  déclenchements manuels par un administrateur conservent l'intensité neutre.
- Le résultat d'un déclenchement expose `pressure`, `intensity`, `chances` et
  `actions_since_last_event`, pour rendre le calcul vérifiable.

### Modifié
- Les identifiants de base de données et de cache sont injectés par Docker Compose dans les
  conteneurs backend, où ils priment sur `backend/.env` : une seule valeur à maintenir pour
  `DB_PASSWORD` et `REDIS_PASSWORD`, au lieu de deux susceptibles de diverger.
- Les services `reverb`, `horizon` et `scheduler` attendent désormais que PostgreSQL et Redis
  soient *sains* avant de démarrer ; ils ne patientaient auparavant que jusqu'au simple
  lancement du conteneur `laravel` et pouvaient donc démarrer avant que le cache soit prêt.
- Vue de ville : les bandeaux « S'aventurer » et « Ferme » sont intégrés à l'illustration
  `town-paris.png`, à l'image des autres lieux déjà légendés sur la carte.

### Modifié
- Le seeder est découpé en trois : `ReferenceDataSeeder` (pays, titres, ressources, actions,
  bâtiments, lois, événements, quêtes), `SeasonSeeder` (partie active et villes, indispensables
  car `GameService::join()` exige une saison active et au moins une ville) et
  `DemoAccountsSeeder` (comptes de démonstration). `DatabaseSeeder` n'appelle ce dernier que
  dans les environnements `local` et `testing`.

### Corrigé
- **Pile de production impossible à démarrer** : `docker-compose.prod.yml` n'était pas un
  YAML valide (`did not find expected key`, ligne 39). Les entrées `- vue` et `- reverb`
  figuraient dans le bloc `healthcheck` du service `nginx` au lieu de son `depends_on`.
- Service `scheduler` rejeté par Docker Compose : valeur `restart: alwayss` (coquille).
- Image `vue` toujours impossible à construire (`node:22-alpin: not found`) : la correction
  annoncée en 1.0.1 (**#44**) n'avait jamais été appliquée au dépôt.
- **`APP_KEY` corrompue au premier démarrage** : `laravel`, `reverb`, `horizon` et `scheduler`
  partagent le même fichier `.env` et exécutaient `key:generate` simultanément, concaténant
  leurs clés respectives. L'application renvoyait alors HTTP 500 (`Unsupported cipher or
  incorrect key length`). La génération est désormais sérialisée par un verrou atomique, et
  la clé obtenue est validée (préfixe `base64:` et longueur de 32 octets) avant démarrage.
- Redis démarrait sans mot de passe en production alors que la configuration Laravel en
  exigeait un, provoquant `NOAUTH Authentication required` sur les sessions, le cache et
  les files d'attente. Le serveur est maintenant protégé par `--requirepass`.
- `REVERB_APP_KEY` divergeait entre le backend (`__CHANGE_ME__`) et le frontend
  (`medievalrealmkey`), ce qui empêchait toute connexion temps réel en production.
- Nginx ne servait pas `/.well-known/acme-challenge/`, rendant impossible la validation de
  domaine par Let's Encrypt.
- Base de données vide après un déploiement : `docker-compose.prod.yml` appliquait les migrations
  sans jamais peupler les données de référence. Aucun royaume n'apparaissait donc à l'inscription,
  et `join()` échouait faute de saison active. `php artisan db:seed --force` est désormais exécuté
  au démarrage du service `laravel` (opération idempotente, fondée sur `firstOrCreate`).

### Sécurité
- Les comptes de démonstration (`admin@`, `player@`, `researcher@`), dont un administrateur au mot
  de passe `password`, ne sont plus créés en production : ils étaient jusqu'ici inclus dans le
  seeder unique, exposant un accès administrateur trivial sur un site public.
- Documentation de mise en ligne : la commande d'émission du certificat omettait
  `--entrypoint certbot`. Le service `certbot` définissant un `entrypoint`, `docker compose run`
  ne remplaçait que la commande : les arguments `certonly` étaient ignorés et la boucle de
  renouvellement démarrait à la place de l'émission. Ajout également d'une étape `--dry-run`
  préalable, pour valider le circuit sans entamer le quota Let's Encrypt.

- Inscription impossible depuis `www.` : la configuration TLS servait l'application sur les deux
  noms d'hôte sans redirection. Une page ouverte sur `www.domaine` appelait l'API servie sur
  `domaine` — deux origines distinctes pour le navigateur — et CORS bloquait la requête, l'API
  n'autorisant que `FRONTEND_URL`. `www` redirige désormais en 301 vers l'origine canonique.

### Sécurité
- Trafic de production chiffré de bout en bout : TLS 1.2/1.3, en-tête HSTS d'un an,
  `X-Content-Type-Options` et `X-Frame-Options`, redirection permanente de HTTP vers HTTPS.
- Redis n'est plus accessible sans authentification ; `REDIS_PASSWORD` et `DB_PASSWORD` sont
  exigés au lancement de la pile de production, qui refuse de démarrer s'ils sont absents.
- `browserslist` relevé de 4.28.2 à 4.28.9 (avis GHSA-c83g-rgw3-j3cx et GHSA-73wf-gq98-2v4g :
  croissance mémoire non bornée et écriture de prototype). Dépendance transitive
  d'`autoprefixer` ; seul le fichier de verrouillage est modifié.

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