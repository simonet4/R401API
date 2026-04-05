<?php

error_log("[VUE_DASHBOARD] Chargement du tableau de bord");
$dashboardResponse = api_get('/api/statistiques/dashboard');
error_log("[VUE_DASHBOARD] Reponse API: ok=" . ($dashboardResponse['ok'] ? 'OUI' : 'NON') . ", status=" . ($dashboardResponse['status'] ?? '?') . ", error=" . ($dashboardResponse['error'] ?? 'aucune'));
if (!$dashboardResponse['ok']) {
    error_log("[VUE_DASHBOARD] Data brute: " . json_encode($dashboardResponse['data'] ?? null));
}
$dashboardData = ($dashboardResponse['ok'] && is_array($dashboardResponse['data'])) ? $dashboardResponse['data'] : [];

$statsEquipe = is_array($dashboardData['equipe'] ?? null) ? $dashboardData['equipe'] : [];
$statsJoueurs = is_array($dashboardData['joueurs'] ?? null) ? $dashboardData['joueurs'] : [];
error_log("[VUE_DASHBOARD] statsEquipe keys: " . implode(',', array_keys($statsEquipe)) . ", nbJoueurs=" . count($statsJoueurs));

$dashboardError = $dashboardResponse['ok'] ? null : ($dashboardResponse['error'] ?? 'Erreur API dashboard');

?>

<!-- DEBUG TEMPORAIRE : a supprimer quand le dashboard fonctionne -->
<div style="background:#ffe0e0;border:1px solid #c00;padding:10px;margin:10px;font-size:12px;font-family:monospace;">
    <strong>DEBUG Dashboard</strong><br>
    API status: <?php echo htmlspecialchars((string)($dashboardResponse['status'] ?? '?')); ?><br>
    API ok: <?php echo $dashboardResponse['ok'] ? 'OUI' : 'NON'; ?><br>
    API error: <?php echo htmlspecialchars((string)($dashboardResponse['error'] ?? 'aucune')); ?><br>
    nbJoueurs: <?php echo count($statsJoueurs); ?><br>
    statsEquipe keys: <?php echo htmlspecialchars(implode(', ', array_keys($statsEquipe))); ?><br>
    Raw body (200 premiers chars): <pre><?php echo htmlspecialchars(substr((string)($dashboardResponse['raw_body'] ?? ''), 0, 500)); ?></pre>
</div>

<?php if ($dashboardError !== null): ?>
    <p><?php echo htmlspecialchars((string)$dashboardError); ?></p>
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
