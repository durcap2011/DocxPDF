<?php
/**
 * Esempio 6: Tipi specificati nel placeholder con formattazione
 *
 * Questo esempio dimostra come specificare esplicitamente il tipo di placeholder
 * e come usare la formattazione rich text nelle celle della tabella.
 *
 * Template DOCX richiesto:
 * - Crea un file Word con il seguente contenuto:
 *   "Cliente: {{testo:cliente}}"
 *   "Dettagli ordine:"
 *   "{{tabella:dettagli}}"
 *   "Note: {{testo:note}}"
 * - Salva come "dettagli_ordine.docx" nella stessa cartella di questo script.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DocxPDF\DocxPDF;

$data = [
    'testo:cliente' => [
        ['text' => 'Acme S.r.l.', 'bold' => true, 'color' => '0000FF'],
    ],
    'tabella:dettagli' => [
        ['Articolo', 'Prezzo'],
        [
            ['text' => 'Prodotto A', 'italic' => true],
            ['text' => '€50,00', 'color' => '008000'],
        ],
        [
            ['text' => 'Prodotto B', 'italic' => true],
            ['text' => '€75,00', 'color' => '008000'],
        ],
    ],
    'testo:note' => 'Consegna entro 5 giorni lavorativi.',
];

try {
    $docxPDF = new DocxPDF();

    $pdfPath = $docxPDF->convert(
        __DIR__ . '/dettagli_ordine.docx',
        $data
    );

    echo "PDF generato con successo: $pdfPath\n";
} catch (\Exception $e) {
    echo "Errore: " . $e->getMessage() . "\n";
}
