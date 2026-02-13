<?php
require 'vendor/autoload.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdf = new \NFePHP\DA\Legacy\Pdf('P', 'mm', [80, 250]);
    $pdf->AddPage();
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->Cell(40, 10, 'Test PDF');
    // FPDF Output signature: string Output([string dest [, string name [, boolean isUTF8]]])
    // dest: I (inline), D (download), F (file), S (string)
    $data = $pdf->Output('S');
    echo "DATA_LENGTH:" . strlen($data) . "\n";
    if (strlen($data) > 0) {
        echo "START:" . substr($data, 0, 10) . "\n";
    }
} catch (\Throwable $e) {
    echo "ERROR:" . $e->getMessage() . "\n";
    echo "TRACE:" . $e->getTraceAsString() . "\n";
}
