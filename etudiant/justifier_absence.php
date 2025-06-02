<?php
require_once '../includes/header.php';
require_once '../config/db.php';

// Récupérer les modules pour le select
$stmt = $pdo->prepare("SELECT * FROM modules");
$stmt->execute();
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="form-section" style="max-width:500px;margin:2rem auto;">
    <h2>Déposer un justificatif d'absence</h2>
    <form action="../upload_justificatif.php" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="date_absence">Date d'absence :</label>
            <input type="date" name="date_absence" id="date_absence" required>
        </div>
        <div class="form-group">
            <label for="module_id">Module concerné :</label>
            <select name="module_id" id="module_id" required>
                <option value="">Sélectionnez un module</option>
                <?php foreach ($modules as $module): ?>
                    <option value="<?php echo $module['id_module']; ?>">
                        <?php echo htmlspecialchars($module['nom']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="justificatif">Fichier justificatif (PDF, JPG, PNG) :</label>
            <input type="file" name="justificatif" id="justificatif" accept=".pdf,.jpg,.jpeg,.png" required>
        </div>
        <button type="submit" class="btn btn-primary">Uploader</button>
    </form>
    <?php if (!empty($_SESSION['upload_success'])): ?>
        <div class="success"><?= $_SESSION['upload_success']; unset($_SESSION['upload_success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['upload_error'])): ?>
        <div class="error"><?= $_SESSION['upload_error']; unset($_SESSION['upload_error']); ?></div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>