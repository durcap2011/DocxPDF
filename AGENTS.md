# AGENTS.md — DocxPDF

## What this is

PHP library that converts DOCX templates to PDF by replacing `{{placeholders}}` with data. Uses LibreOffice for conversion.

## Quick commands

```bash
# Run tests
php vendor/bin/phpunit

# Run a single test class
php vendor/bin/phpunit tests/PlaceholderParserTest.php

# Syntax check after editing src/
php -l src/Path/To/File.php

# Run an example
php examples/text-simple.php

# Validate XML of a DOCX
php -r "$z=new ZipArchive(); $z->open('file.docx'); echo $z->getFromName('word/document.xml'); $z->close();"

# Regenerate DOCX template files used by examples
php examples/generate_templates.php
```

No linter, formatter, or CI is configured.

## Architecture

- Entry: `DocxPDF::convert($docxPath, $data, $pdfPath)` in `src/DocxPDF.php`
- Converters implement `ConverterInterface`. Default is `LibreOfficeConverter`.
- `AbstractConverter::modifyDocx()` is the core: copies DOCX, opens as ZIP, runs `replacePlaceholders()` on each XML part, writes back.
- Placeholders live in `src/Placeholder/`. Parser: `PlaceholderParser.php`. Rich text: `RichTextSegment.php`.

## Placeholder format

`{{type:name}}` — the first capture group is the **type** (`lista`, `tabella`, etc.), the second is the **name** (the key in the `$data` array).

Data keys use the full `type:name` form: `$data['lista:materiale']`.

Rich text segments: pass an array of `['text' => '...', 'bold' => true, ...]` instead of a string. See `FORMATTING.md` for all attributes.

## Important quirks

- **LibreOfficeConverter** creates a temp DOCX in `sys_get_temp_dir()`, modifies it, converts via CLI `soffice --headless`, renames output, then deletes the temp file. The temp file must be cleaned up even on failure (try/finally).
- **Block-level placeholder replacement** (lists, tables): `replacePlaceholders()` replaces the entire `<w:p>` paragraph, not just the text node. The regex uses negative lookahead `(?:(?!<\/w:p>).)*` to avoid matching across paragraph boundaries.
- **Windows paths**: LibreOffice path detection uses `C:\Program Files\LibreOffice\program\soffice.exe`. The `exec()` calls use double quotes around paths with spaces.
- **Security hardening**: `DocxPDF::convert()` validates path traversal, `LibreOfficeConverter` whitelists allowed LibreOffice paths, `AbstractConverter` validates image MIME types and limits DOCX/ZIP entry sizes to prevent zip bombs.
- **Test quirks**: PHPUnit 9.x, `failOnRisky=true` and `failOnWarning=true` in `phpunit.xml.dist`. The `LibreOfficeConverterTest::testConstructorWithExplicitPath` expects an `InvalidArgumentException` for non-existent paths (post-security hardening).

## Rich text handling

- `replaceRichTextInline()` splits the containing `<w:r>` when a rich text placeholder is inline. The regex uses `[^<]*` (not `.*?`) to prevent matching across XML tag boundaries.
- `html_entity_decode(..., ENT_XML1)` is called on extracted text before re-encoding to prevent double-encoding (`&#039;` → `&amp;#039;`).
- `escapeXmlValue()` only escapes `&`, `<`, `>` — not `'` or `"`. These are safe in XML text content and do not need escaping outside of attribute values.
- `RichTextSegment::toXmlString()` uses `htmlspecialchars($text, ENT_XML1)` to avoid converting `'` to `&#039;` (PHP 8.1+ default changed to `ENT_QUOTES`).
- `PlaceholderParser::createPlaceholderByValue()` checks `RichTextSegment::isSegmentArray()` before the table check, since both are arrays of arrays.

## Security considerations

- `escapeXmlValue()` escapes `&`, `<`, `>` and strips control chars. CDATA (`]]>`) and comment (`--`) injection are blocked.
- `sanitizeXmlEntities()` removes `<!DOCTYPE>`, `<!ENTITY>`, and undefined entity references to prevent XXE.
- `generate_templates.php` uses `str_replace(['&','<','>'], ...)` instead of `htmlspecialchars()` to avoid encoding `'` to `&#039;` in template XML.

## File conventions

- PSR-4 autoloading: `DocxPDF\` maps to `src/`
- All source files use `declare(strict_types=1)`
- Italian comments and docblocks throughout (this is an Italian-authored project)
- Examples in `examples/` — each has a comment block describing the required DOCX template
- `.gitignore` has a typo: `gits/vendor/` instead of `vendor/` — the actual vendor directory is not ignored at the project root level
