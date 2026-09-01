<?php

declare(strict_types=1);

namespace durcap2011\DocxPDF\Placeholder;

/**
 * Placeholder per immagini.
 * Il valore deve essere il percorso del file immagine o un array con 'path', 'width', 'height'.
 */
class ImagePlaceholder extends AbstractPlaceholder
{
    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'image';
    }

    /**
     * {@inheritdoc}
     */
    public function toXmlString(): string
    {
        // Per ora, restituiamo un testo descrittivo.
        // In futuro si potrà implementare l'inserimento effettivo dell'immagine.
        $path = is_array($this->value) ? ($this->value['path'] ?? '') : (string)$this->value;
        return '<w:t>[Immagine: ' . htmlspecialchars($path) . ']</w:t>';
    }

    /**
     * MIME types consentiti per le immagini (whitelist).
     */
    private const ALLOWED_MIME_TYPES = [
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/bmp',
        'image/webp',
        'image/tiff',
    ];

    /**
     * Dimensione massima per l'encoding base64 (2 MB).
     */
    private const MAX_BASE64_SIZE = 2 * 1024 * 1024;

    /**
     * Percorsi protocolli consentiti per URL immagini esterne.
     */
    private const ALLOWED_URL_SCHEMES = ['http://', 'https://'];

    /**
     * Valida e sanitizza un percorso/URL immagine.
     *
     * @param string $path Percorso da validare.
     * @return string|null Percorso validato o null se non valido.
     */
    private static function sanitizeImagePath(string $path): ?string
    {
        $path = trim($path);

        if ($path === '') {
            return null;
        }

        // Rimuovi caratteri null byte
        $path = str_replace("\0", '', $path);

        // Se è un URL, accetta solo http/https
        if (preg_match('#^https?://#i', $path)) {
            // Valida che l'URL non contenga caratteri pericolosi
            if (filter_var($path, FILTER_VALIDATE_URL) !== false) {
                return $path;
            }
            return null;
        }

        // Se è un percorso locale, verifica che non contenga traversal
        if (strpos($path, '..') !== false) {
            return null;
        }

        // Verifica che non sia un URI javascript: o data:
        if (preg_match('#^(javascript|data|vbscript):#i', $path)) {
            return null;
        }

        return $path;
    }

    /**
     * {@inheritdoc}
     */
    public function toHtmlString(): string
    {
        $path = is_array($this->value) ? ($this->value['path'] ?? '') : (string)$this->value;
        $width = is_array($this->value) ? ($this->value['width'] ?? 100) : 100;
        $height = is_array($this->value) ? ($this->value['height'] ?? 100) : 100;

        // Validazione dimensioni
        $width = filter_var($width, FILTER_VALIDATE_INT) ?: 100;
        $height = filter_var($height, FILTER_VALIDATE_INT) ?: 100;

        // Limita dimensioni massime per prevenire DoS
        $width = min($width, 10000);
        $height = min($height, 10000);

        // Sanitizza il percorso
        $safePath = self::sanitizeImagePath($path);
        if ($safePath === null) {
            return '<img src="" width="' . $width . '" height="' . $height . '" alt="Immagine non disponibile">';
        }

        // Se il percorso è un file locale, converti in base64
        if (file_exists($safePath)) {
            // Validazione sicurezza: verifica dimensione file
            $fileSize = filesize($safePath);
            if ($fileSize === false || $fileSize > self::MAX_BASE64_SIZE) {
                return '<img src="" width="' . $width . '" height="' . $height . '" alt="Immagine non disponibile">';
            }

            // Validazione MIME type
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($safePath);
            if ($mime === false || !in_array($mime, self::ALLOWED_MIME_TYPES, true)) {
                return '<img src="" width="' . $width . '" height="' . $height . '" alt="Immagine non disponibile">';
            }

            $imageData = file_get_contents($safePath);
            if ($imageData === false) {
                return '<img src="" width="' . $width . '" height="' . $height . '" alt="Immagine non disponibile">';
            }

            $base64 = base64_encode($imageData);

            // Sanitizza il MIME type per prevenire injection
            $mime = preg_replace('/[^a-z\/\-]/i', '', $mime);

            return sprintf(
                '<img src="data:%s;base64,%s" width="%d" height="%d" alt="Immagine">',
                $mime,
                $base64,
                $width,
                $height
            );
        }

        // Se è un URL esterno (http/https), usalo direttamente
        if (preg_match('#^https?://#i', $safePath)) {
            return sprintf(
                '<img src="%s" width="%d" height="%d" alt="Immagine">',
                htmlspecialchars($safePath, ENT_QUOTES),
                $width,
                $height
            );
        }

        // Altrimenti (percorso locale non trovato), mostra placeholder
        return '<img src="" width="' . $width . '" height="' . $height . '" alt="Immagine non disponibile">';
    }
}