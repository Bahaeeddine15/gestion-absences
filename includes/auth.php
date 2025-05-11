<?php
// On configure les cookies AVANT de démarrer une session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false, // mettre true si votre site est en HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start(); // on démarre la session juste après avoir défini les cookies
}

require_once './config/db.php';

function isStarted() {
    return session_status() === PHP_SESSION_ACTIVE;
}

function isConnected() {
    if (!isStarted()) {
        session_start();
    }

    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return false;
    }

    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity'] > 1800)) {
        session_unset();
        session_destroy();
        return false;
    }

    $_SESSION['last_activity'] = time();

    return true;
}

function isAdmin() {
    return isConnected() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function isStudent() {
    return isConnected() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'student';
}

function checkAdmin() {
    if (!isAdmin()) {
        header('Location: index.php');
        exit();
    }
}

function checkEtudiant() {
    if (!isStudent()) {
        header('Location: index.php');
        exit();
    }
}
