<?php
/**
 * Esempio 2: Tabella semplice e formattata
 *
 * Questo esempio dimostra come inserire una tabella in un template DOCX,
 * con celle che possono contenere testo formattato (grassetto, colori, pedice, apice).
 *
 * Template DOCX richiesto:
 * - Crea un file Word con il seguente contenuto:
 *   "Riepilogo ordine:"
 *   "{{tabella:prodotti}}"
 *   "Grazie per l'acquisto."
 * - Salva come "ordine.docx" nella stessa cartella di questo script.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DocxPDF\DocxPDF;

// Tabella con celle formattate
$data = [
    'tabella:prodotti' => [
        // Intestazioni (testo semplice)
        ['Prodotto', 'Quantità', 'Prezzo'],

        // Celle con formattazione
        [
            ['text' => 'Laptop', 'bold' => true],
            '1',
            ['text' => '€999,99', 'color' => 'FF0000'],
        ],
        [
            ['text' => 'Mouse', 'bold' => true],
            '2',
            ['text' => '€25,50', 'color' => '008000'],
        ],
        [
            ['text' => 'Tastiera', 'bold' => true],
            '1',
            ['text' => '€45,00'],
        ],
    ],
];

try {
    $docxPDF = new DocxPDF();

    $pdfPath = $docxPDF->convert(
        __DIR__ . '/ordine.docx',
        $data
    );

    echo "PDF generato con successo: $pdfPath\n";
} catch (\Exception $e) {
    echo "Errore: " . $e->getMessage() . "\n";
}
