# 🍽️ Vite & Gourmand — API Backend

API REST pour l'application de traiteur "Vite & Gourmand" (Symfony 8 / PHP 8.4).

Le frontend (SPA vanilla JS) associé se trouve dans le dépôt [Vite-Gourmand](https://github.com/NicolasVrignaudVG/Vite-Gourmand).

## 🛠️ Stack technique

| Couche | Technologie |
|---|---|
| Framework | PHP 8.4 — Symfony 8 |
| API | REST JSON pure |
| ORM | Doctrine |
| Base de données relationnelle | MySQL 8.0 |
| Base de données NoSQL | MongoDB Atlas (statistiques de vente) |
| Authentification | JWT en cookie HttpOnly + refresh token avec rotation |
| Hashage mots de passe | bcrypt (cost 12) |
| Documentation API | Swagger / OpenAPI (NelmioApiDocBundle) — route `/api/doc` |
| Mails transactionnels | Brevo API |
| Calcul livraison | OpenRouteService API |
| Déploiement | Render (Docker) |
| Base de données production | Clever Cloud MySQL |

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

### 3. Configurer les variables d'environnement

Copiez le fichier `.env.production` (template de référence) en `.env.local`, puis renseignez les vraies valeurs :

```
APP_ENV=dev
APP_DEBUG=true

APP_SECRET=                # chaîne aléatoire, ex: openssl rand -hex 16
JWT_PASSPHRASE=            # passphrase de vos clés JWT (voir étape 4)

# Base de données MySQL locale
DATABASE_URL=mysql://root:@127.0.0.1:3306/vite_gourmand?serverVersion=8.0&charset=utf8mb4

# MongoDB Atlas
MONGO_URI=mongodb+srv://USER:PASSWORD@CLUSTER.mongodb.net/
MONGO_DB=vite_gourmand_stats

# Mails (Brevo API)
MAILER_DSN=brevo+api://VOTRE_CLE_API@default
MAILER_SENDER_EMAIL=votre@email.com
MAILER_SENDER_NAME=Vite & Gourmand

# Calcul livraison
ORS_API_KEY=votre_cle_openrouteservice

# JWT
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem

# Messenger
MESSENGER_TRANSPORT_DSN=sync://

# CORS (autorise localhost en dev)
CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:\d+)?$
```

> **Ne commitez jamais `.env.local`** — il est déjà exclu via `.gitignore`.

### 4. Générer les clés JWT

Les clés sont déjà générées dans `config/jwt/` (`private.pem` / `public.pem`, exclues du dépôt). Si elles sont absentes sur votre machine :

```bash
php bin/console lexik:jwt:generate-keypair
```

Renseignez ensuite la passphrase choisie dans `JWT_PASSPHRASE` (`.env.local`).

### 5. Créer la base de données et appliquer les migrations

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
```

### 6. Charger les données de test

```bash
mysql -u root -p vite_gourmand < data_only.sql
```

> **Note** : le fichier `database.sql` (structure + données, en SQL brut, sans dépendance à Doctrine) est fourni séparément comme livrable démontrant la maîtrise du langage SQL, conformément à l'exigence de l'énoncé ECF (*"L'utilisation de fixture et/ou de migration n'implique pas que vous maitrisez le SQL"*). Le schéma réellement utilisé par l'application est celui généré par les migrations Doctrine ci-dessus, alimenté par `data_only.sql`.

### 7. Lancer le serveur

```bash
symfony server:start
```

L'API est accessible sur `https://127.0.0.1:8000` (ou le port affiché dans le terminal).

Documentation Swagger disponible sur `https://127.0.0.1:8000/api/doc`.

## 👤 Comptes de test

| Rôle | Email | Mot de passe |
|---|---|---|
| Administrateur | admin@vitegourmand.fr | Admin@1234 |
| Employé | employe@vitegourmand.fr | Employe@1234 |

## 🌐 En production

L'API est déployée sur Render via Docker (voir `Dockerfile` à la racine, Apache sur le port 10000).

Les variables d'environnement de production sont définies directement dans le dashboard Render (Environment), à partir du template `.env.production` versionné dans ce dépôt. Aucune valeur sensible n'est commitée.

URL de production : https://vite-gourmand-back-chap.onrender.com

## 📁 Structure du projet

```
Vite-Gourmand-back/
├── src/
│   ├── Controller/     # Routes API REST (annotées OpenAPI)
│   ├── Entity/         # Entités Doctrine
│   ├── Repository/     # Requêtes BDD
│   └── Service/        # Services métier (CommandeService, MailerService, DeliveryService, MongoService)
├── config/             # Configuration Symfony
│   └── jwt/            # Clés JWT (non versionnées)
├── migrations/         # Migrations Doctrine
├── public/             # Point d'entrée Apache
├── database.sql        # Livrable SQL brut (structure + données)
├── data_only.sql       # Données de test (à charger après migrations)
└── Dockerfile          # Image de déploiement Render
```

## 🔒 Sécurité

- Authentification JWT (tokens signés RS256, clés RSA 4096 bits)
- Tokens stockés en cookie HttpOnly + refresh token avec rotation
- Mots de passe hashés en bcrypt (cost 12)
- Validation des entrées côté serveur (Symfony Validator)
- Gestion hiérarchique des rôles : `ROLE_USER`, `ROLE_EMPLOYE`, `ROLE_ADMIN`
- CORS configuré (NelmioCorsBundle)
- Content Security Policy stricte (NelmioSecurityBundle), avec exception ciblée sur `/api/doc` (Swagger UI)
- Conformité RGPD (suppression de compte)

## 🌿 Organisation des branches Git

```
main
└── develop
    ├── feature/authentification
    ├── feature/gestion-menus
    ├── feature/commandes
    ├── feature/espace-admin
    └── feature/espace-employe
```

## 📄 Licence

Projet réalisé dans le cadre de l'ECF — TP Développeur Web et Web Mobile (Studi).
