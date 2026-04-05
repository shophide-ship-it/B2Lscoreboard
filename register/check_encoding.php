<?php
// /b2l/register/check_encoding.php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../config.php';

$file = __DIR__ . '/players.php';
$content = file_get_contents($file);

echo "<h2>File Encoding Check</h2>";
echo "<p>File size: " . strlen($content) . " bytes</p>";

// 最初の500バイトをhex表示
echo "<h3>First 500 bytes (hex):</h3>";
echo "<pre>";
$first500 = substr($content, 0, 500);
for ($i = 0; $i < strlen($first500); $i++) {
    printf("%02X ", ord($first500[$i]));
    if (($i + 1) % 16 === 0) echo "\n";
}
echo "</pre>";

// BOMチェック
$bom = substr($content, 0, 3);
if ($bom === "\xEF\xBB\xBF") {
    echo "<p style='color:red'>?? UTF-8 BOM detected!</p>";
} else {
    echo "<p>No BOM</p>";
}

// mb_detect_encoding
echo "<p>Detected encoding: " . mb_detect_encoding($content, ['UTF-8', 'SJIS', 'EUC-JP', 'ISO-8859-1'], true) . "</p>";

// 日本語テスト
echo "<h3>Direct Japanese test:</h3>";
echo "<p>選手登録フォーム - テスト</p>";
echo "<p>? B2L リーグ</p>";
?>
