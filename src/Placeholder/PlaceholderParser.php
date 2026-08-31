<?php

declare(strict_types=1);

namespace DocxPDF\Placeholder;

/**
 * Parser per i placeholder nel contenuto XML.
 */
class PlaceholderParser
{
    /**
     * Analizza il contenuto XML e restituisce i placeholder trovati.
     *
     * @param string $xmlContent Contenuto XML.
     * @param array $data Dati disponibili.
     * @return PlaceholderInterface[] Lista di placeholder.
     */
    public function parse(string $xmlContent, array $data): array
    {
        $placeholders = [];
        $pattern = '/\{\{(\w+)(?::(\w+))?\}\}/';

        preg_match_all($pattern, $xmlContent, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $fullMatch = $match[0];
            $type = $match[1];
            $name = $match[2] ?? null;

            $varName = $name ?? $type;
            $placeholderType = $name !== null ? $type : null;

            $fullKey = $name !== null ? $type . ':' . $name : null;
            if ($fullKey !== null && isset($data[$fullKey])) {
                $value = $data[$fullKey];
            } elseif (isset($data[$varName])) {
                $value = $data[$varName];
            } else {
                continue;
            }

            if ($placeholderType !== null) {
                $placeholder = $this->createPlaceholderByType($varName, $value, $placeholderType);
            } else {
                $placeholder = $this->createPlaceholderByValue($varName, $value);
            }

            if ($placeholder !== null) {
                $placeholders[$fullMatch] = $placeholder;
            }
        }

        return $placeholders;
    }

    /**
     * Crea un placeholder in base al tipo specificato.
     *
     * @param string $name Nome del placeholder.
     * @param mixed $value Valore.
     * @param string $type Tipo specificato.
     * @return PlaceholderInterface|null
     */
    private function createPlaceholderByType(string $name, $value, string $type): ?PlaceholderInterface
    {
        switch (strtolower($type)) {
            case 'text':
            case 'testo':
                return new TextPlaceholder($name, $value);
            case 'table':
            case 'tabella':
                return new TablePlaceholder($name, $value);
            case 'image':
            case 'immagine':
                return new ImagePlaceholder($name, $value);
            case 'list':
            case 'lista':
                return new ListPlaceholder($name, $value, false);
            case 'ordered_list':
            case 'lista_numerata':
                return new ListPlaceholder($name, $value, true);
            default:
                return new TextPlaceholder($name, $value);
        }
    }

    /**
     * Crea un placeholder deducendo il tipo dal valore.
     *
     * @param string $name Nome del placeholder.
     * @param mixed $value Valore.
     * @return PlaceholderInterface|null
     */
    private function createPlaceholderByValue(string $name, $value): ?PlaceholderInterface
    {
        if (is_string($value)) {
            return new TextPlaceholder($name, $value);
        }

        if (is_array($value)) {
            // Controlla se ha la chiave 'path' (immagine) — va prima di tutto
            if (isset($value['path'])) {
                return new ImagePlaceholder($name, $value);
            }

            // Controlla se è un array di segmenti rich text (elementi con chiave 'text')
            // Va prima del check tabella, perché anche i segmenti rich text sono array di array
            if (RichTextSegment::isSegmentArray($value)) {
                return new TextPlaceholder($name, $value);
            }

            // Controlla se è un array di array (tabella)
            if (!empty($value) && is_array($value[0])) {
                return new TablePlaceholder($name, $value);
            }

            // Altrimenti, è una lista
            return new ListPlaceholder($name, $value);
        }

        // Per altri tipi, converti in testo
        return new TextPlaceholder($name, $value);
    }
}