<?php
/**
 * Esempio 10: Dati annidati con formattazione
 *
 * Questo esempio dimostra come gestire dati strutturati e convertirli in formati
 * supportati, con formattazione rich text in liste e tabelle.
 *
 * Template DOCX richiesto:
 * - Crea un file Word con il seguente contenuto:
 *   "Scheda dipendente:"
 *   "Nome: {{nome}}"
 *   "Reparto: {{reparto}}"
 *   "Competenze:"
 *   "{{lista:competenze}}"
 *   "Progetti attivi:"
 *   "{{tabella:progetti}}"
 * - Salva come "scheda_dipendente.docx" nella stessa cartella di questo script.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DocxPDF\DocxPDF;

$dipendente = [
    'nome' => 'Luca Bianchi',
    'reparto' => 'Sviluppo Software',
    'competenze' => [
        ['text' => 'PHP', 'bold' => true, 'color' => '777BB4'],
        ['text' => 'JavaScript', 'bold' => true, 'color' => 'F7DF1E'],
        'MySQL',
        'Git',
        ['text' => 'Docker', 'bold' => true, 'color' => '2496ED'],
    ],
    'progetti' => [
        ['Progetto', 'Stato', 'Scadenza'],
        [
            ['text' => 'Migrazione DB', 'bold' => true],
            ['text' => 'In corso', 'color' => 'FF8C00'],
            '30/09/2026',
        ],
        [
            ['text' => 'App Mobile', 'bold' => true],
            ['text' => 'Pianificato', 'color' => '008000'],
            '15/11/2026',
        ],
    ],
];

$data = [
    'nome' => $dipendente['nome'],
    'reparto' => $dipendente['reparto'],
    'lista:competenze' => $dipendente['competenze'],
    'tabella:progetti' => $dipendente['progetti'],
];

try {
    $docxPDF = new DocxPDF();

    $pdfPath = $docxPDF->convert(
        __DIR__ . '/scheda_dipendente.docx',
        $data
    );

    echo "PDF generato con successo: $pdfPath\n";
} catch (\Exception $e) {
    echo "Errore: " . $e->getMessage() . "\n";
}
