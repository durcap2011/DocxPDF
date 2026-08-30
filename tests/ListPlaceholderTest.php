<?php

declare(strict_types=1);

namespace DocxPDF\Tests\Placeholder;

use PHPUnit\Framework\TestCase;
use DocxPDF\Placeholder\ListPlaceholder;

class ListPlaceholderTest extends TestCase
{
    public function testGetTypeUnordered(): void
    {
        $list = new ListPlaceholder('elementi', ['A']);
        $this->assertSame('unordered_list', $list->getType());
    }

    public function testGetTypeOrdered(): void
    {
        $list = new ListPlaceholder('passi', ['A'], true);
        $this->assertSame('ordered_list', $list->getType());
    }

    public function testEmptyValueReturnsEmpty(): void
    {
        $list = new ListPlaceholder('elementi', []);
        $this->assertSame('', $list->toXmlString());
    }

    public function testUnorderedListXml(): void
    {
        $list = new ListPlaceholder('elementi', ['Primo', 'Secondo', 'Terzo']);
        $xml = $list->toXmlString();

        $this->assertStringContainsString('•', $xml);
        $this->assertStringContainsString('Primo', $xml);
        $this->assertStringContainsString('Secondo', $xml);
        $this->assertStringContainsString('Terzo', $xml);

        preg_match_all('/<w:p>/', $xml, $matches);
        $this->assertCount(3, $matches[0]);
    }

    public function testOrderedListXml(): void
    {
        $list = new ListPlaceholder('passi', ['Primo', 'Secondo'], true);
        $xml = $list->toXmlString();

        $this->assertStringContainsString('1. ', $xml);
        $this->assertStringContainsString('2. ', $xml);
        $this->assertStringContainsString('Primo', $xml);
        $this->assertStringContainsString('Secondo', $xml);
        $this->assertStringNotContainsString('•', $xml);
    }

    public function testListIndentation(): void
    {
        $list = new ListPlaceholder('elementi', ['A']);
        $xml = $list->toXmlString();

        $this->assertStringContainsString('<w:ind w:left="720" w:hanging="360"/>', $xml);
    }

    public function testUnorderedListHtml(): void
    {
        $list = new ListPlaceholder('elementi', ['A', 'B']);
        $html = $list->toHtmlString();

        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('</ul>', $html);
        $this->assertStringContainsString('<li>A</li>', $html);
        $this->assertStringContainsString('<li>B</li>', $html);
    }

    public function testOrderedListHtml(): void
    {
        $list = new ListPlaceholder('passi', ['Primo', 'Secondo'], true);
        $html = $list->toHtmlString();

        $this->assertStringContainsString('<ol>', $html);
        $this->assertStringContainsString('</ol>', $html);
        $this->assertStringContainsString('<li>Primo</li>', $html);
    }

    public function testListWithRichText(): void
    {
        $items = [
            ['text' => 'Grassetto', 'bold' => true],
            ['text' => 'Rosso', 'color' => 'FF0000'],
            'Semplice',
        ];
        $list = new ListPlaceholder('elementi', $items);
        $xml = $list->toXmlString();

        $this->assertStringContainsString('<w:b/>', $xml);
        $this->assertStringContainsString('Grassetto', $xml);
        $this->assertStringContainsString('<w:color w:val="FF0000"/>', $xml);
        $this->assertStringContainsString('Rosso', $xml);
        $this->assertStringContainsString('Semplice', $xml);
    }

    public function testListWithRichTextHtml(): void
    {
        $items = [
            ['text' => 'Grassetto', 'bold' => true],
            'Semplice',
        ];
        $list = new ListPlaceholder('elementi', $items);
        $html = $list->toHtmlString();

        $this->assertStringContainsString('<b>', $html);
        $this->assertStringContainsString('Grassetto', $html);
        $this->assertStringContainsString('Semplice', $html);
    }

    public function testListWithSingleSegment(): void
    {
        $items = [
            ['text' => 'Unico elemento', 'bold' => true],
        ];
        $list = new ListPlaceholder('elementi', $items);
        $xml = $list->toXmlString();

        $this->assertStringContainsString('<w:b/>', $xml);
        $this->assertStringContainsString('Unico elemento', $xml);
    }

    public function testListEscapesHtml(): void
    {
        $list = new ListPlaceholder('elementi', ['<script>']);
        $xml = $list->toXmlString();

        $this->assertStringNotContainsString('<script>', $xml);
    }
}
