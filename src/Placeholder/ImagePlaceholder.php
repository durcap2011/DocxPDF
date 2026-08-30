<?php

declare(strict_types=1);

namespace DocxPDF\Placeholder;

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
     * {@inheritdoc}
     */
    public function toHtmlString(): string
    {
        $path = is_array($this->value) ? ($this->value['path'] ?? '') : (string)$this->value;
        $width = is_array($this->value) ? ($this->value['width'] ?? 100) : 100;
        $height = is_array($this->value) ? ($this->value['height'] ?? 100) : 100;

        // Se il percorso è un file locale, converti in base64
        if (file_exists($path)) {
            $imageData = file_get_contents($path);
            $base64 = base64_encode($imageData);
            $mime = mime_content_type($path);
            return sprintf(
                '<img src="data:%s;base64,%s" width="%d" height="%d" alt="Immagine">',
                $mime,
                $base64,
                $width,
                $height
            );
        }

        // Altrimenti, usa il percorso diretto
        return sprintf(
            '<img src="%s" width="%d" height="%d" alt="Immagine">',
            htmlspecialchars($path),
            $width,
            $height
        );
    }
}