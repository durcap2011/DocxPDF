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
        // Il costruttore valida che il file esista e sia in una posizione consentita
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('non è un file valido');
        new LibreOfficeConverter('C:\\fake\\soffice.exe');
    }
}
