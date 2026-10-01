<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'transport_ci');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:40px;text-align:center;background:#fff1f2;border:2px solid #fca5a5;margin:40px;border-radius:12px;">
                <h2 style="color:#dc2626;">❌ Erreur de base de données</h2>
                <p>Vérifiez <strong>config/database.php</strong></p>
                <code style="color:#6b7280;">'.$e->getMessage().'</code></div>');
        }
    }
    return $pdo;
}
?>
