<?php
require_once 'includes/functions.php';
$pageTitle = 'Comparez vos transports en Côte d\'Ivoire';
$villes = getVilles();
$db = getDB();

// Stats
$nbCompagnies = $db->query("SELECT COUNT(*) FROM compagnies WHERE actif=1")->fetchColumn();
$nbLignes     = $db->query("SELECT COUNT(*) FROM lignes WHERE actif=1")->fetchColumn();
$nbVilles     = $db->query("SELECT COUNT(*) FROM villes WHERE actif=1")->fetchColumn();

// Compagnies populaires (mieux notées)
$populaires = $db->query("
    SELECT c.*, t.nom as type_nom, t.icone,
           MIN(l.prix) as prix_min
    FROM compagnies c
    LEFT JOIN types_transport t ON c.type_id = t.id
    LEFT JOIN lignes l ON l.compagnie_id = c.id AND l.actif=1
    WHERE c.actif=1
    GROUP BY c.id
    ORDER BY c.note_moyenne DESC, c.nb_avis DESC
    LIMIT 6
")->fetchAll();

// Destinations populaires (nb lignes)
$destinations = $db->query("
    SELECT v.id, v.nom, v.region, COUNT(l.id) as nb_lignes,
           MIN(l.prix) as prix_min
    FROM villes v
    JOIN lignes l ON l.ville_arrivee_id = v.id AND l.actif=1
    WHERE v.id != 1
    GROUP BY v.id
    ORDER BY nb_lignes DESC
    LIMIT 8
")->fetchAll();

$root = '';
require_once 'includes/header.php';
?>

<!-- ─── HERO ─── -->
<section class="hero">
    <div class="hero-pattern"></div>
    <div class="hero-content">
        <div class="hero-badge">🇨🇮 Côte d'Ivoire</div>
        <h1>Voyagez <em>mieux</em>,<br>payez <em>moins</em></h1>
        <p>Comparez toutes les compagnies, horaires et prix pour trouver le meilleur transport vers votre destination.</p>

        <!-- SEARCH BOX -->
        <div class="search-box">
            <form action="resultats.php" method="GET">
                <div class="search-field">
                    <label>🛫 Départ</label>
                    <select name="depart" required>
                        <option value="">Choisir la ville de départ</option>
                        <?php foreach ($villes as $v): ?>
                        <option value="<?= $v['id'] ?>"><?= clean($v['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="button" class="swap-btn" id="swapBtn" title="Inverser">⇄</button>

                <div class="search-field">
                    <label>🛬 Destination</label>
                    <select name="arrivee" required>
                        <option value="">Choisir la destination</option>
                        <?php foreach ($villes as $v): ?>
                        <option value="<?= $v['id'] ?>"><?= clean($v['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn-search">
                    🔍 Rechercher
                </button>
            </form>
        </div>
    </div>
</section>

<!-- ─── STATS ─── -->
<div class="stats-strip">
    <div class="stat-item">
        <div class="num"><?= $nbCompagnies ?>+</div>
        <div class="lbl">Compagnies</div>
    </div>
    <div class="stat-item">
        <div class="num"><?= $nbVilles ?></div>
        <div class="lbl">Villes desservies</div>
    </div>
    <div class="stat-item">
        <div class="num"><?= $nbLignes ?>+</div>
        <div class="lbl">Lignes disponibles</div>
    </div>
    <div class="stat-item">
        <div class="num">100%</div>
        <div class="lbl">Gratuit</div>
    </div>
</div>

<!-- ─── DESTINATIONS ─── -->
<section class="section" id="destinations">
    <div class="section-header">
        <h2>🗺️ Destinations populaires</h2>
        <p>Les villes les plus recherchées depuis Abidjan</p>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px;">
        <?php foreach ($destinations as $i => $d): ?>
        <a href="resultats.php?depart=1&arrivee=<?= $d['id'] ?>"
           class="animate-up"
           style="background:var(--white);border:1.5px solid var(--border);border-radius:var(--r-lg);
                  padding:20px;text-align:center;transition:all .25s;display:block;">
            <div style="font-size:28px;margin-bottom:8px;">
                <?= ['🏙️','🌆','🏘️','🌇','🌴','⛰️','🏞️','🌾'][$i % 8] ?>
            </div>
            <div style="font-family:var(--font-d);font-weight:700;font-size:16px;margin-bottom:3px;">
                <?= clean($d['nom']) ?>
            </div>
            <div style="font-size:12px;color:var(--text-3);margin-bottom:10px;"><?= clean($d['region']) ?></div>
            <div style="display:flex;justify-content:center;gap:8px;flex-wrap:wrap;">
                <span class="chip"><?= $d['nb_lignes'] ?> compagnie<?= $d['nb_lignes']>1?'s':'' ?></span>
                <?php if ($d['prix_min']): ?>
                <span class="chip text-orange fw-bold">dès <?= formatPrix($d['prix_min']) ?></span>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- ─── COMPAGNIES POPULAIRES ─── -->
<section class="section" style="background:var(--white);max-width:100%;padding:56px 0;">
<div style="max-width:1160px;margin:0 auto;padding:0 24px;">
    <div class="section-header">
        <h2>⭐ Compagnies les mieux notées</h2>
        <p>Choisies et évaluées par des milliers de voyageurs</p>
    </div>
    <div class="companies-grid">
        <?php foreach ($populaires as $c):
            $initiales = implode('', array_map(fn($w) => strtoupper($w[0]), explode(' ', $c['nom'])));
            $initiales = substr($initiales, 0, 2);
        ?>
        <div class="company-card animate-up">
            <div class="card-accent" style="background:<?= clean($c['logo_couleur']) ?>;"></div>
            <div class="card-body">
                <div class="card-top">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div class="company-logo" style="background:<?= clean($c['logo_couleur']) ?>;">
                            <?= clean($initiales) ?>
                        </div>
                        <div>
                            <div class="company-name"><?= clean($c['nom']) ?></div>
                            <div class="company-type"><?= clean($c['icone'].' '.$c['type_nom']) ?></div>
                        </div>
                    </div>
                    <div class="company-rating">
                        <div class="rating-num" style="color:<?= clean($c['logo_couleur']) ?>;">
                            <?= $c['note_moyenne'] ?: '—' ?>
                        </div>
                        <div class="stars"><?= etoiles($c['note_moyenne']) ?></div>
                        <div class="rating-avis"><?= $c['nb_avis'] ?> avis</div>
                    </div>
                </div>
                <p class="company-desc"><?= clean($c['description']) ?></p>
                <div class="company-contacts">
                    <?php if ($c['telephone']): ?>
                    <div class="contact-item">
                        <span>📞</span>
                        <span><?= clean($c['telephone']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($c['adresse']): ?>
                    <div class="contact-item">
                        <span>📍</span>
                        <span><?= clean($c['adresse']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer">
                <div>
                    <?php if ($c['prix_min']): ?>
                    <div class="prix-depuis">À partir de</div>
                    <div class="prix-valeur"><?= formatPrix($c['prix_min']) ?></div>
                    <?php else: ?>
                    <div class="prix-valeur">—</div>
                    <?php endif; ?>
                </div>
                <a href="compagnie.php?id=<?= $c['id'] ?>" class="btn-voir">Voir détails →</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div style="text-align:center;margin-top:28px;">
        <a href="compagnies.php" style="display:inline-flex;align-items:center;gap:8px;padding:12px 28px;
           border:2px solid var(--border);border-radius:var(--r);font-weight:600;font-size:14px;
           color:var(--text);transition:all .2s;" onmouseover="this.style.borderColor='var(--orange)';this.style.color='var(--orange)'"
           onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text)'">
            Voir toutes les compagnies →
        </a>
    </div>
</div>
</section>

<!-- ─── COMMENT ÇA MARCHE ─── -->
<section class="section">
    <div class="section-header" style="text-align:center;">
        <h2>💡 Comment ça marche ?</h2>
        <p>Trouvez votre transport en 3 étapes simples</p>
    </div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:24px;max-width:800px;margin:0 auto;">
        <?php foreach ([
            ['🔍','Choisissez','Sélectionnez votre ville de départ et votre destination dans le moteur de recherche.'],
            ['📊','Comparez', 'Consultez tous les transporteurs disponibles avec leurs prix, horaires et avis.'],
            ['📞','Contactez','Appelez directement la compagnie choisie pour réserver votre place.'],
        ] as $i => [$icon, $titre, $desc]): ?>
        <div style="text-align:center;padding:28px 20px;background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);">
            <div style="width:52px;height:52px;background:var(--orange-l);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;margin:0 auto 14px;">
                <?= $icon ?>
            </div>
            <div style="font-family:var(--font-d);font-weight:700;font-size:16px;margin-bottom:8px;"><?= $titre ?></div>
            <p style="font-size:13.5px;color:var(--text-2);line-height:1.6;"><?= $desc ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
