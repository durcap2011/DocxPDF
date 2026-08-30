<?php

declare(strict_types=1);

namespace DocxPDF\Tests\Placeholder;

use PHPUnit\Framework\TestCase;
use DocxPDF\Placeholder\TextPlaceholder;

class TextPlaceholderTest extends TestCase
{
    public function testGetType(): void
    {
        $placeholder = new TextPlaceholder('nome', 'Mario');
        $this->assertSame('text', $placeholder->getType());
    }

    public function testGetName(): void
    {
        $placeholder = new TextPlaceholder('nome', 'Mario');
        $this->assertSame('nome', $placeholder->getName());
    }

    public function testGetValue(): void
    {
        $placeholder = new TextPlaceholder('nome', 'Mario');
        $this->assertSame('Mario', $placeholder->getValue());
    }

    public function testToXmlStringSimple(): void
    {
        $placeholder = new TextPlaceholder('nome', 'Mario Rossi');
        $xml = $placeholder->toXmlString();

        $this->assertStringContainsString('<w:t>', $xml);
        $this->assertStringContainsString('Mario Rossi', $xml);
    }

    public function testToXmlStringEscapesHtml(): void
    {
        $placeholder = new TextPlaceholder('test', '<script>alert("xss")</script>');
        $xml = $placeholder->toXmlString();

        $this->assertStringNotContainsString('<script>', $xml);
        $this->assertStringContainsString('&lt;script&gt;', $xml);
    }

    public function testToHtmlStringSimple(): void
    {
        $placeholder = new TextPlaceholder('nome', 'Mario');
        $html = $placeholder->toHtmlString();

        $this->assertSame('Mario', $html);
    }

    public function testToHtmlStringEscapesHtml(): void
    {
        $placeholder = new TextPlaceholder('test', '<b>bold</b>');
        $html = $placeholder->toHtmlString();

        $this->assertStringNotContainsString('<b>', $html);
        $this->assertStringContainsString('&lt;b&gt;', $html);
    }

    public function testToXmlStringRichText(): void
    {
        $segments = [
            ['text' => 'Mario', 'bold' => true],
            ['text' => ' Rossi'],
        ];

        $placeholder = new TextPlaceholder('nome', $segments);
        $xml = $placeholder->toXmlString();

        $this->assertStringContainsString('<w:r>', $xml);
        $this->assertStringContainsString('<w:b/>', $xml);
        $this->assertStringContainsString('Mario', $xml);
        $this->assertStringContainsString('Rossi', $xml);
    }

    public function testToHtmlStringRichText(): void
    {
        $segments = [
            ['text' => 'Mario', 'bold' => true],
            ['text' => ' Rossi', 'italic' => true],
        ];

        $placeholder = new TextPlaceholder('nome', $segments);
        $html = $placeholder->toHtmlString();

        $this->assertStringContainsString('<b>', $html);
        $this->assertStringContainsString('Mario', $html);
        $this->assertStringContainsString('<i>', $html);
        $this->assertStringContainsString('Rossi', $html);
    }

    public function testToXmlStringNumeric(): void
    {
        $placeholder = new TextPlaceholder('eta', 30);
        $xml = $placeholder->toXmlString();

        $this->assertStringContainsString('30', $xml);
    }

    public function testToHtmlStringNumeric(): void
    {
        $placeholder = new TextPlaceholder('eta', 30);
        $html = $placeholder->toHtmlString();

        $this->assertSame('30', $html);
    }
}
