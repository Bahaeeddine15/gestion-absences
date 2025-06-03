<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/mail_functions.php';

CheckAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_bulk'])) {
    $filiere_id = $_POST['filiere_id'];
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    if (empty($subject) || empty($message)) {
        $_SESSION['error'] = "Le sujet et le message sont obligatoires";
        header('Location: admin/gestion_etudiants.php');
        exit;
    }
    
    try {
        $query = "SELECT id_etudiant, nom, prenom, email FROM etudiants WHERE email IS NOT NULL";
        $params = [];
        
        if ($filiere_id !== 'all') {
            $query .= " AND id_filiere = ?";
            $params[] = $filiere_id;
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        $count = 0;
        while ($etudiant = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $emailBody = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; }
                    </style>
                </head>
                <body>
                    <h2>Message de l'administration</h2>
                    <p>Bonjour {$etudiant['prenom']} {$etudiant['nom']},</p>
                    <div>" . nl2br(htmlspecialchars($message)) . "</div>
                    <p>Cordialement,<br>Administration ENSA</p>
                </body>
                </html>
            ";
            
            if (sendEmail($etudiant['email'], $subject, $emailBody)) {
                $count++;
            }
            
            // Pause pour ne pas surcharger le serveur SMTP
            usleep(200000); // 0.2 seconde
        }
        
        $_SESSION['success'] = "Email envoyé avec succès à $count étudiant(s)";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de l'envoi des emails: " . $e->getMessage();
    }
    
    header('Location: admin/gestion_etudiants.php');
    exit;
}