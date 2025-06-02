<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: index.php');
    exit;
}

$apogee = $_SESSION['apogee'];
$etudiant_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date_absence = $_POST['date_absence'];
    $module_id = $_POST['module_id'];
    $file = $_FILES['justificatif'];

    // Vérification du fichier
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
    if (!in_array($file['type'], $allowedTypes)) {
        $_SESSION['upload_error'] = "Format de fichier non autorisé.";
        header('Location: dashboard_etudiant.php');
        exit;
    }

    // Création du dossier justificatifs/{apogee}/
    $dir = "justificatifs/$apogee/";
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    // Nom du fichier : justif_YYYY-MM-DD.ext
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = "justif_{$date_absence}." . strtolower($ext);
    $filepath = $dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Enregistrement en base du justificatif
        $stmt = $pdo->prepare("INSERT INTO justificatifs (etudiant_id, module_id, date_absence, fichier_path) VALUES (?, ?, ?, ?)");
        $stmt->execute([$etudiant_id, $module_id, $date_absence, "justificatifs/$apogee/$filename"]);

        // Mettre à jour l'absence correspondante comme justifiée
        $stmt = $pdo->prepare("UPDATE absences SET justifiee = 1 WHERE id_etudiant = ? AND id_module = ? AND date = ?");
        $stmt->execute([$etudiant_id, $module_id, $date_absence]);

        $_SESSION['upload_success'] = "Justificatif envoyé avec succès.";
    } else {
        $_SESSION['upload_error'] = "Erreur lors de l'upload du fichier.";
    }
    header('Location: dashboard_etudiant.php');
    exit;
}
?>