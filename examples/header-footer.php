<?php
/**
 * Esempio 9: Placeholder in header/footer
 * 
 * Questo esempio dimostra come utilizzare placeholder in header e footer.
 * 
 * Template DOCX richiesto:
 * - Crea un file Word con:
 *   Header: "Documento {{azienda}} - {{anno}}"
 *   Corpo: "Contenuto principale..."
 *   Footer: "Pagina {{page}} di {{pages}}"
 * - Salva come "documento_azienda.docx" nella stessa cartella di questo script.
 * 
 * Nota: I placeholder {{page}} e {{pages}} non sono supportati attualmente.
 * Sono solo a scopo dimostrativo.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DocxPDF\DocxPDF;

// Dati da sostituire
$data = [
    'azienda' => 'TechCorp',
    'anno' => '2026',
    'page' => '1', // Esempio statico
    'pages' => '5', // Esempio statico
];

try {
    $docxPDF = new DocxPDF();
    
    $pdfPath = $docxPDF->convert(
        __DIR__ . '/documento_azienda.docx',
        $data
    );
    
    echo "PDF generato con successo: $pdfPath\n";
} catch (\Exception $e) {
    echo "Errore: " . $e->getMessage() . "\n";
}