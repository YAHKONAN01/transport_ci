<?php
require_once __DIR__ . '/../config/database.php';

function clean($s) {
    return htmlspecialchars(trim($s ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatPrix($p) {
    return number_format($p, 0, ',', ' ') . ' FCFA';
}

function etoiles($note) {
    $note = round($note);
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= $i <= $note ? '<span class="star filled">★</span>' : '<span class="star">★</span>';
    }
    return $html;
}

function getVilles() {
    return getDB()->query("SELECT * FROM villes WHERE actif=1 ORDER BY nom")->fetchAll();
}

function getVilleById($id) {
    $s = getDB()->prepare("SELECT * FROM villes WHERE id=?");
    $s->execute([$id]);
    return $s->fetch();
}

function slugify($s) {
    $s = strtolower(trim($s));
    $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s);
    return preg_replace('/[^a-z0-9]+/', '-', $s);
}

function getDepuisRoot() {
    $script = realpath($_SERVER['SCRIPT_FILENAME']);
    $root   = realpath(__DIR__ . '/..');
    return dirname($script) === $root ? '' : '../';
}
?>
