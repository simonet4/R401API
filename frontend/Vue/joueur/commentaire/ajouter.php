<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['joueurId'])
    && isset($_POST['contenu'])
) {
    api_post('/api/joueur/' . (int)$_POST['joueurId'] . '/commentaire', [
        'contenu' => (string)$_POST['contenu'],
    ]);
}

if (isset($_POST['joueurId'])) {
    header('Location: /joueur/commentaire?id='.$_POST['joueurId']);
} else {
    header('Location: /joueur');
}