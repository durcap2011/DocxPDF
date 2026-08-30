<?php

declare(strict_types=1);

namespace DocxPDF\Tests\Placeholder;

use PHPUnit\Framework\TestCase;
use DocxPDF\Placeholder\TablePlaceholder;

class TablePlaceholderTest extends TestCase
{
    public function testGetType(): void
    {
        $table = new TablePlaceholder('dati', [['A', 'B']]);
        $this->assertSame('table', $table->getType());
    }

    public function testEmptyValueReturnsEmpty(): void
    {
        $table = new TablePlaceholder('dati', []);
        $this->assertSame('', $table->toXmlString());
    }

    public function testNonArrayValueReturnsEmpty(): void
    {
        $table = new TablePlaceholder('dati', 'stringa');
        $this->assertSame('', $table->toXmlString());
    }

    public function testBasicTable(): void
    {
        $data = [
            ['A', 'B'],
            ['C', 'D'],
        ];
        $table = new TablePlaceholder('dati', $data);
        $xml = $table->toXmlString();

        $this->assertStringContainsString('<w:tbl>', $xml);
        $this->assertStringContainsString('<w:tblPr>', $xml);
        $this->assertStringContainsString('<w:tr>', $xml);
        $this->assertStringContainsString('<w:tc>', $xml);
        $this->assertStringContainsString('A', $xml);
        $this->assertStringContainsString('B', $xml);
        $this->assertStringContainsString('C', $xml);
        $this->assertStringContainsString('D', $xml);
    }

    public function testTableWithHeaderRepeat(): void
    {
        $data = [
            ['Header1', 'Header2'],
            ['Row1A', 'Row1B'],
            ['Row2A', 'Row2B'],
        ];
        $table = new TablePlaceholder('dati', $data);
        $xml = $table->toXmlString();

        $this->assertStringContainsString('<w:tblHeader/>', $xml);
        $this->assertStringContainsString('Header1', $xml);
    }

    public function testTableWithConfig(): void
    {
        $data = [
            'config' => [
                'repeatHeader' => false,
                'align' => 'center',
                'width' => 9000,
            ],
            'rows' => [
                ['A', 'B'],
                ['C', 'D'],
            ],
        ];
        $table = new TablePlaceholder('dati', $data);
        $xml = $table->toXmlString();

        $this->assertStringContainsString('<w:jc w:val="center"/>', $xml);
        $this->assertStringContainsString('<w:tblW w:w="9000"', $xml);
    }

    public function testTableWithRichTextCells(): void
    {
        $data = [
            [
                ['text' => 'Grassetto', 'bold' => true],
                ['text' => 'Corsivo', 'italic' => true],
            ],
            [
                'Testo semplice',
                ['text' => 'Rosso', 'color' => 'FF0000'],
            ],
        ];
        $table = new TablePlaceholder('dati', $data);
        $xml = $table->toXmlString();

        $this->assertStringContainsString('<w:b/>', $xml);
        $this->assertStringContainsString('<w:i/>', $xml);
        $this->assertStringContainsString('Grassetto', $xml);
        $this->assertStringContainsString('Corsivo', $xml);
        $this->assertStringContainsString('Testo semplice', $xml);
        $this->assertStringContainsString('<w:color w:val="FF0000"/>', $xml);
    }

    public function testTableHtmlOutput(): void
    {
        $data = [
            ['A', 'B'],
            ['C', 'D'],
        ];
        $table = new TablePlaceholder('dati', $data);
        $html = $table->toHtmlString();

        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('<th>', $html);
        $this->assertStringContainsString('<td>', $html);
        $this->assertStringContainsString('A', $html);
        $this->assertStringContainsString('D', $html);
    }

    public function testTableBorders(): void
    {
        $data = [
            'config' => [
                'borders' => [
                    'top' => ['val' => 'double', 'sz' => '8', 'color' => 'FF0000'],
                    'insideH' => ['val' => 'none'],
                ],
            ],
            'rows' => [['A', 'B']],
        ];
        $table = new TablePlaceholder('dati', $data);
        $xml = $table->toXmlString();

        $this->assertStringContainsString('w:val="double"', $xml);
        $this->assertStringContainsString('w:sz="8"', $xml);
        $this->assertStringContainsString('w:color="FF0000"', $xml);
        $this->assertStringContainsString('w:val="none"', $xml);
    }

    public function testTableCellPadding(): void
    {
        $data = [
            'config' => [
                'cellPadding' => [
                    'top' => 50,
                    'left' => 100,
                    'bottom' => 50,
                    'right' => 100,
                ],
            ],
            'rows' => [['A', 'B']],
        ];
        $table = new TablePlaceholder('dati', $data);
        $xml = $table->toXmlString();

        $this->assertStringContainsString('<w:tblCellMar>', $xml);
        $this->assertStringContainsString('<w:top w:w="50"', $xml);
        $this->assertStringContainsString('<w:left w:w="100"', $xml);
    }

    public function testTableWidthType(): void
    {
        $data = [
            'config' => [
                'width' => 5000,
                'widthType' => 'pct',
            ],
            'rows' => [['A']],
        ];
        $table = new TablePlaceholder('dati', $data);
        $xml = $table->toXmlString();

        $this->assertStringContainsString('w:type="pct"', $xml);
    }

    public function testTableLayout(): void
    {
        $data = [
            'config' => ['layout' => 'fixed'],
            'rows' => [['A']],
        ];
        $table = new TablePlaceholder('dati', $data);
        $xml = $table->toXmlString();

        $this->assertStringContainsString('<w:tblLayout w:type="fixed"/>', $xml);
    }

    public function testTableIndent(): void
    {
        $data = [
            'config' => ['indent' => 200],
            'rows' => [['A']],
        ];
        $table = new TablePlaceholder('dati', $data);
        $xml = $table->toXmlString();

        $this->assertStringContainsString('<w:tblInd w:w="200"', $xml);
    }

    public function testTableCellSpacing(): void
    {
        $data = [
            'config' => ['cellSpacing' => 10],
            'rows' => [['A']],
        ];
        $table = new TablePlaceholder('dati', $data);
        $xml = $table->toXmlString();

        $this->assertStringContainsString('<w:tblCellSpacing w:w="10"', $xml);
    }

    public function testTableVerticalAlign(): void
    {
        $data = [
            'config' => ['vAlign' => 'center'],
            'rows' => [['A']],
        ];
        $table = new TablePlaceholder('dati', $data);
        $xml = $table->toXmlString();

        $this->assertStringContainsString('<w:vAlign w:val="center"/>', $xml);
    }

    public function testTableRowHeight(): void
    {
        $data = [
            'config' => ['rowHeight' => 400, 'rowHeightRule' => 'exact'],
            'rows' => [['A']],
        ];
        $table = new TablePlaceholder('dati', $data);
        $xml = $table->toXmlString();

        $this->assertStringContainsString('<w:trHeight w:val="400" w:hRule="exact"/>', $xml);
    }

    public function testTableCantSplit(): void
    {
        $data = [
            'config' => ['cantSplit' => true],
            'rows' => [['A']],
        ];
        $table = new TablePlaceholder('dati', $data);
        $xml = $table->toXmlString();

        $this->assertStringContainsString('<w:cantSplit/>', $xml);
    }

    public function testTableHeaderBgColor(): void
    {
        $data = [
            'config' => ['headerBgColor' => 'D9E2F3'],
            'rows' => [
                ['H1', 'H2'],
                ['A', 'B'],
            ],
        ];
        $table = new TablePlaceholder('dati', $data);
        $xml = $table->toXmlString();

        $this->assertStringContainsString('w:fill="D9E2F3"', $xml);
    }

    public function testTableCellGridSpan(): void
    {
        $data = [
            [
                ['text' => 'Titolo', 'gridSpan' => 2],
            ],
            ['A', 'B'],
        ];
        $table = new TablePlaceholder('dati', $data);
        $xml = $table->toXmlString();

        $this->assertStringContainsString('<w:gridSpan w:val="2"/>', $xml);
    }

    public function testTableCellAlign(): void
    {
        $data = [
            [
                ['text' => 'Centrato', 'align' => 'center'],
            ],
        ];
        $table = new TablePlaceholder('dati', $data);
        $xml = $table->toXmlString();

        $this->assertStringContainsString('<w:jc w:val="center"/>', $xml);
    }

    public function testTablePageBreakChunking(): void
    {
        $rows = [['H1', 'H2']];
        for ($i = 0; $i < 25; $i++) {
            $rows[] = ["R{$i}A", "R{$i}B"];
        }

        $data = [
            'config' => ['chunkSize' => 10, 'repeatHeader' => true],
            'rows' => $rows,
        ];
        $table = new TablePlaceholder('dati', $data);
        $xml = $table->toXmlString();

        $this->assertStringContainsString('<w:br w:type="page"/>', $xml);

        preg_match_all('/<w:tbl>/', $xml, $matches);
        $this->assertCount(3, $matches[0]);
    }
}
