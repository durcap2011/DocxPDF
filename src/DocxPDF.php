<?php

declare(strict_types=1);

namespace durcap2011\DocxPDF;

/**
 * Classe principale per la conversione DOCX -> PDF.
 */
class DocxPDF
{
    /**
     * @var ConverterInterface Convertitore da utilizzare.
     */
    private $converter;

    /**
     * Costruttore.
     *
     * @param ConverterInterface|null $converter Convertitore da utilizzare. Se non specificato, viene utilizzato LibreOffice.
     */
    public function __construct(?ConverterInterface $converter = null)
    {
        $this->converter = $converter ?? new LibreOfficeConverter();
    }

    /**
     * Converte un file DOCX in PDF.
     *
     * @param string $docxPath Percorso del file DOCX.
     * @param array $data Dati da sostituire ai placeholder.
     * @param string|null $pdfPath Percorso di output del file PDF. Se non specificato, viene usato lo stesso nome del template con estensione .pdf.
     * @return string Percorso del file PDF generato.
     * @throws \Exception In caso di errore.
     */
    public function convert(string $docxPath, array $data, ?string $pdfPath = null): string
    {
        if (!file_exists($docxPath)) {
            throw new \InvalidArgumentException("Il file DOCX non esiste: $docxPath");
        }

        // Validazione path traversal per il file DOCX
        $realDocxPath = realpath($docxPath);
        if ($realDocxPath === false) {
            throw new \InvalidArgumentException(
                "Percorso DOCX non valido o non accessibile: $docxPath"
            );
        }

        if ($pdfPath === null) {
            $pdfPath = preg_replace('/\.docx$/i', '.pdf', $docxPath);
        }

        // Validazione path traversal per il file PDF di output
        if (strpos($pdfPath, '..') !== false) {
            throw new \InvalidArgumentException(
                "Il percorso PDF contiene componenti di traversal: $pdfPath"
            );
        }

        // Valida che il percorso PDF non sia un URI pericoloso
        if (preg_match('#^(javascript|data|vbscript):#i', $pdfPath)) {
            throw new \InvalidArgumentException(
                "Il percorso PDF contiene uno schema non consentito: $pdfPath"
            );
        }

        // Valida che la directory di output sia scrivibile
        $outputDir = dirname($pdfPath);
        if (!is_dir($outputDir) && !is_writable(dirname($outputDir))) {
            throw new \InvalidArgumentException(
                "La directory di output non è scrivibile: $outputDir"
            );
        }

        $this->converter->convert($docxPath, $pdfPath, $data);
        return $pdfPath;
    }

    /**
     * Imposta il convertitore da utilizzare.
     *
     * @param ConverterInterface $converter
     * @return self
     */
    public function setConverter(ConverterInterface $converter): self
    {
        $this->converter = $converter;
        return $this;
    }
}