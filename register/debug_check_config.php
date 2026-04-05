<?php
// /b2l/register/debug_check_config.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';

echo "<h3>config.php 内の関数確認</h3>";
echo "getDB(): " . (function_exists('getDB') ? '✅ 存在' : '❌ 未定義') . "<br>";
echo "isRegistrationOpen(): " . (function_exists('isRegistrationOpen') ? '✅ 存在' : '❌ 未定義') . "<br>";
echo "getDeadline(): " . (function_exists('getDeadline') ? '✅ 存在' : '❌ 未定義') . "<br>";

echo "<h3>config.php ファイルサイズ</h3>";
$configPath = __DIR__ . '/../config.php';
echo "パス: " . realpath($configPath) . "<br>";
echo "サイズ: " . filesize($configPath) . " bytes<br>";

echo "<h3>config.php 内容（最後の50行）</h3>";
$lines = file($configPath);
$total = count($lines);
echo "総行数: " . $total . "<br><pre>";
// 全行表示
foreach ($lines as $i => $line) {
    echo ($i+1) . ": " . htmlspecialchars($line);
}
echo "</pre>";
?>
