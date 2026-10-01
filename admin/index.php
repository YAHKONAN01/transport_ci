<?php
// admin/index.php
require_once '../includes/functions.php';
$db = getDB();
$pageTitle = 'Administration';
$section = $_GET['s'] ?? 'dashboard';
$flash = $flashType = '';

// ─── TRAITEMENTS POST ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Compagnies ──
    if ($action === 'add_compagnie') {
        $db->prepare("INSERT INTO compagnies (nom,type_id,description,telephone,telephone2,email,adresse,logo_couleur) VALUES (?,?,?,?,?,?,?,?)")
           ->execute([$_POST['nom'],$_POST['type_id']?:null,$_POST['description'],$_POST['telephone'],$_POST['telephone2']?:null,$_POST['email']?:null,$_POST['adresse'],$_POST['logo_couleur']?:'#f97316']);
        $flash='Compagnie ajoutée.'; $flashType='success';
    } elseif ($action === 'del_compagnie') {
        $db->prepare("UPDATE compagnies SET actif=0 WHERE id=?")->execute([$_POST['id']]);
        $flash='Compagnie supprimée.'; $flashType='success';

    // ── Lignes ──
    } elseif ($action === 'add_ligne') {
        $db->prepare("INSERT INTO lignes (compagnie_id,ville_depart_id,ville_arrivee_id,prix,duree_trajet) VALUES (?,?,?,?,?)")
           ->execute([$_POST['compagnie_id'],$_POST['ville_depart_id'],$_POST['ville_arrivee_id'],$_POST['prix'],$_POST['duree_trajet']?:null]);
        $flash='Ligne ajoutée.'; $flashType='success';
    } elseif ($action === 'del_ligne') {
        $db->prepare("DELETE FROM lignes WHERE id=?")->execute([$_POST['id']]);
        $flash='Ligne supprimée.'; $flashType='success';

    // ── Horaires ──
    } elseif ($action === 'add_horaire') {
        $db->prepare("INSERT INTO horaires (ligne_id,heure_depart,jours_semaine) VALUES (?,?,?)")
           ->execute([$_POST['ligne_id'],$_POST['heure_depart'],$_POST['jours_semaine']?:'Tous les jours']);
        $flash='Horaire ajouté.'; $flashType='success';
    } elseif ($action === 'del_horaire') {
        $db->prepare("DELETE FROM horaires WHERE id=?")->execute([$_POST['id']]);
        $flash='Horaire supprimé.'; $flashType='success';

    // ── Villes ──
    } elseif ($action === 'add_ville') {
        $db->prepare("INSERT INTO villes (nom,region) VALUES (?,?)")->execute([$_POST['nom'],$_POST['region']?:null]);
        $flash='Ville ajoutée.'; $flashType='success';
    } elseif ($action === 'del_ville') {
        $db->prepare("UPDATE villes SET actif=0 WHERE id=?")->execute([$_POST['id']]);
        $flash='Ville supprimée.'; $flashType='success';

    // ── Avis ──
    } elseif ($action === 'del_avis') {
        $db->prepare("DELETE FROM avis WHERE id=?")->execute([$_POST['id']]);
        $flash='Avis supprimé.'; $flashType='success';
    }

    header("Location: index.php?s=$section&ok=1"); exit;
}

if (isset($_GET['ok'])) { $flash='Opération effectuée avec succès.'; $flashType='success'; }

// Données
$compagnies  = $db->query("SELECT c.*,t.nom as type_nom FROM compagnies c LEFT JOIN types_transport t ON c.type_id=t.id WHERE c.actif=1 ORDER BY c.nom")->fetchAll();
$villes      = $db->query("SELECT * FROM villes WHERE actif=1 ORDER BY nom")->fetchAll();
$types       = $db->query("SELECT * FROM types_transport ORDER BY nom")->fetchAll();
$lignes      = $db->query("SELECT l.*,c.nom as compagnie_nom,vd.nom as v_dep,va.nom as v_arr FROM lignes l JOIN compagnies c ON l.compagnie_id=c.id JOIN villes vd ON l.ville_depart_id=vd.id JOIN villes va ON l.ville_arrivee_id=va.id WHERE l.actif=1 ORDER BY c.nom,l.prix")->fetchAll();
$horaires    = $db->query("SELECT h.*,vd.nom as v_dep,va.nom as v_arr,c.nom as compagnie_nom FROM horaires h JOIN lignes l ON h.ligne_id=l.id JOIN villes vd ON l.ville_depart_id=vd.id JOIN villes va ON l.ville_arrivee_id=va.id JOIN compagnies c ON l.compagnie_id=c.id ORDER BY h.heure_depart")->fetchAll();
$tousAvis    = $db->query("SELECT a.*,c.nom as compagnie_nom FROM avis a JOIN compagnies c ON a.compagnie_id=c.id ORDER BY a.created_at DESC")->fetchAll();
$stats = [
    'compagnies' => count($compagnies),
    'lignes'     => count($lignes),
    'villes'     => count($villes),
    'avis'       => count($tousAvis),
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Transport CI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f1f5f9; }
        .admin-layout { display: flex; min-height: 100vh; }
        .admin-sidebar {
            width: 240px; background: #0f1117; min-height: 100vh;
            position: fixed; display: flex; flex-direction: column;
        }
        .admin-brand {
            padding: 20px; border-bottom: 1px solid rgba(255,255,255,.07);
            font-family: var(--font-d); font-weight: 800; font-size: 18px; color: #fff;
        }
        .admin-brand span { color: var(--orange); }
        .admin-nav { flex: 1; padding: 12px 0; }
        .admin-nav a {
            display: flex; align-items: center; gap: 10px;
            padding: 11px 20px; color: rgba(255,255,255,.5);
            font-size: 14px; font-weight: 500;
            border-left: 3px solid transparent;
            transition: all .2s;
        }
        .admin-nav a:hover { color: #fff; background: rgba(255,255,255,.05); }
        .admin-nav a.active { color: #fff; background: rgba(249,115,22,.1); border-left-color: var(--orange); }
        .admin-main { margin-left: 240px; flex: 1; }
        .admin-topbar {
            background: #fff; border-bottom: 1px solid var(--border);
            padding: 16px 28px; display: flex; align-items: center;
            justify-content: space-between;
        }
        .admin-topbar h1 { font-family: var(--font-d); font-size: 20px; font-weight: 700; }
        .admin-content { padding: 28px; }
        .admin-card {
            background: #fff; border: 1px solid var(--border);
            border-radius: var(--r-lg); overflow: hidden; margin-bottom: 24px;
        }
        .admin-card-header {
            padding: 16px 22px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
            font-family: var(--font-d); font-weight: 700; font-size: 15px;
        }
        .admin-card-body { padding: 22px; }
        .mini-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap: 14px; margin-bottom: 24px; }
        .mini-stat { background: #fff; border: 1px solid var(--border); border-radius: var(--r-lg); padding: 20px; text-align: center; }
        .mini-stat .n { font-family: var(--font-d); font-size: 28px; font-weight: 800; color: var(--orange); }
        .mini-stat .l { font-size: 12px; color: var(--text-3); text-transform: uppercase; letter-spacing:.05em; margin-top:4px; }
        table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        thead th { background: #f8fafc; color: var(--text-2); font-size: 11px; text-transform: uppercase; letter-spacing:.06em; padding: 11px 14px; border-bottom: 2px solid var(--border); text-align: left; }
        tbody td { padding: 11px 14px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #f8fafc; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap: 12px; margin-bottom: 14px; }
        .fg { display: flex; flex-direction: column; gap: 5px; }
        .fg label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.06em; color: var(--text-3); }
        .fg input, .fg select, .fg textarea { border: 2px solid var(--border); border-radius: 8px; padding: 8px 12px; font-family: var(--font); font-size: 13.5px; outline: none; }
        .fg input:focus, .fg select:focus { border-color: var(--orange); }
        .btn-add { background: var(--orange); color: #fff; border: none; padding: 9px 20px; border-radius: 8px; font-family: var(--font); font-weight: 600; font-size: 13px; cursor: pointer; }
        .btn-del { background: #fee2e2; color: #dc2626; border: none; padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; }
        .btn-del:hover { background: #fca5a5; }
    </style>
</head>
<body>
<div class="admin-layout">

<!-- Sidebar -->
<aside class="admin-sidebar">
    <div class="admin-brand">⚙️ Admin <span>CI</span></div>
    <nav class="admin-nav">
        <a href="?s=dashboard" class="<?= $section==='dashboard'?'active':'' ?>">📊 Tableau de bord</a>
        <a href="?s=compagnies" class="<?= $section==='compagnies'?'active':'' ?>">🚌 Compagnies</a>
        <a href="?s=lignes" class="<?= $section==='lignes'?'active':'' ?>">🗺️ Lignes & Tarifs</a>
        <a href="?s=horaires" class="<?= $section==='horaires'?'active':'' ?>">🕐 Horaires</a>
        <a href="?s=villes" class="<?= $section==='villes'?'active':'' ?>">🏙️ Villes</a>
        <a href="?s=avis" class="<?= $section==='avis'?'active':'' ?>">⭐ Avis</a>
    </nav>
    <div style="padding:16px;border-top:1px solid rgba(255,255,255,.07);">
        <a href="../index.php" style="color:rgba(255,255,255,.4);font-size:13px;">← Voir le site</a>
    </div>
</aside>

<!-- Main -->
<div class="admin-main">
    <div class="admin-topbar">
        <h1><?= ['dashboard'=>'Tableau de bord','compagnies'=>'Compagnies','lignes'=>'Lignes & Tarifs','horaires'=>'Horaires','villes'=>'Villes','avis'=>'Avis'][$section] ?? 'Admin' ?></h1>
        <span style="font-size:13px;color:var(--text-3);">Transport CI</span>
    </div>

    <div class="admin-content">
        <?php if ($flash): ?>
        <div class="alert alert-<?= $flashType ?>" style="margin-bottom:20px;">
            <?= $flashType==='success'?'✅':'❌' ?> <?= clean($flash) ?>
        </div>
        <?php endif; ?>

        <?php if ($section === 'dashboard'): ?>
        <!-- ─── DASHBOARD ─── -->
        <div class="mini-grid">
            <?php foreach (['🚌'=>['Compagnies',$stats['compagnies']],'🗺️'=>['Lignes',$stats['lignes']],'🏙️'=>['Villes',$stats['villes']],'⭐'=>['Avis',$stats['avis']]] as $icon=>[$lbl,$n]): ?>
            <div class="mini-stat"><div style="font-size:28px;"><?= $icon ?></div><div class="n"><?= $n ?></div><div class="l"><?= $lbl ?></div></div>
            <?php endforeach; ?>
        </div>
        <div class="admin-card">
            <div class="admin-card-header">🕐 Derniers avis reçus</div>
            <table>
                <thead><tr><th>Compagnie</th><th>Auteur</th><th>Note</th><th>Commentaire</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach (array_slice($tousAvis, 0, 8) as $a): ?>
                <tr>
                    <td style="font-weight:600;"><?= clean($a['compagnie_nom']) ?></td>
                    <td><?= clean($a['auteur']) ?></td>
                    <td><?= etoiles($a['note']) ?></td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= clean($a['commentaire']) ?></td>
                    <td style="color:var(--text-3);font-size:12px;"><?= date('d/m/Y', strtotime($a['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($section === 'compagnies'): ?>
        <!-- ─── COMPAGNIES ─── -->
        <div class="admin-card">
            <div class="admin-card-header">➕ Ajouter une compagnie</div>
            <div class="admin-card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_compagnie">
                    <div class="form-row">
                        <div class="fg" style="grid-column:span 2;"><label>Nom *</label><input name="nom" required placeholder="Ex: UTB Transport"></div>
                        <div class="fg"><label>Type</label><select name="type_id"><option value="">—</option><?php foreach ($types as $t): ?><option value="<?= $t['id'] ?>"><?= clean($t['icone'].' '.$t['nom']) ?></option><?php endforeach; ?></select></div>
                        <div class="fg"><label>Couleur logo</label><input type="color" name="logo_couleur" value="#f97316"></div>
                    </div>
                    <div class="form-row">
                        <div class="fg"><label>Téléphone</label><input name="telephone" placeholder="+225 07 00 00 00"></div>
                        <div class="fg"><label>Téléphone 2</label><input name="telephone2"></div>
                        <div class="fg"><label>Email</label><input name="email" type="email"></div>
                    </div>
                    <div class="form-row">
                        <div class="fg" style="grid-column:span 2;"><label>Adresse / Gare</label><input name="adresse" placeholder="Gare d'Adjamé, Abidjan"></div>
                    </div>
                    <div class="fg" style="margin-bottom:14px;"><label>Description</label><textarea name="description" rows="2"></textarea></div>
                    <button type="submit" class="btn-add">➕ Ajouter</button>
                </form>
            </div>
        </div>
        <div class="admin-card">
            <div class="admin-card-header">Liste des compagnies (<?= count($compagnies) ?>)</div>
            <table>
                <thead><tr><th>Nom</th><th>Type</th><th>Téléphone</th><th>Note</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($compagnies as $c): ?>
                <tr>
                    <td style="font-weight:600;"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?= $c['logo_couleur'] ?>;margin-right:8px;"></span><?= clean($c['nom']) ?></td>
                    <td><?= clean($c['type_nom'] ?? '—') ?></td>
                    <td><?= clean($c['telephone'] ?? '—') ?></td>
                    <td><?= $c['note_moyenne'] ? etoiles($c['note_moyenne']).' '.$c['note_moyenne'] : '—' ?></td>
                    <td>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette compagnie ?')">
                            <input type="hidden" name="action" value="del_compagnie">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <button class="btn-del">🗑️ Supprimer</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($section === 'lignes'): ?>
        <!-- ─── LIGNES ─── -->
        <div class="admin-card">
            <div class="admin-card-header">➕ Ajouter une ligne</div>
            <div class="admin-card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_ligne">
                    <div class="form-row">
                        <div class="fg"><label>Compagnie *</label><select name="compagnie_id" required><option value="">—</option><?php foreach ($compagnies as $c): ?><option value="<?= $c['id'] ?>"><?= clean($c['nom']) ?></option><?php endforeach; ?></select></div>
                        <div class="fg"><label>Ville départ *</label><select name="ville_depart_id" required><option value="">—</option><?php foreach ($villes as $v): ?><option value="<?= $v['id'] ?>"><?= clean($v['nom']) ?></option><?php endforeach; ?></select></div>
                        <div class="fg"><label>Ville arrivée *</label><select name="ville_arrivee_id" required><option value="">—</option><?php foreach ($villes as $v): ?><option value="<?= $v['id'] ?>"><?= clean($v['nom']) ?></option><?php endforeach; ?></select></div>
                        <div class="fg"><label>Prix (FCFA) *</label><input name="prix" type="number" min="0" required></div>
                        <div class="fg"><label>Durée trajet</label><input name="duree_trajet" placeholder="ex: 5h30"></div>
                    </div>
                    <button type="submit" class="btn-add">➕ Ajouter la ligne</button>
                </form>
            </div>
        </div>
        <div class="admin-card">
            <div class="admin-card-header">Liste des lignes (<?= count($lignes) ?>)</div>
            <table>
                <thead><tr><th>Compagnie</th><th>Départ</th><th>Arrivée</th><th>Prix</th><th>Durée</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($lignes as $l): ?>
                <tr>
                    <td style="font-weight:600;"><?= clean($l['compagnie_nom']) ?></td>
                    <td><?= clean($l['v_dep']) ?></td>
                    <td><?= clean($l['v_arr']) ?></td>
                    <td style="font-weight:700;color:var(--orange);"><?= formatPrix($l['prix']) ?></td>
                    <td><?= clean($l['duree_trajet'] ?? '—') ?></td>
                    <td>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ?')">
                            <input type="hidden" name="action" value="del_ligne">
                            <input type="hidden" name="id" value="<?= $l['id'] ?>">
                            <button class="btn-del">🗑️</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($section === 'horaires'): ?>
        <!-- ─── HORAIRES ─── -->
        <div class="admin-card">
            <div class="admin-card-header">➕ Ajouter un horaire</div>
            <div class="admin-card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_horaire">
                    <div class="form-row">
                        <div class="fg" style="grid-column:span 2;"><label>Ligne *</label>
                            <select name="ligne_id" required>
                                <option value="">— Sélectionner la ligne —</option>
                                <?php foreach ($lignes as $l): ?>
                                <option value="<?= $l['id'] ?>"><?= clean($l['compagnie_nom']) ?> | <?= clean($l['v_dep']) ?> → <?= clean($l['v_arr']) ?> (<?= formatPrix($l['prix']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fg"><label>Heure de départ *</label><input type="time" name="heure_depart" required></div>
                        <div class="fg"><label>Jours</label>
                            <select name="jours_semaine">
                                <option>Tous les jours</option>
                                <option>Lun-Sam</option>
                                <option>Lun-Ven</option>
                                <option>Sam-Dim</option>
                                <option>Dim uniquement</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn-add">➕ Ajouter l'horaire</button>
                </form>
            </div>
        </div>
        <div class="admin-card">
            <div class="admin-card-header">Tous les horaires (<?= count($horaires) ?>)</div>
            <table>
                <thead><tr><th>Compagnie</th><th>Trajet</th><th>Départ</th><th>Jours</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($horaires as $h): ?>
                <tr>
                    <td style="font-weight:600;"><?= clean($h['compagnie_nom']) ?></td>
                    <td><?= clean($h['v_dep']) ?> → <?= clean($h['v_arr']) ?></td>
                    <td style="font-weight:700;font-family:monospace;font-size:15px;"><?= substr($h['heure_depart'],0,5) ?></td>
                    <td style="color:var(--text-3);font-size:12px;"><?= clean($h['jours_semaine']) ?></td>
                    <td>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ?')">
                            <input type="hidden" name="action" value="del_horaire">
                            <input type="hidden" name="id" value="<?= $h['id'] ?>">
                            <button class="btn-del">🗑️</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($section === 'villes'): ?>
        <!-- ─── VILLES ─── -->
        <div class="admin-card">
            <div class="admin-card-header">➕ Ajouter une ville</div>
            <div class="admin-card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_ville">
                    <div class="form-row">
                        <div class="fg"><label>Nom *</label><input name="nom" required placeholder="ex: Duekoué"></div>
                        <div class="fg"><label>Région</label><input name="region" placeholder="ex: Guémon"></div>
                    </div>
                    <button type="submit" class="btn-add">➕ Ajouter</button>
                </form>
            </div>
        </div>
        <div class="admin-card">
            <div class="admin-card-header">Liste des villes (<?= count($villes) ?>)</div>
            <table>
                <thead><tr><th>Ville</th><th>Région</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($villes as $v): ?>
                <tr>
                    <td style="font-weight:600;"><?= clean($v['nom']) ?></td>
                    <td style="color:var(--text-3);"><?= clean($v['region'] ?? '—') ?></td>
                    <td>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ?')">
                            <input type="hidden" name="action" value="del_ville">
                            <input type="hidden" name="id" value="<?= $v['id'] ?>">
                            <button class="btn-del">🗑️</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($section === 'avis'): ?>
        <!-- ─── AVIS ─── -->
        <div class="admin-card">
            <div class="admin-card-header">Tous les avis (<?= count($tousAvis) ?>)</div>
            <table>
                <thead><tr><th>Compagnie</th><th>Auteur</th><th>Note</th><th>Commentaire</th><th>Date</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($tousAvis as $a): ?>
                <tr>
                    <td style="font-weight:600;"><?= clean($a['compagnie_nom']) ?></td>
                    <td><?= clean($a['auteur']) ?></td>
                    <td><?= etoiles($a['note']) ?></td>
                    <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12.5px;color:var(--text-2);"><?= clean($a['commentaire']) ?></td>
                    <td style="font-size:12px;color:var(--text-3);"><?= date('d/m/Y', strtotime($a['created_at'])) ?></td>
                    <td>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cet avis ?')">
                            <input type="hidden" name="action" value="del_avis">
                            <input type="hidden" name="id" value="<?= $a['id'] ?>">
                            <button class="btn-del">🗑️</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div><!-- .admin-content -->
</div><!-- .admin-main -->
</div><!-- .admin-layout -->
</body>
</html>
