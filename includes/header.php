<?php require_once 'auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : "Gestion des absences" ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header>
        <section class="logo-container">
            <h1 class="logo">Gestion des absences</h1>
        </section>
        <nav>
            <?php if (isConnected() && isAdmin()): ?>
                <ul>
                    <li><a href="dashboard_admin.php">Dashboard</a></li>
                    <li><a href="admin/gestion_absences.php">Absences</a></li>
                    <li><a href="admin/gestion_etudiants.php">Etudiants</a></li>
                    <li><a href="admin/gestion_filieres.php">Filieres</a></li>
                    <li><a href="admin/gestion_modules.php">Modules</a></li>
                    <li><a href="logout.php">Se déconnecter</a></li>
                </ul>
            <?php elseif (isConnected() && isStudent()):?>
                <ul>
                    <li><a href="dashboard_etudiant.php">Dashboard</a></li>
                    <li><a href="etudiant/bilan_absences.php">Bilan des Absences</a></li>
                    <li><a href="logout.php">Se déconnecter</a></li>
                </ul>
            <?php elseif(!isConnected()):?>
                <ul>
                    <li><a href="index.php">Se connecter</a></li>
                    <li><a href="register.php">S'inscrire</a></li>
                </ul>
            <?php endif;?>
        </nav>
    </header>
    <main>
