## Lien vers la PROD
https://r301.kilya.coop/

Identifiants:
user: admin
mdp: admin

## Configuration Apache
### MODs à installer
php
php-mysql
rewrite

### Configuration du virtual host
```
<VirtualHost *:80>
    ServerName ${serverName}
    DocumentRoot /var/www/${serverName}

    <Directory "/var/www/${serverName}">
        Options Indexes FollowSymLinks
        AllowOverride None
        Require all granted
    </Directory>

    RewriteEngine On
    RewriteCond %{REQUEST_URI} !\.(css|jpg)$
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ /index.php [QSA,L]
</VirtualHost>
```

## Technologies utilisées
- HTML
- CSS
- PHP
- PDO (pour la gestion de la base de données)
- MySQL

## Architecture des applications
- Front-end (MVC PHP): application principale
- Back-end API équipe: endpoints `/api/joueur`, `/api/rencontre`, `/api/participation`, `/api/statistiques`
- Authentification: application séparée dans `auth-api/`
- Couche HTTP front-end dédiée: `Vue/Http/ApiClient.php` (toutes les vues appellent les APIs via HTTP)

## Documentation API (format standard)

### Auth API

#### `POST /login`
- Description: authentifie un utilisateur et renvoie un JWT.
- Entrée JSON:
```json
{
    "username": "string",
    "password": "string"
}
```
- Sortie 200 JSON:
```json
{
    "message": "Connexion réussie",
    "token": "jwt",
    "role": "admin|joueur"
}
```
- Erreurs: `400` body JSON invalide ou champs manquants, `401` identifiants invalides, `405` méthode non autorisée, `500` erreur serveur.

#### `GET|POST /verify`
- Description: vérifie la validité d'un JWT.
- Entrée: header `Authorization: Bearer <token>` ou body JSON `{ "token": "..." }`.
- Sortie 200 JSON:
```json
{
    "valid": true,
    "payload": {
        "sub": "username",
        "role": "admin|joueur",
        "iat": 0,
        "exp": 0
    }
}
```
- Erreurs: `400` token manquant, `401` token invalide, `405` méthode non autorisée.

### API Joueurs

#### `GET /api/joueur`
- Description: liste tous les joueurs.
- Sortie 200: tableau de joueurs.
- Erreurs: `401` token manquant/invalide.

#### `GET /api/joueur/{id}`
- Description: détail d'un joueur + commentaires.
- Sortie 200: objet joueur et ses commentaires.
- Erreurs: `404` joueur introuvable, `401` token invalide.

#### `POST /api/joueur`
- Description: crée un joueur.
- Entrée JSON:
```json
{
    "nom": "string",
    "prenom": "string",
    "numeroDeLicence": "string",
    "dateDeNaissance": "YYYY-MM-DD",
    "tailleEnCm": 180,
    "poidsEnKg": 75,
    "statut": "ACTIF|BLESSE|ABSENT|SUSPENDU"
}
```
- Sortie: `201` si créé.
- Erreurs: `400` champs invalides/incomplets, `401` token invalide.

#### `PUT /api/joueur/{id}`
- Description: modifie un joueur existant.
- Entrée: même format que création.
- Sortie: `200` si modifié.
- Erreurs: `400` données invalides, `401` token invalide.

#### `DELETE /api/joueur/{id}`
- Description: supprime un joueur s'il n'a jamais participé.
- Sortie: `200` si supprimé.
- Erreurs: `400` joueur déjà participant, `404` joueur introuvable, `401` token invalide.

#### `POST /api/joueur/{id}/commentaire`
- Description: ajoute un commentaire sur un joueur.
- Entrée JSON:
```json
{
    "contenu": "string"
}
```
- Sortie: `201` si créé.
- Erreurs: `400` contenu absent, `401` token invalide.

#### `DELETE /api/joueur/{joueurId}/commentaire/{commentaireId}`
- Description: supprime un commentaire d'un joueur.
- Sortie: `200` si supprimé.
- Erreurs: `404` commentaire introuvable, `401` token invalide.

### API Matchs

#### `GET /api/rencontre`
- Description: liste publique des matchs passés et à venir.
- Auth: non requise.
- Sortie: `200` tableau de rencontres.

#### `GET /api/rencontre/{id}`
- Description: détail d'une rencontre.
- Auth: requise.
- Erreurs: `404` introuvable, `401` token invalide.

#### `POST /api/rencontre`
- Description: crée une rencontre à date future.
- Entrée JSON:
```json
{
    "dateHeure": "YYYY-MM-DD HH:MM:SS",
    "equipeAdverse": "string",
    "adresse": "string",
    "lieu": "DOMICILE|EXTERIEUR"
}
```
- Sortie: `201` si créé.
- Erreurs: `400` date invalide/champs manquants, `401` token invalide.

#### `PUT /api/rencontre/{id}`
- Description: modifie un match à venir avec une date future.
- Entrée: même format que création.
- Sortie: `200` si modifié.
- Erreurs: `400` rencontre passée ou données invalides, `401` token invalide.

#### `DELETE /api/rencontre/{id}`
- Description: supprime un match non joué.
- Sortie: `200` si supprimé.
- Erreurs: `400` match déjà passé, `401` token invalide.

#### `PATCH /api/rencontre/{id}/resultat`
- Description: enregistre le résultat d'un match joué.
- Entrée JSON:
```json
{
    "resultat": "VICTOIRE|DEFAITE|NUL"
}
```
- Sortie: `200` si enregistré.
- Erreurs: `400` valeur invalide/match non joué, `401` token invalide.

### API Feuille de match

#### `GET /api/participation`
- Description: liste des participations.
- Auth: requise.

#### `GET /api/participation/rencontre/{id}`
- Description: feuille de match d'une rencontre.
- Auth: requise.

#### `POST /api/participation`
- Description: ajoute un joueur actif sur une feuille de match à venir.
- Entrée JSON:
```json
{
    "joueurId": 1,
    "rencontreId": 1,
    "poste": "TOPLANE|JUNGLE|MIDLANE|ADCARRY|SUPPORT",
    "titulaireOuRemplacant": "TITULAIRE|REMPLACANT"
}
```
- Erreurs: `400` poste occupé, joueur déjà présent, joueur inactif, match passé; `401` token invalide.

#### `PUT /api/participation/{id}`
- Description: modifie une participation d'un match à venir.
- Entrée: `joueurId`, `poste`, `titulaireOuRemplacant`.
- Erreurs: `400` contraintes métier non respectées; `401` token invalide.

#### `DELETE /api/participation/{id}`
- Description: retire une participation d'un match à venir.
- Erreurs: `400` match déjà passé; `401` token invalide.

#### `PATCH /api/participation/{id}/performance`
- Description: évalue un joueur sur match joué.
- Entrée JSON:
```json
{
    "performance": "EXCELLENTE|BONNE|MOYENNE|MAUVAISE|CATASTROPHIQUE"
}
```
- Erreurs: `400` match non joué ou valeur invalide; `401` token invalide.

### API Statistiques

#### `GET /api/statistiques/equipe`
- Description: statistiques équipe (totaux + pourcentages V/N/D).
- Auth: requise, rôle `joueur` ou `admin`.
- Erreurs: `403` accès interdit, `401` token invalide.

#### `GET /api/statistiques/joueurs`
- Description: statistiques détaillées par joueur.
- Auth: requise.
- Sortie: statut actuel, poste préféré, titularisations/remplacements, moyenne d'évaluation, pourcentage de victoires, sélections consécutives.

#### `GET /api/statistiques/mes-evaluations`
- Description: détails des évaluations du joueur connecté.
- Auth: requise, rôle `joueur`.
- Erreurs: `403` joueur non déterminable ou rôle non autorisé, `401` token invalide.

