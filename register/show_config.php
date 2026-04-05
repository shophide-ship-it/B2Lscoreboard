<?php
// /b2l/register/show_config.php
header('Content-Type: text/plain; charset=utf-8');
$lines = file('/home/kasugai-sp/www/b2l/config.php');
foreach ($lines as $num => $line) {
    printf("%3d: %s", $num + 1, $line);
}
?>
