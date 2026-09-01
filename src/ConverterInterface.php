<?php

declare(strict_types=1);

namespace durcap2011\DocxPDF;

/**
 * Interfaccia per i convertitori DOCX -> PDF.
 */
interface ConverterInterface
{
    /**
     * Converte un file DOCX in PDF.
     *
     * @param string $docxPath Percorso del file DOCX.
     * @param string $pdfPath Percorso di output del file PDF.
     * @param array $data Dati da sostituire ai placeholder.
     * @return bool True se la conversione è riuscita, false altrimenti.
     * @throws \Exception In caso di errore.
     */
    public function convert(string $docxPath, string $pdfPath, array $data): bool;
}