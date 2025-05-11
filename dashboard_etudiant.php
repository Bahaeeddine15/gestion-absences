<?php
require_once 'includes/auth.php';
require_once 'includes/header.php';

checkEtudiant();

$apogee = $_SESSION['apogee']; // Apogée stocké à la connexion

try {
    $stmt = $pdo->prepare("
        SELECT m.nom_module, a.date_absence, a.justifie 
        FROM absences a
        JOIN modules m ON a.id_module = m.id_module
        WHERE a.apogee = ?
        ORDER BY a.date_absence DESC
    ");
    $stmt->execute([$apogee]);
    $absences = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "<p>Erreur lors de la récupération des absences : " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<h2>👋 Bonjour, <?= htmlspecialchars($_SESSION['prenom']) ?> <?= htmlspecialchars($_SESSION['nom']) ?></h2>
<h3>🗓️ Votre Bilan d’Absences</h3>

<?php if ($absences): ?>
    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>Module</th>
                <th>Date</th>
                <th>Justifiée</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($absences as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['nom_module']) ?></td>
                    <td><?= htmlspecialchars($a['date_absence']) ?></td>
                    <td><?= $a['justifie'] ? '✅ Oui' : '❌ Non' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>✅ Aucune absence enregistrée pour le moment.</p>
<?php endif; ?>

<p><a href="logout.php">🔓 Se déconnecter</a></p>
<?php include 'includes/footer.php'; ?>
