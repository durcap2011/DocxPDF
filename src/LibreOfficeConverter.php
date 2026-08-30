<?php

declare(strict_types=1);

namespace DocxPDF;

/**
 * Convertitore che utilizza LibreOffice per generare il PDF.
 */
class LibreOfficeConverter extends AbstractConverter
{
    /**
     * @var string|null Percorso all'eseguibile di LibreOffice.
     */
    private $libreOfficePath;

    /**
     * Costruttore.
     *
     * @param string|null $libreOfficePath Percorso all'eseguibile di LibreOffice. Se non specificato, cerca automaticamente.
     */
    public function __construct(?string $libreOfficePath = null)
    {
        $this->libreOfficePath = $libreOfficePath ?? $this->findLibreOffice();
    }

    /**
     * {@inheritdoc}
     */
    public function convert(string $docxPath, string $pdfPath, array $data): bool
    {
        // Crea un file temporaneo con i placeholder sostituiti
        $tempDocx = tempnam(sys_get_temp_dir(), 'docx_pdf_') . '.docx';
        try {
            $this->modifyDocx($docxPath, $tempDocx, $data);

            // Esegui LibreOffice per la conversione
            $command = sprintf(
                '"%s" --headless --convert-to pdf --outdir "%s" "%s"',
                $this->libreOfficePath,
                dirname($pdfPath),
                $tempDocx
            );

            $output = [];
            $returnCode = 0;
            exec($command . ' 2>&1', $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \RuntimeException(
                    "Errore durante la conversione con LibreOffice. Codice: $returnCode. Output: " . implode("\n", $output)
                );
            }

            // LibreOffice genera il PDF nell'outdir con lo stesso nome base del file di input
            $generatedBase = basename(preg_replace('/\.docx$/i', '.pdf', $tempDocx));
            $generatedPdf = dirname($pdfPath) . DIRECTORY_SEPARATOR . $generatedBase;
            if (!file_exists($generatedPdf)) {
                throw new \RuntimeException("Il file PDF non è stato generato da LibreOffice. Cercato: $generatedPdf");
            }

            // Rinomina il file generato nel percorso desiderato
            if ($generatedPdf !== $pdfPath) {
                $target = str_replace('/', DIRECTORY_SEPARATOR, $pdfPath);
                $generated = str_replace('/', DIRECTORY_SEPARATOR, $generatedPdf);

                // Su Windows il file destinazione deve essere rimosso prima del rename
                if (file_exists($target)) {
                    @unlink($target);
                }
                if (!rename($generated, $target)) {
                    // Fallback: copia il file se il rename fallisce
                    if (!copy($generated, $target)) {
                        throw new \RuntimeException("Impossibile rinominare il PDF generato in: $pdfPath");
                    }
                    @unlink($generated);
                }
            }

            return true;
        } finally {
            // Pulisci il file temporaneo
            if (file_exists($tempDocx)) {
                unlink($tempDocx);
            }
        }
    }

    /**
     * Cerca automaticamente l'eseguibile di LibreOffice.
     *
     * @return string Percorso trovato.
     * @throws \RuntimeException Se non trova LibreOffice.
     */
    private function findLibreOffice(): string
    {
        $possiblePaths = [
            // Windows
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
            // Linux
            '/usr/bin/libreoffice',
            '/usr/bin/soffice',
            '/usr/local/bin/libreoffice',
            // macOS
            '/Applications/LibreOffice.app/Contents/MacOS/soffice',
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Prova a cercare nel PATH
        $output = [];
        $returnCode = 0;
        exec('where soffice 2>&1', $output, $returnCode);
        if ($returnCode === 0 && !empty($output)) {
            return trim($output[0]);
        }

        exec('which libreoffice 2>&1', $output, $returnCode);
        if ($returnCode === 0 && !empty($output)) {
            return trim($output[0]);
        }

        throw new \RuntimeException(
            "LibreOffice non trovato. Installalo o specifica il percorso nel costruttore."
        );
    }
}