<?php

declare(strict_types=1);

namespace DocxPDF;

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

        if ($pdfPath === null) {
            $pdfPath = preg_replace('/\.docx$/i', '.pdf', $docxPath);
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