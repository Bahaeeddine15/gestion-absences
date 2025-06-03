<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// Include only student-related email files
require_once __DIR__ . '/mail/config.php';
require_once __DIR__ . '/mail/core.php';
require_once __DIR__ . '/mail/templates/student.php';

/**
 * Envoyer une notification de dépôt de justificatif uniquement à l'étudiant
 * 
 * @param int $etudiant_id ID de l'étudiant
 * @param int $module_id ID du module
 * @param string $date_absence Date d'absence
 * @return bool True si l'email a été envoyé avec succès
 */
function sendJustificatifOnlyToStudent($etudiant_id, $module_id, $date_absence)
{
    global $pdo;

    try {
        // Récupérer les infos de l'étudiant
        $stmt = $pdo->prepare("
            SELECT e.id_etudiant, e.nom, e.prenom, e.email, e.numero_apogee
            FROM etudiants e
            WHERE e.id_etudiant = ?
        ");
        $stmt->execute([$etudiant_id]);
        $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$etudiant || empty($etudiant['email'])) {
            error_log("Étudiant non trouvé ou pas d'email: ID $etudiant_id");
            return false;
        }

        // Récupérer les infos du module
        $stmt = $pdo->prepare("
            SELECT m.id_module, m.nom as module_nom
            FROM modules m
            WHERE m.id_module = ?
        ");
        $stmt->execute([$module_id]);
        $module = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$module) {
            error_log("Module non trouvé: ID $module_id");
            return false;
        }

        // Email pour l'étudiant uniquement
        $emailEtudiant = buildEmailEtudiant($etudiant, $module, $date_absence);
        return sendEmail(
            $etudiant['email'],
            "Confirmation de dépôt de justificatif d'absence",
            $emailEtudiant
        );
    } catch (Exception $e) {
        error_log("Erreur lors de l'envoi de la notification à l'étudiant: " . $e->getMessage());
        return false;
    }
}

/**
 * Redirection de l'API pour maintenir la compatibilité
 */
function sendJustificatifNotificationToStudent($etudiant_id, $module_id, $date_absence)
{
    return sendJustificatifOnlyToStudent($etudiant_id, $module_id, $date_absence);
}

function sendAbsencesSummaryToStudent($etudiant_id, $absences = null)
{
    return sendAbsencesSummary($etudiant_id, $absences);
}

function sendWelcomeEmailToStudent($email, $nom, $prenom, $password = null)
{
    return sendWelcomeEmail($email, $nom, $prenom, 'student', $password);
}

function sendAbsenceAlertToStudent($etudiant_id, $module_id, $date_absence)
{
    return sendAbsenceAlert($etudiant_id, $module_id, $date_absence);
}

/**
 * Envoyer une alerte d'absence à un étudiant
 * 
 * @param int $etudiant_id ID de l'étudiant
 * @param int $module_id ID du module
 * @param string $date_absence Date de l'absence
 * @return bool True si l'email a été envoyé avec succès
 */
function sendAbsenceAlert($etudiant_id, $module_id, $date_absence)
{
    global $pdo;

    try {
        // Récupérer les infos de l'étudiant
        $stmt = $pdo->prepare("
            SELECT e.nom, e.prenom, e.email
            FROM etudiants e
            WHERE e.id_etudiant = ?
        ");
        $stmt->execute([$etudiant_id]);
        $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$etudiant || empty($etudiant['email'])) {
            return false;
        }

        // Récupérer le module
        $stmt = $pdo->prepare("SELECT nom FROM modules WHERE id_module = ?");
        $stmt->execute([$module_id]);
        $module = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$module) {
            return false;
        }

        $dateFormatted = date('d/m/Y', strtotime($date_absence));

        // Corps de l'email complet
        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: white; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                .header { background-color: #f8f9fa; padding: 15px; text-align: center; border-bottom: 3px solid #e74c3c; }
                .content { padding: 20px 0; }
                .footer { font-size: 12px; text-align: center; margin-top: 30px; color: #777; }
                h1 { color: #e74c3c; }
                .button { display: inline-block; background-color: #3498db; color: white; padding: 10px 20px; 
                         text-decoration: none; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Gestion des Absences - ENSA Marrakech</h2>
                </div>
                <div class='content'>
                    <h1>Notification d'absence enregistrée</h1>
                    
                    <p>Bonjour <strong>{$etudiant['prenom']} {$etudiant['nom']}</strong>,</p>
                    
                    <p>Nous vous informons qu'une absence a été enregistrée à votre nom avec les informations suivantes :</p>
                    
                    <ul>
                        <li><strong>Module :</strong> {$module['nom']}</li>
                        <li><strong>Date :</strong> {$dateFormatted}</li>
                    </ul>
                    
                    <p>Si cette absence est justifiée, veuillez déposer un justificatif via votre espace étudiant 
                    dans les plus brefs délais.</p>
                    
                    <a href='http://localhost/gestion-absences/etudiant/justifier_absence.php' class='button'>
                        Déposer un justificatif
                    </a>
                    
                    <p>Pour rappel, un nombre excessif d'absences non justifiées peut avoir un impact sur votre évaluation.</p>
                    
                    <p>Cordialement,<br>
                    Le service de gestion des absences<br>
                    ENSA Marrakech</p>
                </div>
                <div class='footer'>
                    <p>Ce message a été généré automatiquement, merci de ne pas y répondre.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        return sendEmail(
            $etudiant['email'],
            "Notification d'absence - " . $module['nom'],
            $body
        );
    } catch (Exception $e) {
        error_log("Erreur lors de l'envoi de l'alerte d'absence: " . $e->getMessage());
        return false;
    }
}

/**
 * Construire le tableau des absences
 * 
 * @param array $absences Liste des absences
 * @return string HTML du tableau des absences
 */
function buildAbsencesTable($absences)
{
    if (empty($absences)) {
        return "<p>Aucune absence n'a été enregistrée pour le moment.</p>";
    }

    $html = "
    <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
        <thead>
            <tr style='background-color: #f3f4f6;'>
                <th style='padding: 10px; border: 1px solid #ddd;'>Date</th>
                <th style='padding: 10px; border: 1px solid #ddd;'>Module</th>
                <th style='padding: 10px; border: 1px solid #ddd;'>Statut</th>
            </tr>
        </thead>
        <tbody>";

    foreach ($absences as $absence) {
        $date = date('d/m/Y', strtotime($absence['date']));
        $statusColor = $absence['justifiee'] ? '#4caf50' : '#f44336';
        $justifText = $absence['justifiee'] ? 'Justifiée' : 'Non justifiée';

        $html .= "
            <tr>
                <td style='padding: 10px; border: 1px solid #ddd;'>{$date}</td>
                <td style='padding: 10px; border: 1px solid #ddd;'>{$absence['module_nom']}</td>
                <td style='padding: 10px; border: 1px solid #ddd; color: {$statusColor};'>{$justifText}</td>
            </tr>";
    }

    $html .= "
        </tbody>
    </table>";

    return $html;
}

/**
 * Envoyer un récapitulatif d'absences à l'étudiant
 * 
 * @param int $etudiant_id ID de l'étudiant
 * @param array $absences Liste des absences (optionnel)
 * @return bool True si l'email a été envoyé avec succès
 */
function sendAbsencesSummary($etudiant_id, $absences = null)
{
    global $pdo;

    try {
        // Récupérer les infos de l'étudiant
        $stmt = $pdo->prepare("
            SELECT e.nom, e.prenom, e.email
            FROM etudiants e
            WHERE e.id_etudiant = ?
        ");
        $stmt->execute([$etudiant_id]);
        $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$etudiant || empty($etudiant['email'])) {
            return false;
        }

        // Si aucune absence n'est fournie, récupérer de la base
        if ($absences === null) {
            $stmt = $pdo->prepare("
                SELECT a.date, a.justifiee,
                       m.nom as module_nom
                FROM absences a
                JOIN modules m ON a.id_module = m.id_module
                WHERE a.id_etudiant = ?
                ORDER BY a.date DESC
            ");
            $stmt->execute([$etudiant_id]);
            $absences = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Construire le tableau HTML des absences
        $absencesTable = buildAbsencesTable($absences);

        // Construire l'email
        $emailBody = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: white; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                .header { background-color: #f8f9fa; padding: 15px; text-align: center; border-bottom: 3px solid #3498db; }
                .content { padding: 20px 0; }
                .footer { font-size: 12px; text-align: center; margin-top: 30px; color: #777; border-top: 1px solid #eee; padding-top: 20px; }
                h1 { color: #3498db; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Gestion des Absences - ENSA Marrakech</h2>
                </div>
                <div class='content'>
                    <h1>Récapitulatif de vos absences</h1>
                    
                    <p>Bonjour <strong>{$etudiant['prenom']} {$etudiant['nom']}</strong>,</p>
                    
                    <p>Voici un récapitulatif de vos absences enregistrées dans notre système :</p>
                    
                    $absencesTable
                    
                    <p>Pour rappel, vous pouvez justifier vos absences en déposant un document justificatif
                    via votre espace étudiant.</p>
                    
                    <p>Cordialement,<br>
                    Le service de gestion des absences<br>
                    ENSA Marrakech</p>
                </div>
                <div class='footer'>
                    <p>Ce message a été généré automatiquement, merci de ne pas y répondre.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        return sendEmail(
            $etudiant['email'],
            "Récapitulatif de vos absences - ENSA Marrakech",
            $emailBody
        );
    } catch (Exception $e) {
        error_log("Erreur lors de l'envoi du récapitulatif d'absences: " . $e->getMessage());
        return false;
    }
}

/**
 * Envoyer un message de bienvenue à un nouvel utilisateur
 * 
 * @param string $email Email de l'utilisateur
 * @param string $nom Nom de l'utilisateur
 * @param string $prenom Prénom de l'utilisateur
 * @param string $type Type d'utilisateur (student, professor, admin)
 * @param string $password Mot de passe (optionnel)
 * @return bool True si envoi réussi, sinon False
 */
function sendWelcomeEmail($email, $nom, $prenom, $type, $password = null)
{
    $typeLabel = "étudiant";
    $specificContent = "
        <p>En tant qu'étudiant, vous pouvez désormais :</p>
        <ul>
            <li>Consulter votre historique d'absences</li>
            <li>Recevoir des notifications en temps réel</li>
            <li>Déposer des justificatifs en ligne</li>
            <li>Suivre l'état de vos demandes de justification</li>
        </ul>
        <p>Nous vous rappelons l'importance de justifier vos absences dans les délais impartis pour éviter toute sanction académique.</p>";

    // Afficher uniquement l'email de connexion sans mentionner le mot de passe
    $loginInfo = "
        <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #3498db; margin: 20px 0;'>
            <p><strong>Votre identifiant de connexion :</strong></p>
            <p><strong>Email :</strong> $email</p>
            <p>Vous pouvez vous connecter en utilisant le mot de passe que vous avez défini lors de votre inscription.</p>
        </div>";

    $emailBody = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f9f9f9; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: white; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
            .header { background-color: #3a6c99; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .header h2 { color: white; margin: 0; }
            .content { padding: 20px; }
            .footer { font-size: 12px; text-align: center; margin-top: 30px; color: #777; border-top: 1px solid #eee; padding-top: 20px; }
            h1 { color: #3a6c99; }
            .button {
                display: inline-block;
                background-color: #3a6c99;
                color: white;
                padding: 12px 25px;
                text-decoration: none;
                border-radius: 5px;
                margin: 20px 0;
                font-weight: bold;
            }
            .button:hover {
                background-color: #2c5278;
            }
            ul { background-color: #f8f9fa; padding: 15px 15px 15px 35px; border-radius: 5px; }
            li { padding: 5px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Système de Gestion des Absences - ENSA Marrakech</h2>
            </div>
            <div class='content'>
                <h1>Bienvenue, $prenom !</h1>
                
                <p>Nous confirmons votre inscription sur la plateforme de gestion des absences de l'ENSA Marrakech.</p>
                
                <p>Votre compte $typeLabel a été créé avec succès et est maintenant actif.</p>
                
                $loginInfo
                
                $specificContent
                
                <p style='text-align:center;'>
                    <a href='http://localhost/gestion-absences/' class='button'>Accéder à la plateforme</a>
                </p>
                
                <p>Si vous rencontrez des difficultés pour vous connecter, ou si vous avez des questions concernant l'utilisation 
                de la plateforme, n'hésitez pas à contacter notre département informatique.</p>
                
                <p>Cordialement,<br>
                <strong>Le service de gestion des absences</strong><br>
                ENSA Marrakech</p>
            </div>
            <div class='footer'>
                <p>Ce message a été généré automatiquement, merci de ne pas y répondre.</p>
                <p>&copy; " . date('Y') . " - École Nationale des Sciences Appliquées de Marrakech</p>
            </div>
        </div>
    </body>
    </html>
    ";

    return sendEmail(
        $email,
        "Confirmation d'inscription - Système de gestion des absences ENSA",
        $emailBody
    );
}
