<?php

declare(strict_types=1);

namespace DocxPDF\Tests;

use PHPUnit\Framework\TestCase;
use DocxPDF\Placeholder\PlaceholderParser;
use DocxPDF\Placeholder\TextPlaceholder;
use DocxPDF\Placeholder\TablePlaceholder;
use DocxPDF\Placeholder\ListPlaceholder;

class AbstractConverterTest extends TestCase
{
    private $converter;

    protected function setUp(): void
    {
        $this->converter = new class extends \DocxPDF\AbstractConverter {
            public function convert(string $docxPath, string $pdfPath, array $data): bool
            {
                return true;
            }

            public function testReplacePlaceholders(string $xml, array $data): string
            {
                return $this->replacePlaceholders($xml, $data);
            }

            public function testExtractXmlFiles(string $docxPath): array
            {
                return $this->extractXmlFiles($docxPath);
            }
        };
    }

    public function testReplacePlaceholdersSimpleText(): void
    {
        $xml = '<w:t>Hello {{testo:nome}}</w:t>';
        $data = ['testo:nome' => 'Mario'];

        $result = $this->converter->testReplacePlaceholders($xml, $data);

        $this->assertStringContainsString('Mario', $result);
        $this->assertStringNotContainsString('{{testo:nome}}', $result);
    }

    public function testReplacePlaceholdersTable(): void
    {
        $xml = '<w:p><w:r><w:t>{{tabella:dati}}</w:t></w:r></w:p>';
        $data = [
            'tabella:dati' => [
                ['A', 'B'],
                ['C', 'D'],
            ],
        ];

        $result = $this->converter->testReplacePlaceholders($xml, $data);

        $this->assertStringContainsString('<w:tbl>', $result);
        $this->assertStringContainsString('A', $result);
        $this->assertStringContainsString('D', $result);
        $this->assertStringNotContainsString('{{tabella:dati}}', $result);
    }

    public function testReplacePlaceholdersList(): void
    {
        $xml = '<w:p><w:r><w:t>{{lista:elementi}}</w:t></w:r></w:p>';
        $data = ['lista:elementi' => ['Primo', 'Secondo']];

        $result = $this->converter->testReplacePlaceholders($xml, $data);

        $this->assertStringContainsString('Primo', $result);
        $this->assertStringContainsString('Secondo', $result);
        $this->assertStringNotContainsString('{{lista:elementi}}', $result);
    }

    public function testReplacePlaceholdersMultiple(): void
    {
        $xml = '<w:t>{{testo:a}} e {{testo:b}}</w:t>';
        $data = [
            'testo:a' => 'Uno',
            'testo:b' => 'Due',
        ];

        $result = $this->converter->testReplacePlaceholders($xml, $data);

        $this->assertStringContainsString('Uno', $result);
        $this->assertStringContainsString('Due', $result);
        $this->assertStringNotContainsString('{{', $result);
    }

    public function testReplacePlaceholdersMissingDataKeepsPlaceholder(): void
    {
        $xml = '<w:t>{{testo:inesistente}}</w:t>';
        $data = [];

        $result = $this->converter->testReplacePlaceholders($xml, $data);

        $this->assertStringContainsString('{{testo:inesistente}}', $result);
    }

    public function testExtractXmlFiles(): void
    {
        $tempZip = tempnam(sys_get_temp_dir(), 'docx_') . '.docx';
        $zip = new \ZipArchive();
        $zip->open($tempZip, \ZipArchive::CREATE);
        $zip->addFromString('word/document.xml', '<w:document/>');
        $zip->addFromString('word/header1.xml', '<w:hdr/>');
        $zip->addFromString('word/footer1.xml', '<w:ftr/>');
        $zip->addFromString('word/styles.xml', '<w:styles/>');
        $zip->close();

        try {
            $files = $this->converter->testExtractXmlFiles($tempZip);

            $this->assertContains('word/document.xml', $files);
            $this->assertContains('word/header1.xml', $files);
            $this->assertContains('word/footer1.xml', $files);
            $this->assertNotContains('word/styles.xml', $files);
        } finally {
            @unlink($tempZip);
        }
    }

    public function testExtractXmlFilesRejectsNonDocxParts(): void
    {
        $tempZip = tempnam(sys_get_temp_dir(), 'docx_') . '.docx';
        $zip = new \ZipArchive();
        $zip->open($tempZip, \ZipArchive::CREATE);
        $zip->addFromString('word/document.xml', '<w:document/>');
        $zip->addFromString('word/media/image1.png', 'fake-image');
        $zip->addFromString('[Content_Types].xml', '<Types/>');
        $zip->close();

        try {
            $files = $this->converter->testExtractXmlFiles($tempZip);

            $this->assertContains('word/document.xml', $files);
            $this->assertNotContains('word/media/image1.png', $files);
            $this->assertNotContains('[Content_Types].xml', $files);
        } finally {
            @unlink($tempZip);
        }
    }
}
