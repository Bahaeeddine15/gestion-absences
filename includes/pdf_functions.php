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
        // Calculate column widths based on page width
        $page_width = $this->GetPageWidth() - 20; // 10mm margin on each side

        // Define column widths as percentages of total width
        $w = [
            $page_width * 0.15, // Nom
            $page_width * 0.15, // Prénom
            $page_width * 0.12, // N° Apogée
            $page_width * 0.20, // Module
            $page_width * 0.15, // Filière
            $page_width * 0.13, // Date
            $page_width * 0.10  // Justifiée
        ];

        // Header - Use a professional blue color scheme
        $this->SetFillColor(41, 128, 185); // Blue header background
        $this->SetTextColor(255, 255, 255); // White text for header
        $this->SetFont('Arial', 'B', 10); // Bold font for header
        
        for ($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 8, utf8_decode($header[$i]), 1, 0, 'C', true);
        }
        $this->Ln();

        // Reset text color for data rows
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 9);

        // Data rows with alternating colors
        $fill = false;
        $row_count = 0;
        
        foreach ($data as $row) {
            // Alternate row colors: white and light blue
            if ($fill) {
                $this->SetFillColor(235, 245, 251); // Very light blue
            } else {
                $this->SetFillColor(255, 255, 255); // White
            }

            // Check if row will fit on current page, if not add a new page
            if ($this->GetY() > 270) {
                $this->AddPage();

                // Re-add the header on the new page
                $this->SetFillColor(41, 128, 185); // Blue header
                $this->SetTextColor(255, 255, 255); // White text
                $this->SetFont('Arial', 'B', 10);
                
                for ($i = 0; $i < count($header); $i++) {
                    $this->Cell($w[$i], 8, utf8_decode($header[$i]), 1, 0, 'C', true);
                }
                $this->Ln();
                
                // Reset text color for data
                $this->SetTextColor(0, 0, 0);
                $this->SetFont('Arial', '', 9);
            }

            // Highlight cells with "Non" in the Justifiée column with light red
            for ($i = 0; $i < count($row); $i++) {
                // Special formatting for "Justifiée" column
                if ($i == 6) { // 6 is the "Justifiée" column
                    if ($row[$i] == 'Non') {
                        // Red background for unjustified absences
                        $this->SetTextColor(156, 0, 6); // Dark red text
                        if ($fill) {
                            $this->SetFillColor(255, 235, 235); // Light red for alternating row
                        } else {
                            $this->SetFillColor(255, 220, 220); // Light red for regular row
                        }
                    } else {
                        // Green for justified absences
                        $this->SetTextColor(0, 100, 0); // Dark green text
                        if ($fill) {
                            $this->SetFillColor(235, 255, 235); // Light green for alternating row
                        } else {
                            $this->SetFillColor(220, 255, 220); // Light green for regular row
                        }
                    }
                    
                    $this->Cell($w[$i], 6, utf8_decode($row[$i]), 1, 0, 'C', true);
                    
                    // Reset colors
                    $this->SetTextColor(0, 0, 0);
                    if ($fill) {
                        $this->SetFillColor(235, 245, 251);
                    } else {
                        $this->SetFillColor(255, 255, 255);
                    }
                } else {
                    // Normal cell formatting
                    $this->Cell($w[$i], 6, utf8_decode($row[$i]), 1, 0, 'L', true);
                }
            }
            
            $this->Ln();
            $fill = !$fill;
            $row_count++;
            
            // Add a slightly darker divider row every 5 entries
            if ($row_count % 5 === 0 && $row_count < count($data)) {
                $this->SetFillColor(220, 230, 241);
                $this->Cell(array_sum($w), 1, '', 0, 1, 'L', true);
                $this->SetFillColor(255, 255, 255);
            }
        }
        
        // Summary footer with totals
        $total_absences = count($data);
        $justified = array_filter($data, function($row) {
            return $row[6] == 'Oui';
        });
        $total_justified = count($justified);
        
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(array_sum($w), 8, utf8_decode("Total des absences: $total_absences | Justifiées: $total_justified | Non justifiées: " . ($total_absences - $total_justified)), 0, 1, 'R');
    }
}
