<?php

declare(strict_types=1);

namespace DocxPDF;

/**
 * Classe astratta per i convertitori DOCX -> PDF.
 * Fornisce metodi comuni per la manipolazione dei file DOCX.
 */
abstract class AbstractConverter implements ConverterInterface
{
    /**
     * Sostituisce i placeholder nel contenuto XML.
     *
     * @param string $xmlContent Contenuto XML.
     * @param array $data Dati da sostituire.
     * @return string Contenuto con i placeholder sostituiti.
     */
    protected function replacePlaceholders(string $xmlContent, array $data): string
    {
        $parser = new \DocxPDF\Placeholder\PlaceholderParser();
        $placeholders = $parser->parse($xmlContent, $data);

        foreach ($placeholders as $fullMatch => $placeholder) {
            if ($placeholder instanceof \DocxPDF\Placeholder\TextPlaceholder) {
                // Usa escapeXmlValue per una protezione più robusta
                $value = (string)$placeholder->getValue();
                $replacement = '<w:t xml:space="preserve">' . $this->escapeXmlValue($value) . '</w:t>';
                $xmlContent = str_replace($fullMatch, $replacement, $xmlContent);
            } else {
                $replacement = $placeholder->toXmlString();
                $paragraphPattern = '/<w:p\b[^>]*>(?:(?!<\/w:p>).)*' . preg_quote($fullMatch, '/') . '(?:(?!<\/w:p>).)*<\/w:p>/';
                if (preg_match($paragraphPattern, $xmlContent, $m)) {
                    $xmlContent = str_replace($m[0], $replacement, $xmlContent);
                } else {
                    $xmlContent = str_replace($fullMatch, $replacement, $xmlContent);
                }
            }
        }

        return $xmlContent;
    }

    /**
     * Estrae il contenuto XML da un file DOCX.
     *
     * @param string $docxPath Percorso del file DOCX.
     * @return array Lista di percorsi dei file XML contenenti testo.
     */
    protected function extractXmlFiles(string $docxPath): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($docxPath) !== true) {
            throw new \RuntimeException("Impossibile aprire il file DOCX: $docxPath");
        }

        $xmlFiles = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('#^(word/(document|header\d*|footer\d*|footnote|endnote)\.xml)$#', $name)) {
                $xmlFiles[] = $name;
            }
        }
        $zip->close();
        return $xmlFiles;
    }

    /**
     * Modifica un file DOCX sostituendo i placeholder nei file XML.
     *
     * @param string $docxPath Percorso del file DOCX originale.
     * @param string $outputPath Percorso del file DOCX modificato.
     * @param array $data Dati da sostituire.
     * @return bool True se l'operazione è riuscita.
     */
    protected function modifyDocx(string $docxPath, string $outputPath, array $data): bool
    {
        // Validazione dimensione file DOCX
        $docxSize = @filesize($docxPath);
        if ($docxSize === false || $docxSize > self::MAX_DOCX_SIZE) {
            throw new \InvalidArgumentException(
                'File DOCX troppo grande o dimensione non determinabile: ' . $docxPath
            );
        }

        // Copia l'originale nella destinazione di output
        if (!copy($docxPath, $outputPath)) {
            throw new \RuntimeException("Impossibile copiare il file DOCX: $docxPath");
        }

        // Apri la copia in modalità modifica (CREATE conserva i file esistenti)
        $zip = new \ZipArchive();
        if ($zip->open($outputPath, \ZipArchive::CREATE) !== true) {
            throw new \RuntimeException("Impossibile aprire il file DOCX: $outputPath");
        }

        // Controlli zip bomb: verifica numero massimo di file
        if ($zip->numFiles > self::MAX_ZIP_FILES) {
            $zip->close();
            @unlink($outputPath);
            throw new \InvalidArgumentException(
                'Il file DOCX contiene troppi file: ' . $zip->numFiles
            );
        }

        $xmlFiles = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('#^(word/(document|header\d*|footer\d*|footnote|endnote)\.xml)$#', $name)) {
                // Controlla dimensione singola entry
                $entrySize = $zip->locateName($name) !== false
                    ? $zip->getFromName($name, true)
                    : 0;

                // Usa getFromIndex per ottenere info dimensione
                $stat = $zip->statName($name);
                if ($stat !== false && $stat['size'] > self::MAX_ZIP_ENTRY_SIZE) {
                    $zip->close();
                    @unlink($outputPath);
                    throw new \InvalidArgumentException(
                        'File XML troppo grande nell\'archivio: ' . $name
                    );
                }

                $xmlFiles[] = $name;
            }
        }

        // Contatore per le relazioni immagine
        $this->imageCounter = 0;

        foreach ($xmlFiles as $xmlFile) {
            $content = $zip->getFromName($xmlFile);
            if ($content === false) {
                continue;
            }

            // Sanitizza le entità XML esterne (XXE)
            $content = $this->sanitizeXmlEntities($content);

            // Inserisci le immagini reali
            $content = $this->injectImages($zip, $xmlFile, $content, $data);

            $newContent = $this->replacePlaceholders($content, $data);
            $zip->deleteName($xmlFile);
            $zip->addFromString($xmlFile, $newContent);
        }

        // Ensure TableGrid style exists for tables with header repeat
        $this->ensureTableGridStyle($zip);

        $zip->close();
        return file_exists($outputPath);
    }

    /**
     * @var int Contatore per le relazioni immagine.
     */
    protected $imageCounter = 0;

    /**
     * Garantisce che lo stile TableGrid esista nello styles.xml del DOCX.
     * Lo stile serve per le tabelle generate con tblHeader che devono ripetere
     * l'header ad ogni cambio pagina.
     *
     * @param \ZipArchive $zip Archivio DOCX aperto in modifica.
     */
    protected function ensureTableGridStyle(\ZipArchive $zip): void
    {
        $stylesFile = 'word/styles.xml';
        $content = $zip->getFromName($stylesFile);

        if ($content === false) {
            // No styles.xml — create one with TableGrid
            $content = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
                . $this->getTableGridStyleXml()
                . '</w:styles>';
            $zip->addFromString($stylesFile, $content);
            return;
        }

        if (strpos($content, 'TableGrid') !== false) {
            return; // Already present
        }

        // Insert TableGrid before </w:styles>
        $styleXml = $this->getTableGridStyleXml();
        $content = str_replace('</w:styles>', $styleXml . '</w:styles>', $content);
        $zip->deleteName($stylesFile);
        $zip->addFromString($stylesFile, $content);
    }

    /**
     * Restituisce l'XML dello stile TableGrid per tabella.
     *
     * @return string
     */
    private function getTableGridStyleXml(): string
    {
        return '<w:style w:type="table" w:styleId="TableGrid">'
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
            . '</w:style>';
    }

    /**
     * Inserisce le immagini reali sostituendo i paragrafi che contengono placeholder immagine.
     *
     * @param \ZipArchive $zip Archivio aperto.
     * @param string $xmlFile Percorso del file XML (es. word/document.xml, word/header1.xml).
     * @param string $content Contenuto XML.
     * @param array $data Dati.
     * @return string Contenuto con le immagini inserite.
     */
    protected function injectImages(\ZipArchive $zip, string $xmlFile, string $content, array $data): string
    {
        // Percorso del file rels corrispondente
        $relFile = 'word/_rels/' . basename($xmlFile) . '.rels';

        // Trova i paragrafi che contengono placeholder immagine: {{immagine:nome}}
        $pattern = '/(<w:p\b[^>]*>((?:(?!<\/w:p>).)*?\{\{immagine:(\w+)\}\}.*?)<\/w:p>)/s';

        $content = preg_replace_callback($pattern, function ($m) use ($zip, $relFile, $data) {
            $paragraph = $m[1];
            $name = $m[3];

            if (!isset($data["immagine:$name"]) && !isset($data[$name])) {
                // Non c'è il dato: lascia il paragrafo così com'è (verrà gestito dal placeh. sostituzione)
                return $paragraph;
            }

            $value = $data["immagine:$name"] ?? $data[$name];
            $rawPath = is_array($value) ? ($value['path'] ?? '') : (string)$value;

            // Normalizza il percorso per prevenire path traversal
            $path = $this->sanitizePath($rawPath);

            // Validazione sicurezza: verifica che il file sia un'immagine valida
            try {
                // Verifica che il percorso non contenga traversal
                if (strpos($rawPath, '..') !== false || strpos($rawPath, "\0") !== false) {
                    return $paragraph;
                }
                $this->validateImagePath($path);
            } catch (\InvalidArgumentException $e) {
                // File non valido: lascia il paragrafo così com'è
                return $paragraph;
            }

            $width = is_array($value) ? ($value['width'] ?? 100) : 100;
            $height = is_array($value) ? ($value['height'] ?? 100) : 100;

            // Limita dimensioni massime per prevenire DoS via memory exhaustion
            $width = min((int)$width, self::MAX_IMAGE_DIMENSION);
            $height = min((int)$height, self::MAX_IMAGE_DIMENSION);

            // Determina estensione e tipo MIME
            $mime = mime_content_type($path);
            $ext = $this->getImageExtension($mime, $path);
            $ext = strtolower($ext);

            // Ridimensiona l'immagine alle dimensioni richieste (se possibile)
            // così LibreOffice la renderizza correttamente alla dimensione voluta.
            $imageBytes = $this->resizeImage($path, $width, $height, $ext);

            // Copia l'immagine in word/media/ usando un nome che non collide
            // con eventuali immagini già presenti nel template.
            $this->imageCounter++;
            $mediaName = $this->nextImageName($zip, $ext);
            $zip->addFromString($mediaName, $imageBytes);

            // Aggiunge la relazione nel file .rels
            $rid = $this->addImageRelationship($zip, $relFile, $mediaName);

            // Dimensioni in EMU. width/height sono intesi in pixel a 96 DPI
            // (1 pixel = 914400/96 = 9525 EMU; 1 inch = 914400 EMU)
            $emuWidth = (int)round($width * 9525);
            $emuHeight = (int)round($height * 9525);

            // Costruisce il blocco drawing
            $drawing = $this->buildDrawingXml($rid, $mediaName, $emuWidth, $emuHeight);

            return $drawing;
        }, $content);

        // Assicura che i namespace necessari siano dichiarati nel tag radice
        $content = $this->ensureNamespaces($content);

        return $content;
    }

    /**
     * Garantisce che i namespace XML usati dai disegni siano dichiarati.
     *
     * I namespace vengono aggiunti solo al tag radice del file, verificando
     * che non siano già dichiarati LÌ (una dichiarazione locale su un
     * elemento interno non vale per il documento).
     *
     * @param string $xmlContent Contenuto XML.
     * @return string Contenuto con i namespace garantiti.
     */
    protected function ensureNamespaces(string $xmlContent): string
    {
        $namespaces = [
            'pic' => 'http://schemas.openxmlformats.org/drawingml/2006/picture',
            'wp' => 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing',
            'a' => 'http://schemas.openxmlformats.org/drawingml/2006/main',
            'mc' => 'http://schemas.openxmlformats.org/markup-compatibility/2006',
            'v' => 'urn:schemas-microsoft-com:vml',
            'o' => 'urn:schemas-microsoft-com:office:office',
            'r' => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships',
        ];

        // Estrae il tag radice (w:document, w:hdr, w:ftr, ...)
        if (!preg_match('#<w:(?:document|hdr|ftr|footnotes|endnotes|numbering|comments)\b[^>]*>#', $xmlContent, $m)) {
            return $xmlContent;
        }
        $rootTag = $m[0];

        foreach ($namespaces as $prefix => $uri) {
            // Aggiungi il namespace solo se è usato nel file e NON è
            // dichiarato sul tag radice.
            if (strpos($xmlContent, $prefix . ':') !== false
                && strpos($rootTag, 'xmlns:' . $prefix . '="') === false) {
                $rootTag = preg_replace(
                    '/>\s*$/',
                    ' xmlns:' . $prefix . '="' . $uri . '">',
                    $rootTag
                );
            }
        }

        $count = 0;
        return str_replace($m[0], $rootTag, $xmlContent, $count);
    }

    /**
     * MIME types consentiti per le immagini.
     */
    private const ALLOWED_IMAGE_MIMES = [
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/bmp',
        'image/webp',
        'image/tiff',
    ];

    /**
     * Estensioni file consentite per le immagini.
     */
    private const ALLOWED_IMAGE_EXTENSIONS = [
        'png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp', 'tiff', 'tif',
    ];

    /**
     * Dimensione massima consentita per un'immagine (5 MB).
     */
    private const MAX_IMAGE_SIZE = 5 * 1024 * 1024;

    /**
     * Dimensione massima consentita per un file DOCX (50 MB).
     */
    private const MAX_DOCX_SIZE = 50 * 1024 * 1024;

    /**
     * Numero massimo di file consentiti in un archivio ZIP.
     */
    private const MAX_ZIP_FILES = 500;

    /**
     * Dimensione massima consentita per un singolo file XML all'interno dello ZIP (10 MB).
     */
    private const MAX_ZIP_ENTRY_SIZE = 10 * 1024 * 1024;

    /**
     * Dimensione massima consentita per la larghezza/altezza di un'immagine in pixel.
     */
    private const MAX_IMAGE_DIMENSION = 10000;

    /**
     * Mappatura estensione -> MIME types consentiti.
     */
    private const EXTENSION_TO_MIME = [
        'png' => ['image/png'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'gif' => ['image/gif'],
        'bmp' => ['image/bmp'],
        'webp' => ['image/webp'],
        'tiff' => ['image/tiff', 'image/tif'],
        'tif' => ['image/tiff', 'image/tif'],
    ];

    /**
     * Valida che un file sia un'immagine sicura da elaborare.
     *
     * @param string $path Percorso del file immagine.
     * @return true Se valido.
     * @throws \InvalidArgumentException Se il file non è un'immagine valida.
     */
    protected function validateImagePath(string $path): true
    {
        if (!is_string($path) || $path === '') {
            throw new \InvalidArgumentException('Percorso immagine non valido.');
        }

        if (!file_exists($path) || !is_file($path)) {
            throw new \InvalidArgumentException("File immagine non trovato: $path");
        }

        if (!is_readable($path)) {
            throw new \InvalidArgumentException("File immagine non leggibile: $path");
        }

        // Protezione path traversal: verifica con realpath
        $realPath = realpath($path);
        if ($realPath === false) {
            throw new \InvalidArgumentException(
                'Percorso immagine non risolvibile: ' . $path
            );
        }

        // Verifica che il percorso non contenga traversal
        if (strpos($path, '..') !== false) {
            throw new \InvalidArgumentException(
                'Percorso immagine contiene componenti di traversal: ' . $path
            );
        }

        // Controllo dimensione massima
        $size = filesize($path);
        if ($size === false || $size > self::MAX_IMAGE_SIZE) {
            throw new \InvalidArgumentException(
                'File immagine troppo grande o dimensione non determinabile: ' . $path
            );
        }

        // Validazione estensione file (whitelist)
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === '' || !in_array($ext, self::ALLOWED_IMAGE_EXTENSIONS, true)) {
            throw new \InvalidArgumentException(
                'Estensione immagine non consentita: ' . $ext
            );
        }

        // Validazione MIME type (controllo contenuto reale)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($path);
        if ($realMime === false || !in_array($realMime, self::ALLOWED_IMAGE_MIMES, true)) {
            throw new \InvalidArgumentException(
                'Tipo MIME immagine non consentito: ' . $realMime
            );
        }

        // Validazione croce: verifica che il MIME corrisponda all'estensione
        if (isset(self::EXTENSION_TO_MIME[$ext])) {
            $allowedMimes = self::EXTENSION_TO_MIME[$ext];
            if (!in_array($realMime, $allowedMimes, true)) {
                throw new \InvalidArgumentException(
                    "Il tipo MIME '$realMime' non corrisponde all'estensione '$ext'"
                );
            }
        }

        // Verifica che non sia un file nascosto (inizia con .)
        $basename = basename($path);
        if ($basename[0] === '.') {
            throw new \InvalidArgumentException(
                'File immagine nascosto non consentito: ' . $path
            );
        }

        return true;
    }

    /**
     * Determina l'estensione del file immagine.
     *
     * @param string $mime Tipo MIME.
     * @param string $path Percorso del file.
     * @return string Estensione.
     */
    protected function getImageExtension(string $mime, string $path): string
    {
        $map = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/gif' => 'gif',
            'image/bmp' => 'bmp',
            'image/webp' => 'webp',
            'image/tiff' => 'tiff',
            'image/svg+xml' => 'svg',
        ];

        if (isset($map[$mime])) {
            return $map[$mime];
        }

        // Fallback dall'estensione del file
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        return $ext !== '' ? $ext : 'png';
    }

    /**
     * Ridimensiona (o ricodifica) l'immagine alle dimensioni pixel richieste.
     *
     * LibreOffice dimensiona le immagini in base alle dimensioni pixel native nel
     * DOCX, ignorando spesso il <wp:extent>. Ricampionando l'immagine alle
     * dimensioni richieste si garantisce che il PDF finale abbia la dimensione
     * corretta. Se GD non è disponibile, restituisce i byte originali.
     *
     * @param string $path Percorso immagine sorgente.
     * @param int $width Larghezza target in pixel.
     * @param int $height Altezza target in pixel.
     * @param string $ext Estensione immagine (jpg|png|gif...).
     * @return string Byte dell'immagine ridimensionata (o originale).
     */
    protected function resizeImage(string $path, int $width, int $height, string $ext): string
    {
        // Validazione sicurezza: verifica che il file sia un'immagine valida
        $this->validateImagePath($path);

        if (!function_exists('imagecreatetruecolor')) {
            return file_get_contents($path);
        }

        $create = [
            'jpg'  => 'imagecreatefromjpeg',
            'jpeg' => 'imagecreatefromjpeg',
            'png'  => 'imagecreatefrompng',
            'gif'  => 'imagecreatefromgif',
            'webp' => 'imagecreatefromwebp',
            'bmp'  => 'imagecreatefrombmp',
        ];

        $loader = $create[strtolower($ext)] ?? null;
        if ($loader === null || !function_exists($loader)) {
            return file_get_contents($path);
        }

        $src = @$loader($path);
        if ($src === false) {
            return file_get_contents($path);
        }

        $srcW = imagesx($src);
        $srcH = imagesy($src);

        // Se l'immagine ha già la dimensione richiesta, restituiscila invariata
        if ($srcW === $width && $srcH === $height) {
            imagedestroy($src);
            return file_get_contents($path);
        }

        $dst = imagecreatetruecolor($width, $height);
        if ($dst === false) {
            imagedestroy($src);
            return file_get_contents($path);
        }

        // Mantieni la trasparenza per PNG/GIF
        if (in_array(strtolower($ext), ['png', 'gif'], true)) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefill($dst, 0, 0, $transparent);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, $srcW, $srcH);

        ob_start();
        $saved = false;
        switch (strtolower($ext)) {
            case 'jpg':
            case 'jpeg':
                $saved = imagejpeg($dst, null, 90);
                break;
            case 'gif':
                $saved = imagegif($dst);
                break;
            case 'webp':
                $saved = imagewebp($dst, null, 90);
                break;
            case 'png':
            default:
                $saved = imagepng($dst, null, 9);
                break;
        }
        $bytes = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        return ($saved !== false) ? $bytes : file_get_contents($path);
    }

    /**
     * Normalizza un percorso rimuovendo componenti di traversal.
     *
     * @param string $path Percorso da normalizzare.
     * @return string Percorso normalizzato.
     */
    protected function sanitizePath(string $path): string
    {
        // Rimuovi caratteri null byte
        $path = str_replace("\0", '', $path);

        // Converti backslash in forward slash per uniformità
        $path = str_replace('\\', '/', $path);

        // Rimuovi doppie barre (ma mantieni:// protocolli)
        $path = preg_replace('#/+#', '/', $path);

        // Rimuovi componenti . e .. dal percorso
        $parts = explode('/', $path);
        $clean = [];
        foreach ($parts as $part) {
            if ($part === '.' || $part === '..') {
                continue;
            }
            $clean[] = $part;
        }

        return implode('/', $clean);
    }

    /**
     * Rimuove le entità XML esterne (XXE) dal contenuto XML.
     * Previene attacchi XXE rimuovendo DOCTYPE e dichiarazioni di entità esterne.
     *
     * @param string $xmlContent Contenuto XML.
     * @return string Contenuto XML sanitizzato.
     */
    protected function sanitizeXmlEntities(string $xmlContent): string
    {
        // Rimuovi DOCTYPE che dichiarano entità esterne
        // Pattern: <!DOCTYPE ... SYSTEM "..." ... >
        $xmlContent = preg_replace(
            '/<!DOCTYPE[^>]*>/is',
            '',
            $xmlContent
        );

        // Rimuovi dichiarazioni di entità esterne
        // Pattern: <!ENTITY ... SYSTEM "..." ... >
        $xmlContent = preg_replace(
            '/<!ENTITY[^>]*>/is',
            '',
            $xmlContent
        );

        // Rimuovi entità parametriche esterne
        // Pattern: <!ENTITY % ... SYSTEM "..." ... >
        $xmlContent = preg_replace(
            '/<!ENTITY\s+%[^>]*>/is',
            '',
            $xmlContent
        );

        // Rimuovi riferimenti a entità non definite
        // (mantieni solo le entità standard XML)
        $xmlContent = preg_replace(
            '/&(?!amp;|lt;|gt;|quot;|apos;|#\d+;|#x[0-9a-fA-F]+;)/',
            '&amp;',
            $xmlContent
        );

        return $xmlContent;
    }

    /**
     * Escapa un valore per uso sicuro in contesto XML.
     * Più robusto di htmlspecialchars per l'XML.
     *
     * @param string $value Valore da escapppare.
     * @return string Valore escapppato.
     */
    protected function escapeXmlValue(string $value): string
    {
        // Prima di tutto, escapppa i caratteri XML base
        $value = str_replace(
            ['&', '<', '>', "'", '"'],
            ['&amp;', '&lt;', '&gt;', '&apos;', '&quot;'],
            $value
        );

        // Rimuovi caratteri di controllo non consentiti in XML
        // (consenti tab, newline, carriage return)
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value);

        // Proteggi contro injection di CDATA
        $value = str_replace(']]>', ']]&gt;', $value);

        // Proteggi contro injection di commenti XML
        $value = str_replace('--', '-&#45;', $value);

        return $value;
    }

    /**
     * Determina un nome file media (word/media/imageN.ext) che non sia già
     * presente nell'archivio, per evitare di sovrascrivere immagini esistenti.
     *
     * @param \ZipArchive $zip Archivio aperto.
     * @param string $ext Estensione immagine.
     * @return string Nome file media univoco.
     */
    protected function nextImageName(\ZipArchive $zip, string $ext): string
    {
        $max = $this->imageCounter;
        if (preg_match_all('#word/media/image(\d+)\.#', $this->getZipFileNames($zip), $m)) {
            foreach ($m[1] as $num) {
                $max = max($max, (int)$num);
            }
        }

        // Riparti da un numero sicuramente inesistente
        $num = $max + 1;
        while ($zip->locateName("word/media/image$num.$ext") !== false) {
            $num++;
        }
        return "word/media/image$num.$ext";
    }

    /**
     * Restituisce la lista dei nomi di file contenuti nell'archivio.
     *
     * @param \ZipArchive $zip Archivio aperto.
     * @return string Lista dei nomi separati da newline.
     */
    protected function getZipFileNames(\ZipArchive $zip): string
    {
        $names = '';
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names .= $zip->getNameIndex($i) . "\n";
        }
        return $names;
    }

    /**
     * Calcola il target RELATIVO alla cartella della parte sorgente.
     *
     * Nel package OPC i X target delle relazioni sono risolti rispetto alla
     * cartella della parte. Il file rels word/_rels/header1.xml.rels descrive
     * la parte word/header1.xml (cartella word/), quindi un'immagine
     * word/media/image2.png ha target "media/image2.png".
     *
     * @param string $relFile Percorso del file .rels (word/_rels/xxx.xml.rels).
     * @param string $mediaName Percorso da archiviato dell'immagine (word/media/...).
     * @return string Target relativo.
     */
    protected function getRelativeTarget(string $relFile, string $mediaName): string
    {
        // La cartella della parte è la directory del file descritto dal .rels
        // (word/_rels/header1.xml.rels -> word/)
        $partDir = dirname(str_replace('_rels/', '', $relFile));
        $partDir = rtrim($partDir, '/\\') . '/';

        $relPath = '';
        $mediaDir = dirname($mediaName) . '/';
        $mediaBase = basename($mediaName);

        if (strpos($mediaDir, $partDir) === 0) {
            // Percorso normale: sottraggo la cartella della parte
            $relPath = substr($mediaDir, strlen($partDir)) . $mediaBase;
        } else {
            // Percorso alternativo: risali con "../"
            $relPath = $mediaName;
        }

        return str_replace('\\', '/', $relPath);
    }

    /**
     * Aggiunge una relazione immagine al file .rels e restituisce il nuovo rId.
     *
     * @param \ZipArchive $zip Archivio aperto.
     * @param string $relFile Percorso del file .rels (word/_rels/xxx.xml.rels).
     * @param string $mediaName Percorso da archiviato dell'immagine (word/media/...).
     * @return string Il nuovo rId.
     */
    protected function addImageRelationship(\ZipArchive $zip, string $relFile, string $mediaName): string
    {
        $relsContent = $zip->getFromName($relFile);
        if ($relsContent === false) {
            $relsContent = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>';
        }

        // Trova il prossimo rId disponibile
        $maxNum = 0;
        if (preg_match_all('/<Relationship[^>]*\sId="rId(\d+)"[^>]*>/', $relsContent, $relMatches)) {
            foreach ($relMatches[1] as $num) {
                $maxNum = max($maxNum, (int)$num);
            }
        }
        $newNum = $maxNum + 1;
        $rid = 'rId' . $newNum;

        // Il Target della relazione è RELATIVO alla cartella della parte
        // (word/ per document.xml, header1.xml, footer1.xml...). Es. una
        // immagine in word/media/image2.png ha target "media/image2.png".
        // Un target "word/media/image2.png" punterebbe a word/word/media/...
        // e LibreOffice scarterebbe l'immagine silenziosamente.
        $target = $this->getRelativeTarget($relFile, $mediaName);

        $relationship = '<Relationship Id="' . $rid . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="' . $target . '" />';

        // Inserisci la relazione prima della chiusura del tag Relationships
        $relsContent = preg_replace('#(</Relationships>)#', $relationship . '$1', $relsContent, 1);

        // Aggiorna il file rels (se non esistente, aggiungilo)
        if ($zip->locateName($relFile) !== false) {
            $zip->deleteName($relFile);
        }
        $zip->addFromString($relFile, $relsContent);

        return $rid;
    }

    /**
     * Costruisce il blocco XML <w:drawing> per inserire un'immagine.
     *
     * @param string $rid Relazione immagine.
     * @param string $mediaName Percorso del file immagine.
     * @param int $emuWidth Larghezza in EMU.
     * @param int $emuHeight Altezza in EMU.
     * @return string XML del paragrafo.
     */
    protected function buildDrawingXml(string $rid, string $mediaName, int $emuWidth, int $emuHeight): string
    {
        // Id unici per i disegni: partono da un valore alto per non collidere
        // con i wp:docPr id già presenti nel template (es. id="1" di Word).
        // In caso di collisione LibreOffice scarta silenziosamente il disegno.
        $docId = 1000000 + $this->imageCounter;

        return '<w:p><w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0">'
            . '<wp:extent cx="' . $emuWidth . '" cy="' . $emuHeight . '"/>'
            . '<wp:effectExtent l="0" t="0" r="0" b="0"/>'
            . '<wp:docPr id="' . $docId . '" name="image' . $docId . '"/>'
            . '<wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr>'
            . '<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            . '<pic:pic><pic:nvPicPr><pic:cNvPr id="' . $docId . '" name="' . basename($mediaName) . '"/>'
            . '<pic:cNvPicPr><a:picLocks noChangeAspect="1"/></pic:cNvPicPr><pic:nvPr/></pic:nvPicPr>'
            . '<pic:blipFill rotWithShape="1"><a:blip r:embed="' . $rid . '"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
            . '<pic:spPr bwMode="auto"><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $emuWidth . '" cy="' . $emuHeight . '"/></a:xfrm>'
            . '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
            . '</pic:pic></a:graphicData></a:graphic>'
            . '</wp:inline></w:drawing></w:r></w:p>';
    }
}