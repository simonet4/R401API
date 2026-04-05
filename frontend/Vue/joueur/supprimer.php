<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    api_delete('/api/joueur/' . (int)$_POST['id']);
}

header('Location: /joueur');