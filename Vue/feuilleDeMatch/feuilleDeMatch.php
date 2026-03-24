<?php

use R301\Vue\Component\Select;

$postes = ['TOPLANE', 'JUNGLE', 'MIDLANE', 'ADCARRY', 'SUPPORT'];
$types = ['TITULAIRE', 'REMPLACANT'];

if (!isset($_GET['id'])) :
    header("Location: /rencontre");
else :
    $rencontreId = (int)$_GET['id'];

    $respParticipations = api_get('/api/participation/rencontre/' . $rencontreId);
    $participations = ($respParticipations['ok'] && is_array($respParticipations['data'])) ? $respParticipations['data'] : [];

    $respJoueurs = api_get('/api/joueur');
    $joueurs = ($respJoueurs['ok'] && is_array($respJoueurs['data'])) ? $respJoueurs['data'] : [];

    $joueursById = [];
    foreach ($joueurs as $j) {
        if (!isset($j['joueurId'])) {
            continue;
        }
        $joueursById[(int)$j['joueurId']] = $j;
    }

    $selectedIds = [];
    foreach ($participations as $p) {
        $selectedIds[(int)($p['joueurId'] ?? 0)] = true;
    }

    $joueursSelectionnables = [];
    foreach ($joueurs as $j) {
        $jid = (int)($j['joueurId'] ?? 0);
        $statut = (string)($j['statut'] ?? '');
        if ($jid > 0 && $statut === 'ACTIF' && !isset($selectedIds[$jid])) {
            $joueursSelectionnables[] = $j;
        }
    }

    $findParticipant = function (string $poste, string $type) use ($participations): ?array {
        foreach ($participations as $p) {
            if (($p['poste'] ?? null) === $poste && ($p['titulaireOuRemplacant'] ?? null) === $type) {
                return $p;
            }
        }
        return null;
    };

    $feuilleComplete = true;
    foreach ($postes as $poste) {
        $titulaire = $findParticipant($poste, 'TITULAIRE');
        if ($titulaire === null) {
            $feuilleComplete = false;
            break;
        }
    }
?>
<div style="display: flex; flex-direction: row; justify-content: space-between; align-items: center; padding-right: 30px">
    <h1>Feuille de Match</h1>
    <?php if($feuilleComplete) : ?>
    <div class="etat-feuille-de-match feuille-de-match-complete">
        COMPLÈTE
    </div>
    <?php else: ?>
    <div class="etat-feuille-de-match feuille-de-match-incomplete">
        INCOMPLÈTE
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
            <th style="width:30%">Joueur</th>
            <th style="width:35%">Sélectionner un joueur</th>
            <th style="width:20%; min-width: 150px;"></th>
        </tr>

        <?php
            foreach ($postes as $poste):
                $participant = $findParticipant($poste, $titulaireOuRemplacant);
                $selectedValue = null;
                $selectableValues = [];

                foreach ($joueursSelectionnables as $joueursSelectionnable) {
                    $jid = (int)($joueursSelectionnable['joueurId'] ?? 0);
                    $selectableValues[$jid] = trim((string)$joueursSelectionnable['nom'] . ' ' . (string)$joueursSelectionnable['prenom']);
                }

                if ($participant !== null) {
                    $jid = (int)($participant['joueurId'] ?? 0);
                    $nom = trim((string)($participant['joueurNom'] ?? '') . ' ' . (string)($participant['joueurPrenom'] ?? ''));
                    $selectableValues[$jid] = $nom;
                    $selectedValue = $nom;
                }

                $select = new Select(
                        $selectableValues,
                        "joueurId",
                        null,
                        $selectedValue,
                );
        ?>
        <form action="/feuilleDeMatch/modifier" method="post">
            <tr>
                <input type="hidden" name="participationId" value="<?php if($participant !== null) echo (int)$participant['participationId']; ?>" />
                <input type="hidden" name="poste" value="<?php echo $poste; ?>" />
                <input type="hidden" name="rencontreId" value="<?php echo $rencontreId; ?>" />
                <input type="hidden" name="titulaireOuRemplacant" value="<?php echo $titulaireOuRemplacant; ?>" />
                <td><?php echo $poste; ?></td>
                <td><?php if($participant !== null) echo htmlspecialchars(trim((string)($participant['joueurNom'] ?? '') . ' ' . (string)($participant['joueurPrenom'] ?? ''))); ?></td>
                <td><?php $select->toHTML(); ?></td>
                <td class="actions">
                    <?php if($participant !== null) : ?>
                    <button class="update" type="submit" name="action" value="update">Modifier</button>
                    <button class="delete" type="submit" name="action" value="delete" style="margin-left: 8px">Supprimer</button>
                    <?php else: ?>
                    <button class="create" type="submit" name="action" value="create">Assigner</button>
                    <?php endif; ?>
                </td>
            </tr>
        </form>
        <?php endforeach; ?>
    </table>
    <?php endforeach; ?>
</div>
<?php endif; ?>