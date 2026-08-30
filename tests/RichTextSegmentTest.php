<?php

declare(strict_types=1);

namespace DocxPDF\Tests\Placeholder;

use PHPUnit\Framework\TestCase;
use DocxPDF\Placeholder\RichTextSegment;

class RichTextSegmentTest extends TestCase
{
    public function testIsSegmentArray(): void
    {
        $this->assertTrue(RichTextSegment::isSegmentArray([
            ['text' => 'ciao'],
        ]));

        $this->assertTrue(RichTextSegment::isSegmentArray([
            ['text' => 'a', 'bold' => true],
            ['text' => 'b'],
        ]));

        $this->assertFalse(RichTextSegment::isSegmentArray('stringa'));
        $this->assertFalse(RichTextSegment::isSegmentArray([1, 2, 3]));
        $this->assertFalse(RichTextSegment::isSegmentArray([['value' => 'no-text']]));
    }

    public function testToXmlStringSimple(): void
    {
        $segments = [['text' => 'Ciao']];
        $xml = RichTextSegment::toXmlString($segments);

        $this->assertStringContainsString('<w:r>', $xml);
        $this->assertStringContainsString('<w:t', $xml);
        $this->assertStringContainsString('Ciao', $xml);
        $this->assertStringNotContainsString('<w:rPr>', $xml);
    }

    public function testToXmlStringBold(): void
    {
        $segments = [['text' => 'Grassetto', 'bold' => true]];
        $xml = RichTextSegment::toXmlString($segments);

        $this->assertStringContainsString('<w:rPr>', $xml);
        $this->assertStringContainsString('<w:b/>', $xml);
    }

    public function testToXmlStringItalic(): void
    {
        $segments = [['text' => 'Corsivo', 'italic' => true]];
        $xml = RichTextSegment::toXmlString($segments);

        $this->assertStringContainsString('<w:i/>', $xml);
    }

    public function testToXmlStringUnderline(): void
    {
        $segments = [['text' => 'Sottolineato', 'underline' => true]];
        $xml = RichTextSegment::toXmlString($segments);

        $this->assertStringContainsString('<w:u w:val="single"/>', $xml);
    }

    public function testToXmlStringUnderlineDouble(): void
    {
        $segments = [['text' => 'Doppio', 'underline' => 'double']];
        $xml = RichTextSegment::toXmlString($segments);

        $this->assertStringContainsString('<w:u w:val="double"/>', $xml);
    }

    public function testToXmlStringStrike(): void
    {
        $segments = [['text' => 'Barrato', 'strike' => true]];
        $xml = RichTextSegment::toXmlString($segments);

        $this->assertStringContainsString('<w:strike/>', $xml);
    }

    public function testToXmlStringDoubleStrike(): void
    {
        $segments = [['text' => 'Doppio barrato', 'doubleStrike' => true]];
        $xml = RichTextSegment::toXmlString($segments);

        $this->assertStringContainsString('<w:dstrike/>', $xml);
        $this->assertStringNotContainsString('<w:strike/>', $xml);
    }

    public function testToXmlStringSuperscript(): void
    {
        $segments = [['text' => '2', 'superscript' => true]];
        $xml = RichTextSegment::toXmlString($segments);

        $this->assertStringContainsString('<w:vertAlign w:val="superscript"/>', $xml);
    }

    public function testToXmlStringSubscript(): void
    {
        $segments = [['text' => '2', 'subscript' => true]];
        $xml = RichTextSegment::toXmlString($segments);

        $this->assertStringContainsString('<w:vertAlign w:val="subscript"/>', $xml);
    }

    public function testToXmlStringColor(): void
    {
        $segments = [['text' => 'Rosso', 'color' => 'FF0000']];
        $xml = RichTextSegment::toXmlString($segments);

        $this->assertStringContainsString('<w:color w:val="FF0000"/>', $xml);
    }

    public function testToXmlStringColorWithHash(): void
    {
        $segments = [['text' => 'Rosso', 'color' => '#FF0000']];
        $xml = RichTextSegment::toXmlString($segments);

        $this->assertStringContainsString('<w:color w:val="FF0000"/>', $xml);
    }

    public function testToXmlStringFontSize(): void
    {
        $segments = [['text' => 'Grande', 'fontSize' => 24]];
        $xml = RichTextSegment::toXmlString($segments);

        $this->assertStringContainsString('<w:sz w:val="48"/>', $xml);
        $this->assertStringContainsString('<w:szCs w:val="48"/>', $xml);
    }

    public function testToXmlStringFont(): void
    {
        $segments = [['text' => 'Custom', 'font' => 'Arial']];
        $xml = RichTextSegment::toXmlString($segments);

        $this->assertStringContainsString('<w:rFonts w:ascii="Arial" w:hAnsi="Arial"/>', $xml);
    }

    public function testToXmlStringHighlight(): void
    {
        $segments = [['text' => 'Evidenziato', 'highlight' => 'yellow']];
        $xml = RichTextSegment::toXmlString($segments);

        $this->assertStringContainsString('<w:highlight w:val="yellow"/>', $xml);
    }

    public function testToXmlStringShading(): void
    {
        $segments = [['text' => 'Sfondo', 'shading' => 'FF0000']];
        $xml = RichTextSegment::toXmlString($segments);

        $this->assertStringContainsString('<w:shd w:val="clear" w:color="auto" w:fill="FF0000"/>', $xml);
    }

    public function testToXmlStringCaps(): void
    {
        $segments = [['text' => 'MAIUSCOLO', 'caps' => true]];
        $xml = RichTextSegment::toXmlString($segments);

        $this->assertStringContainsString('<w:caps/>', $xml);
    }

    public function testToXmlStringSmallCaps(): void
    {
        $segments = [['text' => 'piccole', 'smallCaps' => true]];
        $xml = RichTextSegment::toXmlString($segments);

        $this->assertStringContainsString('<w:smallCaps/>', $xml);
    }

    public function testToXmlStringSpacing(): void
    {
        $segments = [['text' => 'Spaziato', 'spacing' => 100]];
        $xml = RichTextSegment::toXmlString($segments);

        $this->assertStringContainsString('<w:spacing w:val="100"/>', $xml);
    }

    public function testToXmlStringMultipleSegments(): void
    {
        $segments = [
            ['text' => 'Uno', 'bold' => true],
            ['text' => 'Due', 'italic' => true],
            ['text' => 'Tre'],
        ];
        $xml = RichTextSegment::toXmlString($segments);

        $this->assertStringContainsString('Uno', $xml);
        $this->assertStringContainsString('Due', $xml);
        $this->assertStringContainsString('Tre', $xml);

        preg_match_all('/<w:r>/', $xml, $matches);
        $this->assertCount(3, $matches[0]);
    }

    public function testToHtmlStringSimple(): void
    {
        $segments = [['text' => 'Ciao']];
        $html = RichTextSegment::toHtmlString($segments);

        $this->assertSame('Ciao', $html);
    }

    public function testToHtmlStringBold(): void
    {
        $segments = [['text' => 'Grassetto', 'bold' => true]];
        $html = RichTextSegment::toHtmlString($segments);

        $this->assertStringContainsString('<b>', $html);
        $this->assertStringContainsString('Grassetto', $html);
        $this->assertStringContainsString('</b>', $html);
    }

    public function testToHtmlStringItalic(): void
    {
        $segments = [['text' => 'Corsivo', 'italic' => true]];
        $html = RichTextSegment::toHtmlString($segments);

        $this->assertStringContainsString('<i>', $html);
    }

    public function testToHtmlStringStrike(): void
    {
        $segments = [['text' => 'Barrato', 'strike' => true]];
        $html = RichTextSegment::toHtmlString($segments);

        $this->assertStringContainsString('<s>', $html);
    }

    public function testToHtmlStringUnderline(): void
    {
        $segments = [['text' => 'Sotto', 'underline' => true]];
        $html = RichTextSegment::toHtmlString($segments);

        $this->assertStringContainsString('<u>', $html);
    }

    public function testToHtmlStringSuperscript(): void
    {
        $segments = [['text' => '2', 'superscript' => true]];
        $html = RichTextSegment::toHtmlString($segments);

        $this->assertStringContainsString('<sup>', $html);
    }

    public function testToHtmlStringSubscript(): void
    {
        $segments = [['text' => '2', 'subscript' => true]];
        $html = RichTextSegment::toHtmlString($segments);

        $this->assertStringContainsString('<sub>', $html);
    }

    public function testToHtmlStringFontSize(): void
    {
        $segments = [['text' => 'Grande', 'fontSize' => 24]];
        $html = RichTextSegment::toHtmlString($segments);

        $this->assertStringContainsString('font-size:24pt', $html);
    }

    public function testToHtmlStringColor(): void
    {
        $segments = [['text' => 'Rosso', 'color' => 'FF0000']];
        $html = RichTextSegment::toHtmlString($segments);

        $this->assertStringContainsString('color:#FF0000', $html);
    }

    public function testToHtmlStringEscapesHtml(): void
    {
        $segments = [['text' => '<script>']];
        $html = RichTextSegment::toHtmlString($segments);

        $this->assertStringNotContainsString('<script>', $html);
    }
}
