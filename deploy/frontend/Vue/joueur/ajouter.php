<h1>Ajouter un joueur</h1>
<?php

use R301\Vue\Component\Formulaire;

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
    $result = api_post('/api/joueur', [
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

$formulaire = new Formulaire('/joueur/ajouter');
$formulaire->setText('Nom', 'nom');
$formulaire->setText('Prenom', 'prenom');
$formulaire->setText('Numéro de license', 'numeroDeLicence', '00042');
$formulaire->setDate('Date de naissance', 'dateDeNaissance');
$formulaire->setText('Taille (en cm)', 'tailleEnCm');
$formulaire->setText('Poids (en kg)', 'poidsEnKg');
$formulaire->setSelect('Statut', ['ACTIF', 'BLESSE', 'ABSENT', 'SUSPENDU'], 'statut');
$formulaire->addButton('Submit', 'create', 'valider', 'Valider');
echo $formulaire;

if ($erreur !== null) {
    echo '<p>' . htmlspecialchars($erreur) . '</p>';
}
