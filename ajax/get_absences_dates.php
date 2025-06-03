<?php
session_start();
require_once '../config/db.php';

// Vérification de sécurité
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$etudiant_id = $_SESSION['user_id'];
$module_id = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;

// Validation du paramètre
if ($module_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID de module invalide']);
    exit;
}

try {
    // Récupérer les dates d'absence non justifiées pour ce module
    $stmt = $pdo->prepare("
        SELECT id_absence, DATE_FORMAT(date, '%Y-%m-%d') as date
        FROM absences 
        WHERE id_etudiant = ? AND id_module = ? AND justifiee = 0
        ORDER BY date DESC
    ");
    $stmt->execute([$etudiant_id, $module_id]);
    $absences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Retourner les résultats au format JSON
    header('Content-Type: application/json');
    echo json_encode($absences);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erreur de base de données']);
    error_log('Erreur AJAX get_absences_dates.php: ' . $e->getMessage());
    exit;
}