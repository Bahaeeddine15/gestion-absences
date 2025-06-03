<?php
require_once '../includes/header.php';
require_once '../config/db.php';
isStudent(); // Vérifier que l'utilisateur est un étudiant

$etudiant_id = $_SESSION['user_id'];

try {
    // Récupérer les absences de l'étudiant connecté
    $stmt = $pdo->prepare("
        SELECT a.date, a.justifiee, m.nom as module_nom
        FROM absences a
        JOIN modules m ON a.id_module = m.id_module
        WHERE a.id_etudiant = ?
        ORDER BY a.date DESC
    ");
    $stmt->execute([$etudiant_id]);
    $absences = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des absences: " . $e->getMessage();
    $absences = [];
}
?>

<div class="container">
    <h1 class="titre-page"><i class="lni lni-calendar"></i> Bilan des absences</h1>

    <?php if (isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Module</th>
                <th>Date d'absence</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($absences)): ?>
                <?php foreach($absences as $absence): ?>
                    <tr>
                        <td><?= htmlspecialchars($absence['module_nom']) ?></td>
                        <td><?= date('d/m/Y', strtotime($absence['date'])) ?></td>
                        <td>
                            <?php if ($absence['justifiee']): ?>
                                <span class="badge-oui">
                                    <i class="lni lni-checkmark-circle"></i> Justifiée
                                </span>
                            <?php else: ?>
                                <span class="badge-non">
                                    <i class="lni lni-close"></i> Non justifiée
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3" class="text-center">Aucune absence trouvée.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>