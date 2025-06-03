<?php
require_once 'config/db.php';
require_once 'includes/pdf_functions.php';

// Get filters from URL
$filiere = $_GET['filiere'] ?? '';
$module = $_GET['module'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');

// Build query with proper joins and grouping
$query = "
    SELECT e.nom, e.prenom, e.numero_apogee, m.nom AS module, f.nom AS filiere,
           a.date AS date_absence,
           CASE WHEN a.justifiee = 1 THEN 'Oui' ELSE 'Non' END AS justifiee
    FROM absences a
    JOIN etudiants e ON a.id_etudiant = e.id_etudiant
    JOIN modules m ON a.id_module = m.id_module
    JOIN filieres f ON e.id_filiere = f.id_filiere
    WHERE 1=1
";

$params = [];

// Apply filters
if ($filiere) {
    $query .= " AND f.id_filiere = ?";
    $params[] = $filiere;
}
if ($module) {
    $query .= " AND m.id_module = ?";
    $params[] = $module;
}
if ($date_debut) {
    $query .= " AND a.date >= ?";
    $params[] = $date_debut;
}
if ($date_fin) {
    $query .= " AND a.date <= ?";
    $params[] = $date_fin;
}

// Order results
$query .= " ORDER BY e.nom, e.prenom, a.date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for table
$header = ['Nom', 'Prénom', 'N° Apogée', 'Module', 'Filière', 'Date', 'Justifiée'];
$rows = [];
foreach ($data as $row) {
    $rows[] = [
        $row['nom'],
        $row['prenom'],
        $row['numero_apogee'],
        $row['module'],
        $row['filiere'],
        date('d/m/Y', strtotime($row['date_absence'])),
        $row['justifiee']
    ];
}

// Create PDF directory if it doesn't exist
$pdf_dir = __DIR__ . '/pdf';
if (!is_dir($pdf_dir)) {
    mkdir($pdf_dir, 0777, true);
}

// Generate unique filename with timestamp
$timestamp = date('YmdHis');
$pdf_file = $pdf_dir . '/absences_' . $timestamp . '.pdf';

// Generate the PDF with title and filters information
$pdf = new AbsencePDF();
$pdf->SetTitle('Rapport des Absences');
$pdf->AddPage();

// Add report title and filters
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Rapport des Absences', 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);

// Add filter information
$pdf->Cell(0, 6, 'Date du rapport: ' . date('d/m/Y'), 0, 1);
if ($filiere) {
    $stmt = $pdo->prepare("SELECT nom FROM filieres WHERE id_filiere = ?");
    $stmt->execute([$filiere]);
    $filiere_nom = $stmt->fetchColumn();
    $pdf->Cell(0, 6, 'Filière: ' . $filiere_nom, 0, 1);
}
if ($module) {
    $stmt = $pdo->prepare("SELECT nom FROM modules WHERE id_module = ?");
    $stmt->execute([$module]);
    $module_nom = $stmt->fetchColumn();
    $pdf->Cell(0, 6, 'Module: ' . $module_nom, 0, 1);
}
if ($date_debut || $date_fin) {
    $periode = 'Période: ';
    if ($date_debut) $periode .= 'du ' . date('d/m/Y', strtotime($date_debut)) . ' ';
    if ($date_fin) $periode .= 'au ' . date('d/m/Y', strtotime($date_fin));
    $pdf->Cell(0, 6, utf8_decode($periode), 0, 1);
}

$pdf->Ln(5);

// Add the table
$pdf->AbsenceTable($header, $rows);

// Output the PDF
$pdf->Output('F', $pdf_file);

// Provide the PDF for download
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="rapport_absences.pdf"');
header('Cache-Control: max-age=0');
readfile($pdf_file);

// Delete the file after sending (optional)
// unlink($pdf_file);

exit;
