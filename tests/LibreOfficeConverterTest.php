<?php

declare(strict_types=1);

namespace DocxPDF\Tests;

use PHPUnit\Framework\TestCase;
use DocxPDF\LibreOfficeConverter;

class LibreOfficeConverterTest extends TestCase
{
    public function testConstructorDoesNotThrowIfLibreOfficeNotFound(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Windows: soffice may not be in PATH');
        }

        // The constructor calls findLibreOffice() which throws if not found.
        // On Linux without LibreOffice this test validates the exception.
        $output = [];
        $returnCode = 0;
        exec('which libreoffice 2>&1', $output, $returnCode);

        if ($returnCode === 0) {
            $converter = new LibreOfficeConverter();
            $this->assertInstanceOf(LibreOfficeConverter::class, $converter);
        } else {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('LibreOffice non trovato');
            new LibreOfficeConverter();
        }
    }

    public function testConstructorWithExplicitPath(): void
    {
        // On Windows, use a dummy path — constructor doesn't validate file exists
        if (PHP_OS_FAMILY === 'Windows') {
            $converter = new LibreOfficeConverter('C:\\fake\\soffice.exe');
            $this->assertInstanceOf(LibreOfficeConverter::class, $converter);
        } else {
            $converter = new LibreOfficeConverter('/usr/bin/fake-soffice');
            $this->assertInstanceOf(LibreOfficeConverter::class, $converter);
        }
    }
}
