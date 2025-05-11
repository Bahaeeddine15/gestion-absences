<?php
session_start();
$title = "Connexion - Gestion des absences";
require_once 'config/db.php';
require_once 'includes/header.php';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_type = $_POST['user_type'] ?? '';

    if ($user_type === 'student') {
        $numero_apogee = $_POST['numero_apogee'];
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM etudiants WHERE numero_apogee = ?");
        $stmt->execute([$numero_apogee]);
        $etudiant = $stmt->fetch();

        if ($etudiant && password_verify($password, $etudiant['password'])) {
            $_SESSION['etudiant_id'] = $etudiant['id'];
            $_SESSION['nom'] = $etudiant['nom'];
            header("Location: dashboard_etudiant.php");
            exit;
        } else {
            $erreur = "Identifiants étudiant incorrects.";
        }

    } elseif ($user_type === 'admin') {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['email'] = $admin['email'];
            header("Location: dashboard_admin.php");
            exit;
        } else {
            $erreur = "Identifiants administrateur incorrects.";
        }
    }
}
?>

<link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />

<div class="container" id="container">

    <!-- Formulaire Connexion Étudiant -->
    <div class="form-container register-container">
        <form action="" method="post">
            <input type="hidden" name="user_type" value="student">
            <h1>Connexion Étudiant</h1>
            <input type="text" name="numero_apogee" placeholder="Numéro Apogée" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <div class="content">
                <div class="checkbox">
                    <input type="checkbox" name="remember" id="remember-student">
                    <label for="remember-student">Se souvenir de moi</label>
                </div>
                <div class="pass-link">
                    <a href="reset_password.php">Mot de passe oublié?</a>
                </div>
            </div>
            <button type="submit" class="btn">Se connecter</button>
            <div class="form-footer">
                <p>Pas encore inscrit? <a href="register.php">S'inscrire</a></p>
            </div>
            <?php if (isset($erreur) && $user_type === 'student'): ?>
                <p style="color:red;"><?= htmlspecialchars($erreur) ?></p>
            <?php endif; ?>
        </form>
    </div>

    <!-- Formulaire Connexion Admin -->
    <div class="form-container login-container">
        <form action="" method="post">
            <input type="hidden" name="user_type" value="admin">
            <h1>Connexion Admin</h1>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <div class="content">
                <div class="checkbox">
                    <input type="checkbox" name="remember" id="remember-admin">
                    <label for="remember-admin">Se souvenir de moi</label>
                </div>
                <div class="pass-link">
                    <a href="reset_password.php">Mot de passe oublié?</a>
                </div>
            </div>
            <button type="submit" class="btn">Se connecter</button>
            <div class="form-footer">
                <p>Pas encore inscrit? <a href="register.php">S'inscrire</a></p>
            </div>
            <?php if (isset($erreur) && $user_type === 'admin'): ?>
                <p style="color:red;"><?= htmlspecialchars($erreur) ?></p>
            <?php endif; ?>
        </form>
    </div>

    <!-- Effet de transition -->
    <div class="overlay-container">
        <div class="overlay">
            <div class="overlay-panel overlay-left">
                <h1 class="title">Admin</h1>
                <p>Connectez-vous en tant qu'administrateur pour gérer le système</p>
                <button class="ghost btn" id="login">
                    Connexion Admin
                    <i class="lni lni-arrow-left login"></i>
                </button>
            </div>
            <div class="overlay-panel overlay-right">
                <h1 class="title">Étudiant</h1>
                <p>Connectez-vous en tant qu'étudiant pour accéder à vos informations</p>
                <button class="ghost btn" id="register">
                    Connexion Étudiant
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

        // Affiche formulaire étudiant par défaut
        container.classList.remove('right-panel-active');

        registrationButton.addEventListener('click', () => {
            container.classList.add('right-panel-active');
        });

        loginButton.addEventListener('click', () => {
            container.classList.remove('right-panel-active');
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>