<?php
// /b2l/register/debug_runtime2.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h3>config.php 関数確認</h3>";
require_once __DIR__ . '/../config.php';
echo "getDB(): " . (function_exists('getDB') ? '✅' : '❌') . "<br>";
echo "isRegistrationOpen(): " . (function_exists('isRegistrationOpen') ? '✅' : '❌') . "<br>";
echo "getDeadline(): " . (function_exists('getDeadline') ? '✅' : '❌') . "<br>";

echo "<h3>isRegistrationOpen() 実行テスト</h3>";
try {
    $result = isRegistrationOpen();
    echo "結果: " . ($result ? 'OPEN' : 'CLOSED') . "<br>";
} catch (Throwable $e) {
    echo "エラー: " . $e->getMessage() . " (行: " . $e->getLine() . ")<br>";
}

echo "<h3>getDeadline() 実行テスト</h3>";
try {
    $deadline = getDeadline();
    echo "締切: " . $deadline->format('Y-m-d H:i:s') . "<br>";
} catch (Throwable $e) {
    echo "エラー: " . $e->getMessage() . " (行: " . $e->getLine() . ")<br>";
}

echo "<h3>players.php include テスト</h3>";
$_GET['token'] = $_GET['token'] ?? 'b913d2f5473df9af25f1a901861a0481f0b78196aefceb2ddbd9f6a71146a7ac';
try {
    include __DIR__ . '/players.php';
} catch (Throwable $e) {
    echo "<p style='color:red;'><strong>エラー:</strong> " . $e->getMessage() . "</p>";
    echo "<p>ファイル: " . $e->getFile() . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
