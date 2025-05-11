<?php
$title = "Inscription - Gestion des absences";

require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_type = $_POST['user_type'] ?? '';

    if ($user_type === 'student') {
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $email = $_POST['email'];
        $numero_apogee = $_POST['numero_apogee'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $id_filiere = $_POST['id_filiere'];

        if ($password !== $confirm_password) {
            echo "Les mots de passe ne correspondent pas.";
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO etudiants (nom, prenom, email, numero_apogee, password, id_filiere)
                                   VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $prenom, $email, $numero_apogee, $hashed_password, $id_filiere]);

            header("Location: index.php?inscription=success");
            exit;
        } catch (PDOException $e) {
            echo "Erreur : " . $e->getMessage();
        }
    } elseif ($user_type === 'admin') {
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $email = $_POST['email'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $admin_code = $_POST['admin_code'];

        /*if ($admin_code !== "ADMIN123") {
            echo "Code administrateur invalide.";
            exit;
        }*/

        if ($password !== $confirm_password) {
            echo "Les mots de passe ne correspondent pas.";
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO admins (username, password, email)
                                   VALUES (?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $email]);

            header("Location: index.php?inscription=success");
            exit;
        } catch (PDOException $e) {
            echo "Erreur : " . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';

try {
    $stmt = $pdo->query("SELECT id, nom FROM filieres ORDER BY nom");
    $filieres = $stmt->fetchAll();
} catch (PDOException $e) {
    $filieres = [];
}
?>

<link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />

<div class="container" id="container">
    <div class="form-container register-container">
        <!-- Correction ici : action="" -->
        <form action="" method="post">
            <input type="hidden" name="user_type" value="student">
            <h1>Inscription Étudiant</h1>
            <input type="text" name="nom" placeholder="Nom" required>
            <input type="text" name="prenom" placeholder="Prénom" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="numero_apogee" placeholder="Numéro Apogée" required>
<!--
            <select name="id_filiere" required>
                <option value="">-- Sélectionner une filière --</option>
                <?php /*foreach ($filieres as $filiere): ?>
                    <option value="<?= htmlspecialchars($filiere['id']) ?>">
                        <?= htmlspecialchars($filiere['nom']) ?>
                    </option>
                <?php endforeach;*/ ?>
            </select>  -->

            <input type="password" name="password" placeholder="Mot de passe" required>
            <input type="password" name="confirm_password" placeholder="Confirmer le mot de passe" required>

            <button type="submit" class="btn">S'inscrire</button>
        </form>
    </div>

    <!-- Admin Registration Form -->
    <div class="form-container login-container">
        <!-- Correction ici aussi : action="" -->
        <form action="" method="post">
            <input type="hidden" name="user_type" value="admin">
            <h1>Inscription Admin</h1>
            <input type="text" name="nom" placeholder="Nom" required>
            <input type="text" name="prenom" placeholder="Prénom" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="username" placeholder="Nom d'utilisateur" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <input type="password" name="confirm_password" placeholder="Confirmer le mot de passe" required>
            <input type="text" name="admin_code" placeholder="Code d'administrateur" required>

            <button type="submit" class="btn">S'inscrire</button>
        </form>
    </div>

    <!-- Overlay Effect -->
    <div class="overlay-container">
        <div class="overlay">
            <div class="overlay-panel overlay-left">
                <h1 class="title">Admin</h1>
                <p>Inscrivez-vous en tant qu'administrateur pour gérer le système</p>
                <button class="ghost btn" id="login">
                    Inscription Admin
                    <i class="lni lni-arrow-left login"></i>
                </button>
            </div>
            <div class="overlay-panel overlay-right">
                <h1 class="title">Étudiant</h1>
                <p>Inscrivez-vous en tant qu'étudiant pour accéder à votre compte</p>
                <button class="ghost btn" id="register">
                    Inscription Étudiant
                    <i class="lni lni-arrow-right register"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const registrationButton = document.getElementById('register');
        const loginButton = document.getElementById('login');
        const container = document.getElementById('container');

        container.classList.remove('right-panel-active');

        registrationButton.addEventListener('click', () => {
            container.classList.add('right-panel-active');
        });

        loginButton.addEventListener('click', () => {
            container.classList.remove('right-panel-active');
        });
    });
</script>

<?php
if (isset($_GET['inscription']) && $_GET['inscription'] == 'success') {
    echo "<script>alert('Inscription réussie ! Vous pouvez maintenant vous connecter.');</script>";
}
?>

<?php require_once 'includes/footer.php'; ?>