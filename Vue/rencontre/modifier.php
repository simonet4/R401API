<h1>Modifier une rencontre</h1>

<?php

use R301\Vue\Component\Formulaire;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: /rencontre');
    die();
}

$erreur = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['dateHeure'])
        && isset($_POST['equipeAdverse'])
        && isset($_POST['adresse'])
        && isset($_POST['lieu'])
) {
    $result = api_put('/api/rencontre/' . $id, [
        'dateHeure' => date('Y-m-d H:i:s', strtotime((string)$_POST['dateHeure'])),
        'equipeAdverse' => (string)$_POST['equipeAdverse'],
        'adresse' => (string)$_POST['adresse'],
        'lieu' => (string)$_POST['lieu'],
    ]);

    if ($result['ok']) {
        header('Location: /rencontre');
        die();
    } else {
        $erreur = $result['error'];
    }
}

$response = api_get('/api/rencontre/' . $id);
if (!$response['ok'] || !is_array($response['data'])) {
    header('Location: /rencontre');
    die();
}

$rencontre = $response['data'];
$valueDate = isset($rencontre['dateHeure']) ? date('Y-m-d\TH:i', strtotime((string)$rencontre['dateHeure'])) : '';

$formulaire = new Formulaire('/rencontre/modifier?id=' . $id);
$formulaire->setDateTime('Date', 'dateHeure', date('Y-m-d\TH:i'), $valueDate);
$formulaire->setText('Equipe adverse', 'equipeAdverse', '', (string)($rencontre['equipeAdverse'] ?? ''));
$formulaire->setText('Adresse', 'adresse', '', (string)($rencontre['adresse'] ?? ''));
$formulaire->setSelect('Lieu', ['DOMICILE', 'EXTERIEUR'], 'lieu', (string)($rencontre['lieu'] ?? ''));
$formulaire->addButton('Submit', 'update', 'Valider', 'Modifier');
echo $formulaire;

if ($erreur !== null) {
    echo '<p>' . htmlspecialchars($erreur) . '</p>';
}