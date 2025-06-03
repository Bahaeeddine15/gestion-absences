<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mail_functions.php';
CheckAdmin();
$title = 'Gestion des absences';
include __DIR__ . '/../includes/header.php';

$message = "";
$error = "";

// Add new absence
if (isset($_POST['add'])) {
    $id_etudiant = $_POST['id_etudiant'];
    $id_module = $_POST['id_module'];
    $date = $_POST['date'];
    $justifiee = isset($_POST['justifiee']) ? 1 : 0;
    $commentaire = trim($_POST['commentaire']);

    if (empty($id_etudiant) || empty($id_module) || empty($date)) {
        $error = "L'étudiant, le module et la date sont obligatoires";
    } else {
        try {
            // Check if absence already exists for this student on this date for this module
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM absences WHERE id_etudiant = ? AND id_module = ? AND date = ?");
            $stmt->execute([$id_etudiant, $id_module, $date]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Cette absence existe déjà pour cet étudiant à cette date et dans ce module";
            } else {
                // Insert new absence
                $stmt = $pdo->prepare("INSERT INTO absences (id_etudiant, id_module, date, justifiee, commentaire) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$id_etudiant, $id_module, $date, $justifiee, $commentaire]);

                require_once __DIR__ . '/../includes/mail_functions.php';
                sendAbsenceAlertToStudent($id_etudiant, $id_module, $date);

                $message = "Absence ajoutée avec succès. L'étudiant a été notifié par email.";
            }
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout de l'absence: " . $e->getMessage();
        }
    }
}

// Update absence
if (isset($_POST['update'])) {
    $id = $_POST['id_absence'];
    $id_etudiant = $_POST['id_etudiant'];
    $id_module = $_POST['id_module'];
    $date = $_POST['date'];
    $justifiee = isset($_POST['justifiee']) ? 1 : 0;
    $commentaire = trim($_POST['commentaire']);

    if (empty($id_etudiant) || empty($id_module) || empty($date)) {
        $error = "L'étudiant, le module et la date sont obligatoires";
    } else {
        try {
            // Check if another absence exists for this student on this date for this module
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM absences WHERE id_etudiant = ? AND id_module = ? AND date = ? AND id_absence != ?");
            $stmt->execute([$id_etudiant, $id_module, $date, $id]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Une autre absence existe déjà pour cet étudiant à cette date et dans ce module";
            } else {
                // Update absence
                $stmt = $pdo->prepare("UPDATE absences SET id_etudiant = ?, id_module = ?, date = ?, justifiee = ?, commentaire = ? WHERE id_absence = ?");
                $stmt->execute([$id_etudiant, $id_module, $date, $justifiee, $commentaire, $id]);
                $message = "Absence mise à jour avec succès";
            }
        } catch (PDOException $e) {
            $error = "Erreur lors de la mise à jour de l'absence: " . $e->getMessage();
        }
    }
}

// Delete absence
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    try {
        $stmt = $pdo->prepare("DELETE FROM absences WHERE id_absence = ?");
        $stmt->execute([$id]);
        $message = "Absence supprimée avec succès";
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression de l'absence: " . $e->getMessage();
    }
}

// Get absence to edit
$absence_to_edit = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM absences WHERE id_absence = ?");
        $stmt->execute([$id]);
        $absence_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$absence_to_edit) {
            $error = "Absence non trouvée";
        }
    } catch (PDOException $e) {
        $error = "Erreur lors de la récupération de l'absence: " . $e->getMessage();
    }
}

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$etudiant_filter = isset($_GET['etudiant']) ? $_GET['etudiant'] : '';
$module_filter = isset($_GET['module']) ? $_GET['module'] : '';
$filiere_filter = isset($_GET['filiere']) ? $_GET['filiere'] : '';
$justifiee_filter = isset($_GET['justifiee']) ? $_GET['justifiee'] : '';

// Get all students for dropdown
try {
    $stmt = $pdo->query("SELECT id_etudiant, nom, prenom FROM etudiants ORDER BY nom, prenom");
    $etudiants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des étudiants: " . $e->getMessage();
    $etudiants = [];
}

// Get all modules for dropdown
try {
    $stmt = $pdo->query("SELECT id_module, nom FROM modules ORDER BY nom");
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des modules: " . $e->getMessage();
    $modules = [];
}

// Get all filières for dropdown
try {
    $stmt = $pdo->query("SELECT id_filiere, nom FROM filieres ORDER BY nom");
    $filieres = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des filières: " . $e->getMessage();
    $filieres = [];
}

// Get all absences with student and module names, with optional filtering
try {
    $query = "
        SELECT a.*, 
               e.nom AS etudiant_nom, e.prenom AS etudiant_prenom, e.id_filiere,
               m.nom AS module_nom,
               f.nom AS filiere_nom
        FROM absences a
        JOIN etudiants e ON a.id_etudiant = e.id_etudiant
        JOIN modules m ON a.id_module = m.id_module
        JOIN filieres f ON e.id_filiere = f.id_filiere
        WHERE 1=1
    ";
    $params = [];

    if (!empty($search)) {
        $query .= " AND (e.nom LIKE ? OR e.prenom LIKE ? OR m.nom LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
    }

    if (!empty($etudiant_filter)) {
        $query .= " AND a.id_etudiant = ?";
        $params[] = $etudiant_filter;
    }

    if (!empty($module_filter)) {
        $query .= " AND a.id_module = ?";
        $params[] = $module_filter;
    }

    if (!empty($filiere_filter)) {
        $query .= " AND e.id_filiere = ?";
        $params[] = $filiere_filter;
    }

    if ($justifiee_filter !== '') {
        $query .= " AND a.justifiee = ?";
        $params[] = $justifiee_filter;
    }

    $query .= " ORDER BY a.date DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $absences = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des absences: " . $e->getMessage();
    $absences = [];
}

// In gestion_absences.php, add this near the top
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'send_summary') {
        // Récupérer tous les étudiants
        $stmt = $pdo->query("
            SELECT DISTINCT e.id_etudiant
            FROM etudiants e
            JOIN absences a ON e.id_etudiant = a.id_etudiant
        ");
        $etudiants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $count = 0;
        foreach ($etudiants as $etudiant) {
            $result = sendAbsencesSummary($etudiant['id_etudiant']);
            if ($result) {
                $count++;
            }
        }

        $_SESSION['success_message'] = "Récapitulatifs d'absences envoyés à {$count} étudiant(s).";

        // Redirection pour éviter la re-soumission du formulaire
        header("Location: gestion_absences.php?summary_sent=1&count={$count}");
        exit;
    }
}
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>Gestion des Absences</h1>

        <?php if (isset($_GET['summary_sent'])): ?>
            <div class="success-message">
                <i class="lni lni-checkmark-circle"></i>
                <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
        <div class="success"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="admin-content">
        <div class="form-section">
            <h2><?php echo $absence_to_edit ? 'Modifier l\'absence' : 'Ajouter une absence'; ?></h2>
            <form method="post">
                <?php if ($absence_to_edit): ?>
                    <input type="hidden" name="id_absence" value="<?php echo $absence_to_edit['id_absence']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="id_etudiant">Étudiant</label>
                    <select id="id_etudiant" name="id_etudiant" required>
                        <option value="">Sélectionnez un étudiant</option>
                        <?php foreach ($etudiants as $etudiant): ?>
                            <option value="<?php echo $etudiant['id_etudiant']; ?>" <?php echo ($absence_to_edit && $absence_to_edit['id_etudiant'] == $etudiant['id_etudiant']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($etudiant['nom'] . ' ' . $etudiant['prenom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="id_module">Module</label>
                    <select id="id_module" name="id_module" required>
                        <option value="">Sélectionnez un module</option>
                        <?php foreach ($modules as $module): ?>
                            <option value="<?php echo $module['id_module']; ?>" <?php echo ($absence_to_edit && $absence_to_edit['id_module'] == $module['id_module']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($module['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" id="date" name="date" value="<?php echo $absence_to_edit ? $absence_to_edit['date'] : date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="commentaire">Commentaire</label>
                    <textarea id="commentaire" name="commentaire" rows="3"><?php echo $absence_to_edit ? htmlspecialchars($absence_to_edit['commentaire']) : ''; ?></textarea>
                </div>

                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="justifiee" name="justifiee" value="1" <?php echo ($absence_to_edit && $absence_to_edit['justifiee']) ? 'checked' : ''; ?>>
                        Absence justifiée
                    </label>
                </div>

                <button type="submit" name="<?php echo $absence_to_edit ? 'update' : 'add'; ?>">
                    <?php echo $absence_to_edit ? 'Mettre à jour' : 'Ajouter'; ?>
                </button>

                <?php if ($absence_to_edit): ?>
                    <a href="gestion_absences.php" class="btn btn-secondary">Annuler</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="list-section">
            <h2>Liste des absences</h2>

            <div class="search-filters">
                <form class="absence-filters" method="get">

                    <select name="etudiant">
                        <option value="">Tous les étudiants</option>
                        <?php foreach ($etudiants as $etudiant): ?>
                            <option value="<?php echo $etudiant['id_etudiant']; ?>" <?php echo ($etudiant_filter == $etudiant['id_etudiant']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($etudiant['nom'] . ' ' . $etudiant['prenom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="module">
                        <option value="">Tous les modules</option>
                        <?php foreach ($modules as $module): ?>
                            <option value="<?php echo $module['id_module']; ?>" <?php echo ($module_filter == $module['id_module']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($module['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="filiere">
                        <option value="">Toutes les filières</option>
                        <?php foreach ($filieres as $filiere): ?>
                            <option value="<?php echo $filiere['id_filiere']; ?>" <?php echo ($filiere_filter == $filiere['id_filiere']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($filiere['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="justifiee">
                        <option value="">Toutes les absences</option>
                        <option value="1" <?php echo ($justifiee_filter === '1') ? 'selected' : ''; ?>>Justifiées</option>
                        <option value="0" <?php echo ($justifiee_filter === '0') ? 'selected' : ''; ?>>Non justifiées</option>
                    </select>

                    <div class="filter-actions">
                        <button type="submit">Filtrer</button>
                        <button type="reset" class="btn-secondary">Réinitialiser</button>
                    </div>
                </form>
            </div>

            <form class="absence-filters" method="get" action="../generer_rapport.php" target="_blank" style="display:inline;">
                <select name="filiere">
                    <option value="">Toutes les filières</option>
                    <?php foreach ($filieres as $filiere): ?>
                        <option value="<?php echo $filiere['id_filiere']; ?>"><?php echo htmlspecialchars($filiere['nom']); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="module">
                    <option value="">Tous les modules</option>
                    <?php foreach ($modules as $module): ?>
                        <option value="<?php echo $module['id_module']; ?>"><?php echo htmlspecialchars($module['nom']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Générer PDF</button>
            </form>

            <div class="admin-actions">
                <h3>Actions groupées</h3>
                <form method="post" action="">
                    <input type="hidden" name="action" value="send_summary">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-envelope"></i> Envoyer récapitulatifs d'absences
                    </button>
                </form>
            </div>

            <?php if (empty($absences)): ?>
                <p>Aucune absence trouvée.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Étudiant</th>
                            <th>Module</th>
                            <th>Date</th>
                            <th>Justifiée</th>
                            <th>Commentaire</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($absences as $absence): ?>
                            <tr>
                                <td><?php echo $absence['id_absence']; ?></td>
                                <td><?php echo htmlspecialchars($absence['etudiant_nom'] . ' ' . $absence['etudiant_prenom']); ?></td>
                                <td><?php echo htmlspecialchars($absence['module_nom']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($absence['date'])); ?></td>
                                <td>
                                    <?php if ($absence['justifiee']): ?>
                                        <span class="badge-yes">Oui</span>
                                    <?php else: ?>
                                        <span class="badge-no">Non</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($absence['commentaire'] ?? ''); ?></td>
                                <td>
                                    <a href="?edit=<?php echo $absence['id_absence']; ?>" class="btn btn-edit">Modifier</a>
                                    <a href="?delete=<?php echo $absence['id_absence']; ?>" class="btn btn-delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette absence?')">Supprimer</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
    document.querySelector('form[action="send_summary"]').addEventListener('submit', function() {
        this.querySelector('button').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours...';
        this.querySelector('button').disabled = true;
    });
</script>