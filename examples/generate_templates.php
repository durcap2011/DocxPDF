<?php
/**
 * Script per generare tutti i template DOCX di esempio.
 *
 * Esegui: php examples/generate_templates.php
 */

$examplesDir = __DIR__;

function createDocx(string $filePath, string $bodyXml, array $headers = [], array $footers = []): void
{
    $zip = new ZipArchive();
    if ($zip->open($filePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException("Impossibile creare: $filePath");
    }

    // [Content_Types].xml
    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
        . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>';

    foreach ($headers as $i => $h) {
        $num = $i + 1;
        $contentTypes .= '<Override PartName="/word/header' . $num . '.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"/>';
    }
    foreach ($footers as $i => $f) {
        $num = $i + 1;
        $contentTypes .= '<Override PartName="/word/footer' . $num . '.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/>';
    }
    $contentTypes .= '</Types>';
    $zip->addFromString('[Content_Types].xml', $contentTypes);

    // _rels/.rels
    $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
        . '</Relationships>';
    $zip->addFromString('_rels/.rels', $rels);

    // word/styles.xml (minimal)
    $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
        . '<w:style w:type="paragraph" w:styleId="Normal"><w:name w:val="Normal"/></w:style>'
        . '<w:style w:type="table" w:styleId="TableGrid">'
        . '<w:name w:val="Table Grid"/>'
        . '<w:tblPr>'
        . '<w:tblBorders>'
        . '<w:top w:val="single" w:sz="4" w:space="0" w:color="auto"/>'
        . '<w:left w:val="single" w:sz="4" w:space="0" w:color="auto"/>'
        . '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="auto"/>'
        . '<w:right w:val="single" w:sz="4" w:space="0" w:color="auto"/>'
        . '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="auto"/>'
        . '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="auto"/>'
        . '</w:tblBorders>'
        . '</w:tblPr>'
        . '</w:style>'
        . '</w:styles>';
    $zip->addFromString('word/styles.xml', $styles);

    // Build body XML with optional headers/footers references
    $sectPr = '';
    if (!empty($headers) || !empty($footers)) {
        $sectPr = '<w:sectPr>';
        foreach ($headers as $i => $h) {
            $num = $i + 1;
            $sectPr .= '<w:headerReference w:type="default" r:id="rIdH' . $num . '"/>';
        }
        foreach ($footers as $i => $f) {
            $num = $i + 1;
            $sectPr .= '<w:footerReference w:type="default" r:id="rIdF' . $num . '"/>';
        }
        $sectPr .= '<w:pgSz w:w="11906" w:h="16838"/>'
            . '<w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134" w:header="709" w:footer="709" w:gutter="0"/>'
            . '</w:sectPr>';
    } else {
        $sectPr = '<w:sectPr>'
            . '<w:pgSz w:w="11906" w:h="16838"/>'
            . '<w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134" w:header="709" w:footer="709" w:gutter="0"/>'
            . '</w:sectPr>';
    }

    $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"'
        . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<w:body>'
        . $bodyXml
        . $sectPr
        . '</w:body>'
        . '</w:document>';
    $zip->addFromString('word/document.xml', $document);

    // word/_rels/document.xml.rels
    $docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
    $relId = 1;
    foreach ($headers as $i => $h) {
        $num = $i + 1;
        $docRels .= '<Relationship Id="rIdH' . $num . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/header" Target="header' . $num . '.xml"/>';
    }
    foreach ($footers as $i => $f) {
        $num = $i + 1;
        $docRels .= '<Relationship Id="rIdF' . $num . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer" Target="footer' . $num . '.xml"/>';
    }
    $docRels .= '</Relationships>';
    $zip->addFromString('word/_rels/document.xml.rels', $docRels);

    // Header files
    foreach ($headers as $i => $h) {
        $num = $i + 1;
        $headerXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:hdr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<w:p><w:r><w:t>' . htmlspecialchars($h) . '</w:t></w:r></w:p>'
            . '</w:hdr>';
        $zip->addFromString('word/header' . $num . '.xml', $headerXml);

        // header rels
        $hdrRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>';
        $zip->addFromString('word/_rels/header' . $num . '.xml.rels', $hdrRels);
    }

    // Footer files
    foreach ($footers as $i => $f) {
        $num = $i + 1;
        $footerXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<w:p><w:r><w:t>' . htmlspecialchars($f) . '</w:t></w:r></w:p>'
            . '</w:ftr>';
        $zip->addFromString('word/footer' . $num . '.xml', $footerXml);

        $ftrRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>';
        $zip->addFromString('word/_rels/footer' . $num . '.xml.rels', $ftrRels);
    }

    $zip->close();
}

function p(string $text): string
{
    return '<w:p><w:r><w:t>' . htmlspecialchars($text) . '</w:t></w:r></w:p>';
}

// ============================================================
// 1. text-simple.php -> prenotazione.docx
// ============================================================
$body = p('Gentile {{nome}}, la sua prenotazione per {{data}} è confermata.')
    . p('Prezzo totale: {{prezzo}}')
    . p('La formula chimica dell\'acqua è: {{formula}}');
createDocx($examplesDir . '/prenotazione.docx', $body);
echo "Creato: prenotazione.docx\n";

// ============================================================
// 2. table-simple.php -> ordine.docx
// ============================================================
$body = p('Riepilogo ordine:')
    . p('{{tabella:prodotti}}')
    . p('Grazie per l\'acquisto.');
createDocx($examplesDir . '/ordine.docx', $body);
echo "Creato: ordine.docx\n";

// ============================================================
// 3. list-bullet.php -> checklist.docx
// ============================================================
$body = p('Materiali da portare:')
    . p('{{lista:materiale}}')
    . p('Non dimenticare nulla!');
createDocx($examplesDir . '/checklist.docx', $body);
echo "Creato: checklist.docx\n";

// ============================================================
// 4. list-numbered.php -> istruzioni.docx
// ============================================================
$body = p('Passaggi da seguire:')
    . p('{{lista_numerata:passaggi}}')
    . p('Seguire attentamente le istruzioni.');
createDocx($examplesDir . '/istruzioni.docx', $body);
echo "Creato: istruzioni.docx\n";

// ============================================================
// 5. image-simple.php -> azienda.docx
// ============================================================
$body = p('Logo dell\'azienda:')
    . p('{{immagine:logo}}')
    . p('Contattaci per informazioni.');
createDocx($examplesDir . '/azienda.docx', $body);
echo "Creato: azienda.docx\n";

// ============================================================
// 6. typed-placeholders.php -> dettagli_ordine.docx
// ============================================================
$body = p('Cliente: {{testo:cliente}}')
    . p('Dettagli ordine:')
    . p('{{tabella:dettagli}}')
    . p('Note: {{testo:note}}');
createDocx($examplesDir . '/dettagli_ordine.docx', $body);
echo "Creato: dettagli_ordine.docx\n";

// ============================================================
// 7. multiple-placeholders.php -> fattura.docx
// ============================================================
$body = p('FATTURA N. {{numero_fattura}}')
    . p('Data: {{data_fattura}}')
    . p('Cliente: {{cliente}}')
    . p('Indirizzo: {{indirizzo}}')
    . p('Dettagli:')
    . p('{{tabella:prodotti}}')
    . p('Totale: {{totale}}')
    . p('Note: {{note}}');
createDocx($examplesDir . '/fattura.docx', $body);
echo "Creato: fattura.docx\n";

// ============================================================
// 8. nested-data.php -> scheda_dipendente.docx
// ============================================================
$body = p('Scheda dipendente:')
    . p('Nome: {{nome}}')
    . p('Reparto: {{reparto}}')
    . p('Competenze:')
    . p('{{lista:competenze}}')
    . p('Progetti attivi:')
    . p('{{tabella:progetti}}');
createDocx($examplesDir . '/scheda_dipendente.docx', $body);
echo "Creato: scheda_dipendente.docx\n";

// ============================================================
// 9. header-footer.php -> documento_azienda.docx
// ============================================================
$body = p('Contenuto principale...');
$headers = ['Documento {{azienda}} - {{anno}}'];
$footers = ['Pagina {{page}} di {{pages}}'];
createDocx($examplesDir . '/documento_azienda.docx', $body, $headers, $footers);
echo "Creato: documento_azienda.docx\n";

echo "\nTutti i template DOCX sono stati creati con successo!\n";
