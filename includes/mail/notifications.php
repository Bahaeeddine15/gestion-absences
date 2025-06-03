<?php
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/templates/student.php';
require_once __DIR__ . '/templates/teacher.php';
require_once __DIR__ . '/templates/admin.php';
require_once __DIR__ . '/../../config/db.php';

/**
 * Envoyer une notification de dépôt de justificatif
 * 
 * @param int $etudiant_id ID de l'étudiant
 * @param int $module_id ID du module
 * @param string $date_absence Date d'absence
 * @return array Tableau contenant le statut d'envoi pour l'étudiant et le responsable
 */
function sendJustificatifNotification($etudiant_id, $module_id, $date_absence) {
    global $pdo;
    $result = [
        'etudiant' => false,
        'responsable' => false
    ];
    
    try {
        // Récupérer les infos de l'étudiant
        $stmt = $pdo->prepare("
            SELECT e.id_etudiant, e.nom, e.prenom, e.email, e.numero_apogee
            FROM etudiants e
            WHERE e.id_etudiant = ?
        ");
        $stmt->execute([$etudiant_id]);
        $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$etudiant) {
            error_log("Étudiant non trouvé: ID $etudiant_id");
            return $result;
        }
        
        /**
         * Génère le contenu de l'email pour le responsable lors du dépôt d'un justificatif
         *
         * @param array $etudiant Informations sur l'étudiant
         * @param array $module Informations sur le module et le responsable
         * @param string $date_absence Date de l'absence
         * @return string Contenu HTML de l'email
         */
        function buildEmailResponsable($etudiant, $module, $date_absence) {
            $dateFormatted = date('d/m/Y', strtotime($date_absence));
            $etudiantNom = htmlspecialchars($etudiant['nom']);
            $etudiantPrenom = htmlspecialchars($etudiant['prenom']);
            $moduleNom = htmlspecialchars($module['module_nom']);
            $respNom = isset($module['resp_nom']) ? htmlspecialchars($module['resp_nom']) : '';
            $respPrenom = isset($module['resp_prenom']) ? htmlspecialchars($module['resp_prenom']) : '';
        
            return "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #f8f9fa; padding: 15px; text-align: center; border-bottom: 3px solid #3498db; }
                    .content { padding: 20px 0; }
                    .footer { font-size: 12px; text-align: center; margin-top: 30px; color: #777; }
                    h1 { color: #3498db; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Gestion des Absences - ENSA Marrakech</h2>
                    </div>
                    <div class='content'>
                        <h1>Nouveau justificatif d'absence déposé</h1>
                        <p>Bonjour <strong>{$respPrenom} {$respNom}</strong>,</p>
                        <p>L'étudiant <strong>{$etudiantPrenom} {$etudiantNom}</strong> a déposé un justificatif pour l'absence suivante :</p>
                        <ul>
                            <li><strong>Module :</strong> {$moduleNom}</li>
                            <li><strong>Date :</strong> {$dateFormatted}</li>
                        </ul>
                        <p>Vous pouvez consulter le justificatif depuis votre espace responsable.</p>
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
        }
        
        // Récupérer les infos du module et son responsable
        $stmt = $pdo->prepare("
            SELECT m.id_module, m.nom as module_nom, 
                   r.id_responsable, r.nom as resp_nom, r.prenom as resp_prenom, r.email as resp_email
            FROM modules m
            LEFT JOIN responsables r ON m.id_responsable = r.id_responsable
            WHERE m.id_module = ?
        ");
        $stmt->execute([$module_id]);
        $module = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$module) {
            error_log("Module non trouvé: ID $module_id");
            return $result;
        }
        
        // Email pour l'étudiant
        if (!empty($etudiant['email'])) {
            $emailEtudiant = buildEmailEtudiant($etudiant, $module, $date_absence);
            $result['etudiant'] = sendEmail(
                $etudiant['email'],
                "Confirmation de dépôt de justificatif d'absence",
                $emailEtudiant
            );
        }
        
        // Email pour le responsable du module
        if (!empty($module['resp_email'])) {
            $emailResp = buildEmailResponsable($etudiant, $module, $date_absence);
            $result['responsable'] = sendEmail(
                $module['resp_email'],
                "Nouveau justificatif d'absence - " . $etudiant['nom'] . " " . $etudiant['prenom'],
                $emailResp
            );
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Erreur lors de l'envoi des notifications: " . $e->getMessage());
        return $result;
    }
}

/**
 * Envoyer un email de récapitulatif d'absences aux étudiants
 * 
 * @param int $etudiant_id ID de l'étudiant
 * @param array $absences Liste des absences
 * @return bool True si envoi réussi, sinon False
 */
function sendAbsencesSummary($etudiant_id, $absences = null) {
    global $pdo;
    
    try {
        // Récupérer les infos de l'étudiant
        $stmt = $pdo->prepare("
            SELECT e.id_etudiant, e.nom, e.prenom, e.email
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
                SELECT a.id_absence, a.date, a.justifiee,
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

        // Définir la fonction pour générer le contenu de l'email de récapitulatif
        if (!function_exists('buildAbsencesSummaryEmail')) {
            /**
             * Génère le contenu HTML de l'email de récapitulatif d'absences pour un étudiant
             *
             * @param array $etudiant Informations sur l'étudiant
             * @param string $absencesTable Tableau HTML des absences
             * @return string Contenu HTML de l'email
             */
            function buildAbsencesSummaryEmail($etudiant, $absencesTable) {
                $etudiantNom = htmlspecialchars($etudiant['nom']);
                $etudiantPrenom = htmlspecialchars($etudiant['prenom']);
                return "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #f8f9fa; padding: 15px; text-align: center; border-bottom: 3px solid #3498db; }
                        .content { padding: 20px 0; }
                        .footer { font-size: 12px; text-align: center; margin-top: 30px; color: #777; }
                        h1 { color: #3498db; }
                        table { border-collapse: collapse; width: 100%; }
                        th, td { border: 1px solid #ddd; padding: 8px; }
                        th { background-color: #f2f2f2; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Gestion des Absences - ENSA Marrakech</h2>
                        </div>
                        <div class='content'>
                            <h1>Récapitulatif de vos absences</h1>
                            <p>Bonjour <strong>{$etudiantPrenom} {$etudiantNom}</strong>,</p>
                            <p>Voici le récapitulatif de vos absences enregistrées :</p>
                            {$absencesTable}
                            <p>Pour toute question, veuillez contacter le service de gestion des absences.</p>
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
            }
        }

        // Construire et envoyer l'email
        $email = buildAbsencesSummaryEmail($etudiant, $absencesTable);
        return sendEmail(
            $etudiant['email'],
            "Récapitulatif de vos absences - ENSA Marrakech",
            $email
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
function sendWelcomeEmail($email, $nom, $prenom, $type, $password = null) {
    if (!function_exists('buildWelcomeEmail')) {
        /**
         * Génère le contenu HTML de l'email de bienvenue pour un nouvel utilisateur
         *
         * @param string $email Email de l'utilisateur
         * @param string $nom Nom de l'utilisateur
         * @param string $prenom Prénom de l'utilisateur
         * @param string $type Type d'utilisateur (student, professor, admin)
         * @param string|null $password Mot de passe (optionnel)
         * @return string Contenu HTML de l'email
         */
        function buildWelcomeEmail($email, $nom, $prenom, $type, $password = null) {
            $nom = htmlspecialchars($nom);
            $prenom = htmlspecialchars($prenom);
            $typeLabel = '';
            switch ($type) {
                case 'student':
                    $typeLabel = 'étudiant';
                    break;
                case 'professor':
                    $typeLabel = 'enseignant';
                    break;
                case 'admin':
                    $typeLabel = 'administrateur';
                    break;
                default:
                    $typeLabel = $type;
            }
            $passwordSection = '';
            if (!empty($password)) {
                $passwordSection = "<p><strong>Mot de passe temporaire :</strong> " . htmlspecialchars($password) . "</p>";
            }
            return "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #f8f9fa; padding: 15px; text-align: center; border-bottom: 3px solid #3498db; }
                    .content { padding: 20px 0; }
                    .footer { font-size: 12px; text-align: center; margin-top: 30px; color: #777; }
                    h1 { color: #3498db; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Gestion des Absences - ENSA Marrakech</h2>
                    </div>
                    <div class='content'>
                        <h1>Bienvenue sur la plateforme</h1>
                        <p>Bonjour <strong>{$prenom} {$nom}</strong>,</p>
                        <p>Votre compte <strong>{$typeLabel}</strong> a été créé avec succès sur le système de gestion des absences de l'ENSA Marrakech.</p>
                        <p>Vous pouvez maintenant vous connecter à la plateforme avec votre adresse email : <strong>{$email}</strong>.</p>
                        {$passwordSection}
                        <p>Nous vous recommandons de changer votre mot de passe lors de votre première connexion.</p>
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
        }
    }
    $emailContent = buildWelcomeEmail($email, $nom, $prenom, $type, $password);

    return sendEmail(
        $email,
        "Bienvenue sur le système de gestion des absences - ENSA Marrakech",
        $emailContent
    );
}

/**
 * Envoyer une notification de dépôt de justificatif uniquement à l'étudiant
 * 
 * @param int $etudiant_id ID de l'étudiant
 * @param int $module_id ID du module
 * @param string $date_absence Date d'absence
 * @return bool True si l'email a été envoyé avec succès
 */
function sendJustificatifOnlyToStudent($etudiant_id, $module_id, $date_absence) {
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
 * Envoyer une alerte d'absence à un étudiant
 * 
 * @param int $etudiant_id ID de l'étudiant
 * @param int $module_id ID du module
 * @param string $date_absence Date de l'absence
 * @return bool True si l'email a été envoyé avec succès
 */
function sendAbsenceAlertToStudent($etudiant_id, $module_id, $date_absence) {
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
        
        // Corps de l'email
        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
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
                    
                    <a href='https://gestion-absences.ensa-marrakech.ac.ma/etudiant/justifier_absence.php' class='button'>
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