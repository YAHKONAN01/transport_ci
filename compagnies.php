<?php
require_once 'includes/functions.php';
$db = getDB();
$pageTitle = 'Toutes les compagnies';

$type   = intval($_GET['type'] ?? 0);
$search = trim($_GET['q'] ?? '');

$where  = ['c.actif=1'];
$params = [];
if ($type)   { $where[] = 'c.type_id=?'; $params[] = $type; }
if ($search) { $where[] = 'c.nom LIKE ?'; $params[] = "%$search%"; }
$whereSQL = implode(' AND ', $where);

$stmt = $db->prepare("
    SELECT c.*, t.nom as type_nom, t.icone,
           MIN(l.prix) as prix_min,
           COUNT(DISTINCT l.id) as nb_lignes
    FROM compagnies c
    LEFT JOIN types_transport t ON c.type_id=t.id
    LEFT JOIN lignes l ON l.compagnie_id=c.id AND l.actif=1
    WHERE $whereSQL
    GROUP BY c.id
    ORDER BY c.note_moyenne DESC, c.nom ASC
");
$stmt->execute($params);
$compagnies = $stmt->fetchAll();

$types = $db->query("SELECT * FROM types_transport ORDER BY nom")->fetchAll();
$root = '';
require_once 'includes/header.php';
?>

<div style="background:var(--dark);padding:36px 24px;color:#fff;">
    <div style="max-width:1160px;margin:0 auto;">
        <h1 style="font-family:var(--font-d);font-size:28px;font-weight:800;margin-bottom:6px;">
            🚌 Toutes les compagnies
        </h1>
        <p style="color:rgba(255,255,255,.4);"><?= count($compagnies) ?> compagnie<?= count($compagnies)>1?'s':'' ?> référencée<?= count($compagnies)>1?'s':'' ?></p>
    </div>
</div>

<!-- Filtres -->
<div style="background:var(--white);border-bottom:1px solid var(--border);padding:14px 24px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;position:sticky;top:64px;z-index:50;">
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;width:100%;align-items:center;">
        <input type="search" name="q" value="<?= clean($search) ?>"
               placeholder="🔍 Rechercher une compagnie..."
               style="padding:8px 14px;border:2px solid var(--border);border-radius:8px;font-family:var(--font);font-size:14px;width:240px;outline:none;"
               onchange="this.form.submit()">
        <select name="type" onchange="this.form.submit()"
                style="padding:8px 14px;border:2px solid var(--border);border-radius:8px;font-family:var(--font);font-size:14px;outline:none;">
            <option value="">Tous types</option>
            <?php foreach ($types as $t): ?>
            <option value="<?= $t['id'] ?>" <?= $type==$t['id']?'selected':'' ?>>
                <?= clean($t['icone'].' '.$t['nom']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php if ($type || $search): ?>
        <a href="compagnies.php" style="padding:8px 14px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;color:var(--text-2);">✕ Réinitialiser</a>
        <?php endif; ?>
    </form>
</div>

<div class="section">
    <?php if (empty($compagnies)): ?>
    <div class="empty-state">
        <div class="icon">🔍</div>
        <h3>Aucune compagnie trouvée</h3>
        <p>Modifiez vos critères de recherche.</p>
    </div>
    <?php else: ?>
    <div class="companies-grid">
        <?php foreach ($compagnies as $c):
            $initiales = substr(implode('', array_map(fn($w)=>strtoupper($w[0]), explode(' ',$c['nom']))),0,2);
        ?>
        <div class="company-card animate-up">
            <div class="card-accent" style="background:<?= clean($c['logo_couleur']) ?>;"></div>
            <div class="card-body">
                <div class="card-top">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div class="company-logo" style="background:<?= clean($c['logo_couleur']) ?>;"><?= clean($initiales) ?></div>
                        <div>
                            <div class="company-name"><?= clean($c['nom']) ?></div>
                            <div class="company-type"><?= clean($c['icone'].' '.$c['type_nom']) ?></div>
                        </div>
                    </div>
                    <?php if ($c['note_moyenne'] > 0): ?>
                    <div class="company-rating">
                        <div class="rating-num" style="color:<?= clean($c['logo_couleur']) ?>;"><?= $c['note_moyenne'] ?></div>
                        <div class="stars"><?= etoiles($c['note_moyenne']) ?></div>
                        <div class="rating-avis"><?= $c['nb_avis'] ?> avis</div>
                    </div>
                    <?php endif; ?>
                </div>
                <p class="company-desc"><?= clean($c['description']) ?></p>
                <div class="company-contacts">
                    <?php if ($c['telephone']): ?>
                    <div class="contact-item"><span>📞</span><span><?= clean($c['telephone']) ?></span></div>
                    <?php endif; ?>
                    <?php if ($c['adresse']): ?>
                    <div class="contact-item"><span>📍</span><span><?= clean($c['adresse']) ?></span></div>
                    <?php endif; ?>
                    <div class="contact-item"><span>🗺️</span><span><?= $c['nb_lignes'] ?> ligne<?= $c['nb_lignes']>1?'s':'' ?> desservie<?= $c['nb_lignes']>1?'s':'' ?></span></div>
                </div>
            </div>
            <div class="card-footer">
                <div>
                    <?php if ($c['prix_min']): ?>
                    <div class="prix-depuis">À partir de</div>
                    <div class="prix-valeur"><?= formatPrix($c['prix_min']) ?></div>
                    <?php endif; ?>
                </div>
                <a href="compagnie.php?id=<?= $c['id'] ?>" class="btn-voir">Voir détails →</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
