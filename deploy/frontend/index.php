<?php
require_once __DIR__ . '/Psr4AutoloaderClass.php';
use R301\Psr4AutoloaderClass;

$loader = new Psr4AutoloaderClass;
$loader->register();
$loader->addNamespace('R301', __DIR__);

require_once __DIR__ . '/Vue/Http/ApiClient.php';

if (preg_match('/\.(?:png|jpg|jpeg|gif|ico|css|js)\??.*$/', $_SERVER["REQUEST_URI"])) {
    return false;
}

session_start();
ob_start();

$routePath = strtok($_SERVER["REQUEST_URI"], '?');
$routePath = rtrim($routePath, '/');
if ($routePath === '' || $routePath === '/') {
    $routePath = '/tableauDeBord';
}

$isLoginRoute = ($routePath === '/login');

if (!$isLoginRoute && !isset($_SESSION['auth_token'])) {
    header('Location: /login');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <title>R4.01</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" charset="UTF-8"/>
        <link rel="stylesheet" href="/stylesheet.css"/>
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
        $viewFile = __DIR__ . '/Vue' . $routePath . '.php';
        if (is_file($viewFile)) {
            require_once $viewFile;
        } else {
            http_response_code(404);
            echo '<p>Page introuvable.</p>';
        }
    ?>
    </body>
</html>
