<?php
require_once 'includes/auth.php';
isStudent(); // Redirect if not a student

$pageTitle = "Tableau de bord étudiant";
include 'includes/header.php';

// Connect to database
require_once 'config/db.php';

// Get student information
$id_etudiant = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT e.*, f.nom as filiere_nom 
    FROM etudiants e
    LEFT JOIN filieres f ON e.id_filiere = f.id_filiere
    WHERE e.id_etudiant = ?
");
$stmt->execute([$id_etudiant]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Count absences
$stmt = $pdo->prepare("SELECT COUNT(*) FROM absences WHERE id_etudiant = ?");
$stmt->execute([$id_etudiant]);
$absenceCount = $stmt->fetchColumn();

// Count justified absences
$stmt = $pdo->prepare("SELECT COUNT(*) FROM absences WHERE id_etudiant = ? AND justifiee = 1");
$stmt->execute([$id_etudiant]);
$justifiedCount = $stmt->fetchColumn();

// Count unjustified absences
$unjustifiedCount = $absenceCount - $justifiedCount;

// Get recent absences for this student
$stmt = $pdo->prepare("
    SELECT a.date, a.justifiee, a.commentaire, m.nom as module_nom
    FROM absences a
    JOIN modules m ON a.id_module = m.id_module
    WHERE a.id_etudiant = ?
    ORDER BY a.date DESC
    LIMIT 5
");
$stmt->execute([$id_etudiant]);
$recentAbsences = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get absences by module
$stmt = $pdo->prepare("
    SELECT m.nom as module_nom, COUNT(*) as absence_count
    FROM absences a
    JOIN modules m ON a.id_module = m.id_module
    WHERE a.id_etudiant = ?
    GROUP BY m.id_module
    ORDER BY absence_count DESC
");
$stmt->execute([$id_etudiant]);
$absencesByModule = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<div class="dashboard">
    <h2>Tableau de bord étudiant</h2>

    <div class="student-info">
        <h3>Informations personnelles</h3>
        <div class="info-card">
            <p><strong>Nom:</strong> <?php echo htmlspecialchars($student['prenom'] . ' ' . $student['nom']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
            <p><strong>Numéro Apogée:</strong> <?php echo htmlspecialchars($student['numero_apogee']); ?></p>
            <p><strong>Filière:</strong> <?php echo htmlspecialchars($student['filiere_nom'] ?? 'Non définie'); ?></p>
        </div>
    </div>

    <div class="stats-container">
        <div class="stat-card">
            <h3>Total absences</h3>
            <p class="stat-number"><?php echo $absenceCount; ?></p>
        </div>

        <div class="stat-card">
            <h3>Absences justifiées</h3>
            <p class="stat-number"><?php echo $justifiedCount; ?></p>
        </div>

        <div class="stat-card">
            <h3>Absences non justifiées</h3>
            <p class="stat-number"><?php echo $unjustifiedCount; ?></p>
        </div>
    </div>

    <div class="recent-section">
        <h3>Absences récentes</h3>
        <table>
            <thead>
                <tr>
                    <th>Module</th>
                    <th>Date</th>
                    <th>Justifiée</th>
                    <th>Commentaire</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentAbsences as $absence): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($absence['module_nom']); ?></td>
                        <td><?php echo htmlspecialchars($absence['date']); ?></td>
                        <td><?php echo $absence['justifiee'] ? 'Oui' : 'Non'; ?></td>
                        <td><?php echo htmlspecialchars($absence['commentaire'] ?? '-'); ?></td>
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

    <div class="recent-section">
        <h3>Absences par module</h3>
        <table>
            <thead>
                <tr>
                    <th>Module</th>
                    <th>Nombre d'absences</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($absencesByModule as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['module_nom']); ?></td>
                        <td><?php echo $item['absence_count']; ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($absencesByModule)): ?>
                    <tr>
                        <td colspan="2" class="text-center">Aucune absence enregistrée</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
