# Medieval Realm

Jeu de gestion multijoueur en temps réel (projet de fin d'études — Bloc 2, RNCP 39583).

**Pile technique :** Laravel 11 (PHP 8.3) · Vue 3 + Vite · PostgreSQL 16 · Redis 7 · Laravel Reverb (WebSockets) · Laravel Horizon · Docker Compose · GitHub Actions (CI).

Tout s'exécute dans **Docker** : aucune installation locale de PHP, Node ou PostgreSQL n'est requise.

---

## 1. Prérequis (nouveau PC)

| Outil | Rôle | Vérification |
|---|---|---|
| **Docker Desktop** (avec le backend **WSL2** sous Windows) | Exécute les 9 conteneurs | `docker --version` et `docker compose version` |
| **Git** | Récupérer le code | `git --version` |

> Sous Windows, ouvrir Docker Desktop **avant** toute commande et attendre qu'il soit « Running ». WSL2 est fortement recommandé pour les performances.

Aucun autre logiciel n'est nécessaire (PHP, Composer, Node, npm, PostgreSQL sont tous fournis par les images Docker).

---

## 2. Installation depuis zéro

```bash
# 1. Récupérer le code
git clone https://github.com/Movchanv/Kingdom_ruler_multiplayer_web_game.git
cd Kingdom_ruler_multiplayer_web_game

# 2. Construire et démarrer les 9 services (première fois : ~5-10 min de build)
docker compose up -d --build

# 3. Créer la base et charger les données de démonstration
docker compose exec laravel php artisan migrate:fresh --seed
```

C'est tout. Les fichiers `.env` sont **créés automatiquement** au démarrage des conteneurs (copie de `.env.example`), et la clé d'application Laravel (`APP_KEY`) est générée automatiquement — rien à configurer à la main pour l'environnement de développement.

> **Note :** `docker compose up` exécute déjà `migrate --force`, mais **sans** les données de démonstration. L'étape 3 (`migrate:fresh --seed`) recrée une base propre **avec** les comptes et le royaume de démo. À la première installation, lancez-la une fois.

---

## 3. Accès à l'application

Une fois les conteneurs démarrés :

| Service | URL |
|---|---|
| **Application (jeu)** | http://localhost:5173 |
| API (Nginx → Laravel) | http://localhost:8000/api/v1 |
| Santé de l'API | http://localhost:8000/up |
| WebSockets (Reverb) | ws://localhost:8080 |
| E-mails de développement (Mailpit) | http://localhost:8025 |

### Comptes de démonstration

| Rôle | Identifiant | Mot de passe |
|---|---|---|
| **Joueur** (déjà en partie, à Paris) | `player@medieval-realm.test` | `password` |
| **Administrateur** | `admin@medieval-realm.test` | `password` |
| **Chercheur** | `researcher@medieval-realm.test` | `password` |

> Les e-mails (vérification de compte, réinitialisation de mot de passe) ne partent pas vers Internet : ils sont capturés par **Mailpit** → http://localhost:8025.

---

## 4. Les 9 services Docker

| Service | Rôle | Port |
|---|---|---|
| `nginx` | Serveur web / reverse proxy vers PHP-FPM | 8000 |
| `laravel` | API Laravel (PHP-FPM) ; applique les migrations au démarrage | — |
| `vue` | Frontend Vite (serveur de dev) | 5173 |
| `postgres` | Base de données PostgreSQL 16 | 5432 |
| `redis` | Cache, files d'attente, compteurs de jeu, verrous | 6379 |
| `reverb` | Serveur WebSockets (temps réel) | 8080 |
| `horizon` | Traitement des files d'attente (diffusion des événements) | — |
| `scheduler` | Tâches planifiées (votes, événements, entretien, purge) | — |
| `mailpit` | Capture des e-mails de développement | 1025 / 8025 |

Vérifier que tout tourne :

```bash
docker compose ps
```

---

## 5. Commandes utiles

```bash
# --- Cycle de vie ---
docker compose up -d              # démarrer
docker compose down               # arrêter (conserve les données)
docker compose down -v            # arrêter ET supprimer les données (base + redis)
docker compose logs -f laravel    # suivre les logs d'un service

# --- Backend (dans le conteneur laravel) ---
docker compose exec laravel php artisan migrate:fresh --seed   # réinitialiser la base + démo
docker compose exec laravel php artisan test                   # tests (88 tests)
docker compose exec laravel ./vendor/bin/pint                  # style de code (PSR-12)
docker compose exec laravel ./vendor/bin/phpstan analyse       # analyse statique (Larastan)

# --- Tâches planifiées (lançables à la main pour la démo) ---
docker compose exec laravel php artisan votes:close     # clôturer les votes échus
docker compose exec laravel php artisan events:tick     # déclencher les événements "monde"
docker compose exec laravel php artisan game:upkeep     # cycle d'entretien / fin de saison

# --- Frontend (dans le conteneur vue) ---
docker compose exec vue npm run lint     # ESLint
docker compose exec vue npm run build    # build de production
```

---

## 6. Dépannage

| Problème | Cause probable | Solution |
|---|---|---|
| `error during connect … dockerDesktopLinuxEngine` | Docker Desktop n'est pas lancé | Ouvrir Docker Desktop et attendre l'état « Running » |
| `bind: address already in use` (5173, 8000, 5432…) | Un port est déjà occupé | Libérer le port, ou modifier le mappage dans `docker-compose.yml` |
| Base vide (aucun pays, pas de comptes démo) | Le seed n'a pas été lancé | `docker compose exec laravel php artisan migrate:fresh --seed` |
| `exec entrypoint: no such file` (conteneur laravel) | Fins de ligne CRLF (Windows) sur un script `.sh` | Déjà corrigé dans le `Dockerfile` ; reconstruire : `docker compose build laravel` |
| Le temps réel ne se met pas à jour | Reverb ou Horizon arrêté | `docker compose ps` puis `docker compose up -d reverb horizon` |
| Requêtes lentes (plusieurs secondes) sous Windows | Montage de fichiers Windows lent | Utiliser le backend **WSL2** de Docker Desktop (déjà optimisé côté OPcache) |

Reconstruire complètement en cas de doute :

```bash
docker compose down -v
docker compose up -d --build
docker compose exec laravel php artisan migrate:fresh --seed
```

---

## 7. Environnements et branches

- **Branches :** `dev` (intégration) → `pre-prod` (recette) → `main` (production). Chaque fonctionnalité est développée sur une branche `feature/*` puis fusionnée par *pull request* validée par la CI.
- **Intégration continue :** `.github/workflows/ci.yml` exécute à chaque *push* / *pull request* : Pint, Larastan et PHPUnit (backend) + ESLint et build (frontend).
- **Production :** utiliser `docker-compose.prod.yml` (caches activés, frontend compilé, Nginx en 80). Le HTTPS s'ajoute avec l'overlay `docker-compose.ssl.yml` (Let's Encrypt + renouvellement automatique).

### Préparation du serveur

```bash
cp .env.example .env                              # DB_PASSWORD et REDIS_PASSWORD (obligatoires)
cp backend/.env.production.example backend/.env   # APP_URL, REVERB_APP_SECRET, MAIL_*, ...
```

Les mots de passe de `.env` (racine) sont injectés dans les conteneurs backend et priment
sur `backend/.env` : une seule valeur à maintenir pour `DB_PASSWORD` et `REDIS_PASSWORD`.
`APP_KEY` peut rester vide : l'entrypoint la génère au premier démarrage.

`frontend/.env.production` est lu **au moment du build** de l'image : le domaine doit y être
renseigné avant `docker compose build`, un changement ultérieur impose de reconstruire le front.

### Déploiement en HTTP

```bash
docker compose -f docker-compose.prod.yml up -d --build
curl http://localhost/up      # {"status":"ok","checks":{"database":true,"cache":true}}
```

### Passage en HTTPS

Le certificat doit exister **avant** que Nginx charge la configuration TLS, sinon il refuse de
démarrer. L'ordre ci-dessous est donc impératif (le domaine doit déjà pointer sur le serveur).

`--entrypoint certbot` n'est pas optionnel : le service `certbot` définit un `entrypoint` qui
boucle sur le renouvellement. Sans cette option, `docker compose run` ne remplace que la
*commande* — les arguments `certonly …` sont alors ignorés par ce script et c'est la boucle de
renouvellement qui démarre, au lieu de l'émission du certificat.

Renseigner d'abord les deux variables :

```bash
export DOMAIN=mon-domaine.com CERTBOT_EMAIL=moi@exemple.com
```

**1.** La pile tourne en HTTP et sert déjà `/.well-known/acme-challenge/` :

```bash
docker compose -f docker-compose.prod.yml up -d
```

**2.** Essai à blanc — valide tout le circuit sans consommer le quota Let's Encrypt (5 échecs de
validation par heure, 5 certificats identiques par semaine) :

```bash
docker compose -f docker-compose.prod.yml -f docker-compose.ssl.yml run --rm --entrypoint certbot certbot certonly --webroot -w /var/www/certbot -d "$DOMAIN" -d "www.$DOMAIN" --email "$CERTBOT_EMAIL" --agree-tos --no-eff-email --dry-run
```

Attendu : `The dry run was successful.`

**3.** Émission réelle — la même commande sans `--dry-run` :

```bash
docker compose -f docker-compose.prod.yml -f docker-compose.ssl.yml run --rm --entrypoint certbot certbot certonly --webroot -w /var/www/certbot -d "$DOMAIN" -d "www.$DOMAIN" --email "$CERTBOT_EMAIL" --agree-tos --no-eff-email
```

**4.** Bascule de Nginx en TLS et démarrage du renouvellement automatique (toutes les 12 h) :

```bash
docker compose -f docker-compose.prod.yml -f docker-compose.ssl.yml up -d
```

Vérification depuis un autre poste — la première commande doit renvoyer `{"status":"ok",…}`,
la seconde un code `301` vers HTTPS :

```bash
curl -s https://$DOMAIN/up
```

```bash
curl -s -o /dev/null -w "%{http_code}" http://$DOMAIN/
```

### Mise à jour du code

Le volume nommé `backend-vendor` n'est initialisé qu'à sa création : après une modification de
`composer.lock`, il faut le supprimer pour que les nouvelles dépendances soient prises en compte.

```bash
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml down
docker volume rm medieval-realm-prod_backend-vendor
docker compose -f docker-compose.prod.yml up -d
```

---

## 8. Documentation

La documentation technique complète (architecture, sécurité, cahier de recettes, manuels d'exploitation) est disponible dans `docs/Documentation-technique-Medieval-Realm.docx`.
