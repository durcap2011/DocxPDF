<?php

declare(strict_types=1);

namespace DocxPDF;

use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

/**
 * Convertitore che utilizza mPDF per generare il PDF.
 * Estrae il contenuto testuale dal DOCX e genera un HTML semplice.
 */
class MPDFConverter extends AbstractConverter
{
    /**
     * @var array Opzioni per mPDF.
     */
    private $mpdfOptions;

    /**
     * Costruttore.
     *
     * @param array $mpdfOptions Opzioni per mPDF.
     */
    public function __construct(array $mpdfOptions = [])
    {
        $this->mpdfOptions = $mpdfOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function convert(string $docxPath, string $pdfPath, array $data): bool
    {
        // Estrai il contenuto XML dal DOCX
        $xmlFiles = $this->extractXmlFiles($docxPath);
        $html = '';

        $zip = new \ZipArchive();
        if ($zip->open($docxPath) !== true) {
            throw new \RuntimeException("Impossibile aprire il file DOCX: $docxPath");
        }

        $parser = new \DocxPDF\Placeholder\PlaceholderParser();

        foreach ($xmlFiles as $xmlFile) {
            $content = $zip->getFromName($xmlFile);
            if ($content === false) {
                continue;
            }
            // Trova e sostituisci i placeholder con HTML
            $html .= $this->processXmlForHtml($content, $data, $parser);
        }
        $zip->close();

        // Configura mPDF
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $config = [
            'mode' => 'utf-8',
            'tempDir' => sys_get_temp_dir(),
            'fontDir' => array_merge($fontDirs, [
                dirname(__DIR__) . '/fonts',
            ]),
            'fontdata' => $fontData + [
                'dejavusans' => [
                    'R' => 'DejaVuSans.ttf',
                    'B' => 'DejaVuSans-Bold.ttf',
                ],
            ],
            'default_font' => 'dejavusans',
        ];

        $mpdf = new Mpdf(array_merge($config, $this->mpdfOptions));

        // Wrappa l'HTML in un documento completo
        $fullHtml = '<html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>';
        $mpdf->WriteHTML($fullHtml);

        // Salva il PDF
        $mpdf->Output($pdfPath, 'F');

        return file_exists($pdfPath);
    }

    /**
     * Processa il contenuto XML e genera HTML con i placeholder sostituiti.
     *
     * @param string $xmlContent Contenuto XML.
     * @param array $data Dati.
     * @param \DocxPDF\Placeholder\PlaceholderParser $parser Parser.
     * @return string HTML generato.
     */
    private function processXmlForHtml(string $xmlContent, array $data, \DocxPDF\Placeholder\PlaceholderParser $parser): string
    {
        // Trova i placeholder nel contenuto XML
        $placeholders = $parser->parse($xmlContent, $data);

        // Sostituisci i placeholder con HTML
        foreach ($placeholders as $fullMatch => $placeholder) {
            $html = $placeholder->toHtmlString();
            $xmlContent = str_replace($fullMatch, $html, $xmlContent);
        }

        // Converti il testo rimanente in HTML semplice
        $text = strip_tags($xmlContent);
        $text = html_entity_decode($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (empty($text)) {
            return '';
        }

        return '<p>' . htmlspecialchars($text) . '</p>';
    }
}