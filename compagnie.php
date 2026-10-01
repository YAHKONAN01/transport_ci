<?php
require_once 'includes/functions.php';
$db = getDB();
$id = intval($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT c.*, t.nom as type_nom, t.icone FROM compagnies c LEFT JOIN types_transport t ON c.type_id=t.id WHERE c.id=? AND c.actif=1");
$stmt->execute([$id]);
$compagnie = $stmt->fetch();

if (!$compagnie) {
    header('Location: index.php'); exit;
}

// Lignes de la compagnie
$lignes = $db->prepare("
    SELECT l.*, vd.nom as ville_depart, va.nom as ville_arrivee
    FROM lignes l
    JOIN villes vd ON l.ville_depart_id = vd.id
    JOIN villes va ON l.ville_arrivee_id = va.id
    WHERE l.compagnie_id = ? AND l.actif=1
    ORDER BY l.prix ASC
");
$lignes->execute([$id]);
$lignes = $lignes->fetchAll();

// Horaires par ligne
foreach ($lignes as &$l) {
    $h = $db->prepare("SELECT heure_depart, jours_semaine FROM horaires WHERE ligne_id=? ORDER BY heure_depart");
    $h->execute([$l['id']]);
    $l['horaires'] = $h->fetchAll();
}
unset($l);

// Avis
$avis = $db->prepare("SELECT * FROM avis WHERE compagnie_id=? ORDER BY created_at DESC LIMIT 10");
$avis->execute([$id]);
$avis = $avis->fetchAll();

// Soumission d'un avis
$flashMsg = $flashType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['soumettre_avis'])) {
    $auteur = trim($_POST['auteur'] ?? 'Anonyme');
    $note   = intval($_POST['note'] ?? 0);
    $comm   = trim($_POST['commentaire'] ?? '');

    if ($note < 1 || $note > 5) {
        $flashMsg = 'Veuillez sélectionner une note entre 1 et 5.';
        $flashType = 'danger';
    } else {
        $db->prepare("INSERT INTO avis (compagnie_id, auteur, note, commentaire) VALUES (?,?,?,?)")
           ->execute([$id, $auteur ?: 'Anonyme', $note, $comm]);
        // Recalcul note moyenne
        $db->prepare("UPDATE compagnies SET
            note_moyenne = (SELECT ROUND(AVG(note),1) FROM avis WHERE compagnie_id=?),
            nb_avis = (SELECT COUNT(*) FROM avis WHERE compagnie_id=?)
            WHERE id=?")->execute([$id, $id, $id]);
        $flashMsg = 'Merci pour votre avis !';
        $flashType = 'success';
        header("Location: compagnie.php?id=$id&avis=ok");
        exit;
    }
}
if (isset($_GET['avis'])) { $flashMsg = 'Votre avis a bien été enregistré. Merci !'; $flashType = 'success'; }

$initiales = substr(implode('', array_map(fn($w)=>strtoupper($w[0]), explode(' ',$compagnie['nom']))), 0, 2);
$pageTitle = $compagnie['nom'];
$root = '';
require_once 'includes/header.php';
?>

<!-- ─── HERO COMPAGNIE ─── -->
<div class="detail-hero">
    <div class="detail-inner">
        <div class="detail-logo" style="background:<?= clean($compagnie['logo_couleur']) ?>;">
            <?= clean($initiales) ?>
        </div>
        <div>
            <div class="detail-name"><?= clean($compagnie['nom']) ?></div>
            <div class="detail-type"><?= clean($compagnie['icone'].' '.$compagnie['type_nom']) ?></div>
            <div class="detail-rating">
                <?= etoiles($compagnie['note_moyenne']) ?>
                <span style="color:#fff;font-weight:700;margin-left:4px;"><?= $compagnie['note_moyenne'] ?></span>
                <span style="color:rgba(255,255,255,.4);">(<?= $compagnie['nb_avis'] ?> avis)</span>
            </div>
        </div>
    </div>
</div>

<div class="detail-body">
    <?php if ($flashMsg): ?>
    <div class="alert alert-<?= $flashType ?>">
        <?= $flashType==='success'?'✅':'❌' ?> <?= clean($flashMsg) ?>
    </div>
    <?php endif; ?>

    <div class="detail-grid">

        <!-- Colonne principale -->
        <div>
            <!-- Description -->
            <div class="info-card">
                <div class="info-card-header">ℹ️ À propos</div>
                <div class="info-card-body">
                    <p style="color:var(--text-2);line-height:1.8;"><?= clean($compagnie['description']) ?></p>
                </div>
            </div>

            <!-- Lignes & Tarifs -->
            <div class="info-card">
                <div class="info-card-header">🗺️ Lignes & Tarifs</div>
                <div class="info-card-body" style="padding:0 22px;">
                    <?php if (empty($lignes)): ?>
                    <p style="padding:20px 0;color:var(--text-3);">Aucune ligne enregistrée.</p>
                    <?php else: ?>
                    <?php foreach ($lignes as $l): ?>
                    <div class="ligne-row">
                        <div>
                            <div class="ligne-route">
                                <?= clean($l['ville_depart']) ?> → <?= clean($l['ville_arrivee']) ?>
                            </div>
                            <?php if (!empty($l['horaires'])): ?>
                            <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:8px;">
                                <?php foreach ($l['horaires'] as $h): ?>
                                <span class="horaire-badge"><?= substr($h['heure_depart'],0,5) ?></span>
                                <?php endforeach; ?>
                                <span style="font-size:11px;color:var(--text-3);align-self:center;margin-left:4px;">
                                    — <?= clean($l['horaires'][0]['jours_semaine']) ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="ligne-duree" style="text-align:center;">
                            <?php if ($l['duree_trajet']): ?>
                            ⏱️ <?= clean($l['duree_trajet']) ?>
                            <?php endif; ?>
                        </div>
                        <div class="ligne-prix-val"><?= formatPrix($l['prix']) ?></div>
                        <div>
                            <a href="resultats.php?depart=<?= $l['ville_depart_id'] ?>&arrivee=<?= $l['ville_arrivee_id'] ?>"
                               style="font-size:12px;color:var(--orange);font-weight:600;">
                               Comparer →
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Avis -->
            <div class="info-card">
                <div class="info-card-header">
                    ⭐ Avis des voyageurs
                    <span style="margin-left:auto;font-size:13px;color:var(--text-3);font-weight:400;">
                        <?= $compagnie['nb_avis'] ?> avis • Moyenne <?= $compagnie['note_moyenne'] ?>/5
                    </span>
                </div>
                <div class="info-card-body">
                    <?php if (empty($avis)): ?>
                    <p style="color:var(--text-3);font-size:14px;">Aucun avis pour le moment. Soyez le premier !</p>
                    <?php else: ?>
                    <?php foreach ($avis as $a): ?>
                    <div class="avis-card">
                        <div class="avis-header">
                            <div>
                                <div class="avis-auteur"><?= clean($a['auteur']) ?></div>
                                <div><?= etoiles($a['note']) ?></div>
                            </div>
                            <div class="avis-date"><?= date('d/m/Y', strtotime($a['created_at'])) ?></div>
                        </div>
                        <?php if ($a['commentaire']): ?>
                        <div class="avis-texte"><?= clean($a['commentaire']) ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Formulaire avis -->
                    <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--border);">
                        <div style="font-family:var(--font-d);font-weight:700;margin-bottom:16px;">Laisser un avis</div>
                        <form method="POST" class="form-avis">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                                <div class="form-group">
                                    <label>Votre nom</label>
                                    <input type="text" name="auteur" placeholder="Kouamé Jean">
                                </div>
                                <div class="form-group">
                                    <label>Note *</label>
                                    <select name="note" required>
                                        <option value="">Choisir...</option>
                                        <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                                        <option value="4">⭐⭐⭐⭐ Bien</option>
                                        <option value="3">⭐⭐⭐ Correct</option>
                                        <option value="2">⭐⭐ Passable</option>
                                        <option value="1">⭐ Mauvais</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Commentaire</label>
                                <textarea name="commentaire" rows="3" placeholder="Partagez votre expérience..."></textarea>
                            </div>
                            <button type="submit" name="soumettre_avis" class="btn-submit">
                                ✅ Publier mon avis
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar contacts -->
        <div>
            <div class="contact-box">
                <h3>📞 Contacts</h3>
                <?php if ($compagnie['telephone']): ?>
                <div class="contact-line">
                    <span class="icon">📱</span>
                    <div>
                        <span class="lbl">Téléphone principal</span>
                        <span class="val"><?= clean($compagnie['telephone']) ?></span>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($compagnie['telephone2']): ?>
                <div class="contact-line">
                    <span class="icon">📱</span>
                    <div>
                        <span class="lbl">Téléphone secondaire</span>
                        <span class="val"><?= clean($compagnie['telephone2']) ?></span>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($compagnie['email']): ?>
                <div class="contact-line">
                    <span class="icon">✉️</span>
                    <div>
                        <span class="lbl">Email</span>
                        <span class="val"><?= clean($compagnie['email']) ?></span>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($compagnie['adresse']): ?>
                <div class="contact-line">
                    <span class="icon">📍</span>
                    <div>
                        <span class="lbl">Adresse / Gare</span>
                        <span class="val"><?= clean($compagnie['adresse']) ?></span>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($compagnie['telephone']): ?>
                <a href="tel:<?= clean($compagnie['telephone']) ?>" class="btn-appel">
                    📞 Appeler maintenant
                </a>
                <?php endif; ?>
            </div>

            <!-- Prix résumé -->
            <div class="contact-box">
                <h3>💰 Tarifs proposés</h3>
                <?php if (!empty($lignes)):
                    $prixMin = min(array_column($lignes,'prix'));
                    $prixMax = max(array_column($lignes,'prix'));
                ?>
                <div style="text-align:center;padding:16px 0;">
                    <div style="font-size:12px;color:var(--text-3);margin-bottom:4px;">À partir de</div>
                    <div style="font-family:var(--font-d);font-size:28px;font-weight:800;color:var(--orange);"><?= formatPrix($prixMin) ?></div>
                    <?php if ($prixMin !== $prixMax): ?>
                    <div style="font-size:13px;color:var(--text-3);margin-top:4px;">jusqu'à <?= formatPrix($prixMax) ?></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <a href="index.php" style="display:flex;align-items:center;justify-content:center;gap:6px;background:var(--bg);border:1.5px solid var(--border);color:var(--text);padding:10px;border-radius:var(--r);font-weight:600;font-size:13px;margin-top:8px;">
                    🔍 Nouvelle recherche
                </a>
            </div>
        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
