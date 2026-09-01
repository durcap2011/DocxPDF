<?php

declare(strict_types=1);

namespace durcap2011\DocxPDF\Placeholder;

class TextPlaceholder extends AbstractPlaceholder
{
    public function getType(): string
    {
        return 'text';
    }

    public function toXmlString(): string
    {
        if (is_array($this->value) && RichTextSegment::isSegmentArray($this->value)) {
            return RichTextSegment::toXmlString($this->value);
        }
        return '<w:t>' . htmlspecialchars((string)$this->value) . '</w:t>';
    }

    public function toHtmlString(): string
    {
        if (is_array($this->value) && RichTextSegment::isSegmentArray($this->value)) {
            return RichTextSegment::toHtmlString($this->value);
        }
        return htmlspecialchars((string)$this->value);
    }
}
