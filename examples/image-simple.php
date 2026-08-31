<?php
/**
 * Esempio 5: Immagine
 * 
 * Questo esempio dimostra come inserire un'immagine in un template DOCX.
 * 
 * Template DOCX richiesto:
 * - Crea un file Word con il seguente contenuto:
 *   "Logo dell'azienda:"
 *   "{{immagine:logo}}"
 *   "Contattaci per informazioni."
 * - Salva come "azienda.docx" nella stessa cartella di questo script.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DocxPDF\DocxPDF;

// Dati da sostituire (immagine come percorso file)
$data = [
    'immagine:logo' => [
        'path' => '/path/to/logo.jpg',
        'width' => 200,
        'height' => 100,
    ],
    'page' => 1,
    'pages' => 5,
];

// Alternativa: percorso semplice
// $data = ['immagine:logo' => '/path/to/logo.png'];

try {
    $docxPDF = new DocxPDF();
    
    $pdfPath = $docxPDF->convert(
        __DIR__ . '/azienda.docx',
        $data
    );
    
    echo "PDF generato con successo: $pdfPath\n";
} catch (\Exception $e) {
    echo "Errore: " . $e->getMessage() . "\n";
}