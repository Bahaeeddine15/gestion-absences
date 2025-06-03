<?php
require_once __DIR__ . '/../includes/auth.php';
checkAdmin();
$title = 'Gestion des étudiants';
require_once __DIR__ . '/../includes/header.php';
?>
<script>
    document.body.classList.add('page-gestion-etudiants');
</script>

<?php
$error = '';
$success = '';
$student_to_edit = null; // <-- AJOUTE CETTE LIGNE
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}


#Update student
if (isset($_POST['update'])) {
    $id = $_POST['id_etudiant'];
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $numero_apogee = trim($_POST['numero_apogee']);
    $id_filiere = $_POST['id_filiere'];
    $mot_de_passe = $_POST['mot_de_passe'];

    if (empty($nom) || empty($prenom) || empty($email) || empty($numero_apogee) || empty($id_filiere)) {
        $error = "Tous les champs sauf le mot de passe sont obligatoires";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format d'email invalide";
    } else {
        try {
            // Vérifie s'il existe un autre étudiant avec le même email ou numéro apogée
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM etudiants WHERE (email = ? OR numero_apogee = ?) AND id_etudiant != ?");
            $stmt->execute([$email, $numero_apogee, $id]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Un autre étudiant avec cet email ou ce numéro Apogée existe déjà';
            } else {
                if (!empty($mot_de_passe)) {
                    $hashedPassword = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE etudiants SET nom = ?, prenom = ?, email = ?, numero_apogee = ?, password = ?, id_filiere = ? WHERE id_etudiant = ?");
                    $stmt->execute([$nom, $prenom, $email, $numero_apogee, $hashedPassword, $id_filiere, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE etudiants SET nom = ?, prenom = ?, email = ?, numero_apogee = ?, id_filiere = ? WHERE id_etudiant = ?");
                    $stmt->execute([$nom, $prenom, $email, $numero_apogee, $id_filiere, $id]);
                }
                $success = 'Étudiant mis à jour avec succès';
                // After successful insert/update/delete operations:
                header('Location: /gestion-absences/admin/gestion_etudiants.php?success=' . urlencode($success));
                exit;
            }
        } catch (PDOException $e) {
            $error = "Erreur lors de la mise à jour de l'étudiant: " . $e->getMessage();
        }
    }
}

#delete etudiant
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM absences WHERE id_etudiant = ?");
        $stmt->execute([$id]);
        $absenceCount = $stmt->fetchColumn();
        if ($absenceCount > 0) {
            $error = "Impossible de supprimer cet étudiant car il est associé à des absences";
        } else {
            $stmt = $pdo->prepare("DELETE FROM etudiants WHERE id_etudiant = ?");
            $stmt->execute([$id]);
            $success = "Étudiant supprimé avec succès";
            // After successful insert/update/delete operations:
            header('Location: /gestion-absences/admin/gestion_etudiants.php?success=' . urlencode($success));
            exit;
        }
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression de l'étudiant: " . $e->getMessage();
    }
}

// Get student to edit
$student_to_edit = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM etudiants WHERE id_etudiant = ?");
        $stmt->execute([$id]);
        $student_to_edit = $stmt->fetch();

        if (!$student_to_edit) {
            $error = "Étudiant non trouvé";
        }
    } catch (PDOException $e) {
        $error = "Erreur lors de la récupération de l'étudiant: " . $e->getMessage();
    }
}

#fetch all filieres
try {
    $stmt = $pdo->query("SELECT * FROM filieres ORDER BY nom");
    $filieres = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Erreur lors de la récupértion des filières:" . $e->getMessage();
}

// Get all students with filière names, with optional filtering
try {
    $query = "
        SELECT e.*, f.nom AS filiere_nom
        FROM etudiants e
        LEFT JOIN filieres f ON e.id_filiere = f.id_filiere
        WHERE 1=1
        ORDER BY e.nom, e.prenom
    ";
    $params = [];
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des étudiants: " . $e->getMessage();
    $students = [];
}
?>

<div class="admin-container">
    <h3>Gestion des Étudiants</h3>

    <?php if (!empty($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="admin-content">
        <div class="list-section">
            <h2>Liste des étudiants</h2>
            <?php if (empty($students)): ?>
                <p>Aucun étudiant trouvé.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Email</th>
                            <th>N° Apogée</th>
                            <th>Filière</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo $student['id_etudiant']; ?></td>
                                <td><?php echo htmlspecialchars($student['nom']); ?></td>
                                <td><?php echo htmlspecialchars($student['prenom'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($student['email'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($student['numero_apogee'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($student['filiere_nom'] ?? ''); ?></td>
                                <td>
                                    <a href="?edit=<?php echo $student['id_etudiant']; ?>" class="btn btn-edit">Modifier</a>
                                    <a href="?delete=<?php echo $student['id_etudiant']; ?>" class="btn btn-delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet étudiant?')">Supprimer</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <?php if (!empty($students)): ?>
        <div class="form-section">
            <?php if ($student_to_edit): ?>
                <div class="edit-student-form">
                    <h3>Modifier l'étudiant</h3>
                    <form method="post">
                        <input type="hidden" name="id_etudiant" value="<?php echo $student_to_edit['id_etudiant']; ?>">
                        <div class="form-group">
                            <label for="nom">Nom</label>
                            <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($student_to_edit['nom']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="prenom">Prénom</label>
                            <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($student_to_edit['prenom']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($student_to_edit['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="numero_apogee">Numéro Apogée</label>
                            <input type="text" id="numero_apogee" name="numero_apogee" value="<?php echo htmlspecialchars($student_to_edit['numero_apogee']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="mot_de_passe">Mot de passe (laisser vide pour ne pas modifier)</label>
                            <input type="password" id="mot_de_passe" name="mot_de_passe" placeholder="••••••••">
                        </div>
                        <div class="form-group">
                            <label for="id_filiere">Filière</label>
                            <select name="id_filiere" id="id_filiere" required>
                                <option value="">Sélectionnez une filière</option>
                                <?php foreach ($filieres as $filiere): ?>
                                    <option value="<?php echo $filiere['id_filiere']; ?>" <?php echo ($student_to_edit['id_filiere'] == $filiere['id_filiere']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($filiere['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="update" class="btn btn-primary">Enregistrer</button>
                        <a href="gestion_etudiants.php" class="btn btn-secondary">Annuler</a>
                    </form>
                </div>
            <?php else: ?>
                <div class="bulk-actions">
                    <h3>Actions groupées</h3>
                    <form method="post" action="../send_bulk_email.php">
                        <div class="form-group">
                            <label for="filiere_bulk">Filière:</label>
                            <select name="filiere_id" id="filiere_bulk">
                                <option value="all">Toutes les filières</option>
                                <?php foreach ($filieres as $filiere): ?>
                                    <option value="<?php echo $filiere['id_filiere']; ?>">
                                        <?php echo htmlspecialchars($filiere['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="email_subject">Sujet:</label>
                            <input type="text" name="subject" id="email_subject" required>
                        </div>
                        <div class="form-group">
                            <label for="email_message">Message:</label>
                            <textarea name="message" id="email_message" rows="5" required></textarea>
                        </div>
                        <button type="submit" name="send_bulk" class="btn">Envoyer email groupé</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>



<?php include __DIR__ . '/../includes/footer.php'; ?>