<?php
/**
 * Esempio 1: Testo semplice e formattato
 *
 * Questo esempio dimostra come sostituire placeholder di testo semplice
 * e come formattare il testo con grassetto, corsivo, pedice, apice, colori, ecc.
 *
 * Template DOCX richiesto:
 * - Crea un file Word con il seguente contenuto:
 *   "Gentile {{nome}}, la sua prenotazione per {{data}} è confermata."
 *   "Prezzo totale: {{prezzo}}"
 *   "La formula chimica dell'acqua è: {{formula}}"
 * - Salva come "prenotazione.docx" nella stessa cartella di questo script.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use durcap2011\DocxPDF\DocxPDF;

// Testo semplice (come prima)
$nome = 'Mario Rossi';
$data = '25/12/2026';

// Testo formattato con segmenti ricchi
$prezzo = [
    ['text' => 'Totale: ', 'bold' => true],
    ['text' => '€1.250,00', 'color' => '008000', 'bold' => true],
];

// Mix di pedice e testo normale
$formula = [
    ['text' => 'H'],
    ['text' => '2', 'subscript' => true],
    ['text' => 'O'],
    ['text' => ' (acqua)'],
];

$data = [
    'nome' => $nome,
    'data' => $data,
    'prezzo' => $prezzo,
    'formula' => $formula,
];

try {
    $docxPDF = new DocxPDF();

    $pdfPath = $docxPDF->convert(
        __DIR__ . '/prenotazione.docx',
        $data
    );

    echo "PDF generato con successo: $pdfPath\n";
} catch (\Exception $e) {
    echo "Errore: " . $e->getMessage() . "\n";
}
