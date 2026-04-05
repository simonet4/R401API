<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
    && isset($_POST['poste'])
    && isset($_POST['titulaireOuRemplacant'])
    && isset($_POST['joueurId']) && $_POST['joueurId'] !== ""
    && isset($_POST['rencontreId'])
) {
    switch($_POST['action']) {
        case "create":
            api_post('/api/participation', [
                'joueurId' => (int)$_POST['joueurId'],
                'rencontreId' => (int)$_POST['rencontreId'],
                'poste' => (string)$_POST['poste'],
                'titulaireOuRemplacant' => (string)$_POST['titulaireOuRemplacant'],
            ]);
            break;
        case "update":
            if (isset($_POST['participationId'])) {
                api_put('/api/participation/' . (int)$_POST['participationId'], [
                    'joueurId' => (int)$_POST['joueurId'],
                    'poste' => (string)$_POST['poste'],
                    'titulaireOuRemplacant' => (string)$_POST['titulaireOuRemplacant'],
                ]);
            }
            break;
        case "delete":
            if (isset($_POST['participationId'])) {
                api_delete('/api/participation/' . (int)$_POST['participationId']);
            }
            break;
        default:
    }
    header('Location: /feuilleDeMatch/feuilleDeMatch?id='.$_POST['rencontreId']);
} else {
    if (isset($_POST['rencontreId'])) {
        header('Location: /feuilleDeMatch/feuilleDeMatch?id='.$_POST['rencontreId']);
    } else {
        header('Location: /rencontre');
    }
}