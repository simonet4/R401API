<?php

use R301\Vue\Component\Formulaire;

if (!isset($_GET['id'])) {
    header('Location: /joueur');
    die();
$id = (int)$_GET['id'];
$response = api_get('/api/joueur/' . $id);
if (!$response['ok'] || !is_array($response['data'])) {
    header('Location: /joueur');
    die();
}

$joueur = is_array($response['data']['joueur'] ?? null) ? $response['data']['joueur'] : [];
$commentaires = is_array($response['data']['commentaires'] ?? null) ? $response['data']['commentaires'] : [];
?>

<h1>Commentaires de <?php echo htmlspecialchars(trim((string)($joueur['nom'] ?? '') . ' ' . (string)($joueur['prenom'] ?? ''))); ?></h1>

<?php
$form = new Formulaire("commentaire/ajouter");
$form->addTextArea("contenu");
$form->addHiddenInput("joueurId", (string)$id);
$form->addButton("submit", "create", "Publier le commentaire", "Publier le commentaire");
echo $form;

usort($commentaires, function ($a, $b) {
    return strtotime((string)($b['date'] ?? '')) <=> strtotime((string)($a['date'] ?? ''));
});

?>
<div class="container">
    <table>
        <tr>
            <th style="min-width: 100px; width: 1%">Date</th>
            <th style="width: 80%">Commentaire</th>
            <th style="width: 1%"></th>
        </tr>
        <?php foreach ($commentaires as $commentaire): ?>
        <form action="/joueur/commentaire/supprimer" method="post">
            <input type="hidden" name="commentaireId" value="<?php echo (int)($commentaire['commentaireId'] ?? 0); ?>" />
            <input type="hidden" name="joueurId" value="<?php echo $id; ?>" />
            <tr>
                <td><?php echo isset($commentaire['date']) ? date('d/m/Y H:i', strtotime((string)$commentaire['date'])) : ''; ?></td>
                <td><?php echo htmlspecialchars((string)($commentaire['contenu'] ?? '')); ?></td>
                <td class="actions">
                    <button class="delete" type="submit">Supprimer</button>
                </td>
            </tr>
        </form>
        <?php endforeach; ?>
    </table>
</div>
