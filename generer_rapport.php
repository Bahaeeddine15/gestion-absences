<?php
require_once 'config/db.php';
require_once 'includes/pdf_functions.php';

$filiere = $_GET['filiere'] ?? '';
$module = $_GET['module'] ?? '';

// Récupérer les absences filtrées
$query = "
    SELECT e.nom, e.prenom, e.numero_apogee, m.nom AS module, f.nom AS filiere,
           IF(j.id IS NOT NULL, 'oui', 'non') AS justificatif
    FROM absences a
    JOIN etudiants e ON a.id_etudiant = e.id_etudiant
    JOIN modules m ON a.id_module = m.id_module
    JOIN filieres f ON e.id_filiere = f.id_filiere
    LEFT JOIN justificatifs j ON j.etudiant_id = e.id_etudiant AND j.module_id = m.id_module AND j.date_absence = a.date
    WHERE 1=1
";
$params = [];
if ($filiere) {
    $query .= " AND f.id_filiere = ?";
    $params[] = $filiere;
}
if ($module) {
    $query .= " AND m.id_module = ?";
    $params[] = $module;
}
$query .= " ORDER BY e.nom, a.date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Préparer les données pour le tableau
$header = ['Nom', 'Prénom', 'Apogée', 'Module', 'Filière', 'Justif.'];
$rows = [];
foreach ($data as $row) {
    $rows[] = [
        $row['nom'],
        $row['prenom'],
        $row['numero_apogee'],
        $row['module'],
        $row['filiere'],
        $row['justificatif']
    ];
}

// Générer le nom du fichier
$filename = 'absences';
if ($filiere) $filename .= '_filiere' . $filiere;
if ($module) $filename .= '_module' . $module;
$filename .= '.pdf';

// Générer le PDF
$pdf = new AbsencePDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);
$pdf->AbsenceTable($header, $rows);

// Sauvegarder dans /pdf/
$pdf_path = __DIR__ . '/pdf/' . $filename;
$pdf->Output('F', $pdf_path);

// Télécharger directement
$pdf->Output('D', $filename);
exit;