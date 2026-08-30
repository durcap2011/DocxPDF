<?php
/**
 * Esempio 7: Uso con mPDF
 * 
 * Questo esempio dimostra come utilizzare il convertitore mPDF invece di LibreOffice.
 * 
 * Template DOCX richiesto:
 * - Crea un file Word con il seguente contenuto:
 *   "Rapporto mensile di {{mese}} {{anno}}"
 *   "Vendite totali: {{vendite}}"
 * - Salva come "rapporto.docx" nella stessa cartella di questo script.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DocxPDF\DocxPDF;
use DocxPDF\MPDFConverter;

// Dati da sostituire
$data = [
    'mese' => 'Agosto',
    'anno' => '2026',
    'vendite' => '€12,345.67',
];

try {
    // Crea il convertitore mPDF
    $converter = new MPDFConverter();
    
    // Crea l'istanza del convertitore con mPDF
    $docxPDF = new DocxPDF($converter);
    
    // Converti il template
    $pdfPath = $docxPDF->convert(
        __DIR__ . '/rapporto.docx',
        $data
    );
    
    echo "PDF generato con successo (mPDF): $pdfPath\n";
} catch (\Exception $e) {
    echo "Errore: " . $e->getMessage() . "\n";
}