<?php
/**
 * Esempio 4: Lista numerata con formattazione
 *
 * Questo esempio dimostra come inserire una lista numerata in un template DOCX,
 * con elementi che possono essere formattati.
 *
 * Template DOCX richiesto:
 * - Crea un file Word con il seguente contenuto:
 *   "Passaggi da seguire:"
 *   "{{lista_numerata:passaggi}}"
 *   "Seguire attentamente le istruzioni."
 * - Salva come "istruzioni.docx" nella stessa cartella di questo script.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use durcap2011\DocxPDF\DocxPDF;

$data = [
    'lista_numerata:passaggi' => [
        'Accedere all\'account',
        ['text' => 'Selezionare "Nuovo progetto"', 'bold' => true],
        'Compilare i campi obbligatori',
        ['text' => 'Caricare i documenti', 'italic' => true],
        ['text' => 'Confermare la registrazione', 'bold' => true, 'color' => '008000'],
    ],
    'page' => '1',
    'pages' => '5',
];

try {
    $docxPDF = new DocxPDF();

    $pdfPath = $docxPDF->convert(
        __DIR__ . '/istruzioni.docx',
        $data
    );

    echo "PDF generato con successo: $pdfPath\n";
} catch (\Exception $e) {
    echo "Errore: " . $e->getMessage() . "\n";
}
