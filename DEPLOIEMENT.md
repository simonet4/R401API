# Guide de déploiement sur Alwaysdata

Ce projet est organisé en **trois parties indépendantes**, chacune à déployer sur un compte Alwaysdata séparé.

| Dossier     | Rôle                         | Exemple de compte Alwaysdata       |
|-------------|------------------------------|------------------------------------|
| `frontend/` | Interface web (MVC PHP)      | `victor-front.alwaysdata.net`      |
| `backend/`  | API REST équipe (données)    | `victor-back.alwaysdata.net`       |
| `api/`      | API d'authentification (JWT) | `victor-api.alwaysdata.net`        |

---

## 1. Compte API d'authentification (`api/`)

### Fichiers à déployer
Copiez tout le contenu du dossier `api/` dans `/home/victor-api/www/` de votre compte Alwaysdata.

### Base de données
1. Créez une base MySQL depuis le panneau Alwaysdata
2. Exécutez le fichier `schema_auth.sql` via phpMyAdmin (accessible depuis le panneau Alwaysdata)

### Variables d'environnement
Allez dans **Sites > votre site > Configuration avancée > Environnement** et ajoutez :

```
AUTH_DB_HOST=mysql-victor-api.alwaysdata.net
AUTH_DB_NAME=victor-api_auth
AUTH_DB_USER=victor-api
AUTH_DB_PASS=votre_mot_de_passe_mysql
AUTH_JWT_SECRET=une_cle_secrete_longue_et_aleatoire
```

> **Important** : Changez `AUTH_JWT_SECRET` par une chaîne longue et aléatoire. Ne la partagez jamais.

### Test
Visitez `https://victor-api.alwaysdata.net/ping` — vous devez obtenir :
```json
{"message": "Auth API OK"}
```

---

## 2. Compte Backend API équipe (`backend/`)

### Fichiers à déployer
Copiez tout le contenu du dossier `backend/` dans `/home/victor-back/www/` de votre compte Alwaysdata.

### Base de données
1. Créez une base MySQL depuis le panneau Alwaysdata
2. Exécutez le fichier `schema.sql` via phpMyAdmin

### Variables d'environnement

```
DB_HOST=mysql-victor-back.alwaysdata.net
DB_NAME=victor-back_r401
DB_USER=victor-back
DB_PASS=votre_mot_de_passe_mysql
AUTH_VERIFY_URL=https://victor-api.alwaysdata.net/verify
```

> `AUTH_VERIFY_URL` pointe vers l'API d'authentification déployée à l'étape 1.

### Test
Visitez `https://victor-back.alwaysdata.net/api/rencontre` — vous devez obtenir la liste des rencontres (ou une erreur 401 si le token est requis).

---

## 3. Compte Frontend (`frontend/`)

### Fichiers à déployer
Copiez tout le contenu du dossier `frontend/` dans `/home/victor-front/www/` de votre compte Alwaysdata.

### Pas de base de données nécessaire
Le frontend ne se connecte pas directement à la base de données.

### Variables d'environnement

```
TEAM_API_BASE_URL=https://victor-back.alwaysdata.net
AUTH_LOGIN_URL=https://victor-api.alwaysdata.net/login
```

> `TEAM_API_BASE_URL` pointe vers le backend API (étape 2).
> `AUTH_LOGIN_URL` pointe vers l'API d'authentification (étape 1).

### Test
Visitez `https://victor-front.alwaysdata.net/login` — la page de connexion doit s'afficher.

---

## Ordre de déploiement recommandé

1. **api/** en premier (l'authentification est utilisée par les deux autres)
2. **backend/** en deuxième (il a besoin de l'API auth pour vérifier les tokens)
3. **frontend/** en dernier (il a besoin du backend ET de l'API auth)

---

## Configuration des variables d'environnement sur Alwaysdata

Il y a deux méthodes :

### Méthode 1 : Panneau d'administration (recommandé)
1. Connectez-vous à votre compte Alwaysdata
2. Allez dans **Sites** > cliquez sur votre site
3. Dans **Configuration avancée** > **Environnement**
4. Ajoutez chaque variable au format `VARIABLE=valeur`

### Méthode 2 : Via .htaccess
Ajoutez les directives `SetEnv` dans le fichier `.htaccess` :
```apache
SetEnv TEAM_API_BASE_URL https://victor-back.alwaysdata.net
SetEnv AUTH_LOGIN_URL https://victor-api.alwaysdata.net/login
```

---

## Résumé des variables par compte

### api/ (authentification)
| Variable          | Description                        | Exemple                                |
|-------------------|------------------------------------|----------------------------------------|
| `AUTH_DB_HOST`    | Hôte MySQL                         | `mysql-victor-api.alwaysdata.net`      |
| `AUTH_DB_NAME`    | Nom de la base                     | `victor-api_auth`                      |
| `AUTH_DB_USER`    | Utilisateur MySQL                  | `victor-api`                           |
| `AUTH_DB_PASS`    | Mot de passe MySQL                 | *(votre mot de passe)*                 |
| `AUTH_JWT_SECRET` | Clé secrète pour signer les JWT    | *(une chaîne aléatoire longue)*        |

### backend/ (API équipe)
| Variable          | Description                        | Exemple                                |
|-------------------|------------------------------------|----------------------------------------|
| `DB_HOST`         | Hôte MySQL                         | `mysql-victor-back.alwaysdata.net`     |
| `DB_NAME`         | Nom de la base                     | `victor-back_r401`                     |
| `DB_USER`         | Utilisateur MySQL                  | `victor-back`                          |
| `DB_PASS`         | Mot de passe MySQL                 | *(votre mot de passe)*                 |
| `AUTH_VERIFY_URL` | URL de vérification de token       | `https://victor-api.alwaysdata.net/verify` |

### frontend/ (interface web)
| Variable             | Description                     | Exemple                                |
|----------------------|---------------------------------|----------------------------------------|
| `TEAM_API_BASE_URL`  | URL de base de l'API équipe     | `https://victor-back.alwaysdata.net`   |
| `AUTH_LOGIN_URL`     | URL de connexion auth           | `https://victor-api.alwaysdata.net/login` |

---

## Architecture de communication

```
┌──────────────────┐       HTTP        ┌───────────────────┐
│                  │──── api_get() ───>│                   │
│    FRONTEND      │<── JSON resp ─────│    BACKEND        │
│  (victor-front)  │                   │  (victor-back)    │
│                  │                   │                   │
│  - Interface web │                   │  - API REST       │
│  - Formulaires   │                   │  - Controleurs    │
│  - Affichage     │                   │  - Modeles + BDD  │
└──────┬───────────┘                   └──────┬────────────┘
       │                                      │
       │ POST /login                          │ GET /verify
       │                                      │
       ▼                                      ▼
┌──────────────────────────────────────────────────────────┐
│                     API AUTH                             │
│                   (victor-api)                           │
│                                                          │
│  - Connexion (login)                                     │
│  - Vérification de token (verify)                        │
│  - Génération JWT                                        │
└──────────────────────────────────────────────────────────┘
```
