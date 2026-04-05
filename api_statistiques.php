<?php
/**
 * API de demande de Statistiques
 *
 * Routes :
 *   GET /api/statistiques/equipe           -> statistiques de l'équipe
 *   GET /api/statistiques/joueurs          -> statistiques de tous les joueurs
 *   GET /api/statistiques/dashboard        -> statistiques équipe + joueurs
 *   GET /api/statistiques/mes-evaluations  -> évaluations du joueur connecté
 */

require_once __DIR__ . '/Psr4AutoloaderClass.php';
require_once __DIR__ . '/api_auth_client.php';
use R301\Psr4AutoloaderClass;

$loader = new Psr4AutoloaderClass;
$loader->register();
$loader->addNamespace('R301', __DIR__);

use R301\Controleur\StatistiquesControleur;
use R301\Controleur\JoueurControleur;
use R301\Controleur\ParticipationControleur;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

error_log("[API_STATS] Requete recue: method={$_SERVER['REQUEST_METHOD']}, uri={$_SERVER['REQUEST_URI']}");

// ---- Vérification du token JWT via API d'auth ----
$bearerToken = get_bearer_token();
error_log("[API_STATS] Bearer token: " . ($bearerToken ? substr($bearerToken, 0, 20) . '...' : 'ABSENT'));

$tokenStatus = verify_token_with_auth_api($bearerToken);
error_log("[API_STATS] Token status: valid=" . ($tokenStatus['valid'] ? 'OUI' : 'NON') . ", error=" . ($tokenStatus['error'] ?? 'aucune'));

if (!$tokenStatus['valid']) {
    http_response_code($tokenStatus['status']);
    echo json_encode([
        'erreur' => $tokenStatus['error'],
        'debug_auth' => $tokenStatus['debug'] ?? [],
    ]);
    exit();
}

// ---- Parsing de l'URL ----
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = preg_replace('#^.*?/api/statistiques#', '', $uri);
$sousRoute = trim($uri, '/'); // "equipe", "joueurs" ou "mes-evaluations"

$method = $_SERVER['REQUEST_METHOD'];

$ctrl = StatistiquesControleur::getInstance();
$participationCtrl = ParticipationControleur::getInstance();

// ---- Routage ----
try {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['erreur' => 'Méthode non autorisée. Seul GET est accepté.']);
        exit();
    }

    // GET /api/statistiques/equipe
    if ($sousRoute === 'equipe') {
        $payload = is_array($tokenStatus['payload'] ?? null) ? $tokenStatus['payload'] : [];
        $role = strtolower((string)($payload['role'] ?? ''));
        if ($role !== 'joueur' && $role !== 'admin') {
            http_response_code(403);
            echo json_encode(['erreur' => 'Accès interdit']);
            exit();
        }

        $stats = $ctrl->getStatistiquesEquipe();
        echo json_encode([
            'nbVictoires'            => $stats->nbVictoires(),
            'nbNuls'                 => $stats->nbNuls(),
            'nbDefaites'             => $stats->nbDefaites(),
            'pourcentageDeVictoires' => $stats->pourcentageDeVictoires(),
            'pourcentageDeNuls'      => $stats->pourcentageDeNuls(),
            'pourcentageDeDefaites'  => $stats->pourcentageDeDefaites(),
        ]);
        exit();
    }

    // GET /api/statistiques/dashboard
    if ($sousRoute === 'dashboard') {
        $payload = is_array($tokenStatus['payload'] ?? null) ? $tokenStatus['payload'] : [];
        $role = strtolower((string)($payload['role'] ?? ''));
        if ($role !== 'joueur' && $role !== 'admin') {
            http_response_code(403);
            echo json_encode(['erreur' => 'Accès interdit']);
            exit();
        }

        $statsEquipe = $ctrl->getStatistiquesEquipe();
        $statsJoueurs = $ctrl->getStatistiquesJoueurs();
        $joueurs = JoueurControleur::getInstance()->listerTousLesJoueurs();

        $joueursPayload = [];
        foreach ($joueurs as $joueur) {
            $joueursPayload[] = [
                'joueurId'                    => $joueur->getJoueurId(),
                'nom'                         => $joueur->getNom(),
                'prenom'                      => $joueur->getPrenom(),
                'statutActuel'                => $joueur->getStatut()?->name,
                'moyenneDesEvaluations'       => $statsJoueurs->moyenneDesEvaluations($joueur),
                'nbTitularisations'           => $statsJoueurs->nbTitularisations($joueur),
                'nbRemplacant'                => $statsJoueurs->nbRemplacant($joueur),
                'nbRencontresConsecutives'    => $statsJoueurs->nbRencontresConsecutivesADate($joueur),
                'postePrefere'                => $statsJoueurs->posteLePlusPerformant($joueur)?->name,
                'posteLePlusPerformant'       => $statsJoueurs->posteLePlusPerformant($joueur)?->name,
                'pourcentageDeMatchsGagnes'   => $statsJoueurs->pourcentageDeMatchsGagnes($joueur),
            ];
        }

        echo json_encode([
            'equipe' => [
                'nbVictoires'            => $statsEquipe->nbVictoires(),
                'nbNuls'                 => $statsEquipe->nbNuls(),
                'nbDefaites'             => $statsEquipe->nbDefaites(),
                'pourcentageDeVictoires' => $statsEquipe->pourcentageDeVictoires(),
                'pourcentageDeNuls'      => $statsEquipe->pourcentageDeNuls(),
                'pourcentageDeDefaites'  => $statsEquipe->pourcentageDeDefaites(),
            ],
            'joueurs' => $joueursPayload,
        ]);
        exit();
    }

    // GET /api/statistiques/joueurs
    if ($sousRoute === 'joueurs') {
        $statsJoueurs = $ctrl->getStatistiquesJoueurs();
        $joueurs = JoueurControleur::getInstance()->listerTousLesJoueurs();

        $result = [];
        foreach ($joueurs as $joueur) {
            $result[] = [
                'joueurId'                    => $joueur->getJoueurId(),
                'nom'                         => $joueur->getNom(),
                'prenom'                      => $joueur->getPrenom(),
                'statutActuel'                => $joueur->getStatut()?->name,
                'moyenneDesEvaluations'       => $statsJoueurs->moyenneDesEvaluations($joueur),
                'nbTitularisations'           => $statsJoueurs->nbTitularisations($joueur),
                'nbRemplacant'                => $statsJoueurs->nbRemplacant($joueur),
                'nbRencontresConsecutives'    => $statsJoueurs->nbRencontresConsecutivesADate($joueur),
                'postePrefere'                => $statsJoueurs->posteLePlusPerformant($joueur)?->name,
                'posteLePlusPerformant'       => $statsJoueurs->posteLePlusPerformant($joueur)?->name,
                'pourcentageDeMatchsGagnes'   => $statsJoueurs->pourcentageDeMatchsGagnes($joueur),
            ];
        }
        echo json_encode($result);
        exit();
    }

    // GET /api/statistiques/mes-evaluations
    if ($sousRoute === 'mes-evaluations') {
        $payload = is_array($tokenStatus['payload'] ?? null) ? $tokenStatus['payload'] : [];
        $role = strtolower((string)($payload['role'] ?? ''));

        if ($role !== 'joueur') {
            http_response_code(403);
            echo json_encode(['erreur' => 'Accès réservé aux joueurs connectés']);
            exit();
        }

        $joueurIdDepuisToken = isset($payload['joueurId']) ? (int)$payload['joueurId'] : null;
        $username = (string)($payload['sub'] ?? '');

        $joueurConnecte = null;
        foreach (JoueurControleur::getInstance()->listerTousLesJoueurs() as $joueur) {
            if (($joueurIdDepuisToken !== null && $joueur->getJoueurId() === $joueurIdDepuisToken)
                || ($username !== '' && $joueur->getNumeroDeLicence() === $username)
            ) {
                $joueurConnecte = $joueur;
                break;
            }
        }

        if ($joueurConnecte === null) {
            http_response_code(403);
            echo json_encode(['erreur' => 'Impossible de déterminer le joueur connecté']);
            exit();
        }

        $evaluations = [];
        foreach ($participationCtrl->listerToutesLesParticipations() as $participation) {
            if ($participation->getParticipant()->getJoueurId() !== $joueurConnecte->getJoueurId()) {
                continue;
            }

            if (!$participation->getRencontre()->joue()) {
                continue;
            }

            $evaluations[] = [
                'rencontreId' => $participation->getRencontre()->getRencontreId(),
                'dateHeure' => $participation->getRencontre()->getDateEtHeure()->format('Y-m-d H:i:s'),
                'equipeAdverse' => $participation->getRencontre()->getEquipeAdverse(),
                'poste' => $participation->getPoste()->name,
                'titulaireOuRemplacant' => $participation->getTitulaireOuRemplacant()->name,
                'evaluationCoach' => $participation->getPerformance()?->name,
            ];
        }

        echo json_encode([
            'joueurId' => $joueurConnecte->getJoueurId(),
            'numeroDeLicence' => $joueurConnecte->getNumeroDeLicence(),
            'nom' => $joueurConnecte->getNom(),
            'prenom' => $joueurConnecte->getPrenom(),
            'evaluations' => $evaluations,
        ]);
        exit();
    }

    http_response_code(404);
    echo json_encode(['erreur' => 'Route non trouvée. Utilisez /api/statistiques/equipe, /api/statistiques/joueurs ou /api/statistiques/mes-evaluations']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur serveur : ' . $e->getMessage()]);
}