<?php
require_once __DIR__ . '/fpdf/fpdf.php';

class AbsencePDF extends FPDF
{
    function Header()
    {
        // Logo
        $this->Image(__DIR__ . '/../assets/logo_ensa.png', 10, 6, 30);
        // Police Arial gras 15
        $this->SetFont('Arial', 'B', 16);
        // Décalage à droite
        $this->Cell(80);
        // Titre (gestion accents)
        $this->Cell(100, 10, utf8_decode('Rapport des absences'), 0, 0, 'C');
        // Saut de ligne
        $this->Ln(20);
    }

    function Footer()
    {
        // Position à 1,5 cm du bas
        $this->SetY(-15);
        // Police Arial italique 8
        $this->SetFont('Arial', 'I', 8);
        // Date de génération (gestion accents)
        $this->Cell(0, 10, utf8_decode('Généré le ') . date('d/m/Y H:i'), 0, 0, 'C');
    }

    function AbsenceTable($header, $data)
    {
        // Couleurs, épaisseur du trait et police grasse
        $this->SetFillColor(41, 128, 185);
        $this->SetTextColor(255);
        $this->SetDrawColor(44, 62, 80);
        $this->SetLineWidth(.3);
        $this->SetFont('', 'B');
        // En-tête (gestion accents)
        foreach ($header as $col)
            $this->Cell(32, 7, utf8_decode($col), 1, 0, 'C', true);
        $this->Ln();
        // Restauration des couleurs et police
        $this->SetFillColor(236, 240, 241);
        $this->SetTextColor(44, 62, 80);
        $this->SetFont('');
        // Données (gestion accents)
        $fill = false;
        foreach ($data as $row) {
            foreach ($row as $cell)
                $this->Cell(32, 6, utf8_decode($cell), 'LR', 0, 'C', $fill);
            $this->Ln();
            $fill = !$fill;
        }
        $this->Cell(32 * count($header), 0, '', 'T');
    }
}