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

// Create PDF directory if it doesn't exist
$pdf_dir = __DIR__ . '/pdf';
if (!is_dir($pdf_dir)) {
    mkdir($pdf_dir, 0777, true);
}

// Generate the PDF
$pdf = new AbsencePDF();
$pdf->AddPage();
$pdf->AbsenceTable($header, $rows);

// Make sure the path exists and is writeable
$pdf_file = $pdf_dir . '/absences.pdf';
$pdf->Output('F', $pdf_file);

// Provide the PDF for download
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="rapport_absences.pdf"');
header('Cache-Control: max-age=0');
readfile($pdf_file);

exit;