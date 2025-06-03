<?php

/**
 * Construit le corps de l'email pour l'étudiant (justificatif)
 */
function buildEmailEtudiant($etudiant, $module, $date_absence) {
    $dateFormatted = date('d/m/Y', strtotime($date_absence));
    
    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #f8f9fa; padding: 15px; text-align: center; border-bottom: 3px solid #e67e22; }
            .content { padding: 20px 0; }
            .footer { font-size: 12px; text-align: center; margin-top: 30px; color: #777; }
            h1 { color: #e67e22; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Gestion des Absences - ENSA Marrakech</h2>
            </div>
            <div class='content'>
                <h1>Confirmation de justificatif d'absence</h1>
                
                <p>Bonjour <strong>{$etudiant['prenom']} {$etudiant['nom']}</strong>,</p>
                
                <p>Nous vous confirmons que votre justificatif d'absence a bien été reçu et enregistré 
                dans notre système pour l'absence suivante :</p>
                
                <ul>
                    <li><strong>Module :</strong> {$module['module_nom']}</li>
                    <li><strong>Date :</strong> {$dateFormatted}</li>
                </ul>
                
                <p>Votre absence a été marquée comme justifiée. Le responsable du module a été informé.</p>
                
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
