<?php
// auth.php

session_start();
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: index.php'); // 成功したら管理画面にリダイレクト
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}

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

