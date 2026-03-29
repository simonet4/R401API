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
$isLoginRoute = strtok($_SERVER["REQUEST_URI"], '?') === '/login';
if (!$isLoginRoute && !isset($_SESSION['auth_token'])) {
    header('Location: /login');
    exit();
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
    // On récupère l'URI sans les paramètres GET (?id=...)
    $uri = strtok($_SERVER["REQUEST_URI"], '?');

    // Si l'utilisateur est à la racine, on définit une page par défaut
    if ($uri === '/' || $uri === '') {
        $uri = '/tableau_de_bord';
    }

    // 3. On construit le chemin du fichier
    $chemin_vue = './Vue' . $uri . '.php';

    // fichier existe ? on l'inclut, sinon 404
    if (file_exists($chemin_vue)) {
        require_once $chemin_vue;
    } else {
        // erreur 404 si le fichier n'existe pas
        http_response_code(404);
        echo "<h1>Erreur 404</h1><p>La page demandée n'existe pas.</p>";
    }
    ?>
    </body>
</html>
<?php
}