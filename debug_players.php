<?php
// config.php の内容を安全に確認
$configPath = __DIR__ . '/config.php';

echo "<h2>Config File Debug</h2>";
echo "<pre>";

// ファイルの存在確認
if (file_exists($configPath)) {
    echo "Config file EXISTS at: " . $configPath . "\n\n";
    
    // ファイル内容を表示（パスワードはマスク）
    $content = file_get_contents($configPath);
    
    // パスワードをマスク
    $masked = preg_replace(
        "/(password|passwd|pass|secret|token|access_token)(['\"]?\s*(?:=>|=)\s*['\"])([^'\"]+)(['\"])/i",
        '$1$2****$4',
        $content
    );
    
    echo "=== CONFIG CONTENT (passwords masked) ===\n";
    echo htmlspecialchars($masked);
    echo "\n\n";
} else {
    echo "Config file NOT FOUND at: " . $configPath . "\n";
}

// config.php を読み込み
require_once $configPath;

echo "\n=== DEFINED CONSTANTS ===\n";
$allConstants = get_defined_constants(true);
if (isset($allConstants['user'])) {
    foreach ($allConstants['user'] as $name => $value) {
        if (stripos($name, 'pass') !== false || stripos($name, 'secret') !== false || stripos($name, 'token') !== false) {
            echo "$name = ****\n";
        } else {
            echo "$name = " . var_export($value, true) . "\n";
        }
    }
} else {
    echo "No user-defined constants found.\n";
}

echo "\n=== DEFINED FUNCTIONS ===\n";
$functions = get_defined_functions();
$userFunctions = $functions['user'];
echo "User-defined functions:\n";
foreach ($userFunctions as $func) {
    echo "  - $func()\n";
}

echo "\n=== INCLUDED FILES ===\n";
$included = get_included_files();
foreach ($included as $file) {
    echo "  " . $file . "\n";
}

echo "</pre>";
?>
