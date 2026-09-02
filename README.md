# DocxPDF

A PHP package for converting DOCX documents to PDF, using templates with **placeholders**. Generates PDFs from Word documents, without manually handling HTML.

## Features

- **DOCX Templates** — use Word documents as templates with `{{type:name}}` placeholders
- **Rich Text** — support for bold, italic, colors, subscript, superscript, custom fonts, and other formatting properties
- **Tables** — support for repeated headers across pages, customizable borders, width, alignment, colspan, and rowspan
- **Bulleted and numbered lists** — with rich formatting support for each item
- **Images** — dynamic insertion with automatic resizing
- **LibreOffice** — faithful conversion via soffice --headless
- **Zero framework** — pure PHP, designed to be usable in various types of PHP projects

## Requirements

- PHP >= 8.1
- `zip` extension (usually already included)
- **LibreOffice** — used for converting DOCX documents to PDF, must be installed and accessible in the environment where the conversion is performed.

## Installation

```bash
composer require durcap2011/docxpdf
```

## Quick Start
Create a file named **test.php**, and write the following code inside it:

```php
<?php
require_once 'vendor/autoload.php';

use durcap2011\DocxPDF\DocxPDF;

$data = [
    'testo:nome_cliente' => 'Mario Rossi',
    'testo:data'         => '25/12/2026',
    'testo:azienda'      => 'Agenzia Viaggi S.r.l.',
];

$docxPDF = new DocxPDF();
$pdfPath = $docxPDF->convert('template.docx', $data);

echo "PDF generato: $pdfPath";
```
Now in the same folder, create the file **template.docx** and paste the following text inside it:
```docx
Nome cliente: {{testo:nome_cliente}}    Data documento: {{testo:data}}
Nome azienda: {{testo:azienda}}
```

Running the PHP code above **(php test.php)** generates a PDF file named **template.pdf** containing the data passed to the *convert* function.

## Placeholder format

```
{{type:name}}
```

- **`type`** — the placeholder type can be one of the following values:
    - tabella
    - lista (bulleted lists in docx)
    - testo
    - immagine
    - lista_numerata (numbered lists in docx)

- **`name`** — the key in the `$data` array

Data must be passed with the full `type:name` key:

```php
$data = [
    'lista:materiale'  => ['Forbici', 'Colla', 'Righello'],
    'tabella:prodotti' => [['Prodotto', 'Prezzo'], ['Laptop', '€999']],
    'testo:note'       => 'Consegna urgente',
    'immagine:logo'    => '/path/to/logo.png',
];
```

## Placeholder types
Below are some usage examples of placeholders, for more information click [here](FORMATTING.md)

### Plain text

```php
$data = ['testo:nome' => 'Mario Rossi'];
```

Template: `{{testo:nome}}`

### Rich Text

Pass an **array of segments**, each with `text` and the desired attributes:

```php
$data = [
    'testo:nome' => [
        ['text' => 'Mario', 'bold' => true],
        ['text' => ' Rossi', 'color' => 'FF0000', 'italic' => true],
    ],
];
```

**Available attributes:** `bold`, `italic`, `underline`, `strike`, `doubleStrike`, `superscript`, `subscript`, `font`, `fontSize`, `color`, `highlight`, `shading`, `caps`, `smallCaps`, `outline`, `shadow`, `emboss`, `imprint`, `spacing`.

### Tables

Simple syntax — the first row is treated as a header (auto-bold) and is repeated on every page:

```php
$data = [
    'tabella:prodotti' => [
        ['Prodotto', 'Prezzo', 'Quantità'],   // Header row
        ['Laptop',   '€999',  '10'],           // Data row
        ['Mouse',    '€25',   '50'],
    ],
];
```

> **Note:** this is an alternative to the `config`/`rows` syntax documented below. Both forms are supported.

Cells can contain rich text:

```php
$data = [
    'tabella:prodotti' => [
        [
            ['Prodotto', 'bold' => true],
            ['Prezzo',   'bold' => true, 'color' => '0000FF'],
        ],
        [
            ['Laptop', 'italic' => true],
            ['€999',   'color' => '008000'],
        ],
    ],
];
```

#### Table configuration

Pass an array with `config` and `rows` keys to customize the table:

```php
$data = [
    'tabella:dati' => [
        'config' => [
            'repeatHeader'  => true,          // Repeat header on every page (default: true; the first row of rows becomes the header)
            'chunkSize'     => 15,            // Rows per chunk before page break
            'style'         => 'TableGrid',   // Word table style (default: TableGrid)
            'align'         => 'center',      // Table alignment (left/center/right)
            'width'         => 9000,          // Width in twips
            'widthType'     => 'dxa',         // dxa | pct | auto
            'indent'        => 100,           // Indent from left margin
            'cellSpacing'   => 10,            // Space between cells
            'layout'        => 'fixed',       // fixed | autofit
            'vAlign'        => 'center',      // Vertical cell alignment (top/center/bottom)
            'colWidth'      => 2000,          // Column width in twips
            'rowHeight'     => 400,           // Row height in twips
            'rowHeightRule' => 'atLeast',     // atLeast | exact
            'cantSplit'     => true,          // Do not split rows across pages
            'headerBgColor' => 'D9E2F3',      // Header background color
            'borders' => [                    // Custom borders
                'top'     => ['val' => 'single', 'sz' => '8', 'color' => '000000'],
                'bottom'  => ['val' => 'double', 'sz' => '4', 'color' => 'FF0000'],
                'insideH' => ['val' => 'single', 'sz' => '2', 'color' => 'CCCCCC'],
                'insideV' => ['val' => 'none'],
            ],
            'cellPadding' => [                // Cell inner margins
                'top'    => 50,
                'left'   => 100,
                'bottom' => 50,
                'right'  => 100,
            ],
        ],
        'rows' => [
            ['Intestazione', 'Valore'],
            ['Riga 1',       'Dato 1'],
            ['Riga 2',       'Dato 2'],
        ],
    ],
];
```

#### Cell attributes

Each cell can be a string or an array with:

```php
// Text with colspan
['text' => 'Titolo', 'bold' => true, 'gridSpan' => 3]

// Vertical merge (rowspan)
['text' => 'Prima', 'vMerge' => 'restart']   // first row
['text' => 'Seconda', 'vMerge' => true]       // subsequent rows

// Horizontal alignment
['text' => 'Centrato', 'align' => 'center']
```

### Bulleted lists

```php
$data = [
    'lista:elementi' => [
        'Primo elemento',
        'Secondo elemento',
        'Terzo elemento',
    ],
];
```

### Numbered lists

```php
$data = [
    'lista_numerata:passaggi' => [
        'Primo passaggio',
        'Secondo passaggio',
        'Terzo passaggio',
    ],
];
```

List items can contain formatting:

```php
$data = [
    'lista:elementi' => [
        ['text' => 'Grassetto', 'bold' => true],
        ['text' => 'Rosso', 'color' => 'FF0000'],
        'Testo semplice',
    ],
];
```

### Images

```php
// Simple path
$data = ['immagine:logo' => '/path/to/logo.png'];

// With dimensions
$data = [
    'immagine:logo' => [
        'path'   => '/path/to/logo.png',
        'width'  => 200,
        'height' => 100,
    ],
];
```

> **Note:** the actual image insertion into the DOCX document is handled internally by the converter (AbstractConverter). The `{{immagine:name}}` placeholder is recognized and the image is injected as drawing XML with automatic resizing. The `width` and `height` dimensions are expressed in pixels.

## Converters

### LibreOffice (default)

Generally faithful conversion, compatible with the features supported by LibreOffice.

```php
use DocxPDF\DocxPDF;
use DocxPDF\LibreOfficeConverter;

// Auto-detection
$docxPDF = new DocxPDF();

// Explicit path (recommended)
$converter = new LibreOfficeConverter('C:\Program Files\LibreOffice\program\soffice.exe');
$docxPDF = new DocxPDF($converter);

// Change converter after instantiation
$docxPDF->setConverter($converter);
```

## Output path

By default the PDF is generated in the same folder as the template:

```php
// Same folder as template
$pdfPath = $docxPDF->convert('template.docx', $data);

// Custom path
$pdfPath = $docxPDF->convert('template.docx', $data, 'output/rapporto.pdf');
```

## Examples

| File | Description |
|------|-------------|
| `examples/text-simple.php` | Plain and formatted text |
| `examples/table-simple.php` | Tables with formatted cells |
| `examples/list-bullet.php` | Bulleted lists |
| `examples/list-numbered.php` | Numbered lists |
| `examples/image-simple.php` | Images |
| `examples/typed-placeholders.php` | Typed placeholders |
| `examples/multiple-placeholders.php` | Multiple placeholders |
| `examples/header-footer.php` | Header and footer |
| `examples/nested-data.php` | Nested data with formatting |

## Project structure

```
docx-pdf/
├── src/
│   ├── ConverterInterface.php
│   ├── AbstractConverter.php
│   ├── DocxPDF.php
│   ├── LibreOfficeConverter.php
│   └── Placeholder/
│       ├── PlaceholderInterface.php
│       ├── AbstractPlaceholder.php
│       ├── PlaceholderParser.php
│       ├── TextPlaceholder.php
│       ├── TablePlaceholder.php
│       ├── ListPlaceholder.php
│       ├── ImagePlaceholder.php
│       └── RichTextSegment.php
├── examples/
├── tests/
├── FORMATTING.md
├── composer.json
└── README.md
```

## Known limitations

- **LibreOffice ignores `<w:tblHeader/>`** — repeated header across pages is handled with forced page breaks between row chunks
- **Images** — insertion only works with the LibreOffice converter
Documents provided to LibreOffice should be treated as untrusted input and conversion should be performed in an adequately isolated environment when files come from untrusted users.

## Troubleshooting

### LibreOffice not found

```php
// Specify the path manually
$converter = new LibreOfficeConverter('C:\\Program Files\\LibreOffice\\program\\soffice.exe');
$docxPDF = new DocxPDF($converter);
```

### Special characters not displayed

Correct character rendering may depend on the fonts available in the system and the LibreOffice environment settings.
Verify that the fonts used by the document are installed and available in the environment where the conversion is performed.

## Disclaimer

This package is provided "as is" and "as available", without warranties of any kind, to the maximum extent permitted by applicable law.
The package may contain bugs, limitations, or compatibility issues, and the generated result may vary depending on the source DOCX document, the PHP version, the operating system, the installed fonts, the LibreOffice version, and other factors in the environment where it is used.
The author does not guarantee that the package or the generated PDF documents are error-free, complete, accurate, suitable for a specific purpose, or compatible with any environment or document.
The user is responsible for verifying and validating the generated documents before using them in production or for legal, financial, tax, regulatory, contractual, or otherwise critical purposes.
To the extent permitted by applicable law, the author is not liable for any losses, damages, or consequences arising from the use of the package or reliance on the documents generated through it.
Nothing in this disclaimer is intended to exclude or limit any liability that cannot be legally excluded or limited under applicable law.

## Contributing

1. Fork the project
2. Create a branch for your feature
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

MIT — see the `LICENSE.md` file.