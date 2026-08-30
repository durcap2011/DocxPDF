<?php
/**
 * Esempio 3: Lista puntata con formattazione
 *
 * Questo esempio dimostra come inserire una lista puntata in un template DOCX,
 * con elementi che possono essere formattati (grassetto, corsivo, colori, ecc.).
 *
 * Template DOCX richiesto:
 * - Crea un file Word con il seguente contenuto:
 *   "Materiali da portare:"
 *   "{{lista:materiale}}"
 *   "Non dimenticare nulla!"
 * - Salva come "checklist.docx" nella stessa cartella di questo script.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DocxPDF\DocxPDF;

// Lista con elementi formattati
$data = [
    'lista:materiale' => [
        'Passaporto',
        'Biglietto aereo',
        ['text' => 'Assicurazione sanitaria', 'bold' => true, 'color' => 'FF0000'],
        'Caricatore telefono',
        ['text' => 'Farmaci personali', 'italic' => true],
    ],
];

try {
    $docxPDF = new DocxPDF();

    $pdfPath = $docxPDF->convert(
        __DIR__ . '/checklist.docx',
        $data
    );

    echo "PDF generato con successo: $pdfPath\n";
} catch (\Exception $e) {
    echo "Errore: " . $e->getMessage() . "\n";
}
