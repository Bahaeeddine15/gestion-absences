<?php
$host = 'localhost';
$dbname = 'gestion_absences';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

    PDO::ATTR_EMULATE_PREPARES => false,

    PDO::MYSQL_ATTR_FOUND_ROWS => true
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    error_log('Database Connection Error: ' . $e->getMessage());
    die('Could not connect to the database. Please try again later.');
}
?>