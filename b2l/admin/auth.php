<?php
require_once dirname(__DIR__) . '/config.php';

function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}
function requireLogin() {
    if (!isLoggedIn()) { header('Location: ' . url('admin/index.php')); exit; }
}
function handleLogin() {
    if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='login') {
        if ($_POST['username']===ADMIN_USER && $_POST['password']===ADMIN_PASS) {
            $_SESSION['admin_logged_in'] = true;
            header('Location: ' . url('admin/index.php')); exit;
        }
        return 'ユーザー名またはパスワードが正しくありません。';
    }
    return null;
}
function handleLogout() {
    if (($_GET['action']??'')==='logout') { session_destroy(); header('Location: ' . url('admin/index.php')); exit; }
}
?>

