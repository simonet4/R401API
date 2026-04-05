<h1>Modifier un joueur</h1>
<?php

use R301\Vue\Component\Formulaire;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: /joueur');
    die();
}

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['nom'])
    && isset($_POST['prenom'])
    && isset($_POST['numeroDeLicence'])
    && isset($_POST['dateDeNaissance'])
    && isset($_POST['tailleEnCm'])
    && isset($_POST['poidsEnKg'])
    && isset($_POST['statut'])
) {
    $result = api_put('/api/joueur/' . $id, [
        'nom' => (string)$_POST['nom'],
        'prenom' => (string)$_POST['prenom'],
        'numeroDeLicence' => (string)$_POST['numeroDeLicence'],
        'dateDeNaissance' => (string)$_POST['dateDeNaissance'],
        'tailleEnCm' => (int)$_POST['tailleEnCm'],
        'poidsEnKg' => (int)$_POST['poidsEnKg'],
        'statut' => (string)$_POST['statut'],
    ]);

    if ($result['ok']) {
        header('Location: /joueur');
        die();
    } else {
        $erreur = $result['error'];
    }
}

$response = api_get('/api/joueur/' . $id);
if (!$response['ok'] || !is_array($response['data']['joueur'] ?? null)) {
    header('Location: /joueur');
    die();
}

$joueur = $response['data']['joueur'];

$formulaire = new Formulaire('/joueur/modifier?id=' . $id);
$formulaire->setText('Nom', 'nom', '', (string)($joueur['nom'] ?? ''));
$formulaire->setText('Prenom', 'prenom', '', (string)($joueur['prenom'] ?? ''));
$formulaire->setText('Numéro de license', 'numeroDeLicence', '00042', (string)($joueur['numeroDeLicence'] ?? ''));
$formulaire->setDate('Date de naissance', 'dateDeNaissance', (string)($joueur['dateDeNaissance'] ?? ''));
$formulaire->setText('Taille (en cm)', 'tailleEnCm', '', (string)($joueur['tailleEnCm'] ?? ''));
$formulaire->setText('Poids (en Kg)', 'poidsEnKg', '', (string)($joueur['poidsEnKg'] ?? ''));
$formulaire->setSelect('Statut', ['ACTIF', 'BLESSE', 'ABSENT', 'SUSPENDU'], 'statut', (string)($joueur['statut'] ?? ''));
$formulaire->addButton('Submit', 'update', 'modifier', 'Modifier');
echo $formulaire;

if ($erreur !== null) {
    echo '<p>' . htmlspecialchars($erreur) . '</p>';
}