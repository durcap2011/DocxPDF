<?php

declare(strict_types=1);

namespace durcap2011\DocxPDF\Tests;

use PHPUnit\Framework\TestCase;
use durcap2011\DocxPDF\DocxPDF;
use durcap2011\DocxPDF\ConverterInterface;

class DocxPDFTest extends TestCase
{
    public function testDefaultConverterIsLibreOffice(): void
    {
        $docxPDF = new DocxPDF();
        $this->assertInstanceOf(\durcap2011\DocxPDF\LibreOfficeConverter::class, $this->getConverter($docxPDF));
    }

    public function testSetConverter(): void
    {
        $docxPDF = new DocxPDF();
        $mockConverter = $this->createMock(ConverterInterface::class);

        $result = $docxPDF->setConverter($mockConverter);

        $this->assertSame($docxPDF, $result);
    }

    public function testConvertThrowsOnMissingFile(): void
    {
        $docxPDF = new DocxPDF();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Il file DOCX non esiste');

        $docxPDF->convert('non_esiste.docx', []);
    }

    public function testConvertReturnsPdfPath(): void
    {
        $mockConverter = $this->createMock(ConverterInterface::class);
        $mockConverter->method('convert')->willReturn(true);

        $docxPDF = new DocxPDF($mockConverter);

        $tempDocx = tempnam(sys_get_temp_dir(), 'test_') . '.docx';
        file_put_contents($tempDocx, 'dummy');
        $expectedPdf = str_replace('.docx', '.pdf', $tempDocx);

        try {
            $result = $docxPDF->convert($tempDocx, []);

            $this->assertSame($expectedPdf, $result);
        } finally {
            @unlink($tempDocx);
            @unlink($expectedPdf);
        }
    }

    public function testConvertCustomOutputPath(): void
    {
        $mockConverter = $this->createMock(ConverterInterface::class);
        $mockConverter->method('convert')->willReturn(true);

        $docxPDF = new DocxPDF($mockConverter);

        $tempDocx = tempnam(sys_get_temp_dir(), 'test_') . '.docx';
        file_put_contents($tempDocx, 'dummy');
        $customPdf = sys_get_temp_dir() . '/custom_output.pdf';

        try {
            $result = $docxPDF->convert($tempDocx, [], $customPdf);

            $this->assertSame($customPdf, $result);
        } finally {
            @unlink($tempDocx);
            @unlink($customPdf);
        }
    }

    private function getConverter(DocxPDF $docxPDF): ConverterInterface
    {
        $ref = new \ReflectionProperty($docxPDF, 'converter');
        $ref->setAccessible(true);
        return $ref->getValue($docxPDF);
    }
}
