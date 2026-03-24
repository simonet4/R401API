<?php
/**
 * API de gestion des Rencontres (matchs)
 * 
 * Routes :
 *   GET    /api/rencontre           -> lister toutes les rencontres
 *   GET    /api/rencontre/{id}      -> obtenir une rencontre par ID
 *   POST   /api/rencontre           -> créer une rencontre
 *   PUT    /api/rencontre/{id}      -> modifier une rencontre
 *   DELETE /api/rencontre/{id}      -> supprimer une rencontre
 *   PATCH  /api/rencontre/{id}/resultat -> enregistrer le résultat
 */

require_once __DIR__ . '/Psr4AutoloaderClass.php';
require_once __DIR__ . '/api_auth_client.php';
use R301\Psr4AutoloaderClass;

$loader = new Psr4AutoloaderClass;
$loader->register();
$loader->addNamespace('R301', __DIR__);

use R301\Controleur\RencontreControleur;
use R301\Modele\Rencontre\RencontreLieu;
use R301\Modele\Rencontre\RencontreResultat;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ---- Vérification du token JWT via API d'auth ----
$tokenStatus = verify_token_with_auth_api(get_bearer_token());
if (!$tokenStatus['valid']) {
    http_response_code($tokenStatus['status']);
    echo json_encode(['erreur' => $tokenStatus['error']]);
    exit();
}

// ---- Parsing de l'URL ----
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Compatible URL directe /api/rencontre et /r301-main/api/rencontre
$uri = preg_replace('#^.*?/api/rencontre#', '', $uri);
$parts = explode('/', trim($uri, '/'));

$id = isset($parts[0]) && is_numeric($parts[0]) ? (int)$parts[0] : null;
$sousRoute = isset($parts[1]) ? $parts[1] : null;

$method = $_SERVER['REQUEST_METHOD'];
$body = json_decode(file_get_contents('php://input'), true);

$ctrl = RencontreControleur::getInstance();

// ---- Helpers ----
function rencontreToArray($r): array {
    return [
        'rencontreId'   => $r->getRencontreId(),
        'dateHeure'     => $r->getDateEtHeure()->format('Y-m-d H:i:s'),
        'equipeAdverse' => $r->getEquipeAdverse(),
        'adresse'       => $r->getAdresse(),
        'lieu'          => $r->getLieu()?->name,
        'resultat'      => $r->getResultat()?->name,
        'estPassee'     => $r->estPassee(),
    ];
}

// ---- Routage ----
try {
    // PATCH /api/rencontre/{id}/resultat
    if ($method === 'PATCH' && $id !== null && $sousRoute === 'resultat') {
        if (empty($body['resultat'])) {
            http_response_code(400);
            echo json_encode(['erreur' => 'Champ "resultat" requis (VICTOIRE, DEFAITE, NUL)']);
            exit();
        }
        if (!RencontreResultat::fromName($body['resultat'])) {
            http_response_code(400);
            echo json_encode(['erreur' => 'Valeur invalide. Valeurs acceptées : VICTOIRE, DEFAITE, NUL']);
            exit();
        }
        $ok = $ctrl->enregistrerResultat($id, $body['resultat']);
        if ($ok) {
            http_response_code(200);
            echo json_encode(['message' => 'Résultat enregistré']);
        } else {
            http_response_code(400);
            echo json_encode(['erreur' => 'Impossible d\'enregistrer le résultat (rencontre non passée ?)']);
        }
        exit();
    }

    // GET /api/rencontre
    if ($method === 'GET' && $id === null) {
        $rencontres = $ctrl->listerToutesLesRencontres();
        echo json_encode(array_map('rencontreToArray', $rencontres));
        exit();
    }

    // GET /api/rencontre/{id}
    if ($method === 'GET' && $id !== null) {
        try {
            $rencontre = $ctrl->getRenconterById($id);
        } catch (Throwable $e) {
            http_response_code(404);
            echo json_encode(['erreur' => 'Rencontre introuvable']);
            exit();
        }
        echo json_encode(rencontreToArray($rencontre));
        exit();
    }

    // POST /api/rencontre
    if ($method === 'POST') {
        $champsRequis = ['dateHeure', 'equipeAdverse', 'adresse', 'lieu'];
        foreach ($champsRequis as $champ) {
            if (empty($body[$champ])) {
                http_response_code(400);
                echo json_encode(['erreur' => "Champ \"$champ\" requis"]);
                exit();
            }
        }
        $lieu = RencontreLieu::fromName($body['lieu']);
        if (!$lieu) {
            http_response_code(400);
            echo json_encode(['erreur' => 'Valeur "lieu" invalide. Valeurs acceptées : DOMICILE, EXTERIEUR']);
            exit();
        }
        $dateHeure = new DateTime($body['dateHeure']);
        $ok = $ctrl->ajouterRencontre($dateHeure, $body['equipeAdverse'], $body['adresse'], $lieu);
        if ($ok) {
            http_response_code(201);
            echo json_encode(['message' => 'Rencontre créée']);
        } else {
            http_response_code(400);
            echo json_encode(['erreur' => 'La date doit être dans le futur']);
        }
        exit();
    }

    // PUT /api/rencontre/{id}
    if ($method === 'PUT' && $id !== null) {
        $champsRequis = ['dateHeure', 'equipeAdverse', 'adresse', 'lieu'];
        foreach ($champsRequis as $champ) {
            if (empty($body[$champ])) {
                http_response_code(400);
                echo json_encode(['erreur' => "Champ \"$champ\" requis"]);
                exit();
            }
        }
        $lieu = RencontreLieu::fromName($body['lieu']);
        if (!$lieu) {
            http_response_code(400);
            echo json_encode(['erreur' => 'Valeur "lieu" invalide. Valeurs acceptées : DOMICILE, EXTERIEUR']);
            exit();
        }
        $dateHeure = new DateTime($body['dateHeure']);
        $ok = $ctrl->modifierRencontre($id, $dateHeure, $body['equipeAdverse'], $body['adresse'], $lieu);
        if ($ok) {
            http_response_code(200);
            echo json_encode(['message' => 'Rencontre modifiée']);
        } else {
            http_response_code(400);
            echo json_encode(['erreur' => 'Modification impossible (rencontre passée ou date invalide)']);
        }
        exit();
    }

    // DELETE /api/rencontre/{id}
    if ($method === 'DELETE' && $id !== null) {
        $ok = $ctrl->supprimerRencontre($id);
        if ($ok) {
            http_response_code(200);
            echo json_encode(['message' => 'Rencontre supprimée']);
        } else {
            http_response_code(400);
            echo json_encode(['erreur' => 'Suppression impossible (résultat déjà enregistré ?)']);
        }
        exit();
    }

    http_response_code(404);
    echo json_encode(['erreur' => 'Route non trouvée']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur serveur : ' . $e->getMessage()]);
}