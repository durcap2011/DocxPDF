<?php
/**
 * Esempio 8: Multiplo placeholder con formattazione
 *
 * Questo esempio dimostra come gestire molti placeholder in un documento,
 * con testo formattato (grassetto, colori, apice, pedice).
 *
 * La tabella supporta due formati:
 *   - Array semplice di righe: l'header (prima riga) si ripete ad ogni cambio pagina
 *   - Array con chiavi 'config' e 'rows': per configurare il comportamento
 *
 * Template DOCX richiesto:
 * - Crea un file Word con il seguente contenuto:
 *   "FATTURA N. {{numero_fattura}}"
 *   "Data: {{data_fattura}}"
 *   "Cliente: {{cliente}}"
 *   "Indirizzo: {{indirizzo}}"
 *   "Dettagli:"
 *   "{{tabella:prodotti}}"
 *   "Totale: {{totale}}"
 *   "Note: {{note}}"
 * - Salva come "fattura.docx" nella stessa cartella di questo script.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DocxPDF\DocxPDF;

$data_table = [
    [
        ['text' => 'Prodotto', 'valign'=>'center', 'align' => 'center'], 
        ['text' => 'Quantità', 'valign'=>'center', 'align' => 'center'],
        ['text' => 'Prezzo Unitario', 'valign'=>'center', 'align' => 'center'],
        ['text' => 'Totale', 'valign'=>'center', 'align' => 'center'],
    ],
    [
        ['text' => 'Servizio consulenza', 'bold' => true],
        '10 ore',
        '€80,00',
        ['text' => '€800,00', 'color' => '008000'],
    ],
    [
        ['text' => 'Sviluppo software', 'bold' => true],
        '1',
        '€2.500,00',
        ['text' => '€2.500,00', 'color' => '008000'],
    ],
    [
        ['text' => 'Assistenza tecnica', 'bold' => true],
        '1 mese',
        '€150,00',
        ['text' => '€150,00', 'color' => '008000'],
    ],
    [
        ['text' => 'Servizio consulenza', 'bold' => true],
        '10 ore',
        '€80,00',
        ['text' => '€800,00', 'color' => '008000'],
    ],
    [
        ['text' => 'Sviluppo software', 'bold' => true],
        '1',
        '€2.500,00',
        ['text' => '€2.500,00', 'color' => '008000'],
    ],
    [
        ['text' => 'Assistenza tecnica', 'bold' => true],
        '1 mese',
        '€150,00',
        ['text' => '€150,00', 'color' => '008000'],
    ],
    [
        ['text' => 'Servizio consulenza', 'bold' => true],
        '10 ore',
        '€80,00',
        ['text' => '€800,00', 'color' => '008000'],
    ],
    [
        ['text' => 'Sviluppo software', 'bold' => true],
        '1',
        '€2.500,00',
        ['text' => '€2.500,00', 'color' => '008000'],
    ],
    [
        ['text' => 'Assistenza tecnica', 'bold' => true],
        '1 mese',
        '€150,00',
        ['text' => '€150,00', 'color' => '008000'],
    ],
    [
        ['text' => 'Servizio consulenza', 'bold' => true],
        '10 ore',
        '€80,00',
        ['text' => '€800,00', 'color' => '008000'],
    ],
    [
        ['text' => 'Sviluppo software', 'bold' => true],
        '1',
        '€2.500,00',
        ['text' => '€2.500,00', 'color' => '008000'],
    ],
    [
        ['text' => 'Assistenza tecnica', 'bold' => true],
        '1 mese',
        '€150,00',
        ['text' => '€150,00', 'color' => '008000'],
    ],
    [
        ['text' => 'Servizio consulenza', 'bold' => true],
        '10 ore',
        '€80,00',
        ['text' => '€800,00', 'color' => '008000'],
    ],
    [
        ['text' => 'Sviluppo software', 'bold' => true],
        '1',
        '€2.500,00',
        ['text' => '€2.500,00', 'color' => '008000'],
    ],
    [
        ['text' => 'Assistenza tecnica', 'bold' => true],
        '1 mese',
        '€150,00',
        ['text' => '€150,00', 'color' => '008000'],
    ],
    [
        ['text' => 'Assistenza tecnica', 'bold' => true],
        '1 mese',
        '€150,00',
        ['text' => '€150,00', 'color' => '008000'],
    ],
    [
        ['text' => 'Servizio consulenza', 'bold' => true],
        '10 ore',
        '€80,00',
        ['text' => '€800,00', 'color' => '008000'],
    ],
    [
        ['text' => 'Sviluppo software', 'bold' => true],
        '1',
        '€2.500,00',
        ['text' => '€2.500,00', 'color' => '008000'],
    ],
    [
        ['text' => 'Assistenza tecnica', 'bold' => true],
        '1 mese',
        '€150,00',
        ['text' => '€150,00', 'color' => '008000'],
    ],
    [
        ['text' => 'Assistenza tecnica', 'bold' => true],
        '1 mese',
        '€150,00',
        ['text' => '€150,00', 'color' => '008000'],
    ],
    [
        ['text' => 'Servizio consulenza', 'bold' => true],
        '10 ore',
        '€80,00',
        ['text' => '€800,00', 'color' => '008000'],
    ],
    [
        ['text' => 'Sviluppo software', 'bold' => true],
        '1',
        '€2.500,00',
        ['text' => '€2.500,00', 'color' => '008000'],
    ],
    [
        ['text' => 'Assistenza tecnica', 'bold' => true],
        '1 mese',
        '€150,00',
        ['text' => '€150,00', 'color' => '008000'],
    ],
    [
        ['text' => 'Servizio consulenza', 'bold' => true],
        '10 ore',
        '€80,00',
        ['text' => '€800,00', 'color' => '008000'],
    ],
    [
        ['text' => 'Sviluppo software', 'bold' => true],
        '1',
        '€2.500,00',
        ['text' => '€2.500,00', 'color' => '008000'],
    ],
    [
        ['text' => 'Assistenza tecnica', 'bold' => true],
        '1 mese',
        '€150,00',
        ['text' => '€150,00', 'color' => '008000'],
    ],
];

// Formato semplice: l'header (prima riga) si ripete automaticamente ad ogni pagina
$dataSimple = [
    'numero_fattura' => 'F-2026-001',
    'data_fattura' => '28/08/2026',
    'cliente' => 'Rossi & Verdi S.p.A.',
    'indirizzo' => 'Via Roma 1, 00100 Roma (RM)',
    'tabella:prodotti' => $data_table,
    'totale' => [
        ['text' => 'TOTALE: ', 'bold' => true, 'fontSize' => 14],
        ['text' => '€3.450,00', 'bold' => true, 'color' => 'FF0000', 'fontSize' => 14],
    ],
    'note' => 'Pagamento entro 30 giorni. IVA esclusa.',
];

// Formato con config: disattiva la ripetizione dell'header
$dataNoRepeat = [
    'numero_fattura' => 'F-2026-002',
    'data_fattura' => '29/08/2026',
    'cliente' => 'Bianchi S.r.l.',
    'indirizzo' => 'Via Milano 2, 20100 Milano (MI)',
    'tabella:prodotti' => [
        'config' => ['repeatHeader' => false],
        'rows' => $data_table,
    ],
    'totale' => [
        ['text' => 'TOTALE: ', 'bold' => true, 'fontSize' => 14],
        ['text' => '€400,00', 'bold' => true, 'color' => 'FF0000', 'fontSize' => 14],
    ],
    'note' => 'Pagamento entro 30 giorni. IVA esclusa.',
];

try {
    $docxPDF = new DocxPDF();

    // Default: header ripete ad ogni pagina
    $pdfPath = $docxPDF->convert(
        __DIR__ . '/fattura.docx',
        $dataSimple
    );
    echo "PDF generato con successo: $pdfPath\n";

    // Con header NON ripetuto
    $pdfPath2 = $docxPDF->convert(
        __DIR__ . '/fattura.docx',
        $dataNoRepeat,
        __DIR__ . '/fattura_no_repeat.pdf'
    );
    echo "PDF generato con successo: $pdfPath2\n";
} catch (\Exception $e) {
    echo "Errore: " . $e->getMessage() . "\n";
}
