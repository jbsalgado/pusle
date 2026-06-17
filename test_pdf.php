<?php
require 'vendor/autoload.php';
try {
    $pdf = new \NFePHP\DA\Legacy\Pdf('P', 'mm', [80, 250]);
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(40, 10, 'Test PDF');
    $data = $pdf->Output('S');
    echo "OK:" . strlen($data);
} catch (\Exception $e) {
    echo "ERROR:" . $e->getMessage();
}
