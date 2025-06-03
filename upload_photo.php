<?php
require_once 'config/db.php';
require_once 'includes/mail_functions.php';

$error = '';
$success = '';

if (isset($_POST['register'])) {
    // Récupération des données
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $numero_apogee = trim($_POST['numero_apogee']);
    $id_filiere = $_POST['id_filiere'];

    // Gestion de la photo de profil
    $photo_profil_path = null;
    if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/photos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileTmpPath = $_FILES['photo_profil']['tmp_name'];
        $fileName = uniqid() . '_' . basename($_FILES['photo_profil']['name']);
        $filePath = $uploadDir . $fileName;
        $fileType = mime_content_type($fileTmpPath);

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($fileTmpPath, $filePath)) {
                $photo_profil_path = $filePath;
            } else {
                $error = "Erreur lors de l'upload de la photo de profil.";
            }
        } else {
            $error = "Format de photo non supporté.";
        }
    } else {
        $error = "Veuillez sélectionner une photo de profil.";
    }

    if (empty($error)) {
        // Hashage du mot de passe
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Vérification que l'email et le numéro apogée n'existent pas déjà
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM etudiants WHERE email = ? OR numero_apogee = ?");
        $stmt->execute([$email, $numero_apogee]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Cet email ou ce numéro Apogée est déjà utilisé";
        } else {
            // Insertion de l'étudiant avec la photo de profil
            $stmt = $pdo->prepare("INSERT INTO etudiants (nom, prenom, email, numero_apogee, password, id_filiere, photo_profil) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $prenom, $email, $numero_apogee, $hashed_password, $id_filiere, $photo_profil_path]);

            // Envoi de l'email de bienvenue
            sendWelcomeEmailToStudent($email, $nom, $prenom, $password);

            $success = "Compte étudiant créé avec succès! Vous pouvez maintenant vous connecter.";
        }
    }
}

// Redirection ou affichage d'un message
if (!empty($error)) {
    urlencode($error);
    exit;
} else {
    header("Location: register.php?success=" . urlencode($success));
    exit;
}
?>