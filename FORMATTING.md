# Text Formatting Guide

DocxPDF supports inline text formatting through **rich segments**. Each placeholder value can be:

- A **plain string** → unformatted text (original behavior)
- An **array of segments** → each segment has `text` + optional formatting attributes

---

## How to Use Segments

### Plain string (backward compatible)

```php
$nome = 'Mario Rossi';
```

### Single segment with formatting

```php
$prezzo = [
    ['text' => '€1.250,00', 'bold' => true, 'color' => 'FF0000']
];
```

### Multiple segments (mixed formatting)

```php
$formula = [
    ['text' => 'H', 'subscript' => true],
    ['text' => '2'],
    ['text' => 'O'],
];
// Result: H₂O
```

---

## Available Attributes

### Basic Formatting

| Attribute | Type | Values | Description |
|-----------|------|--------|-------------|
| `text` | `string` | any | The segment text (required) |
| `bold` | `bool` | `true` / `false` | Bold |
| `italic` | `bool` | `true` / `false` | Italic |
| `strike` | `bool` | `true` / `false` | Single strikethrough |
| `doubleStrike` | `bool` | `true` / `false` | Double strikethrough |
| `underline` | `string` | `single`, `double`, `wavy`, `dotted`, `dash`, `dotDash`, etc. | Underline |
| `caps` | `bool` | `true` / `false` | Visual caps |
| `smallCaps` | `bool` | `true` / `false` | Small caps |

### Superscript and Subscript

| Attribute | Type | Values | Description |
|-----------|------|--------|-------------|
| `superscript` | `bool` | `true` / `false` | Superscript (raised text) |
| `subscript` | `bool` | `true` / `false` | Subscript (lowered text) |

**Subscript example:**
```php
[['text' => 'H'], ['text' => '2', 'subscript' => true], ['text' => 'O']]
// → H₂O
```

**Superscript example:**
```php
[['text' => 'x'], ['text' => '2', 'superscript' => true]]
// → x²
```

### Font and Size

| Attribute | Type | Values | Description |
|-----------|------|--------|-------------|
| `font` | `string` | font name | Font name (e.g. `Arial`, `Times New Roman`, `Calibri`) |
| `fontSize` | `int` | points | Size in points (e.g. `12` = 12pt) |

```php
[['text' => 'Large text', 'font' => 'Arial', 'fontSize' => 18]]
```

### Color and Background

| Attribute | Type | Values | Description |
|-----------|------|--------|-------------|
| `color` | `string` | hex | Text color (e.g. `FF0000` = red, `008000` = green) |
| `highlight` | `string` | `yellow`, `red`, `green`, `blue`, `cyan`, `magenta`, etc. | Highlighter (fixed Word palette) |
| `shading` | `string` | hex | Free background (custom color) |

```php
[['text' => 'Warning', 'color' => 'FF0000', 'highlight' => 'yellow']]
```

### Text Effects

| Attribute | Type | Values | Description |
|-----------|------|--------|-------------|
| `outline` | `bool` | `true` / `false` | Hollow text (outline) |
| `shadow` | `bool` | `true` / `false` | Shadow |
| `emboss` | `bool` | `true` / `false` | Emboss |
| `imprint` | `bool` | `true` / `false` | Imprint |

### Spacing

| Attribute | Type | Values | Description |
|-----------|------|--------|-------------|
| `spacing` | `int` | twips | Character spacing (positive = expanded, negative = condensed) |

```php
[['text' => 'Spaced text', 'spacing' => 50]]
```

---

## Usage by Placeholder Type

### Plain Text `{{testo:nome}}`

```php
$data = [
    'testo:nome' => [
        ['text' => 'Mario', 'bold' => true],
        ['text' => ' Rossi'],
    ],
];
```

### Table `{{tabella:prodotti}}`

Each cell can be a string or an array of segments.

#### Simple format

```php
$data = [
    'tabella:prodotti' => [
        // Headers
        ['Product', 'Price'],
        // Cells with formatting
        [
            ['text' => 'Laptop', 'bold' => true],
            ['text' => '€999.99', 'color' => 'FF0000'],
        ],
        // Cell with mixed formatting (subscript)
        [
            ['text' => 'H'], ['text' => '2', 'subscript' => true], ['text' => 'O'],
            ' €10.00',
        ],
    ],
];
```

#### Config format

To configure table attributes, use the `config` key:

```php
$data = [
    'tabella:prodotti' => [
        'config' => [
            'repeatHeader' => true,      // Repeat header on every page (default: true)
            'chunkSize' => 20,           // Rows per block with page break (default: 20)
            'align' => 'center',         // Table alignment: left, center, right
            'width' => 5000,             // Table width in twips
            'widthType' => 'dxa',        // Width type: auto, dxa, pct
            'indent' => 0,               // Indent from left (twips)
            'cellSpacing' => 0,          // Spacing between cells (twips)
            'layout' => 'autofit',       // Layout: fixed, autofit
            'cellPadding' => [           // Cell inner margins (twips)
                'top' => 40,
                'left' => 80,
                'bottom' => 40,
                'right' => 80,
            ],
            'borders' => [               // Custom borders
                'top' => ['val' => 'single', 'sz' => '4', 'color' => '000000'],
                'bottom' => ['val' => 'single', 'sz' => '8', 'color' => 'FF0000'],
                'insideH' => ['val' => 'single', 'sz' => '4', 'color' => 'CCCCCC'],
                // missing borders inherit default single sz=4
            ],
            'vAlign' => 'center',        // Vertical cell alignment: top, center, bottom
            'rowHeight' => 400,          // Row height in twips
            'rowHeightRule' => 'atLeast', // Height rule: exact, atLeast, auto
            'cantSplit' => true,         // Do not split row across pages
            'headerBgColor' => 'D9E2F3', // Header row background color
        ],
        'rows' => [
            ['Product', 'Price'],
            ['Laptop', ['text' => '€999.99', 'color' => '008000']],
        ],
    ],
];
```

#### Table attributes (`config`)

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `repeatHeader` | `bool` | `true` | Repeats the header row on every page |
| `chunkSize` | `int` | `20` | Number of rows per block before page break |
| `align` | `string` | — | Horizontal alignment: `left`, `center`, `right` |
| `width` | `int` | — | Table width (twips) |
| `widthType` | `string` | `dxa` | Width type: `auto`, `dxa` (twips), `pct` (percentage) |
| `indent` | `int` | — | Indent from left (twips) |
| `cellSpacing` | `int` | — | Spacing between cells (twips) |
| `layout` | `string` | — | Table layout: `fixed`, `autofit` |
| `cellPadding` | `array` | — | Inner margins: `top`, `left`, `bottom`, `right` (twips) |
| `borders` | `array` | — | Borders per side (see below) |
| `vAlign` | `string` | — | Vertical cell alignment: `top`, `center`, `bottom` |
| `rowHeight` | `int` | — | Row height in twips |
| `rowHeightRule` | `string` | `atLeast` | Height rule: `exact`, `atLeast`, `auto` |
| `cantSplit` | `bool` | `false` | Prevents splitting the row across pages |
| `headerBgColor` | `string` | `D9E2F3` | Header row background color |

#### Custom borders

Each border accepts an array with: `val` (type), `sz` (thickness), `color` (hex color).

```php
'borders' => [
    'top'     => ['val' => 'single', 'sz' => '4', 'color' => '000000'],
    'left'    => ['val' => 'double', 'sz' => '6', 'color' => 'FF0000'],
    'bottom'  => ['val' => 'single', 'sz' => '4', 'color' => 'auto'],
    'right'   => ['val' => 'none'],
    'insideH' => ['val' => 'single', 'sz' => '4', 'color' => 'CCCCCC'],
    'insideV' => ['val' => 'dotted', 'sz' => '2', 'color' => '999999'],
],
```

`val` values: `single`, `double`, `dotted`, `dash`, `dotDash`, `none`, etc.

#### Cell attributes

Cells can be strings, arrays of segments, or associative arrays:

```php
// Plain string
'Laptop'

// Single segment
['text' => '€999', 'bold' => true]

// Array of segments
[
    ['text' => 'H'],
    ['text' => '2', 'subscript' => true],
]

// Cell with attributes
[
    'text' => 'Text',
    'bold' => true,
    'gridSpan' => 2,     // Merges 2 columns
    'vMerge' => true,    // Start vertical merge ('restart')
    'vMerge' => 'continue', // Continue vertical merge
    'align' => 'center', // Horizontal alignment in the paragraph
]
```

### Bulleted List `{{lista:elementi}}`

Each item can be a string or an array of segments:

```php
$data = [
    'lista:elementi' => [
        'Simple item',
        ['text' => 'Bold item', 'bold' => true],
        ['text' => 'Red item', 'color' => 'FF0000'],
    ],
];
```

### Numbered List `{{lista_numerata:passaggi}}`

```php
$data = [
    'lista_numerata:passaggi' => [
        ['text' => 'First step', 'bold' => true],
        ['text' => 'Second step', 'italic' => true],
        'Third step',
    ],
];
```

---

## Complete Examples

### Chemical Formula

```php
$formula = [
    ['text' => 'H'],
    ['text' => '2', 'subscript' => true],
    ['text' => 'SO'],
    ['text' => '4', 'subscript' => true],
];
// → H₂SO₄
```

### Mathematical Formula

```php
$formula = [
    ['text' => 'x'],
    ['text' => '2', 'superscript' => true],
    ['text' => ' + y'],
    ['text' => '2', 'superscript' => true],
];
// → x² + y²
```

### Discounted Price

```php
$prezzo = [
    ['text' => '€100.00', 'strike' => true, 'color' => '999999'],
    ['text' => ' → '],
    ['text' => '€75.00', 'bold' => true, 'color' => 'FF0000'],
];
// → ~~€100.00~~ → €75.00
```

### Formatted Table Header

```php
$tabella = [
    [
        ['text' => 'Product', 'bold' => true, 'color' => 'FFFFFF', 'shading' => '003366'],
        ['text' => 'Price', 'bold' => true, 'color' => 'FFFFFF', 'shading' => '003366'],
    ],
    ['Laptop', ['text' => '€999.99', 'color' => '008000']],
];
```

---

## Technical Notes

### Compatibility

- **LibreOffice**: Supports all attributes. Tested on Windows, Linux, macOS.

### Color Values

Colors are specified in 6-digit hex format **without** the `#` prefix:
- `FF0000` → Red
- `008000` → Green
- `0000FF` → Blue
- `000000` → Black
- `FFFFFF` → White

### Font Size

The size is in **points** (not half-points as in OOXML XML). Conversion is automatic:
- `12` → 12pt (24 half-points)
- `18` → 18pt (36 half-points)
- `24` → 24pt (48 half-points)

### Attribute Order

Attributes can be specified in any order in the array. The only rule is that `text` must be present.

---

## Internal Structure

For each segment, DocxPDF generates the corresponding WordprocessingML XML:

```xml
<w:r>
  <w:rPr>
    <w:b/>                          <!-- bold: true -->
    <w:color w:val="FF0000"/>       <!-- color: FF0000 -->
    <w:vertAlign w:val="subscript"/> <!-- subscript: true -->
  </w:rPr>
  <w:t xml:space="preserve">text</w:t>
</w:r>
```

The `RichTextSegment` class handles automatic conversion from PHP array to OOXML XML.