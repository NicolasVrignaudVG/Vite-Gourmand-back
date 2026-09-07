# 🍽️ Vite & Gourmand : API Backend

API REST pour l'application de traiteur "Vite & Gourmand" (Symfony 8 / PHP 8.4).

Le frontend (SPA vanilla JS) associé se trouve dans le dépôt [Vite-Gourmand](https://github.com/NicolasVrignaudVG/Vite-Gourmand).

## 🛠️ Stack technique

| Couche | Technologie |
|---|---|
| Framework | PHP 8.4, Symfony 8 |
| API | REST JSON pure |
| ORM | Doctrine |
| Base de données relationnelle | MySQL 8.0 |
| Base de données NoSQL | MongoDB Atlas (statistiques de vente) |
| Authentification | JWT en cookie HttpOnly + refresh token avec rotation |
| Hashage mots de passe | bcrypt (cost 12) |
| Documentation API | Swagger / OpenAPI (NelmioApiDocBundle), route `/api/doc` |
| Tests | PHPUnit (unitaires et fonctionnels), fixtures Doctrine + Faker |
| Mails transactionnels | Brevo API |
| Calcul livraison | OpenRouteService API |
| Déploiement | Render (Docker) |
| Base de données production | Clever Cloud MySQL |

## 📚 Documentation

Les livrables documentaires du projet (documentation technique, déploiement, gestion de projet, manuel d'utilisation, charte graphique) sont regroupés dans le dossier [`docu/` du dépôt front-end](https://github.com/NicolasVrignaudVG/Vite-Gourmand/tree/main/docu).

## ⚙️ Prérequis

- PHP >= 8.4 avec les extensions : `pdo_mysql`, `mongodb`, `intl`, `ctype`, `iconv`, `fileinfo`, `zip`
- [Composer](https://getcomposer.org/)
- [Symfony CLI](https://symfony.com/download)
- MySQL >= 8.0 (local ou distant)
- Une instance MongoDB accessible (local ou Atlas)

> L'extension PHP `mongodb` ne fait pas partie de PHP par défaut. Si `php -m` (Windows : `php -m | findstr mongodb`) ne la liste pas, installez-la via PECL ou activez-la dans votre `php.ini`.

## 🚀 Installation en local

### 1. Cloner le dépôt

```bash
git clone https://github.com/NicolasVrignaudVG/Vite-Gourmand-back.git
cd Vite-Gourmand-back
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Générer (ou récupérer) les clés JWT

Cette étape précède la configuration : les clés doivent exister pour pouvoir être encodées à l'étape suivante.

Les clés se trouvent dans `config/jwt/` (`private.pem` / `public.pem`) et sont **exclues du dépôt**. Si elles sont absentes sur votre machine :

```bash
php bin/console lexik:jwt:generate-keypair
```

La commande demande une passphrase, notez-la : elle sera reportée dans `JWT_PASSPHRASE`.

### 4. Configurer les variables d'environnement

Copiez le fichier `.env.production` (template de référence) en `.env.local`, puis renseignez les vraies valeurs :

```
APP_ENV=dev
APP_DEBUG=true

APP_SECRET=                # chaîne aléatoire, ex: openssl rand -hex 16
JWT_PASSPHRASE=            # passphrase des clés JWT (étape 3)

# Base de données MySQL locale
DATABASE_URL=mysql://root:MOT_DE_PASSE@127.0.0.1:3306/vite_gourmand?serverVersion=8.0&charset=utf8mb4

# MongoDB Atlas
MONGO_URI=mongodb+srv://USER:PASSWORD@CLUSTER.mongodb.net/
MONGO_DB=vite_gourmand_stats

# Mails (Brevo API)
MAILER_DSN=brevo+api://VOTRE_CLE_API@default
MAILER_SENDER_EMAIL=votre@email.com
MAILER_SENDER_NAME="Vite & Gourmand"

# Calcul livraison
ORS_API_KEY=votre_cle_openrouteservice

# JWT : contenu des clés encodé en base64 (voir encadré ci-dessous)
JWT_SECRET_KEY=
JWT_PUBLIC_KEY=

# Messenger
MESSENGER_TRANSPORT_DSN=sync://

# CORS (autorise localhost en dev)
CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:\d+)?$
```

> ⚠️ **Format des clés JWT.** `config/packages/lexik_jwt_authentication.yaml` utilise le processeur `%env(base64:...)%` : les variables `JWT_SECRET_KEY` et `JWT_PUBLIC_KEY` doivent contenir le **contenu des fichiers `.pem` encodé en base64**, et non un chemin d'accès. Renseigner un chemin (`%kernel.project_dir%/config/jwt/private.pem`) provoque l'erreur `Unable to create a signed JWT from the given configuration` à la connexion, le processeur tentant de décoder le chemin lui-même. Cette convention est identique en local et sur Render.
>
> Pour générer les valeurs :
>
> ```powershell
> # Windows (PowerShell)
> [Convert]::ToBase64String([IO.File]::ReadAllBytes("config\jwt\private.pem"))
> [Convert]::ToBase64String([IO.File]::ReadAllBytes("config\jwt\public.pem"))
> ```
>
> ```bash
> # Linux / macOS
> base64 -w 0 config/jwt/private.pem
> base64 -w 0 config/jwt/public.pem
> ```
>
> Collez chaque résultat sur une seule ligne, sans espace ni guillemets.

> ⚠️ **Valeurs contenant des espaces.** Le composant Dotenv de Symfony refuse une valeur non quotée contenant un espace (`A value containing spaces must be surrounded by quotes`). C'est le cas de `MAILER_SENDER_NAME` : les guillemets sont obligatoires.

> **Ne commitez jamais `.env.local`**, il est déjà exclu via `.gitignore`.

### 5. Créer la base de données et appliquer les migrations

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
```

### 6. Charger les données de test

```bash
mysql -u root -p vite_gourmand < data_only.sql
```

> Si le client `mysql` n'est pas dans votre `PATH` (cas fréquent sous Windows avec une installation via MySQL Installer), utilisez MySQL Workbench (menu *File → Run SQL Script*) ou appelez le binaire par son chemin complet, par exemple `& "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe"`.
>
> Pour exécuter une requête ponctuelle sans client externe, Doctrine expose `php bin/console dbal:run-sql "SELECT ..."`, qui utilise la connexion déjà configurée dans `.env.local`. Pratique pour vérifier sur quelle base vous travaillez réellement : `php bin/console dbal:run-sql "SELECT DATABASE()"`.

> **Note** : le fichier `database.sql` (structure + données, en SQL brut, sans dépendance à Doctrine) est fourni séparément comme livrable démontrant la maîtrise du langage SQL, conformément à l'exigence de l'énoncé ECF (*"L'utilisation de fixture et/ou de migration n'implique pas que vous maitrisez le SQL"*). Le schéma réellement utilisé par l'application est celui généré par les migrations Doctrine ci-dessus, alimenté par `data_only.sql`.

### 7. Lancer le serveur

```bash
symfony server:start
```

L'API est accessible sur `http://127.0.0.1:8000` (ou le port affiché dans le terminal). Pour servir en HTTPS localement, installez au préalable l'autorité de certification locale : `symfony server:ca:install`.

Documentation Swagger disponible sur `/api/doc`.

> **Cookies et nom d'hôte.** Les navigateurs traitent `localhost` et `127.0.0.1` comme deux origines distinctes : un cookie posé sur l'une n'est pas envoyé à l'autre. Le frontend cible `http://localhost:8000` en développement (voir `js/api.js`), servez donc le front sous `localhost` également, faute de quoi l'authentification échouera silencieusement.

## 🧪 Tests

Le projet inclut des tests unitaires et fonctionnels (PHPUnit).

### 1. Configurer la base de test

Renseignez une `DATABASE_URL` dédiée dans `.env.test` (base distincte de celle de développement) :

```
DATABASE_URL="mysql://root:MOT_DE_PASSE@127.0.0.1:3306/vite_gourmand_test?serverVersion=8.0&charset=utf8mb4"
```

Créez ensuite le schéma et chargez les données de test :

```bash
php bin/console --env=test doctrine:database:create
php bin/console --env=test doctrine:schema:create
php bin/console --env=test doctrine:fixtures:load --no-interaction
```

### 2. Lancer les tests

```bash
php bin/phpunit
```

### 3. Organisation des tests

| Dossier | Type | Contenu |
|---|---|---|
| `tests/Entity/` | Unitaires | Logique métier isolée : calcul du prix et de la remise (`Menu::calculerPrix`) |
| `tests/Controller/` | Fonctionnels | Routes de l'API : accès public, contrôle des rôles, filtres de menus |
| `tests/Validator/` | Unitaires | Contrainte de validation du mot de passe : cas valides et chaque critère manquant |

Les tests fonctionnels vérifient notamment que les routes protégées (création de menu, par exemple) refusent bien les requêtes non authentifiées, et que les filtres de la liste des menus renvoient les bons résultats.

### 4. Jeux de données (fixtures)

Deux jeux de fixtures cohabitent, avec des usages distincts :

| Fixture | Groupe | Usage |
|---|---|---|
| `AppFixtures` | (aucun) | Données **déterministes** (menus, thèmes, régimes, plats) sur lesquelles s'appuient les assertions des tests |
| `FakerFixtures` | `faker` | Données **aléatoires réalistes** (utilisateurs, messages de contact) pour peupler une base de développement |

Pour ajouter les données de volume en développement, sans écraser les données existantes :

```bash
php bin/console doctrine:fixtures:load --group=faker --append
```

> **Note** : les données générées par Faker changent à chaque exécution. Elles ne servent donc pas de base aux assertions des tests automatisés, qui reposent exclusivement sur `AppFixtures`.

## 👤 Comptes de test

| Rôle | Email | Mot de passe |
|---|---|---|
| Administrateur | admin@vitegourmand.fr | Admin@1234 |
| Employé | employe@vitegourmand.fr | Employe@1234 |
| Client | marie.dupont24@email.com | Visiteur@12345 |

## 🌐 En production

L'API est déployée sur Render via Docker (voir `Dockerfile` à la racine, Apache sur le port 10000).

Les variables d'environnement de production sont définies directement dans le dashboard Render (Environment), à partir du template `.env.production` versionné dans ce dépôt. Aucune valeur sensible n'est commitée.

URL de production : https://vite-gourmand-back-chap.onrender.com

> L'offre gratuite Render met le service en veille après 15 minutes d'inactivité : la première requête suivante peut prendre 30 à 60 secondes (*cold start*).

## 📁 Structure du projet

```
Vite-Gourmand-back/
├── src/
│   ├── Controller/       # Routes API REST (annotées OpenAPI)
│   ├── Entity/           # Entités Doctrine
│   ├── Repository/       # Composants d'accès aux données (QueryBuilder)
│   ├── Service/          # Services métier (CommandeService, MailerService, DeliveryService, MongoService)
│   ├── Security/         # JwtCookieSuccessHandler : émission des cookies JWT et refresh
│   ├── EventListener/    # ExceptionListener : réponses d'erreur JSON
│   ├── EventSubscriber/  # LoginRateLimiterSubscriber : limitation des tentatives de connexion
│   ├── Validator/        # MotDePasseValide : contrainte de robustesse des mots de passe
│   └── DataFixtures/     # Jeux de données (AppFixtures, FakerFixtures)
├── tests/
│   ├── Entity/           # Tests unitaires (logique métier)
│   ├── Controller/       # Tests fonctionnels (routes API)
│   └── Validator/        # Tests unitaires de la contrainte de mot de passe
├── config/               # Configuration Symfony
│   └── jwt/              # Clés JWT (non versionnées)
├── migrations/           # Migrations Doctrine
├── public/               # Point d'entrée Apache
├── database.sql          # Livrable SQL brut (structure + données)
├── data_only.sql         # Données de test (à charger après migrations)
└── Dockerfile            # Image de déploiement Render
```

## 🔒 Sécurité

### Authentification

- Authentification JWT (tokens signés RS256, clés RSA 4096 bits)
- Tokens stockés en cookie HttpOnly + refresh token avec rotation et révocation en base
- Cookie de refresh limité au chemin `/api/auth`
- Mots de passe hashés en bcrypt (cost 12)
- Limitation des tentatives de connexion par IP : `LoginRateLimiterSubscriber`, exécuté avant le pare-feu, réponse `429` avec en-tête `Retry-After`

### Contrôle d'accès

- Gestion hiérarchique des rôles : `ROLE_USER`, `ROLE_EMPLOYE`, `ROLE_ADMIN`
- Double niveau : liste `access_control` de `security.yaml` et attributs `#[IsGranted]` sur les contrôleurs
- **Règle de repli** en fin d'`access_control` (`^/api` vers `IS_AUTHENTICATED_FULLY`) : toute route non explicitement déclarée exige une authentification. Le principe appliqué est le refus par défaut : l'ouverture d'une route devient un acte explicite
- L'ordre des règles est signifiant : les routes publiques les plus spécifiques précèdent les règles générales qui les englobent

### Entrées et sorties

- Validation des entrées côté serveur (Symfony Validator)
- Politique de mot de passe centralisée dans une contrainte réutilisable (`App\Validator\MotDePasseValide`) : 10 caractères minimum, majuscule, minuscule, chiffre, caractère spécial. Appliquée à l'inscription, à la réinitialisation et à la modification du profil, et couverte par des tests unitaires
- Statuts de commande validés contre `Commande::STATUTS` avant traitement
- Références de commande générées via `random_bytes(4)` (aléa cryptographique)
- Upload d'images : types MIME validés, taille limitée
- CORS configuré (NelmioCorsBundle)
- Content Security Policy stricte (NelmioSecurityBundle), avec exception ciblée sur `/api/doc` (Swagger UI)
- En-tête HSTS posé par Apache (voir `Dockerfile`)
- `ExceptionListener` : messages d'erreur génériques en production, pas de fuite d'informations
- Conformité RGPD (suppression de compte)

> Les en-têtes de sécurité et la CSP configurés ici ne couvrent que les réponses de l'API. Le frontend étant servi par Vercel, ses propres en-têtes sont définis dans le bloc `headers` de `vercel.json`, côté dépôt front.

### Résilience des services externes

La synchronisation MongoDB et l'envoi des e-mails transactionnels sont traités comme des effets de bord : leur échec est journalisé via le `LoggerInterface` mais n'interrompt pas la requête, la commande ayant déjà été persistée. Le calcul des frais de livraison suit le même principe : en cas d'indisponibilité d'OpenRouteService, un forfait de repli est appliqué.

## 🌿 Organisation des branches Git

```
main
└── develop
    ├── feature/authentification
    ├── feature/gestion-menus
    ├── feature/commandes
    ├── feature/espace-utilisateur
    ├── feature/espace-employe
    └── feature/espace-admin
```

Convention de nommage selon la nature du travail : `feature/*` pour les fonctionnalités, `fix/*` pour les correctifs, `security/*` pour le durcissement, `test/*` pour l'ajout de tests, `refactor/*` pour les refactorisations.

## 📄 Licence

Projet réalisé dans le cadre de l'ECF, TP Développeur Web et Web Mobile (Studi).
