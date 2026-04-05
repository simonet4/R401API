<?php

$response = api_get('/api/joueur');
$joueurs = ($response['ok'] && is_array($response['data'])) ? $response['data'] : [];
$apiError = $response['ok'] ? null : ($response['error'] ?? 'Erreur API');

$recherche = trim((string)($_GET['recherche'] ?? ''));
$statut = trim((string)($_GET['statut'] ?? ''));

if ($recherche !== '' || $statut !== '') {
    $joueurs = array_values(array_filter($joueurs, function (array $joueur) use ($recherche, $statut) {
        $ok = true;

        if ($recherche !== '') {
            $needle = mb_strtolower($recherche);
            $nom = mb_strtolower((string)($joueur['nom'] ?? ''));
            $prenom = mb_strtolower((string)($joueur['prenom'] ?? ''));
            $ok = str_contains($nom, $needle) || str_contains($prenom, $needle);
        }

        if ($ok && $statut !== '') {
            $ok = (string)($joueur['statut'] ?? '') === $statut;
        }

        return $ok;
    }));
}

?>

<h1>Joueurs</h1>
<?php if ($apiError !== null): ?>
    <p><?php echo htmlspecialchars((string)$apiError); ?></p>
<?php endif; ?>
<div class="container">
    <form action="joueur" method="get">
        <div class="row">
            <div class="invCol-80">
                <input type="search" name="recherche" placeholder="Rechercher" <?= isset($_GET['recherche']) ? 'value="'.$_GET['recherche'].'"' : '' ?>/>
            </div>
        </div>
        <div class="row">
            <div class="invCol-80">
                <select name="statut" id="statut">
                    <option value="">Tous</option>
                    <option value="ACTIF" <?= (isset($_GET['statut']) && $_GET['statut'] === "ACTIF") ? 'selected' : '' ?>>Actif</option>
                    <option value="BLESSE" <?= (isset($_GET['statut']) && $_GET['statut'] === "BLESSE") ? 'selected' : '' ?>>Blessé</option>
                    <option value="ABSENT" <?= (isset($_GET['statut']) && $_GET['statut'] === "ABSENT") ? 'selected' : '' ?>>Absent</option>
                    <option value="SUSPENDU" <?= (isset($_GET['statut']) && $_GET['statut'] === "SUSPENDU") ? 'selected' : '' ?>>Suspendu</option>
                </select>
            </div>
            <div class="invCol-20">
                <input class="filter-button" type="submit" value="Filtrer">
            </div>
        </div>
    </form>
</div>

<div class="overflow container">
    <table style="width: 100%">
        <tr>
            <th style="width:8%">Numero Licence</th>
            <th style="width:12%">Nom</th>
            <th style="width:12%">Prenom</th>
            <th style="width:12%">Date de naissance</th>
            <th style="width:12%">Taille</th>
            <th style="width:12%">Poids</th>
            <th style="width:12%">Statut</th>
            <th style="width:20%; min-width: 370px;">Actions</th>
        </tr>

        <?php foreach ($joueurs as $joueur) { ?>
            <tr>
                <td><?php echo htmlspecialchars((string)($joueur['numeroDeLicence'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars((string)($joueur['nom'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars((string)($joueur['prenom'] ?? '')); ?></td>
                <td><?php echo isset($joueur['dateDeNaissance']) ? date('d/m/Y', strtotime((string)$joueur['dateDeNaissance'])) : ''; ?></td>
                <td><?php echo htmlspecialchars((string)($joueur['tailleEnCm'] ?? '')); ?> cm</td>
                <td><?php echo htmlspecialchars((string)($joueur['poidsEnKg'] ?? '')); ?> kg</td>
                <td><?php echo htmlspecialchars((string)($joueur['statut'] ?? '')); ?></td>
                <td class="actions">
                    <form action="joueur/modifier" method="get"><button class="update" type="submit" name="id" value="<?php echo (int)($joueur['joueurId'] ?? 0); ?>">Modifier</button></form>
                    <form action="joueur/supprimer" method="post"><button class="delete" type="submit" name="id" value="<?php echo (int)($joueur['joueurId'] ?? 0); ?>"  onclick="return confirm('Voulez-vous vraiment supprimer ce joueur?')">Supprimer</button></form>
                    <form action="joueur/commentaire" method="get"><button class="info" type="submit" name="id" value="<?php echo (int)($joueur['joueurId'] ?? 0); ?>">Commentaires</button></form>
                </td>
            </tr>
        <?php } ?>
    </table>
    <?php
    echo count($joueurs)." joueurs retournés</p>";
    ?>
</div>
