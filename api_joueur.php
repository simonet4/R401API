<?php
/**
 * API de gestion des Joueurs
 *
 * Routes :
 *   GET    /api/joueur                    -> liste des joueurs
 *   GET    /api/joueur/{id}               -> détail d'un joueur
 *   POST   /api/joueur                    -> créer un joueur
 *   PUT    /api/joueur/{id}               -> modifier un joueur
 *   DELETE /api/joueur/{id}               -> supprimer un joueur (si jamais participé)
 *   POST   /api/joueur/{id}/commentaire   -> ajouter un commentaire
 */

require_once __DIR__ . '/Psr4AutoloaderClass.php';
require_once __DIR__ . '/api_auth_client.php';

use R301\Psr4AutoloaderClass;
use R301\Controleur\JoueurControleur;
use R301\Controleur\ParticipationControleur;
use R301\Controleur\CommentaireControleur;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$tokenStatus = verify_token_with_auth_api(get_bearer_token());
if (!$tokenStatus['valid']) {
    http_response_code($tokenStatus['status']);
    echo json_encode(['erreur' => $tokenStatus['error']]);
    exit();
}

$loader = new Psr4AutoloaderClass;
$loader->register();
$loader->addNamespace('R301', __DIR__);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = preg_replace('#^.*?/api/joueur#', '', $uri);
$parts = explode('/', trim($uri, '/'));

$id = isset($parts[0]) && is_numeric($parts[0]) ? (int)$parts[0] : null;
$sousRoute = isset($parts[1]) ? $parts[1] : null;

$method = $_SERVER['REQUEST_METHOD'];
$body = json_decode(file_get_contents('php://input'), true);

$joueursCtrl = JoueurControleur::getInstance();
$participationCtrl = ParticipationControleur::getInstance();
$commentaireCtrl = CommentaireControleur::getInstance();

function joueurToArray($j): array {
    return [
        'joueurId' => $j->getJoueurId(),
        'numeroDeLicence' => $j->getNumeroDeLicence(),
        'nom' => $j->getNom(),
        'prenom' => $j->getPrenom(),
        'dateDeNaissance' => $j->getDateDeNaissance()->format('Y-m-d'),
        'tailleEnCm' => $j->getTailleEnCm(),
        'poidsEnKg' => $j->getPoidsEnKg(),
        'statut' => $j->getStatut()->name,
    ];
}

try {
    if ($method === 'GET' && $id === null) {
        $joueurs = $joueursCtrl->listerTousLesJoueurs();
        echo json_encode(array_map('joueurToArray', $joueurs));
        exit();
    }

    if ($method === 'GET' && $id !== null) {
        try {
            $joueur = $joueursCtrl->getJoueurById($id);
        } catch (Throwable $e) {
            http_response_code(404);
            echo json_encode(['erreur' => 'Joueur introuvable']);
            exit();
        }

        $commentaires = $commentaireCtrl->listerLesCommentairesDuJoueur($joueur);
        echo json_encode([
            'joueur' => joueurToArray($joueur),
            'commentaires' => array_map(function($c) {
                return [
                    'commentaireId' => $c->getCommentaireId(),
                    'contenu' => $c->getContenu(),
                    'date' => $c->getDate()->format('Y-m-d H:i:s')
                ];
            }, $commentaires)
        ]);
        exit();
    }

    if ($method === 'POST' && $id === null) {
        $required = ['nom', 'prenom', 'numeroDeLicence', 'dateDeNaissance', 'tailleEnCm', 'poidsEnKg', 'statut'];
        foreach ($required as $field) {
            if (!is_array($body) || !isset($body[$field]) || $body[$field] === '') {
                http_response_code(400);
                echo json_encode(['erreur' => "Champ \"$field\" requis"]);
                exit();
            }
        }

        $ok = $joueursCtrl->ajouterJoueur(
            (string)$body['nom'],
            (string)$body['prenom'],
            (string)$body['numeroDeLicence'],
            new DateTime((string)$body['dateDeNaissance']),
            (int)$body['tailleEnCm'],
            (int)$body['poidsEnKg'],
            (string)$body['statut']
        );

        if ($ok) {
            http_response_code(201);
            echo json_encode(['message' => 'Joueur créé']);
        } else {
            http_response_code(400);
            echo json_encode(['erreur' => 'Création impossible']);
        }
        exit();
    }

    if ($method === 'PUT' && $id !== null) {
        $required = ['nom', 'prenom', 'numeroDeLicence', 'dateDeNaissance', 'tailleEnCm', 'poidsEnKg', 'statut'];
        foreach ($required as $field) {
            if (!is_array($body) || !isset($body[$field]) || $body[$field] === '') {
                http_response_code(400);
                echo json_encode(['erreur' => "Champ \"$field\" requis"]);
                exit();
            }
        }

        $ok = $joueursCtrl->modifierJoueur(
            $id,
            (string)$body['nom'],
            (string)$body['prenom'],
            (string)$body['numeroDeLicence'],
            new DateTime((string)$body['dateDeNaissance']),
            (int)$body['tailleEnCm'],
            (int)$body['poidsEnKg'],
            (string)$body['statut']
        );

        if ($ok) {
            echo json_encode(['message' => 'Joueur modifié']);
        } else {
            http_response_code(400);
            echo json_encode(['erreur' => 'Modification impossible']);
        }
        exit();
    }

    if ($method === 'DELETE' && $id !== null && $sousRoute === null) {
        $aDejaParticipe = false;
        foreach ($participationCtrl->listerToutesLesParticipations() as $p) {
            if ($p->getParticipant()->getJoueurId() === $id) {
                $aDejaParticipe = true;
                break;
            }
        }

        if ($aDejaParticipe) {
            http_response_code(400);
            echo json_encode(['erreur' => 'Suppression interdite: le joueur a deja participe a un match']);
            exit();
        }

        $ok = $joueursCtrl->supprimerJoueur($id);
        if ($ok) {
            echo json_encode(['message' => 'Joueur supprimé']);
        } else {
            http_response_code(404);
            echo json_encode(['erreur' => 'Joueur introuvable']);
        }
        exit();
    }

    if ($method === 'POST' && $id !== null && $sousRoute === 'commentaire') {
        if (!is_array($body) || !isset($body['contenu']) || trim((string)$body['contenu']) === '') {
            http_response_code(400);
            echo json_encode(['erreur' => 'Champ "contenu" requis']);
            exit();
        }

        $ok = $commentaireCtrl->ajouterCommentaire((string)$body['contenu'], (string)$id);
        if ($ok) {
            http_response_code(201);
            echo json_encode(['message' => 'Commentaire ajouté']);
        } else {
            http_response_code(400);
            echo json_encode(['erreur' => 'Ajout du commentaire impossible']);
        }
        exit();
    }

    http_response_code(404);
    echo json_encode(['erreur' => 'Route non trouvée']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur serveur : ' . $e->getMessage()]);
}
