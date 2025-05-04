<?php
require_once './config/db.php';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

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

function isAdmin(){
    if(!isConnected()){
        return false;
    }
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function isStudent(){
    if(!isConnected()){
        return false;
    }
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'etudiant';
}
?>