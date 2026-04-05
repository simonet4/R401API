<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['commentaireId']) && isset($_POST['joueurId'])) {
        api_delete('/api/joueur/' . (int)$_POST['joueurId'] . '/commentaire/' . (int)$_POST['commentaireId']);
    }
}

if (isset($_POST['joueurId'])) {
    header('Location: /joueur/commentaire?id='.$_POST['joueurId']);
} else {
    header('Location: /joueur');
}