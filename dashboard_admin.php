<?php
require_once 'includes/auth.php';
CheckAdmin();

$title = "Tableau de bord administrateur";
include 'includes/header.php';

$stmt = $pdo->query("SELECT COUNT(*) FROM etudiants");
$studentCount = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM filieres");
$filiereCount = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM modules");
$moduleCount = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM absences");
$absenceCount = $stmt->fetchColumn();

$stmt = $pdo->query("
    SELECT a.date, a.justifiee,
           e.nom as etudiant_nom, e.prenom as etudiant_prenom,
           m.nom as module_nom
    FROM absences a
    JOIN etudiants e ON a.id_etudiant = e.id_etudiant
    JOIN modules m ON a.id_module = m.id_module
    ORDER BY a.date DESC
    LIMIT 5
");
$recentAbsences = $stmt->fetchAll();

$id_admin = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT a.*
    FROM admins a
    WHERE a.id_admin = ?
");
$stmt->execute([$id_admin]);
$admin = $stmt->fetch();
?>

<div class="dashboard">
    <div class="welcome-banner">
        <div class="welcome-text">
            <h1>
                <?php
                $hour = date('H');
                if ($hour >= 5 && $hour < 12) {
                    echo "☀️ Bonjour, ";
                } elseif ($hour >= 12 && $hour < 18) {
                    echo "🌤️ Bon après-midi, ";
                } else {
                    echo '🌙 Bonsoir, ';
                }
                ?>
                <span class="admin-name"><?php echo htmlspecialchars($admin['username'] ?? 'Admin'); ?></span>
            </h1>

            <p class="tagline">
                <?php
                $catchyPhrases = [
                    "Suivez vos étudiants, gérez vos filières, contrôlez les absences - tout en un seul endroit.",
                    "Une présence suivie, c'est une réussite assurée pour vos étudiants.",
                    "Données précises, décisions éclairées pour votre établissement.",
                    "Simplifiez la gestion de votre école avec notre plateforme intuitive.",
                    "Transformez la gestion des absences en outil de réussite académique.",
                    "Visualisez les tendances d'assiduité pour mieux accompagner vos étudiants.",
                    "Un suivi rigoureux aujourd'hui, des diplômés performants demain.",
                    "La gestion optimisée des présences commence ici.",
                    "Votre tableau de bord : le pouls quotidien de votre établissement.",
                    "L'excellence académique passe par un suivi attentif des présences."
                ];
                echo $catchyPhrases[array_rand($catchyPhrases)];
                ?>
            </p>
        </div>
        <div class="welcome-image">
            <img src="assets/welcome-img.png" alt="Welcome illustration">
        </div>
    </div>
    
    <div class ="stats-container">
        <div class="stat-card">
            <h3>Étudiants</h3>
            <p class="stat-number"><?php echo $studentCount; ?></p>
        </div>
        <div class="stat-card">
            <h3>Filières</h3>
            <p class="stat-number"><?php echo $filiereCount; ?></p>
        </div>
        <div class="stat-card">
            <h3>Modules</h3>
            <p class="stat-number"><?php echo $moduleCount; ?></p>
        </div>
        <div class="stat-card">
            <h3>Absences</h3>
            <p class="stat-number"><?php echo $absenceCount; ?></p>
        </div>
    </div>
    
    <div class="recent-section">
        <h3>Absences récentes</h3>
        <table>
            <thead>
                <tr>
                    <th>Étudiant</th>
                    <th>Module</th>
                    <th>Date</th>
                    <th>Justifiée</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($recentAbsences as $absence) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($absence['etudiant_prenom']). ' '. htmlspecialchars($absence['etudiant_nom']);?></td>                 
                        <td><?php echo htmlspecialchars($absence['module_nom']); ?></td>
                        <td><?php echo htmlspecialchars($absence['date']); ?></td>
                        <td><?php echo $absence['justifiee'] ? 'Oui' : 'Non'; ?></td>                
                    </tr>
                    <?php endforeach; ?>

                    <?php if (empty($recentAbsences)): ?>
                        <tr>
                            <td colspan="4" class="text-center">Aucune absence enregistrée</td>
                        </tr>
                    <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php';?>