<?php
require_once __DIR__ . '/../includes/auth.php';
CheckAdmin();
$title = 'Gestion des filières';
include __DIR__ . '/../includes/header.php';

$error = '';
$success = '';
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

//Add filiere
if (isset($_POST['add'])) {
    $nom = trim($_POST['nom']);
    $description = trim($_POST['description'] ?? '');

    if (empty($nom)) {
        $error = "Le nom de la filière est obligatoire";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM filieres WHERE nom = ?");
            $stmt->execute([$nom]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Cette filière existe déjà";
            } else {
                $stmt = $pdo->prepare("INSERT INTO filieres (nom, description) VALUES (?, ?)");
                $stmt->execute([$nom, $description]);
                $success = "Filière ajoutée avec succès";
                header('Location: /gestion-absences/admin/gestion_filieres.php?success=' . urlencode($success));
                exit;
            }
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout de la filière : " . $e->getMessage();
        }
    }
}

#Update filière
if (isset($_POST['update'])) {
    $id = $_POST['id_filiere'];
    $nom = trim($_POST['nom']);
    $description = trim($_POST['description'] ?? '');

    if (empty($nom)) {
        $error = "Le nom de la filière est obligatoire";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM filieres WHERE nom = ? AND id_filiere != ?");
            $stmt->execute([$nom, $id]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Une autre filière avec ce nom existe déjà";
            } else {
                $stmt = $pdo->prepare("UPDATE filieres SET nom = ?, description = ? WHERE id_filiere = ?");
                $stmt->execute([$nom, $description, $id]);
                $success = "Filière mise à jour avec succès";
                header('Location: /gestion-absences/admin/gestion_filieres.php?success=' . urlencode($success));
                exit;
            }
        } catch (PDOException $e) {
            $error = "Erreur lors de la mise à jour de la filière: " . $e->getMessage();
        }
    }
}

#Delete filière
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM etudiants WHERE id_filiere = ? ");
        $stmt->execute([$id]);
        $studentCount = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM modules WHERE id_filiere = ?");
        $stmt->execute([$id]);
        $moduleCount = $stmt->fetchColumn();

        if ($studentCount > 0 || $moduleCount > 0) {
            $error = "Impossible de supprimer cette filière car elle est associée à des étudiants ou des modules";
        } else {
            $stmt = $pdo->prepare("DELETE FROM filieres WHERE id_filiere = ?");
            $stmt->execute([$id]);
            $success = "Filière supprimée avec succès";
            try {
                $stmt = $pdo->query("SELECT MAX(id_filiere) as max_id FROM filieres");
                $result = $stmt->fetch();
                $max_id = (int)$result['max_id'];
                $stmt = $pdo->prepare("ALTER TABLE filieres AUTO_INCREMENT = ?");
                $stmt->execute([$max_id + 1]);
            } catch (PDOException $e) {
                error_log("Couldn't reset AUTO_INCREMENT: " . $e->getMessage());
            }

            header('Location: /gestion-absences/admin/gestion_filieres.php?success=' . urlencode($success));
            exit;
        }
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression de la filière: " . $e->getMessage();
    }
}

#Modify filière
$filiere_to_edit = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM filieres WHERE id_filiere=?");
        $stmt->execute([$id]);
        $filiere_to_edit = $stmt->fetch();

        if (!$filiere_to_edit) {
            $error = "Filière non trouvée";
        }
    } catch (PDOException $e) {
        $error = "Erreur lors de la récupération de la filière: " . $e->getMessage();
    }
}

#Get all filières (removed search functionality)
try {
    $stmt = $pdo->query("SELECT * FROM filieres ORDER BY nom");
    $filieres = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des filières: " . $e->getMessage();
    $filieres = [];
}
?>

<div class="admin-container">
    <h3>Gestion des Filières</h3>

    <?php if (!empty($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="admin-content">
        <div class="form-section">
            <h2><?php echo ($filiere_to_edit ? 'Modifier la filière' : 'Ajouter une filière'); ?></h2>
            <form method="post">
                <?php if ($filiere_to_edit) : ?>
                    <input type="hidden" name="id_filiere" value="<?php echo $filiere_to_edit['id_filiere']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="nom">Nom de la filière</label>
                    <input type="text" id="nom" name="nom" value="<?php echo $filiere_to_edit ? htmlspecialchars($filiere_to_edit['nom']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"><?php echo $filiere_to_edit ? htmlspecialchars($filiere_to_edit['description']) : ''; ?></textarea>
                </div>

                <button type="submit" name="<?php echo $filiere_to_edit ? 'update' : 'add'; ?>" class="btn btn-primary">
                    <?php echo $filiere_to_edit ? 'Mettre à jour' : 'Ajouter'; ?>
                </button>

                <?php if ($filiere_to_edit) : ?>
                    <a href="/gestion-absences/admin/gestion_filieres.php" class="btn btn-secondary">Annuler</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="list-section">
            <h2>Liste des filières</h2>
            <?php if (empty($filieres)): ?>
                <p>Aucune filière trouvé.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filieres as $filiere): ?>
                            <tr>
                                <td><?php echo $filiere['id_filiere']; ?></td>
                                <td><?php echo htmlspecialchars($filiere['nom']); ?></td>
                                <td><?php echo htmlspecialchars($filiere['description'] ?? ''); ?></td>
                                <td>
                                    <a href="?edit=<?php echo $filiere['id_filiere']; ?>" class="btn btn-edit">Modifier</a>
                                    <a href="?delete=<?php echo $filiere['id_filiere']; ?>" class="btn btn-delete" onclick="return confirm('Êtes-vous sûr?')">Supprimer</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>