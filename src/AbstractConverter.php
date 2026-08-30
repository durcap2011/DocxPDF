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
                $replacement = htmlspecialchars((string)$placeholder->getValue());
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
        // Copia l'originale nella destinazione di output
        if (!copy($docxPath, $outputPath)) {
            throw new \RuntimeException("Impossibile copiare il file DOCX: $docxPath");
        }

        // Apri la copia in modalità modifica (CREATE conserva i file esistenti)
        $zip = new \ZipArchive();
        if ($zip->open($outputPath, \ZipArchive::CREATE) !== true) {
            throw new \RuntimeException("Impossibile aprire il file DOCX: $outputPath");
        }

        $xmlFiles = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('#^(word/(document|header\d*|footer\d*|footnote|endnote)\.xml)$#', $name)) {
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
            $path = is_array($value) ? ($value['path'] ?? '') : (string)$value;

            if (!is_string($path) || $path === '' || !file_exists($path)) {
                return $paragraph;
            }

            $width = is_array($value) ? ($value['width'] ?? 100) : 100;
            $height = is_array($value) ? ($value['height'] ?? 100) : 100;

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