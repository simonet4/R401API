<h1>Ajouter une rencontre</h1>

<?php

use R301\Vue\Component\Formulaire;

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['dateHeure'])
        && isset($_POST['equipeAdverse'])
        && isset($_POST['adresse'])
        && isset($_POST['lieu'])
) {
    $result = api_post('/api/rencontre', [
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

$formulaire = new Formulaire('/rencontre/ajouter');
$formulaire->setDateTime('Date', 'dateHeure', date('Y-m-d\TH:i'));
$formulaire->setText('Equipe adverse', 'equipeAdverse');
$formulaire->setText('Adresse', 'adresse');
$formulaire->setSelect('Lieu', ['DOMICILE', 'EXTERIEUR'], 'lieu');
$formulaire->addButton('Submit', 'create', 'Valider', 'Valider');
echo $formulaire;

if ($erreur !== null) {
    echo '<p>' . htmlspecialchars($erreur) . '</p>';
}