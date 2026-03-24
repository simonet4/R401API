
<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['action'])
        && isset($_POST['rencontreId'])
) {
    $rencontreId = (int)$_POST['rencontreId'];
    switch($_POST['action']) {
        case "ouvrirFeuilleDeMatch":
            header('Location: /feuilleDeMatch/feuilleDeMatch?id=' . $rencontreId);
            die();
        case "ouvrirEvaluations":
            header('Location: /feuilleDeMatch/evaluation?id=' . $rencontreId);
            die();
        case "modifier":
            header('Location: /rencontre/modifier?id=' . $rencontreId);
            die();
        case "enregistrerResultat":
            if (isset($_POST['resultat'])) {
                api_patch('/api/rencontre/' . $rencontreId . '/resultat', ['resultat' => (string)$_POST['resultat']]);
                header('Location: /rencontre');
                die();
            }
            break;
        case "supprimer":
            api_delete('/api/rencontre/' . $rencontreId);
            header('Location: /rencontre');
            die();
    }
} else {

$response = api_get('/api/rencontre', false);
$rencontres = ($response['ok'] && is_array($response['data'])) ? $response['data'] : [];


?>
<h1>Rencontres</h1>
<div class="overflow container">
    <table>
        <tr>
            <th style="width:10%">Date</th>
            <th style="width:10%">Equipe Adverse</th>
            <th style="width:20%">Adresse</th>
            <th style="width:8%">Lieu</th>
            <th style="width:8%">Résultat</th>
            <th style="width:20%; min-width: 200px;">Actions</th>
        </tr>
        <?php foreach ($rencontres as $rencontre): ?>
        <form action="rencontre" method="post">
            <tr>
                <?php
                    $id = (int)($rencontre['rencontreId'] ?? 0);
                    $estPassee = (bool)($rencontre['estPassee'] ?? false);
                    $resultat = (string)($rencontre['resultat'] ?? '');
                ?>
                <input type="hidden" name="rencontreId" value="<?php echo $id; ?>" />
                <td><?php echo isset($rencontre['dateHeure']) ? date('d/m/Y H:i', strtotime((string)$rencontre['dateHeure'])) : ''; ?></td>
                <td><?php echo htmlspecialchars((string)($rencontre['equipeAdverse'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars((string)($rencontre['adresse'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars((string)($rencontre['lieu'] ?? '')); ?></td>
                <?php if ($estPassee && $resultat === ''): ?>
                    <td>
                        <select name="resultat">
                            <option value="VICTOIRE">VICTOIRE</option>
                            <option value="DEFAITE">DEFAITE</option>
                            <option value="NUL">NUL</option>
                        </select>
                    </td>
                <?php else: ?>
                    <td><?php echo htmlspecialchars($resultat); ?></td>
                <?php endif; ?>
                <td class="actions">
                    <?php if (!$estPassee): ?>
                    <button name="action" value="ouvrirFeuilleDeMatch" class="info">Feuilles de match</button>
                    <button name="action" value="modifier" class="update">Modifier</button>
                    <button name="action" value="supprimer" class="delete">Supprimer</button>
                    <?php else: ?>
                    <button name="action" value="ouvrirEvaluations" class="info">Évaluations</button>
                    <?php if ($estPassee && $resultat === ''): ?>
                    <button class="create" name="action" value="enregistrerResultat">Enregistrer résultat</button>
                    <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
        </form>
        <?php endforeach; ?>
    </table>
</div>
<?php } ?>