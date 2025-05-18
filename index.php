<?php
$title = "Connexion - Gestion des absences";
require_once 'includes/auth.php';
require_once 'includes/header.php';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_type = $_POST['user_type'] ?? '';

    if ($user_type === 'student') {
        $numero_apogee = $_POST['numero_apogee'];
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM etudiants WHERE numero_apogee = ?");
        $stmt->execute([$numero_apogee]);
        $student = $stmt->fetch();

        if ($student && password_verify($password, $student['password'])) {
            $_SESSION['user_id'] = $student['id_etudiant'];
            $_SESSION['user_type'] = 'student';
            $_SESSION['apogee'] = $student['numero_apogee'];
            $_SESSION['nom'] = $student['nom'];
            $_SESSION['prenom'] = $student['prenom'];
            $_SESSION['id_filiere'] = $student['id_filiere'];
            $_SESSION['last_activity'] = time();

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
            $_SESSION['user_id'] = $admin['id_admin'];       // Change from admin_id to user_id
            $_SESSION['user_role'] = 'admin';          // This is already correct
            $_SESSION['email'] = $admin['email'];      // Keep this
            $_SESSION['nom'] = $admin['nom'];          // Add these for consistency
            $_SESSION['prenom'] = $admin['prenom'];    // Add these for consistency
            $_SESSION['last_activity'] = time();
            
            header("Location: dashboard_admin.php");
            exit;
        } else {
            $erreur = "Identifiants administrateur incorrects.";
        }
    }
}
?>

<link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />

<?php if (isset($erreur)): ?>
    <div class="error-message" style="background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; margin: 20px auto; border: 1px solid #ef9a9a; max-width: 768px; text-align: center;">
        <?= htmlspecialchars($erreur) ?>
    </div>
<?php endif; ?>

<div class="container" id="container">

    <!-- Formulaire Connexion Étudiant -->
    <div class="form-container register-container">
        <form action="" method="post">
            <input type="hidden" name="user_type" value="student">
            <h1>Connexion Étudiant</h1>
            <input type="number" name="numero_apogee" placeholder="Numéro Apogée" required>
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
            <button type="submit" class="login-btn">Se connecter</button>
            <div class="form-footer">
                <p>Pas encore inscrit? <a href="register.php">S'inscrire</a></p>
            </div>
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
            <button type="submit" class="login-btn">Se connecter</button>
            <div class="form-footer">
                <p>Pas encore inscrit? <a href="register.php">S'inscrire</a></p>
            </div>
        </form>
    </div>

    <!-- Effet de transition -->
    <div class="overlay-container">
        <div class="overlay">
            <div class="overlay-panel overlay-left">
                <h1 class="title">Admin</h1>
                <p>Connectez-vous en tant qu'administrateur pour gérer le système</p>
                <button class="ghost-btn" id="login">
                    Connexion Admin
                    <i class="lni lni-arrow-left login"></i>
                </button>
            </div>
            <div class="overlay-panel overlay-right">
                <h1 class="title">Étudiant</h1>
                <p>Connectez-vous en tant qu'étudiant pour accéder à vos informations</p>
                <button class="ghost-btn" id="register">
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