<?php
include '../includes/header.php';

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "gestion_absences");
if ($conn->connect_error) {
    die("Erreur de connexion: " . $conn->connect_error);
}

$sql = "SELECT e.nom, e.prenom, e.email, a.date, a.justifiee
        FROM etudiants e
        JOIN absences a ON e.id_etudiant = a.id_etudiant
        ORDER BY e.nom, a.date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bilan des absences</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
</head>
<body>
    <div class="container">
        <h1 class="titre-page"><i class="lni lni-calendar"></i> Bilan des absences</h1>
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Date d'absence</th>
                    <th>Justifiée</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nom']) ?></td>
                            <td><?= htmlspecialchars($row['prenom']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['date']) ?></td>
                            <td>
                                <?php if ($row['justifiee']): ?>
                                    <span class="badge-oui">
                                        <i class="lni lni-checkmark-circle"></i> Oui
                                    </span>
                                <?php else: ?>
                                    <span class="badge-non">
                                        <i class="lni lni-close"></i> Non
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center">Aucune absence trouvée.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php $conn->close(); ?>
<?php include '../includes/footer.php'; ?>