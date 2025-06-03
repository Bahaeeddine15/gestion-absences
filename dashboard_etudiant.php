<?php
require_once 'includes/auth.php';
isStudent(); // Redirect if not a student

$title = "Tableau de bord étudiant";
include 'includes/header.php';

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

// Get all modules for the student
$stmt = $pdo->prepare("
    SELECT * FROM modules
");
$stmt->execute();
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle photo update or delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Changer la photo
    if (isset($_POST['update_photo']) && isset($_FILES['nouvelle_photo']) && $_FILES['nouvelle_photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/photos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileTmpPath = $_FILES['nouvelle_photo']['tmp_name'];
        $fileName = uniqid() . '_' . basename($_FILES['nouvelle_photo']['name']);
        $filePath = $uploadDir . $fileName;
        $fileType = mime_content_type($fileTmpPath);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($fileTmpPath, $filePath)) {
                // Supprimer l'ancienne photo si ce n'est pas celle par défaut
                if (!empty($student['photo_profil']) && $student['photo_profil'] !== 'uploads/photos/par_defaut.png' && file_exists($student['photo_profil'])) {
                    unlink($student['photo_profil']);
                }
                $stmt = $pdo->prepare("UPDATE etudiants SET photo_profil = ? WHERE id_etudiant = ?");
                $stmt->execute([$filePath, $id_etudiant]);
                header("Location: dashboard_etudiant.php");
                exit;
            } else {
                $error = "Erreur lors de l'upload de la nouvelle photo.";
            }
        } else {
            $error = "Format de photo non supporté (JPEG, PNG, GIF, WEBP uniquement).";
        }
    }
    // Supprimer la photo
    if (isset($_POST['delete_photo'])) {
        if (!empty($student['photo_profil']) && $student['photo_profil'] !== 'uploads/photos/par_defaut.png' && file_exists($student['photo_profil'])) {
            unlink($student['photo_profil']);
        }
        $stmt = $pdo->prepare("UPDATE etudiants SET photo_profil = ? WHERE id_etudiant = ?");
        $stmt->execute(['uploads/photos/par_defaut.png', $id_etudiant]);
        header("Location: dashboard_etudiant.php");
        exit;
    }
}
?>


<div class="dashboard">
    <div class="welcome-banner" style="display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 24px;">
            <?php if (!empty($student['photo_profil'])): ?>
                <img src="<?php echo htmlspecialchars($student['photo_profil']); ?>" alt="Photo de profil" style="width:100px;height:100px;border-radius:50%;object-fit:cover;">
            <?php else: ?>
                <img src="uploads/photos/par_defaut.png" alt="Photo de profil" style="width:100px;height:100px;border-radius:50%;object-fit:cover;">
            <?php endif; ?>
            <div style="display: flex; flex-direction: column; align-items: flex-start;">
                <div class="welcome-text">
                    <h1>Hi, <?php echo htmlspecialchars($student['prenom']); ?> 👋</h1>
                    <p>Time to check in on your attendance!</p>
                </div>
                <a href="#" onclick="document.getElementById('edit-photo-form').style.display='block';return false;" style="margin-top:8px;font-size:0.98em;color:#2a3b8f;text-decoration:underline;cursor:pointer;">
                    Modifier photo de profil
                </a>
                <!-- Formulaire caché pour modifier/supprimer la photo -->
                <form id="edit-photo-form" action="" method="post" enctype="multipart/form-data" style="display:none;margin-top:10px;">
                    <input type="file" name="nouvelle_photo" accept="image/*">
                    <button type="submit" name="update_photo" class="btn btn-primary btn-sm">Changer</button>
                    <?php if (!empty($student['photo_profil'])): ?>
                        <button type="submit" name="delete_photo" class="btn btn-danger btn-sm" style="margin-left:8px;">Supprimer</button>
                    <?php endif; ?>
                    <button type="button" onclick="document.getElementById('edit-photo-form').style.display='none';" class="btn btn-secondary btn-sm" style="margin-left:8px;">Annuler</button>
                </form>
            </div>
        </div>
        <div class="welcome-image">
            <img src="assets/welcome-img.png" alt="Welcome illustration" style="width:170px;max-width:100%;">
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
