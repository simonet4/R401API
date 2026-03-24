<?php
/**
 * API de gestion des Participations (feuilles de match)
 *
 * Routes :
 *   GET    /api/participation                        -> lister toutes les participations
 *   GET    /api/participation/rencontre/{id}         -> feuille de match d'une rencontre
 *   POST   /api/participation                        -> assigner un joueur à une rencontre
 *   PUT    /api/participation/{id}                   -> modifier une participation
 *   DELETE /api/participation/{id}                   -> supprimer une participation
 *   PATCH  /api/participation/{id}/performance       -> mettre à jour la performance
 *   DELETE /api/participation/{id}/performance       -> supprimer la performance
 */

require_once __DIR__ . '/Psr4AutoloaderClass.php';
require_once __DIR__ . '/api_auth_client.php';
use R301\Psr4AutoloaderClass;

$loader = new Psr4AutoloaderClass;
$loader->register();
$loader->addNamespace('R301', __DIR__);

use R301\Controleur\ParticipationControleur;
use R301\Modele\Participation\Poste;
use R301\Modele\Participation\TitulaireOuRemplacant;
use R301\Modele\Participation\Performance;

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
$uri = preg_replace('#^.*?/api/participation#', '', $uri);
$parts = explode('/', trim($uri, '/'));

// Détection des sous-routes
$sousEntite = isset($parts[0]) && !is_numeric($parts[0]) ? $parts[0] : null; // ex: "rencontre"
$idSousEntite = isset($parts[1]) && is_numeric($parts[1]) ? (int)$parts[1] : null;
$id = isset($parts[0]) && is_numeric($parts[0]) ? (int)$parts[0] : null;
$sousRoute = isset($parts[1]) && !is_numeric($parts[1]) ? $parts[1] : null; // ex: "performance"

$method = $_SERVER['REQUEST_METHOD'];
$body = json_decode(file_get_contents('php://input'), true);

$ctrl = ParticipationControleur::getInstance();

function require_admin(array $tokenStatus): void {
    $payload = is_array($tokenStatus['payload'] ?? null) ? $tokenStatus['payload'] : [];
    $role = strtolower((string)($payload['role'] ?? ''));
    if ($role !== 'admin') {
        http_response_code(403);
        echo json_encode(['erreur' => 'Accès admin requis']);
        exit();
    }
}

// ---- Helpers ----
function participationToArray($p): array {
    return [
        'participationId'       => $p->getParticipationId(),
        'joueurId'              => $p->getParticipant()->getJoueurId(),
        'joueurNom'             => $p->getParticipant()->getNom(),
        'joueurPrenom'          => $p->getParticipant()->getPrenom(),
        'rencontreId'           => $p->getRencontre()->getRencontreId(),
        'poste'                 => $p->getPoste()->name,
        'titulaireOuRemplacant' => $p->getTitulaireOuRemplacant()->name,
        'performance'           => $p->getPerformance()?->name,
    ];
}

function feuilleDeMatchToArray($feuille): array {
    return array_map('participationToArray', $feuille->getParticipants());
}

// ---- Routage ----
try {

    // GET /api/participation/rencontre/{id}
    if ($method === 'GET' && $sousEntite === 'rencontre' && $idSousEntite !== null) {
        $feuille = $ctrl->getFeuilleDeMatch($idSousEntite);
        echo json_encode(feuilleDeMatchToArray($feuille));
        exit();
    }

    // GET /api/participation
    if ($method === 'GET' && $id === null && $sousEntite === null) {
        $participations = $ctrl->listerToutesLesParticipations();
        echo json_encode(array_map('participationToArray', $participations));
        exit();
    }

    // POST /api/participation
    if ($method === 'POST') {
        require_admin($tokenStatus);
        $champsRequis = ['joueurId', 'rencontreId', 'poste', 'titulaireOuRemplacant'];
        foreach ($champsRequis as $champ) {
            if (empty($body[$champ])) {
                http_response_code(400);
                echo json_encode(['erreur' => "Champ \"$champ\" requis"]);
                exit();
            }
        }
        $poste = Poste::fromName($body['poste']);
        if (!$poste) {
            http_response_code(400);
            echo json_encode(['erreur' => 'Valeur "poste" invalide. Valeurs acceptées : TOPLANE, JUNGLE, MIDLANE, ADCARRY, SUPPORT']);
            exit();
        }
        $titulaire = TitulaireOuRemplacant::fromName($body['titulaireOuRemplacant']);
        if (!$titulaire) {
            http_response_code(400);
            echo json_encode(['erreur' => 'Valeur "titulaireOuRemplacant" invalide. Valeurs acceptées : TITULAIRE, REMPLACANT']);
            exit();
        }
        $ok = $ctrl->assignerUnParticipant(
            (int)$body['joueurId'],
            (int)$body['rencontreId'],
            $poste,
            $titulaire
        );
        if ($ok) {
            http_response_code(201);
            echo json_encode(['message' => 'Participation créée']);
        } else {
            http_response_code(400);
            echo json_encode(['erreur' => 'Poste déjà occupé ou joueur déjà sur la feuille de match']);
        }
        exit();
    }

    // PUT /api/participation/{id}
    if ($method === 'PUT' && $id !== null && $sousRoute === null) {
        require_admin($tokenStatus);
        $champsRequis = ['joueurId', 'poste', 'titulaireOuRemplacant'];
        foreach ($champsRequis as $champ) {
            if (empty($body[$champ])) {
                http_response_code(400);
                echo json_encode(['erreur' => "Champ \"$champ\" requis"]);
                exit();
            }
        }
        $poste = Poste::fromName($body['poste']);
        if (!$poste) {
            http_response_code(400);
            echo json_encode(['erreur' => 'Valeur "poste" invalide. Valeurs acceptées : TOPLANE, JUNGLE, MIDLANE, ADCARRY, SUPPORT']);
            exit();
        }
        $titulaire = TitulaireOuRemplacant::fromName($body['titulaireOuRemplacant']);
        if (!$titulaire) {
            http_response_code(400);
            echo json_encode(['erreur' => 'Valeur "titulaireOuRemplacant" invalide. Valeurs acceptées : TITULAIRE, REMPLACANT']);
            exit();
        }
        $ok = $ctrl->modifierParticipation($id, $poste, $titulaire, (int)$body['joueurId']);
        if ($ok) {
            http_response_code(200);
            echo json_encode(['message' => 'Participation modifiée']);
        } else {
            http_response_code(400);
            echo json_encode(['erreur' => 'Modification impossible']);
        }
        exit();
    }

    // PATCH /api/participation/{id}/performance
    if ($method === 'PATCH' && $id !== null && $sousRoute === 'performance') {
        require_admin($tokenStatus);
        if (empty($body['performance'])) {
            http_response_code(400);
            echo json_encode(['erreur' => 'Champ "performance" requis (EXCELLENTE, BONNE, MOYENNE, MAUVAISE, CATASTROPHIQUE)']);
            exit();
        }
        if (Performance::fromName($body['performance']) === null) {
            http_response_code(400);
            echo json_encode(['erreur' => 'Valeur invalide. Valeurs acceptées : EXCELLENTE, BONNE, MOYENNE, MAUVAISE, CATASTROPHIQUE']);
            exit();
        }
        $ok = $ctrl->mettreAJourLaPerformance($id, $body['performance']);
        if ($ok) {
            http_response_code(200);
            echo json_encode(['message' => 'Performance mise à jour']);
        } else {
            http_response_code(400);
            echo json_encode(['erreur' => 'Mise à jour impossible (rencontre non passée ?)']);
        }
        exit();
    }

    // DELETE /api/participation/{id}/performance
    if ($method === 'DELETE' && $id !== null && $sousRoute === 'performance') {
        require_admin($tokenStatus);
        $ok = $ctrl->supprimerLaPerformance($id);
        if ($ok) {
            http_response_code(200);
            echo json_encode(['message' => 'Performance supprimée']);
        } else {
            http_response_code(400);
            echo json_encode(['erreur' => 'Suppression impossible (rencontre non passée ?)']);
        }
        exit();
    }

    // DELETE /api/participation/{id}
    if ($method === 'DELETE' && $id !== null && $sousRoute === null) {
        require_admin($tokenStatus);
        $ok = $ctrl->supprimerLaParticipation($id);
        if ($ok) {
            http_response_code(200);
            echo json_encode(['message' => 'Participation supprimée']);
        } else {
            http_response_code(400);
            echo json_encode(['erreur' => 'Suppression impossible']);
        }
        exit();
    }

    http_response_code(404);
    echo json_encode(['erreur' => 'Route non trouvée']);

} catch (RuntimeException $e) {
    http_response_code(404);
    echo json_encode(['erreur' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur serveur : ' . $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur serveur : ' . $e->getMessage()]);
}