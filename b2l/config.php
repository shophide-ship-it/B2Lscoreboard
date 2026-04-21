<?php
$host = 'mysql80.kasugai-sp.sakura.ne.jp';
$dbname = 'kasugai-sp_b2l-league';
$username = 'kasugai-sp_b2l-league';
$password = 'B2L_db2025secure';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database $dbname :" . $e->getMessage());
}
?>
