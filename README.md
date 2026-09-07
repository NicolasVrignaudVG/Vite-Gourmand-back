# 🍽️ Vite & Gourmand

Application web de présentation et commande de menus pour la société Vite & Gourmand, entreprise de traiteur basée à Bordeaux.

## 📋 Description

Vite & Gourmand propose ses prestations pour tout type d'événement (Noël, Pâques, événements professionnels...). Cette application permet :

- De consulter les menus disponibles et de composer son repas plat par plat
- De passer commande en ligne avec calcul automatique des frais de livraison
- De gérer les commandes (espace utilisateur, employé et administrateur)
- De valider/refuser les avis clients
- De consulter les statistiques via MongoDB

## 🛠️ Stack technique

| Couche | Technologie |
|---|---|
| Front-end | HTML5, CSS3, JavaScript vanilla (SPA) |
| Back-end | PHP 8.4, Symfony 8 |
| ORM | Doctrine |
| Authentification | JWT (LexikJWTAuthenticationBundle) |
| Base de données relationnelle | MySQL 8.0 |
| Base de données NoSQL | MongoDB Atlas |
| Mails transactionnels | Brevo API |
| Calcul livraison | OpenRouteService API |
| Déploiement front | Vercel |
| Déploiement back | Render (Docker) |
| Base de données production | Clever Cloud MySQL |

## 📚 Documentation

Les livrables documentaires du projet sont regroupés dans le dossier [`docu/`](docu/) :

- [Documentation technique](docu/Documentation_Technique_Vite_Gourmand_v1.4.pdf) : réflexions technologiques, environnement, MCD, diagrammes UML, API, sécurité
- [Documentation de déploiement](docu/Documentation_Deploiement_Vite_Gourmand_v2.pdf)
- [Gestion de projet](docu/Gestion_de_Projet_Vite_Gourmand_v2.pdf)
- [Manuel d'utilisation](docu/Manuel_Utilisation_Vite_Gourmand_v2.pdf)
- [Charte graphique & maquettes](docu/Charte_Graphique_Vite_Gourmand_v2.pdf)

## ⚙️ Prérequis

- PHP >= 8.4
- Composer
- Symfony CLI
- MySQL >= 8.0
- Git
- Extensions PHP : `pdo_mysql`, `fileinfo`, `mongodb`, `zip`

## 🚀 Installation en local

### 1. Cloner les dépôts

Les deux dépôts sont indépendants et doivent être clonés côte à côte, non imbriqués :

```bash
# Front-end
git clone https://github.com/NicolasVrignaudVG/Vite-Gourmand.git

# Back-end
git clone https://github.com/NicolasVrignaudVG/Vite-Gourmand-back.git
```

### 2. Installer les dépendances back-end

```bash
cd Vite-Gourmand-back
composer install
```

### 3. Générer (ou récupérer) les clés JWT

Cette étape précède la configuration : les clés doivent exister pour pouvoir être encodées à l'étape suivante.

Les clés se trouvent dans `config/jwt/` et sont exclues du dépôt via `.gitignore`. Si elles sont absentes sur votre machine :

```bash
php bin/console lexik:jwt:generate-keypair
```

La commande demande une passphrase, notez-la : elle sera reportée dans `JWT_PASSPHRASE`.

### 4. Configurer les variables d'environnement

Créez un fichier `.env.local` à la racine du back-end (référez-vous à `.env.production` pour la liste des clés attendues) :

```
APP_ENV=dev
APP_SECRET=votre_secret_32_caracteres

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
JWT_PASSPHRASE=

# Messenger
MESSENGER_TRANSPORT_DSN=sync://

# CORS
CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$
```

> ⚠️ **Format des clés JWT.** La configuration du bundle utilise le processeur `%env(base64:...)%` : les variables `JWT_SECRET_KEY` et `JWT_PUBLIC_KEY` attendent le **contenu des fichiers `.pem` encodé en base64**, et non un chemin d'accès. Renseigner un chemin provoque un échec de signature des tokens à la connexion. La procédure d'encodage complète figure dans le [README du back-end](https://github.com/NicolasVrignaudVG/Vite-Gourmand-back).

> ⚠️ **Valeurs contenant des espaces.** Le composant Dotenv de Symfony refuse une valeur non quotée contenant un espace. C'est le cas de `MAILER_SENDER_NAME` : les guillemets sont obligatoires.

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

### 7. Lancer les serveurs

Back-end, depuis `Vite-Gourmand-back/` :

```bash
symfony server:start
```

Front-end, depuis `Vite-Gourmand/` :

```bash
php -S localhost:3000
```

L'application est accessible sur `http://localhost:3000`

> **Cookies et nom d'hôte.** Les navigateurs traitent `localhost` et `127.0.0.1` comme deux origines distinctes : un cookie posé sur l'une n'est pas envoyé à l'autre. En développement, `js/api.js` cible `http://localhost:8000`, servez donc le front sous `localhost` également, faute de quoi l'authentification échouera silencieusement.

## 👤 Comptes de test

| Rôle | Email | Mot de passe |
|---|---|---|
| Administrateur | admin@vitegourmand.fr | Admin@1234 |
| Employé | employe@vitegourmand.fr | Employe@1234 |
| Utilisateur | marie.dupont24@email.com | Visiteur@12345 |

## 🌐 Application en ligne

| Service | URL |
|---|---|
| Front-end | https://vitegourmand33.vercel.app |
| Back-end | https://vite-gourmand-back-chap.onrender.com |

> L'offre gratuite Render met l'API en veille après 15 minutes d'inactivité : la première requête suivante peut prendre 30 à 60 secondes.

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

## 📁 Structure du projet

```
Vite-Gourmand/          ← Front-end
├── css/
│   └── main.css        # Design tokens, composants, mise en page
├── js/
│   ├── api.js          # Appels API centralisés
│   └── script.js       # Logique applicative et délégation d'événements
├── Router/
│   └── router.js       # Routeur SPA (module ES)
├── images/             # Assets
├── pages/              # Pages HTML (SPA)
├── docu/               # Livrables documentaires ECF (PDF + diagrammes)
├── vercel.json         # Proxy /api/* vers Render, routing SPA, en-têtes de sécurité
├── index.html          # Point d'entrée
└── README.md

Vite-Gourmand-back/     ← Back-end Symfony
├── src/
│   ├── Controller/     # Routes API REST
│   ├── Entity/         # Entités Doctrine
│   ├── Repository/     # Composants d'accès aux données (QueryBuilder)
│   ├── Service/        # Services métier (Commande, Menu, Mail, Livraison, MongoDB)
│   ├── Security/       # Émission des cookies JWT et refresh
│   ├── EventListener/  # Réponses d'erreur JSON
│   └── EventSubscriber/ # Limitation des tentatives de connexion
├── config/             # Configuration Symfony
├── migrations/         # Migrations Doctrine
└── public/             # Point d'entrée Apache
```

> **Casse du dossier `Router/`.** Le dossier porte une majuscule initiale et est importé comme module ES depuis `index.html`. Les systèmes de fichiers de Windows étant insensibles à la casse, contrairement à ceux des plateformes de déploiement, un écart de casse passe inaperçu en local et provoque une erreur 404 en production.

## 🔒 Sécurité

### Authentification et contrôle d'accès

- Authentification JWT (tokens signés RS256, clés RSA 4096 bits)
- Token stocké en cookie HttpOnly, accompagné d'un refresh token à rotation
- Mots de passe hashés en bcrypt (cost 12)
- Limitation des tentatives de connexion par IP, avant vérification du mot de passe
- Gestion hiérarchique des rôles : `ROLE_USER`, `ROLE_EMPLOYE`, `ROLE_ADMIN`
- Règle de repli côté API : toute route `/api` non explicitement déclarée exige une authentification
- Le contrôle des droits est effectué côté serveur. Côté client, les rôles lus depuis le `localStorage` ne servent qu'à afficher ou masquer des éléments d'interface et ne constituent pas une mesure de sécurité

### Protection du front

- En-têtes de sécurité définis dans le bloc `headers` de `vercel.json`, appliqués à toutes les réponses du domaine front : HSTS, Content Security Policy, `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`
- Content Security Policy sans source inline pour les scripts (`script-src 'self'` plus le CDN jsDelivr). Les gestionnaires d'événements inline ont été remplacés par les attributs `data-nav` et `data-close-modal`, traités par délégation sur `document` dans `js/script.js`, ce qui couvre également les éléments injectés dynamiquement par le routeur
- Protection XSS côté front (`sanitize()`)
- Note A+ sur securityheaders.com

> Les en-têtes définis dans `vercel.json` couvrent le domaine du front. Les réponses de l'API portent leurs propres en-têtes, configurés côté Symfony (NelmioSecurityBundle et Apache).

### Entrées, sorties et données

- Validation des entrées côté serveur (Symfony Validator)
- Protection CSRF : cookies SameSite=Lax + Secure (proxy Vercel same-site), CORS restrictif, Content-Type JSON obligatoire
- Références de commande générées par tirage cryptographique
- Synchronisation MongoDB et envoi des e-mails traités comme des effets de bord : leur échec est journalisé sans invalider la commande
- Conformité RGPD

## ♿ Accessibilité

L'application respecte les critères du RGAA (Référentiel Général d'Amélioration de l'Accessibilité) :

- Skip links
- Attributs `aria-*`
- Navigation clavier
- Contraste suffisant

## 🔗 Gestion de projet

Tableau de suivi : [Notion, suivi des tâches Vite & Gourmand](https://www.notion.so/7bbf6c33689f48798cac103ce1ece2a3?v=7f5ec6945ca4454ca6b9e22069144fa5)

## 📄 Licence

Projet réalisé dans le cadre de l'ECF, TP Développeur Web et Web Mobile (Studi).
