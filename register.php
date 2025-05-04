<?php
$title = "Inscription - Gestion des absences";
require_once 'includes/header.php';

// Fetch filières from database for student registration
try {
    $stmt = $pdo->query("SELECT id, nom FROM filieres ORDER BY nom");
    $filieres = $stmt->fetchAll();
} catch (PDOException $e) {
    $filieres = []; // Empty array if query fails
}
?>

<link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />

<div class="container" id="container">
    <div class="form-container register-container">
        <form action="register_process.php" method="post">
            <input type="hidden" name="user_type" value="student">
            <h1>Inscription Étudiant</h1>
            <input type="text" name="nom" placeholder="Nom" required>
            <input type="text" name="prenom" placeholder="Prénom" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="numero_apogee" placeholder="Numéro Apogée" required>

            <select name="id_filiere" required>
                <option value="">-- Sélectionner une filière --</option>
                <?php foreach ($filieres as $filiere): ?>
                    <option value="<?= htmlspecialchars($filiere['id']) ?>">
                        <?= htmlspecialchars($filiere['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="password" name="password" placeholder="Mot de passe" required>
            <input type="password" name="confirm_password" placeholder="Confirmer le mot de passe" required>

            <button type="submit" class="btn">S'inscrire</button>
        </form>
    </div>

    <!-- Admin Registration Form -->
    <div class="form-container login-container">
        <form action="register_admin_process.php" method="post">
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

        // Initially show student registration
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