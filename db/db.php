<?php
$host = 'mysql3114.db.sakura.ne.jp';
$dbname = 'kasugai-sp_b2l-league';
$user = 'kasugai-sp_b2l-league';
$pass = 'B2L_db2025secure';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
?>
