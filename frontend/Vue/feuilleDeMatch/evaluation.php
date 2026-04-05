
<?php

$postes = ['TOPLANE', 'JUNGLE', 'MIDLANE', 'ADCARRY', 'SUPPORT'];
$types = ['TITULAIRE', 'REMPLACANT'];
$performances = ['EXCELLENTE', 'BONNE', 'MOYENNE', 'MAUVAISE', 'CATASTROPHIQUE'];

if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['action'])
        && isset($_POST['participationId'])
        && isset($_POST['rencontreId'])
        && isset($_POST['performance'])
) :
    switch($_POST['action']) {
        case "update":
            api_patch('/api/participation/' . (int)$_POST['participationId'] . '/performance', [
                'performance' => (string)$_POST['performance'],
            ]);
            break;
        case "delete":
            api_delete('/api/participation/' . (int)$_POST['participationId'] . '/performance');
            break;
    }

    header('Location: /feuilleDeMatch/evaluation?id=' . $_POST['rencontreId']);
    die();
else :
    if (!isset($_GET['id'])) :
        header("Location: /rencontre"); die();
    else :
        $rencontreId = (int)$_GET['id'];
        $respParticipations = api_get('/api/participation/rencontre/' . $rencontreId);
        $participations = ($respParticipations['ok'] && is_array($respParticipations['data'])) ? $respParticipations['data'] : [];

        $findParticipant = function (string $poste, string $type) use ($participations): ?array {
            foreach ($participations as $p) {
                if (($p['poste'] ?? null) === $poste && ($p['titulaireOuRemplacant'] ?? null) === $type) {
                    return $p;
                }
            }
            return null;
        };

        $estEvalue = count($participations) > 0;
        foreach ($participations as $p) {
            if (empty($p['performance'])) {
                $estEvalue = false;
                break;
            }
        }
?>
<div style="display: flex; flex-direction: row; justify-content: space-between; align-items: center; padding-right: 30px">
    <h1>Évaluations</h1>
    <?php if($estEvalue) : ?>
        <div class="etat-feuille-de-match feuille-de-match-complete">
            TERMINÉES
        </div>
    <?php else: ?>
        <div class="etat-feuille-de-match feuille-de-match-incomplete">
            INCOMPLÈTES
        </div>
    <?php endif; ?>
</div>

<div class="container" style="display: flex; flex-direction: row; justify-content: space-between">
    <?php foreach ($types as $titulaireOuRemplacant) : ?>
        <table style="width: 49.5%">
            <caption>
                <?php echo $titulaireOuRemplacant . 'S'; ?>
            </caption>
            <tr>
                <th style="width:15%">Poste</th>
                <th style="width:25%">Joueur</th>
                <th style="width:15%">Performance</th>
                <th style="width:20%">Mettre à jour la performance</th>
                <th style="width:25%; min-width: 150px;"></th>
            </tr>

            <?php
            foreach ($postes as $poste):
                $participant = $findParticipant($poste, $titulaireOuRemplacant);
                ?>
                <form action="/feuilleDeMatch/evaluation" method="post">
                    <tr>
                        <input type="hidden" name="rencontreId" value="<?php echo $rencontreId; ?>" />
                        <input type="hidden" name="participationId" value="<?php if($participant !== null) echo (int)$participant['participationId']; ?>" />
                        <td><?php echo $poste; ?></td>
                        <td><?php if($participant !== null) echo htmlspecialchars(trim((string)($participant['joueurNom'] ?? '') . ' ' . (string)($participant['joueurPrenom'] ?? ''))); ?></td>
                        <td><?php if(!empty($participant['performance'])) echo htmlspecialchars((string)$participant['performance']); ?></td>
                        <td>
                            <select name="performance">
                                <?php foreach ($performances as $performance): ?>
                                    <option value="<?php echo $performance; ?>" <?php echo (($participant['performance'] ?? '') === $performance) ? 'selected' : ''; ?>><?php echo $performance; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <?php if($participant !== null) : ?>
                        <td class="actions">
                                <button class="update" type="submit" name="action" value="update">Mettre à jour</button>
                                <button class="delete" type="submit" name="action" value="delete" style="margin-left: 8px">Supprimer</button>
                        </td>
                        <?php else: ?>
                        <td></td>
                        <?php endif; ?>
                    </tr>
                </form>
            <?php endforeach; ?>
        </table>
    <?php endforeach; ?>
</div>
<?php endif; endif; ?>