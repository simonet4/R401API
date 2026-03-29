<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Psr4AutoloaderClass.php';
use R301\Psr4AutoloaderClass;

$loader = new Psr4AutoloaderClass;
// register the autoloader
$loader->register();
// register the base directories for the namespace prefix
$loader->addNamespace('R301', '.');

require_once __DIR__ . '/Vue/Http/ApiClient.php';

if (preg_match('/\.(?:png|jpg|jpeg|gif|ico|css|js)\??.*$/', $_SERVER["REQUEST_URI"])) {
    return false; // serve the requested resource as-is.
} else {

session_start();

// --- AJOUT DE LA MODIFICATION ICI ---
// Si l'utilisateur arrive sur la racine, on définit le tableau de bord comme page par défaut
$uri_actuelle = strtok($_SERVER["REQUEST_URI"], '?');
if ($uri_actuelle === '/' || $uri_actuelle === '/index.php') {
    $_SERVER["REQUEST_URI"] = '/tableauDeBord'; 
}
// ------------------------------------

$isLoginRoute = strtok($_SERVER["REQUEST_URI"], '?') === '/login';
if (!$isLoginRoute && !isset($_SESSION['auth_token'])) {
    header('Location: /login');
    exit; // Ajout d'un exit ici par sécurité après une redirection
}
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <title>R3.01</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" charset="UTF-8"/>
        <link rel="stylesheet" href="/stylesheet.css"/>
        <link rel="icon" type="image/jpg" href="/favicon.jpg">
    </head>
    <body>
    <?php if (!$isLoginRoute) : ?>
        <nav class="navbar">
            <a href="/tableauDeBord" class="dropbtn">Tableau de bord</a>
            <div class="dropdown">
                <button class="dropbtn">Joueurs</button>
                <div class="dropdown-content">
                    <a href="/joueur/ajouter">Ajouter un joueur</a>
                    <a href="/joueur">Liste de joueurs</a>
                </div>
            </div>
            <div class="dropdown">
                <button class="dropbtn">Rencontres</button>
                <div class="dropdown-content">
                    <a href="/rencontre/ajouter">Ajouter une rencontre</a>
                    <a href="/rencontre">Liste des rencontres</a>
                </div>
            </div>
        </nav>
    <?php endif; ?>
    <?php
        // On récupère la vue dynamique
        require_once './Vue' . strtok($_SERVER["REQUEST_URI"],'?') . '.php';
    } ?>
    </body>
</html>