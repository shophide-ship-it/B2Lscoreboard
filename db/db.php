<?php
$host = 'mysql3114.db.sakura.ne.jp';
$dbname = 'kasugai-sp_b2l-league';
$username = 'kasugai-sp_b2l-league';
$password = 'B2L_db2025secure';

include 'db.php';
/** @var PDO $pdo */ // これを追加するとエディタの警告が消えることが多いです
$stmt = $pdo->query("SELECT * FROM teams");
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database {$dbname} :" . $e->getMessage());
}
?>
