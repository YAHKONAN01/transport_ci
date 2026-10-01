<?php
require_once 'includes/functions.php';

$departId  = intval($_GET['depart']  ?? 0);
$arriveeId = intval($_GET['arrivee'] ?? 0);
$tri       = $_GET['tri'] ?? 'prix';
$villes    = getVilles();
$db        = getDB();

$villeDepart  = $departId  ? getVilleById($departId)  : null;
$villeArrivee = $arriveeId ? getVilleById($arriveeId) : null;

$resultats = [];
$error     = '';

if ($departId && $arriveeId) {
    if ($departId === $arriveeId) {
        $error = 'La ville de départ et la destination doivent être différentes.';
    } else {
        $orderBy = match($tri) {
            'note'   => 'c.note_moyenne DESC, prix ASC',
            'duree'  => 'l.duree_trajet ASC',
            default  => 'l.prix ASC',
        };

        $stmt = $db->prepare("
            SELECT l.id as ligne_id, l.prix, l.duree_trajet,
                   c.id as compagnie_id, c.nom, c.description,
                   c.telephone, c.telephone2, c.logo_couleur,
                   c.note_moyenne, c.nb_avis,
                   t.nom as type_nom, t.icone
            FROM lignes l
            JOIN compagnies c ON l.compagnie_id = c.id
            LEFT JOIN types_transport t ON c.type_id = t.id
            WHERE l.ville_depart_id = ?
              AND l.ville_arrivee_id = ?
              AND l.actif = 1
              AND c.actif = 1
            ORDER BY $orderBy
        ");
        $stmt->execute([$departId, $arriveeId]);
        $resultats = $stmt->fetchAll();

        // Horaires pour chaque ligne
        foreach ($resultats as &$r) {
            $h = $db->prepare("SELECT heure_depart, jours_semaine FROM horaires WHERE ligne_id=? ORDER BY heure_depart LIMIT 8");
            $h->execute([$r['ligne_id']]);
            $r['horaires'] = $h->fetchAll();
        }
        unset($r);
    }
}

$pageTitle = $villeDepart && $villeArrivee
    ? clean($villeDepart['nom']).' → '.clean($villeArrivee['nom'])
    : 'Recherche de trajet';

$root = '';
require_once 'includes/header.php';
?>

<!-- ─── EN-TÊTE RÉSULTATS ─── -->
<div class="results-header">
    <div style="max-width:920px;margin:0 auto;">
        <div class="results-breadcrumb">
            <a href="index.php">Accueil</a> › Résultats
        </div>
        <?php if ($villeDepart && $villeArrivee): ?>
        <h1><?= clean($villeDepart['nom']) ?> → <?= clean($villeArrivee['nom']) ?></h1>
        <p><?= count($resultats) ?> compagnie<?= count($resultats)>1?'s':'' ?> disponible<?= count($resultats)>1?'s':'' ?></p>
        <div class="route-display">
            <div>
                <div class="route-city"><?= clean($villeDepart['nom']) ?></div>
                <div class="route-info"><?= clean($villeDepart['region']) ?></div>
            </div>
            <div class="route-arrow">✈️ ──────── </div>
            <div>
                <div class="route-city"><?= clean($villeArrivee['nom']) ?></div>
                <div class="route-info"><?= clean($villeArrivee['region']) ?></div>
            </div>
        </div>
        <?php else: ?>
        <h1>Recherche de trajet</h1>
        <?php endif; ?>

        <!-- Formulaire de modification rapide -->
        <form action="resultats.php" method="GET" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:20px;">
            <select name="depart" style="padding:8px 12px;border-radius:8px;border:none;font-family:var(--font);font-size:14px;min-width:160px;">
                <option value="">Départ...</option>
                <?php foreach ($villes as $v): ?>
                <option value="<?= $v['id'] ?>" <?= $v['id']==$departId?'selected':'' ?>><?= clean($v['nom']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="arrivee" style="padding:8px 12px;border-radius:8px;border:none;font-family:var(--font);font-size:14px;min-width:160px;">
                <option value="">Arrivée...</option>
                <?php foreach ($villes as $v): ?>
                <option value="<?= $v['id'] ?>" <?= $v['id']==$arriveeId?'selected':'' ?>><?= clean($v['nom']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" style="background:var(--orange);color:#fff;border:none;padding:8px 20px;border-radius:8px;font-family:var(--font);font-weight:600;cursor:pointer;">
                🔍 Modifier
            </button>
        </form>
    </div>
</div>

<!-- ─── BARRE FILTRES / TRI ─── -->
<?php if ($villeDepart && $villeArrivee && !$error): ?>
<div class="filter-bar">
    <span class="filter-label">Trier par :</span>
    <a href="?depart=<?= $departId ?>&arrivee=<?= $arriveeId ?>&tri=prix"
       class="filter-btn <?= $tri==='prix'?'active':'' ?>">💰 Prix</a>
    <a href="?depart=<?= $departId ?>&arrivee=<?= $arriveeId ?>&tri=note"
       class="filter-btn <?= $tri==='note'?'active':'' ?>">⭐ Note</a>
    <a href="?depart=<?= $departId ?>&arrivee=<?= $arriveeId ?>&tri=duree"
       class="filter-btn <?= $tri==='duree'?'active':'' ?>">⏱️ Durée</a>
    <?php if (!empty($resultats)): ?>
    <span style="margin-left:auto;font-size:13px;color:var(--text-3);">
        Prix : <?= formatPrix(min(array_column($resultats,'prix'))) ?> – <?= formatPrix(max(array_column($resultats,'prix'))) ?>
    </span>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ─── RÉSULTATS ─── -->
<div class="results-container">

    <?php if ($error): ?>
    <div class="alert alert-danger">⚠️ <?= clean($error) ?></div>

    <?php elseif (!$departId || !$arriveeId): ?>
    <div class="empty-state">
        <div class="icon">🔍</div>
        <h3>Commencez votre recherche</h3>
        <p>Sélectionnez une ville de départ et une destination pour voir les compagnies disponibles.</p>
        <a href="index.php" style="display:inline-flex;align-items:center;gap:6px;background:var(--orange);color:#fff;padding:10px 24px;border-radius:var(--r);font-weight:600;">
            ← Retour à l'accueil
        </a>
    </div>

    <?php elseif (empty($resultats)): ?>
    <div class="empty-state">
        <div class="icon">😕</div>
        <h3>Aucun trajet trouvé</h3>
        <p>Aucune compagnie ne dessert la route <strong><?= clean($villeDepart['nom']) ?> → <?= clean($villeArrivee['nom']) ?></strong> pour le moment.</p>
        <a href="index.php" style="display:inline-flex;align-items:center;gap:6px;background:var(--orange);color:#fff;padding:10px 24px;border-radius:var(--r);font-weight:600;">
            ← Nouvelle recherche
        </a>
    </div>

    <?php else:
        $prixMin = min(array_column($resultats, 'prix'));
        foreach ($resultats as $i => $r):
            $initiales = implode('', array_map(fn($w) => strtoupper($w[0]), explode(' ', $r['nom'])));
            $initiales = substr($initiales, 0, 2);
            $isBest = $r['prix'] == $prixMin;
    ?>
    <div class="result-card <?= $isBest ? 'best-deal' : '' ?> animate-up">
        <?php if ($isBest): ?>
        <div class="best-badge">🏆 Meilleur prix</div>
        <?php endif; ?>
        <div class="result-card-inner">

            <!-- Logo -->
            <div class="result-logo-col">
                <div class="result-logo" style="background:<?= clean($r['logo_couleur']) ?>;">
                    <?= clean($initiales) ?>
                </div>
            </div>

            <!-- Info -->
            <div class="result-info-col">
                <div class="result-name"><?= clean($r['nom']) ?></div>
                <div class="result-type"><?= clean($r['icone'].' '.$r['type_nom']) ?></div>
                <div class="result-meta">
                    <?php if ($r['duree_trajet']): ?>
                    <div class="meta-item">
                        <span class="meta-icon">⏱️</span>
                        <span><?= clean($r['duree_trajet']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($r['telephone']): ?>
                    <div class="meta-item">
                        <span class="meta-icon">📞</span>
                        <span><?= clean($r['telephone']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($r['note_moyenne'] > 0): ?>
                    <div class="meta-item">
                        <span><?= etoiles($r['note_moyenne']) ?></span>
                        <span style="color:var(--text-3);font-size:12px;"><?= $r['note_moyenne'] ?>/5 (<?= $r['nb_avis'] ?> avis)</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Horaires -->
            <div class="result-horaires-col">
                <div class="horaires-title">Horaires de départ</div>
                <?php if (empty($r['horaires'])): ?>
                <div style="font-size:13px;color:var(--text-3);">Nous contacter</div>
                <?php else: ?>
                <div class="horaires-list">
                    <?php foreach ($r['horaires'] as $h): ?>
                    <span class="horaire-badge"><?= substr($h['heure_depart'], 0, 5) ?></span>
                    <?php endforeach; ?>
                </div>
                <div style="font-size:11px;color:var(--text-3);margin-top:8px;">
                    <?= clean($r['horaires'][0]['jours_semaine'] ?? '') ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Prix + Action -->
            <div class="result-prix-col">
                <div class="prix-label">Prix du billet</div>
                <div class="prix-amount"><?= formatPrix($r['prix']) ?></div>
                <?php if ($r['note_moyenne'] > 0): ?>
                <div class="prix-note">
                    <span style="color:#f59e0b;">★</span>
                    <?= $r['note_moyenne'] ?>/5
                </div>
                <?php endif; ?>
                <a href="compagnie.php?id=<?= $r['compagnie_id'] ?>" class="btn-detail">
                    Voir détails →
                </a>
            </div>

        </div>
    </div>
    <?php endforeach; ?>

    <!-- Résumé comparatif -->
    <?php if (count($resultats) > 1): ?>
    <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);padding:20px 24px;">
        <div style="font-family:var(--font-d);font-weight:700;margin-bottom:12px;">📊 Résumé comparatif</div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;text-align:center;">
            <div style="padding:12px;background:var(--bg);border-radius:var(--r);">
                <div style="font-size:11px;color:var(--text-3);text-transform:uppercase;margin-bottom:4px;">Prix le plus bas</div>
                <div style="font-family:var(--font-d);font-size:20px;font-weight:800;color:var(--green);"><?= formatPrix(min(array_column($resultats,'prix'))) ?></div>
            </div>
            <div style="padding:12px;background:var(--bg);border-radius:var(--r);">
                <div style="font-size:11px;color:var(--text-3);text-transform:uppercase;margin-bottom:4px;">Prix le plus haut</div>
                <div style="font-family:var(--font-d);font-size:20px;font-weight:800;color:var(--orange);"><?= formatPrix(max(array_column($resultats,'prix'))) ?></div>
            </div>
            <div style="padding:12px;background:var(--bg);border-radius:var(--r);">
                <div style="font-size:11px;color:var(--text-3);text-transform:uppercase;margin-bottom:4px;">Compagnies</div>
                <div style="font-family:var(--font-d);font-size:20px;font-weight:800;"><?= count($resultats) ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
