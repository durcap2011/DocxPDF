<?php

declare(strict_types=1);

namespace DocxPDF\Tests\Placeholder;

use PHPUnit\Framework\TestCase;
use DocxPDF\Placeholder\PlaceholderParser;
use DocxPDF\Placeholder\TextPlaceholder;
use DocxPDF\Placeholder\TablePlaceholder;
use DocxPDF\Placeholder\ListPlaceholder;
use DocxPDF\Placeholder\ImagePlaceholder;

class PlaceholderParserTest extends TestCase
{
    private PlaceholderParser $parser;

    protected function setUp(): void
    {
        $this->parser = new PlaceholderParser();
    }

    public function testParseSimpleText(): void
    {
        $xml = '<w:t>Hello {{testo:nome}}</w:t>';
        $data = ['testo:nome' => 'Mario'];

        $placeholders = $this->parser->parse($xml, $data);

        $this->assertArrayHasKey('{{testo:nome}}', $placeholders);
        $this->assertInstanceOf(TextPlaceholder::class, $placeholders['{{testo:nome}}']);
    }

    public function testParseTable(): void
    {
        $xml = '<w:t>{{tabella:dati}}</w:t>';
        $data = [
            'tabella:dati' => [
                ['A', 'B'],
                ['C', 'D'],
            ],
        ];

        $placeholders = $this->parser->parse($xml, $data);

        $this->assertArrayHasKey('{{tabella:dati}}', $placeholders);
        $this->assertInstanceOf(TablePlaceholder::class, $placeholders['{{tabella:dati}}']);
    }

    public function testParseList(): void
    {
        $xml = '<w:t>{{lista:elementi}}</w/tty>';
        $data = ['lista:elementi' => ['A', 'B', 'C']];

        $placeholders = $this->parser->parse($xml, $data);

        $this->assertArrayHasKey('{{lista:elementi}}', $placeholders);
        $this->assertInstanceOf(ListPlaceholder::class, $placeholders['{{lista:elementi}}']);
    }

    public function testParseOrderedList(): void
    {
        $xml = '<w:t>{{lista_numerata:passi}}</w:t>';
        $data = ['lista_numerata:passi' => ['Primo', 'Secondo']];

        $placeholders = $this->parser->parse($xml, $data);

        $this->assertArrayHasKey('{{lista_numerata:passi}}', $placeholders);
        $this->assertInstanceOf(ListPlaceholder::class, $placeholders['{{lista_numerata:passi}}']);
        $this->assertSame('ordered_list', $placeholders['{{lista_numerata:passi}}']->getType());
    }

    public function testParseImage(): void
    {
        $xml = '<w:t>{{immagine:foto}}</w:t>';
        $data = ['immagine:foto' => '/path/to/image.png'];

        $placeholders = $this->parser->parse($xml, $data);

        $this->assertArrayHasKey('{{immagine:foto}}', $placeholders);
        $this->assertInstanceOf(ImagePlaceholder::class, $placeholders['{{immagine:foto}}']);
    }

    public function testParseWithoutType(): void
    {
        $xml = '<w:t>{{nome}}</w:t>';
        $data = ['nome' => 'Mario'];

        $placeholders = $this->parser->parse($xml, $data);

        $this->assertArrayHasKey('{{nome}}', $placeholders);
        $this->assertInstanceOf(TextPlaceholder::class, $placeholders['{{nome}}']);
    }

    public function testParseAutoDetectTable(): void
    {
        $xml = '<w:t>{{dati}}</w:t>';
        $data = ['dati' => [['A', 'B'], ['C', 'D']]];

        $placeholders = $this->parser->parse($xml, $data);

        $this->assertArrayHasKey('{{dati}}', $placeholders);
        $this->assertInstanceOf(TablePlaceholder::class, $placeholders['{{dati}}']);
    }

    public function testParseAutoDetectImage(): void
    {
        $xml = '<w:t>{{foto}}</w:t>';
        $data = ['foto' => ['path' => '/img.png', 'width' => 100]];

        $placeholders = $this->parser->parse($xml, $data);

        $this->assertArrayHasKey('{{foto}}', $placeholders);
        $this->assertInstanceOf(ImagePlaceholder::class, $placeholders['{{foto}}']);
    }

    public function testParseAutoDetectList(): void
    {
        $xml = '<w:t>{{elementi}}</w:t>';
        $data = ['elementi' => ['A', 'B', 'C']];

        $placeholders = $this->parser->parse($xml, $data);

        $this->assertArrayHasKey('{{elementi}}', $placeholders);
        $this->assertInstanceOf(ListPlaceholder::class, $placeholders['{{elementi}}']);
    }

    public function testParseMissingDataReturnsEmpty(): void
    {
        $xml = '<w:t>{{testo:inesistente}}</w:t>';
        $data = [];

        $placeholders = $this->parser->parse($xml, $data);

        $this->assertEmpty($placeholders);
    }

    public function testParseFullKeyLookupFirst(): void
    {
        $xml = '<w:t>{{lista:materiale}}</w:t>';
        $data = [
            'lista:materiale' => ['A', 'B'],
        ];

        $placeholders = $this->parser->parse($xml, $data);

        $this->assertArrayHasKey('{{lista:materiale}}', $placeholders);
        $this->assertInstanceOf(ListPlaceholder::class, $placeholders['{{lista:materiale}}']);
    }

    public function testParseMultiplePlaceholders(): void
    {
        $xml = '<w:t>{{testo:a}} e {{testo:b}}</w:t>';
        $data = [
            'testo:a' => 'Uno',
            'testo:b' => 'Due',
        ];

        $placeholders = $this->parser->parse($xml, $data);

        $this->assertCount(2, $placeholders);
        $this->assertArrayHasKey('{{testo:a}}', $placeholders);
        $this->assertArrayHasKey('{{testo:b}}', $placeholders);
    }

    public function testParsePlainStringValueFallback(): void
    {
        $xml = '<w:t>{{nome}}</w:t>';
        $data = ['nome' => 'Mario'];

        $placeholders = $this->parser->parse($xml, $data);

        $this->assertInstanceOf(TextPlaceholder::class, $placeholders['{{nome}}']);
        $this->assertSame('Mario', $placeholders['{{nome}}']->getValue());
    }

    public function testParseNumericValue(): void
    {
        $xml = '<w:t>{{testo:eta}}</w:t>';
        $data = ['testo:eta' => 30];

        $placeholders = $this->parser->parse($xml, $data);

        $this->assertInstanceOf(TextPlaceholder::class, $placeholders['{{testo:eta}}']);
        $this->assertSame(30, $placeholders['{{testo:eta}}']->getValue());
    }
}
