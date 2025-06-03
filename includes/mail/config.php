<?php
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Configuration des paramètres d'envoi d'email
 * 
 * @param PHPMailer $mail L'instance PHPMailer à configurer
 */
function configureMail(PHPMailer $mail) {
    // Configuration du serveur SMTP - À MODIFIER SELON VOTRE CONFIGURATION
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'malikaizelgue@gmail.com';
    $mail->Password   = 'ukcm jifk qdyb qcox';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    
    // Paramètres de l'expéditeur
    $mail->setFrom('no-reply@ensa-marrakech.ac.ma', 'Gestion des Absences ENSA');
    $mail->CharSet = 'UTF-8';
}