<?php
session_start();
require_once 'includes/auth.php';
require_once 'config/db.php';
include 'includes/header.php';

// checkAdmin(); // Redirige si l'utilisateur n'est pas un administrateur

try {
    // Requêtes pour afficher les statistiques principales
    $countFilieres = $pdo->query("SELECT COUNT(*) FROM filieres")->fetchColumn();
    $countModules = $pdo->query("SELECT COUNT(*) FROM modules")->fetchColumn();
    $countEtudiants = $pdo->query("SELECT COUNT(*) FROM etudiants")->fetchColumn();
    $countAbsences = $pdo->query("SELECT COUNT(*) FROM absences")->fetchColumn();
} catch (PDOException $e) {
    echo "<p>Erreur de récupération des données : " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Administrateur</title>
    <style>
        /* Tableau de bord Administrateur */
        .dashboard-title {
            font-size: 2.5em;
            color: #1E3A8A; /* Bleu foncé */
            text-align: center;
            margin-top: 20px;
        }

        /* Stats section */
        .stats {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }

        .stats ul {
            list-style-type: none;
            padding: 0;
        }

        .stats li {
            font-size: 1.2em;
            margin-bottom: 15px;
            color: #333;
        }

        .stats li strong {
            color: #1E40AF; /* Bleu clair */
        }

        .stats li:nth-child(even) {
            background-color: #F0F9FF; /* Couleur de fond légère */
            padding: 10px;
            border-radius: 5px;
        }

        /* Admin Navigation */
        .admin-nav {
            background-color: #1E40AF;
            color: white;
            padding: 20px;
            margin-top: 40px;
            border-radius: 10px;
        }

        .admin-nav h3 {
            font-size: 1.8em;
            margin-bottom: 20px;
        }

        .admin-nav ul {
            list-style-type: none;
            padding: 0;
        }

        .admin-nav ul li {
            margin-bottom: 15px;
        }

        .admin-nav ul li a {
            color: #F9FAFB; /* Blanc */
            text-decoration: none;
            font-size: 1.1em;
            transition: color 0.3s;
        }

        .admin-nav ul li a:hover {
            color: #93C5FD; /* Bleu clair au survol */
        }

        /* Logout Link */
        .logout-link {
            text-align: center;
            margin-top: 40px;
            font-size: 1.2em;
        }

        .logout-link a {
            color: #EF4444; /* Rouge pour la déconnexion */
            text-decoration: none;
            font-weight: bold;
        }

        .logout-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<h2 class="dashboard-title">📊 Tableau de bord Administrateur</h2>
<div class="stats">
    <ul>
        <li>📚 <strong>Filières</strong>: <?= $countFilieres ?></li>
        <li>📘 <strong>Modules</strong>: <?= $countModules ?></li>
        <li>👨‍🎓 <strong>Étudiants</strong>: <?= $countEtudiants ?></li>
        <li>📅 <strong>Absences enregistrées</strong>: <?= $countAbsences ?></li>
    </ul>
</div>

<nav class="admin-nav">
    <h3>🛠️ Gestion</h3>
    <ul>
        <li><a href="admin/gestion_filieres.php">Gérer les Filières</a></li>
        <li><a href="admin/gestion_modules.php">Gérer les Modules</a></li>
        <li><a href="admin/gestion_etudiants.php">Gérer les Étudiants</a></li>
        <li><a href="admin/gestion_absences.php">Consulter les Absences</a></li>
    </ul>
</nav>

<p class="logout-link"><a href="logout.php">🔓 Se déconnecter</a></p>

<?php include 'includes/footer.php'; ?>

</body>
</html>
