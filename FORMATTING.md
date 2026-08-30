# Guida alla Formattazione Testo

DocxPDF supporta la formattazione inline del testo tramite **segmenti ricchi**. Ogni valore placeholder può essere:

- Una **stringa semplice** → testo non formattato (comportamento originale)
- Un **array di segmenti** → ogni segmento ha `text` + attributi di formattazione opzionali

---

## Come Usare i Segmenti

### Stringa semplice (backward compatible)

```php
$nome = 'Mario Rossi';
```

### Segmento singolo con formattazione

```php
$prezzo = [
    ['text' => '€1.250,00', 'bold' => true, 'color' => 'FF0000']
];
```

### Segmenti multipli (mix di formattazioni)

```php
$formula = [
    ['text' => 'H', 'subscript' => true],
    ['text' => '2'],
    ['text' => 'O'],
];
// Risultato: H₂O
```

---

## Attributi Disponibili

### Formattazione Base

| Attributo | Tipo | Valori | Descrizione |
|-----------|------|--------|-------------|
| `text` | `string` | qualunque | Il testo del segmento (obbligatorio) |
| `bold` | `bool` | `true` / `false` | Grassetto |
| `italic` | `bool` | `true` / `false` | Corsivo |
| `strike` | `bool` | `true` / `false` | Barrato singolo |
| `doubleStrike` | `bool` | `true` / `false` | Doppio barrato |
| `underline` | `string` | `single`, `double`, `wavy`, `dotted`, `dash`, `dotDash`, ecc. | Sottolineato |
| `caps` | `bool` | `true` / `false` | Maiuscole visuali |
| `smallCaps` | `bool` | `true` / `false` | Maiuscole piccole |

### Apice e Pedice

| Attributo | Tipo | Valori | Descrizione |
|-----------|------|--------|-------------|
| `superscript` | `bool` | `true` / `false` | Apice (testo rialzato) |
| `subscript` | `bool` | `true` / `false` | Pedice (testo abbassato) |

**Esempio pedice:**
```php
[['text' => 'H'], ['text' => '2', 'subscript' => true], ['text' => 'O']]
// → H₂O
```

**Esempio apice:**
```php
[['text' => 'x'], ['text' => '2', 'superscript' => true]]
// → x²
```

### Font e Dimensione

| Attributo | Tipo | Valori | Descrizione |
|-----------|------|--------|-------------|
| `font` | `string` | nome font | Nome del font (es. `Arial`, `Times New Roman`, `Calibri`) |
| `fontSize` | `int` | punti | Dimensione in punti (es. `12` = 12pt) |

```php
[['text' => 'Testo grande', 'font' => 'Arial', 'fontSize' => 18]]
```

### Colore e Sfondo

| Attributo | Tipo | Valori | Descrizione |
|-----------|------|--------|-------------|
| `color` | `string` | hex | Colore del testo (es. `FF0000` = rosso, `008000` = verde) |
| `highlight` | `string` | `yellow`, `red`, `green`, `blue`, `cyan`, `magenta`, ecc. | Evidenziatore (palette fissa Word) |
| `shading` | `string` | hex | Sfondo libero (colore personalizzato) |

```php
[['text' => 'Attenzione', 'color' => 'FF0000', 'highlight' => 'yellow']]
```

### Effetti Testo

| Attributo | Tipo | Valori | Descrizione |
|-----------|------|--------|-------------|
| `outline` | `bool` | `true` / `false` | Testo hollow (contorno) |
| `shadow` | `bool` | `true` / `false` | Ombra |
| `emboss` | `bool` | `true` / `false` | Rilievo |
| `imprint` | `bool` | `true` / `false` | Incavo |

### Spaziatura

| Attributo | Tipo | Valori | Descrizione |
|-----------|------|--------|-------------|
| `spacing` | `int` | twips | Spaziatura caratteri (positivo = espanso, negativo = compresso) |

```php
[['text' => 'Testo con spazio', 'spacing' => 50]]
```

---

## Utilizzo per Tipo di Placeholder

### Testo Semplice `{{testo:nome}}`

```php
$data = [
    'testo:nome' => [
        ['text' => 'Mario', 'bold' => true],
        ['text' => ' Rossi'],
    ],
];
```

### Tabella `{{tabella:prodotti}}`

Ogni cella può essere una stringa o un array di segmenti.

#### Formato semplice

```php
$data = [
    'tabella:prodotti' => [
        // Intestazioni
        ['Prodotto', 'Prezzo'],
        // Celle con formattazione
        [
            ['text' => 'Laptop', 'bold' => true],
            ['text' => '€999,99', 'color' => 'FF0000'],
        ],
        // Cella con mix di formattazioni (pedice)
        [
            ['text' => 'H'], ['text' => '2', 'subscript' => true], ['text' => 'O'],
            ' €10,00',
        ],
    ],
];
```

#### Formato con config

Per configurare attributi della tabella, usa la chiave `config`:

```php
$data = [
    'tabella:prodotti' => [
        'config' => [
            'repeatHeader' => true,      // Ripeti header ad ogni pagina (default: true)
            'chunkSize' => 20,           // Righe per blocco con page break (default: 20)
            'align' => 'center',         // Allineamento tabella: left, center, right
            'width' => 5000,             // Larghezza tabella in twips
            'widthType' => 'dxa',        // Tipo larghezza: auto, dxa, pct
            'indent' => 0,               // Indentazione da sinistra (twips)
            'cellSpacing' => 0,          // Spaziatura tra celle (twips)
            'layout' => 'autofit',       // Layout: fixed, autofit
            'cellPadding' => [           // Margini interni celle (twips)
                'top' => 40,
                'left' => 80,
                'bottom' => 40,
                'right' => 80,
            ],
            'borders' => [               // Bordi personalizzati
                'top' => ['val' => 'single', 'sz' => '4', 'color' => '000000'],
                'bottom' => ['val' => 'single', 'sz' => '8', 'color' => 'FF0000'],
                'insideH' => ['val' => 'single', 'sz' => '4', 'color' => 'CCCCCC'],
                // bordi mancanti ereditano default single sz=4
            ],
            'vAlign' => 'center',        // Allineamento verticale celle: top, center, bottom
            'rowHeight' => 400,          // Altezza riga in twips
            'rowHeightRule' => 'atLeast', // Regola altezza: exact, atLeast, auto
            'cantSplit' => true,         // Non spezzare riga su pagine diverse
            'headerBgColor' => 'D9E2F3', // Colore sfondo riga header
        ],
        'rows' => [
            ['Prodotto', 'Prezzo'],
            ['Laptop', ['text' => '€999,99', 'color' => '008000']],
        ],
    ],
];
```

#### Attributi tabella (`config`)

| Attributo | Tipo | Default | Descrizione |
|-----------|------|---------|-------------|
| `repeatHeader` | `bool` | `true` | Ripete la riga di intestazione ad ogni pagina |
| `chunkSize` | `int` | `20` | Numero righe per blocco prima del page break |
| `align` | `string` | — | Allineamento orizzontale: `left`, `center`, `right` |
| `width` | `int` | — | Larghezza tabella (twips) |
| `widthType` | `string` | `dxa` | Tipo larghezza: `auto`, `dxa` (twips), `pct` (percentuale) |
| `indent` | `int` | — | Indentazione da sinistra (twips) |
| `cellSpacing` | `int` | — | Spaziatura tra celle (twips) |
| `layout` | `string` | — | Layout tabella: `fixed`, `autofit` |
| `cellPadding` | `array` | — | Margini interni: `top`, `left`, `bottom`, `right` (twips) |
| `borders` | `array` | — | Bordi per lato (vedi sotto) |
| `vAlign` | `string` | — | Allineamento verticale celle: `top`, `center`, `bottom` |
| `rowHeight` | `int` | — | Altezza riga in twips |
| `rowHeightRule` | `string` | `atLeast` | Regola altezza: `exact`, `atLeast`, `auto` |
| `cantSplit` | `bool` | `false` | Impedisce di spezzare la riga su pagine diverse |
| `headerBgColor` | `string` | `D9E2F3` | Colore sfondo riga intestazione |

#### Bordi personalizzati

Ogni bordo accetta un array con: `val` (tipo), `sz` (spessore), `color` (colore hex).

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

Valori `val`: `single`, `double`, `dotted`, `dash`, `dotDash`, `none`, ecc.

#### Attributi cella

Le celle possono essere stringhe, array di segmenti, o array associativi:

```php
// Stringa semplice
'Laptop'

// Segmento singolo
['text' => '€999', 'bold' => true]

// Array di segmenti
[
    ['text' => 'H'],
    ['text' => '2', 'subscript' => true],
]

// Cella con attributi
[
    'text' => 'Testo',
    'bold' => true,
    'gridSpan' => 2,     // Unisce 2 colonne
    'vMerge' => true,    // Inizio merge verticale ('restart')
    'vMerge' => 'continue', // Continua merge verticale
    'align' => 'center', // Allineamento orizzontale nel paragrafo
]
```

### Lista Puntata `{{lista:elementi}}`

Ogni elemento può essere una stringa o un array di segmenti:

```php
$data = [
    'lista:elementi' => [
        'Elemento semplice',
        ['text' => 'Elemento grassetto', 'bold' => true],
        ['text' => 'Elemento rosso', 'color' => 'FF0000'],
    ],
];
```

### Lista Numerata `{{lista_numerata:passaggi}}`

```php
$data = [
    'lista_numerata:passaggi' => [
        ['text' => 'Primo passaggio', 'bold' => true],
        ['text' => 'Secondo passaggio', 'italic' => true],
        'Terzo passaggio',
    ],
];
```

---

## Esempi Completi

### Formula Chimica

```php
$formula = [
    ['text' => 'H'],
    ['text' => '2', 'subscript' => true],
    ['text' => 'SO'],
    ['text' => '4', 'subscript' => true],
];
// → H₂SO₄
```

### Formula Matematica

```php
$formula = [
    ['text' => 'x'],
    ['text' => '2', 'superscript' => true],
    ['text' => ' + y'],
    ['text' => '2', 'superscript' => true],
];
// → x² + y²
```

### Prezzo Scontato

```php
$prezzo = [
    ['text' => '€100,00', 'strike' => true, 'color' => '999999'],
    ['text' => ' → '],
    ['text' => '€75,00', 'bold' => true, 'color' => 'FF0000'],
];
// → ~~€100,00~~ → €75,00
```

### Intestazione Tabella Formattata

```php
$tabella = [
    [
        ['text' => 'Prodotto', 'bold' => true, 'color' => 'FFFFFF', 'shading' => '003366'],
        ['text' => 'Prezzo', 'bold' => true, 'color' => 'FFFFFF', 'shading' => '003366'],
    ],
    ['Laptop', ['text' => '€999,99', 'color' => '008000']],
];
```

---

## Note Tecniche

### Compatibilità

- **LibreOffice**: Supporta tutti gli attributi. Testato su Windows, Linux, macOS.
- **mPDF**: Supporta `bold`, `italic`, `color`, `fontSize`, `font`, `subscript`, `superscript`. Alcuni effetti (emboss, imprint, outline) potrebbero non essere visibili.

### Valori Colore

I colori sono specificati in formato hex a 6 cifre **senza** il prefisso `#`:
- `FF0000` → Rosso
- `008000` → Verde
- `0000FF` → Blu
- `000000` → Nero
- `FFFFFF` → Bianco

### Dimensione Font

La dimensione è in **punti** (non half-points come nell'XML OOXML). La conversione è automatica:
- `12` → 12pt (24 half-points)
- `18` → 18pt (36 half-points)
- `24` → 24pt (48 half-points)

### Ordine degli Attributi

Gli attributi possono essere specificati in qualsiasi ordine nell'array. L'unica regola è che `text` deve essere presente.

---

## Struttura Interna

Per ogni segmento, DocxPDF genera l'XML WordprocessingML corrispondente:

```xml
<w:r>
  <w:rPr>
    <w:b/>                          <!-- bold: true -->
    <w:color w:val="FF0000"/>       <!-- color: FF0000 -->
    <w:vertAlign w:val="subscript"/> <!-- subscript: true -->
  </w:rPr>
  <w:t xml:space="preserve">testo</w:t>
</w:r>
```

La classe `RichTextSegment` si occupa della conversione automatica da array PHP a XML OOXML.
