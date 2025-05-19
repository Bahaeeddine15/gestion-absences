<?php
require_once __DIR__ . '/../includes/auth.php';
CheckAdmin();
$title = 'Gestion des modules';
include __DIR__ . '/../includes/header.php';

$error = '';
$success = '';
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Ajouter un module
if (isset($_POST['add'])) {
    $nom = trim($_POST['nom']);
    $semestre = trim($_POST['semestre']);
    $id_filiere = $_POST['id_filiere'] ?? null;

    $resp_nom = trim($_POST['resp_nom'] ?? '');
    $resp_prenom = trim($_POST['resp_prenom'] ?? '');
    $resp_email = trim($_POST['resp_email'] ?? '');

    if (
        empty($nom) || empty($semestre) || empty($id_filiere) ||
        empty($resp_nom) || empty($resp_prenom) || empty($resp_email)
    ) {
        $error = "Tous les champs sont obligatoires";
    } else {
        try {
            // Vérifier si le responsable existe déjà (par email)
            $stmt = $pdo->prepare("SELECT id_responsable FROM responsables WHERE email = ?");
            $stmt->execute([$resp_email]);
            $id_responsable = $stmt->fetchColumn();

            if (!$id_responsable) {
                // Ajouter le responsable
                $stmt = $pdo->prepare("INSERT INTO responsables (nom, prenom, email) VALUES (?, ?, ?)");
                $stmt->execute([$resp_nom, $resp_prenom, $resp_email]);
                $id_responsable = $pdo->lastInsertId();
            }

            // Vérifier l'unicité du module
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM modules WHERE nom = ? AND semestre = ? AND id_filiere = ?");
            $stmt->execute([$nom, $semestre, $id_filiere]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Ce module existe déjà pour cette filière et ce semestre";
            } else {
                $stmt = $pdo->prepare("INSERT INTO modules (nom, semestre, id_filiere, id_responsable) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nom, $semestre, $id_filiere, $id_responsable]);
                $success = "Module ajouté avec succès";
                header('Location: /gestion-absences/admin/gestion_modules.php?success=' . urlencode($success));
                exit;
            }
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout du module : " . $e->getMessage();
        }
    }
}

// Modifier un module
if (isset($_POST['update'])) {
    $id = $_POST['id_module'];
    $nom = trim($_POST['nom']);
    $semestre = trim($_POST['semestre']);
    $id_filiere = $_POST['id_filiere'] ?? null;
    $id_responsable = $_POST['id_responsable'] ?? null;

    if (empty($nom) || empty($semestre) || empty($id_filiere) || empty($id_responsable)) {
        $error = "Tous les champs sont obligatoires";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM modules WHERE nom = ? AND semestre = ? AND id_filiere = ? AND id_module != ?");
            $stmt->execute([$nom, $semestre, $id_filiere, $id]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Un autre module avec ce nom existe déjà pour cette filière et ce semestre";
            } else {
                $stmt = $pdo->prepare("UPDATE modules SET nom = ?, semestre = ?, id_filiere = ?, id_responsable = ? WHERE id_module = ?");
                $stmt->execute([$nom, $semestre, $id_filiere, $id_responsable, $id]);
                $success = "Module mis à jour avec succès";
                header('Location: /gestion-absences/admin/gestion_modules.php?success=' . urlencode($success));
                exit;
            }
        } catch (PDOException $e) {
            $error = "Erreur lors de la mise à jour du module : " . $e->getMessage();
        }
    }
}

// Supprimer un module
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM modules WHERE id_module = ?");
        $stmt->execute([$id]);
        $success = "Module supprimé avec succès";
        header('Location: /gestion-absences/admin/gestion_modules.php?success=' . urlencode($success));
        exit;
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression du module : " . $e->getMessage();
    }
}

// Préparer l'édition
$module_to_edit = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM modules WHERE id_module = ?");
        $stmt->execute([$id]);
        $module_to_edit = $stmt->fetch();
        if (!$module_to_edit) {
            $error = "Module non trouvé";
        }
    } catch (PDOException $e) {
        $error = "Erreur lors de la récupération du module : " . $e->getMessage();
    }
}

// Récupérer toutes les filières et responsables
try {
    $filieres = $pdo->query("SELECT * FROM filieres ORDER BY nom")->fetchAll();
    $responsables = $pdo->query("SELECT * FROM responsables ORDER BY nom, prenom")->fetchAll();
    $modules = $pdo->query("SELECT * FROM modules ORDER BY semestre, nom")->fetchAll();
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des données : " . $e->getMessage();
    $filieres = [];
    $responsables = [];
    $modules = [];
}
?>

<div class="admin-container">
    <h3>Gestion des Modules</h3>

    <?php if (!empty($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="admin-content">
        <div class="form-section">
            <h2><?php echo ($module_to_edit ? 'Modifier le module' : 'Ajouter un module'); ?></h2>
            <form method="post">
                <?php if ($module_to_edit) : ?>
                    <input type="hidden" name="id_module" value="<?php echo $module_to_edit['id_module']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="nom">Nom du module</label>
                    <input type="text" id="nom" name="nom" value="<?php echo $module_to_edit ? htmlspecialchars($module_to_edit['nom']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="semestre">Semestre</label>
                    <input type="text" id="semestre" name="semestre" value="<?php echo $module_to_edit ? htmlspecialchars($module_to_edit['semestre']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="id_filiere">Filière</label>
                    <select id="id_filiere" name="id_filiere" required>
                        <option value="">Sélectionner une filière</option>
                        <?php foreach ($filieres as $f): ?>
                            <option value="<?php echo $f['id_filiere']; ?>" <?php if ($module_to_edit && $f['id_filiere'] == $module_to_edit['id_filiere']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($f['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if (!$module_to_edit): // Ajout d'un module ?>
                <div class="form-group">
                    <label for="resp_nom">Nom du responsable</label>
                    <input type="text" id="resp_nom" name="resp_nom" required>
                </div>
                <div class="form-group">
                    <label for="resp_prenom">Prénom du responsable</label>
                    <input type="text" id="resp_prenom" name="resp_prenom" required>
                </div>
                <div class="form-group">
                    <label for="resp_email">Email du responsable</label>
                    <input type="email" id="resp_email" name="resp_email" required>
                </div>
                <?php else: // Edition d'un module ?>
                <div class="form-group">
                    <label>Responsable</label>
                    <?php
                    $resp = null;
                    foreach ($responsables as $r) {
                        if ($r['id_responsable'] == $module_to_edit['id_responsable']) {
                            $resp = $r;
                            break;
                        }
                    }
                    echo $resp ? htmlspecialchars($resp['nom'] . ' ' . $resp['prenom'] . ' (' . $resp['email'] . ')') : 'Non défini';
                    ?>
                </div>
                <?php endif; ?>

                <button type="submit" name="<?php echo $module_to_edit ? 'update' : 'add'; ?>" class="btn btn-primary">
                    <?php echo $module_to_edit ? 'Mettre à jour' : 'Ajouter'; ?>
                </button>

                <?php if ($module_to_edit) : ?>
                    <a href="/gestion-absences/admin/gestion_modules.php" class="btn btn-secondary">Annuler</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="list-section">
            <h2>Liste des modules</h2>
            <?php if (empty($modules)): ?>
                <p>Aucun module trouvé.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Semestre</th>
                            <th>Filière</th>
                            <th>Responsable</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modules as $m): ?>
                            <tr>
                                <td><?php echo $m['id_module']; ?></td>
                                <td><?php echo htmlspecialchars($m['nom']); ?></td>
                                <td><?php echo htmlspecialchars($m['semestre']); ?></td>
                                <td>
                                    <?php
                                    $filiere = array_filter($filieres, fn($f) => $f['id_filiere'] == $m['id_filiere']);
                                    echo $filiere ? htmlspecialchars(array_values($filiere)[0]['nom']) : '';
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $resp = array_filter($responsables, fn($r) => $r['id_responsable'] == $m['id_responsable']);
                                    if ($resp) {
                                        $r = array_values($resp)[0];
                                        echo htmlspecialchars($r['nom'] . ' ' . $r['prenom']);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="?edit=<?php echo $m['id_module']; ?>" class="btn btn-edit">Modifier</a>
                                    <a href="?delete=<?php echo $m['id_module']; ?>" class="btn btn-delete" onclick="return confirm('Êtes-vous sûr?')">Supprimer</a>
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