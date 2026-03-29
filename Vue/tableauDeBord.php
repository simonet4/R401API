<?php

$statsEquipeResponse = api_get('/api/statistiques/equipe');

echo "<pre style='background:#fff; padding:10px; border:2px solid red; position:relative; z-index:9999; color:black;'>";
var_dump($statsEquipeResponse);
echo "</pre>";
die();
$statsJoueursResponse = api_get('/api/statistiques/joueurs');

$statsEquipe = ($statsEquipeResponse['ok'] && is_array($statsEquipeResponse['data']))
    ? $statsEquipeResponse['data']
    : [];
$statsJoueurs = ($statsJoueursResponse['ok'] && is_array($statsJoueursResponse['data']))
    ? $statsJoueursResponse['data']
    : [];

$errors = [];
if (!$statsEquipeResponse['ok']) {
    $errors[] = (string)($statsEquipeResponse['error'] ?? 'Erreur API statistiques equipe');
}
if (!$statsJoueursResponse['ok']) {
    $errors[] = (string)($statsJoueursResponse['error'] ?? 'Erreur API statistiques joueurs');
}

?>

<?php if ($errors !== []): ?>
    <p><?php echo htmlspecialchars(implode(' | ', $errors)); ?></p>
<?php endif; ?>

<div class="TripleGrid">
    <div>
        <h1><?php echo (int)($statsEquipe['nbVictoires'] ?? 0); ?></h1>
        <p> matchs gagnés</p>
    </div>
    <div>
        <h1><?php echo (int)($statsEquipe['nbNuls'] ?? 0); ?></h1>
        <p> matchs nuls</p>
    </div>
    <div>
        <h1><?php echo (int)($statsEquipe['nbDefaites'] ?? 0); ?></h1>
        <p> matchs perdus</p>
    </div>
    <div>
        <h1><?php echo (int)($statsEquipe['pourcentageDeVictoires'] ?? 0); ?>%</h1>
        <p> de matchs gagnés</p>
    </div>
    <div>
        <h1><?php echo (int)($statsEquipe['pourcentageDeNuls'] ?? 0); ?>%</h1>
        <p> de matchs nuls</p>
    </div>
    <div>
        <h1><?php echo (int)($statsEquipe['pourcentageDeDefaites'] ?? 0); ?>%</h1>
        <p> de matchs perdus</p>
    </div>
</div>
<div class="overflow">
    <table >
        <tr>
            <th style="width:15%;">Joueur</th>
            <th style="width:7%;">Statut</th>
            <th style="width:7%;">Poste le plus performant</th>
            <th style="width:7%;">Nombre de matchs consécutifs</th>
            <th style="width:7%;">Nombre titularisations</th>
            <th style="width:7%;">Nombre remplaçants</th>
            <th style="width:7%;">Moyenne évaluations</th>
            <th style="width:7%;">Pourcentage gagnés</th>
        </tr>
        <?php foreach ($statsJoueurs as $joueur): ?>
        <tr>
            <td><?php echo htmlspecialchars(trim((string)($joueur['nom'] ?? '') . ' ' . (string)($joueur['prenom'] ?? ''))); ?></td>
            <td><?php echo htmlspecialchars((string)($joueur['statutActuel'] ?? '')); ?></td>
            <td><?php echo htmlspecialchars((string)($joueur['posteLePlusPerformant'] ?? '')); ?></td>
            <td><?php echo htmlspecialchars((string)($joueur['nbRencontresConsecutives'] ?? '')); ?></td>
            <td><?php echo htmlspecialchars((string)($joueur['nbTitularisations'] ?? '')); ?></td>
            <td><?php echo htmlspecialchars((string)($joueur['nbRemplacant'] ?? '')); ?></td>
            <td><?php echo htmlspecialchars((string)($joueur['moyenneDesEvaluations'] ?? '')); ?></td>
            <td><?php echo htmlspecialchars((string)($joueur['pourcentageDeMatchsGagnes'] ?? '')); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
