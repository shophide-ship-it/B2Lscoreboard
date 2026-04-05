<?php
require_once dirname(__DIR__) . '/config.php';

function isLoggedIn()
{
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function handleLogin()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
        $user = $_POST['username'] ?? '';
        $pass = $_POST['password'] ?? '';
        if ($user === ADMIN_USER && $pass === ADMIN_PASS) {
            $_SESSION['admin_logged_in'] = true;
            header('Location: index.php');
            exit;
        }
        return 'ユーザー名またはパスワードが正しくありません。';
    }
    return null;
}

function handleLogout()
{
    if (isset($_GET['action']) && $_GET['action'] === 'logout') {
        session_destroy();
        header('Location: index.php');
        exit;
    }
}
?>