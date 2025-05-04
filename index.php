<?php
$title = "Connexion - Gestion des absences";
require_once 'includes/header.php';
?>

<link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />

<div class="container" id="container">
    <!-- Student Login Form -->
    <div class="form-container register-container">
        <form action="login_process.php" method="post">
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
        </form>
    </div>

    <!-- Admin Login Form -->
    <div class="form-container login-container">
        <form action="login_process.php" method="post">
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
        </form>
    </div>

    <!-- Overlay Effect -->
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

        // Initially show student login
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