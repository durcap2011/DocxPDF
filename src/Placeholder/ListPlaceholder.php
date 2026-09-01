<?php

declare(strict_types=1);

namespace durcap2011\DocxPDF\Placeholder;

class ListPlaceholder extends AbstractPlaceholder
{
    private $ordered;

    public function __construct(string $name, $value, bool $ordered = false)
    {
        parent::__construct($name, $value);
        $this->ordered = $ordered;
    }

    public function getType(): string
    {
        return $this->ordered ? 'ordered_list' : 'unordered_list';
    }

    public function toXmlString(): string
    {
        if (!is_array($this->value) || empty($this->value)) {
            return '';
        }

        $xml = '';
        $counter = 0;
        foreach ($this->value as $item) {
            $counter++;
            $prefix = $this->ordered ? "$counter. " : "• ";

            $xml .= '<w:p>';
            $xml .= '<w:pPr>';
            $xml .= '<w:ind w:left="720" w:hanging="360"/>';
            $xml .= '</w:pPr>';

            $xml .= '<w:r>';
            $xml .= '<w:t xml:space="preserve">' . htmlspecialchars($prefix) . '</w:t>';
            $xml .= '</w:r>';

            $segments = self::normalizeItem($item);
            if ($segments !== null) {
                $xml .= RichTextSegment::toXmlString($segments);
            } else {
                $xml .= '<w:r>';
                $xml .= '<w:t xml:space="preserve">' . htmlspecialchars((string)$item) . '</w:t>';
                $xml .= '</w:r>';
            }

            $xml .= '</w:p>';
        }

        return $xml;
    }

    public function toHtmlString(): string
    {
        if (!is_array($this->value) || empty($this->value)) {
            return '';
        }

        $tag = $this->ordered ? 'ol' : 'ul';
        $html = "<$tag>";
        foreach ($this->value as $item) {
            $segments = self::normalizeItem($item);
            if ($segments !== null) {
                $html .= '<li>' . RichTextSegment::toHtmlString($segments) . '</li>';
            } else {
                $html .= '<li>' . htmlspecialchars((string)$item) . '</li>';
            }
        }
        $html .= "</$tag>";
        return $html;
    }

    private static function normalizeItem($item): ?array
    {
        if (!is_array($item)) {
            return null;
        }

        if (isset($item['text'])) {
            return [$item];
        }

        if (isset($item[0]) && is_array($item[0]) && isset($item[0]['text'])) {
            return $item;
        }

        return null;
    }
}
