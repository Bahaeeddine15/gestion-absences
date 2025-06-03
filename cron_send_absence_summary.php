<?php
// Vérifier si le script est appelé via le Web par un administrateur ou via cron
$isWebCall = isset($_SERVER['HTTP_HOST']);
$isAdminTrigger = isset($_GET['admin_trigger']) && $_GET['admin_trigger'] == 1;

// Si appelé via le Web, vérifier l'authentification
if ($isWebCall) {
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
        echo "Accès non autorisé.";
        exit;
    }
}

require_once 'config/db.php';
require_once 'includes/mail_functions.php';

// Récupérer tous les étudiants ayant au moins une absence
$stmt = $pdo->query("
    SELECT DISTINCT e.id_etudiant, e.email, e.nom, e.prenom 
    FROM etudiants e
    JOIN absences a ON e.id_etudiant = a.id_etudiant
    WHERE e.email IS NOT NULL AND e.email != ''
");

$count = 0;
$startTime = microtime(true);
$etudiants = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalEtudiants = count($etudiants);

// Ajouter une indication de progression si appelé via le Web
if ($isWebCall && !$isAdminTrigger) {
    echo "<p>Envoi des récapitulatifs à {$totalEtudiants} étudiants...</p>";
    flush();
}

foreach ($etudiants as $etudiant) {
    $result = sendAbsencesSummaryToStudent($etudiant['id_etudiant']);
    if ($result) {
        $count++;
    }
}

$duration = round(microtime(true) - $startTime, 2);

// Formater le message de résultat
$message = ($count === 0) 
    ? "Aucun récapitulatif d'absence n'a été envoyé." 
    : "Récapitulatif d'absences envoyé à {$count}/{$totalEtudiants} étudiant(s) en {$duration} secondes.";

// Si déclenché par un administrateur via le bouton, rediriger vers la page de gestion
if ($isAdminTrigger) {
    header("Location: admin/gestion_absences.php?summary_sent=1&count={$count}");
    exit;
} else {
    // Sinon afficher le résultat directement
    echo $message;
}