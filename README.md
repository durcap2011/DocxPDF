# DocxPDF

Un package PHP per convertire documenti DOCX in PDF, utilizzando template con **placeholder**. Genera PDF partendo da documenti Word, senza gestire manualmente l'HTML.

## Caratteristiche

- **Template DOCX** — usa documenti Word come template con placeholder `{{tipo:nome}}`
- **Testo formattato (Rich Text)** — supporto a grassetto, corsivo, colori, pedice, apice, font personalizzati e altre proprietà di formattazione supportate
- **Tabelle** — supporto a intestazioni ripetute su più pagine, bordi personalizzabili, larghezza, allineamento, colspan e rowspan
- **Liste puntate e numerate** — con supporto a formattazione ricca per ogni elemento
- **Immagini** — inserimento dinamico con ridimensionamento automatico
- **LibreOffice** — conversione fedele all'originale tramite soffice --headless
- **Zero framework** — PHP puro, progettato per essere utilizzabile in diversi tipi di progetto PHP

## Requisiti

- PHP >= 8.1
- Estensione `zip` (di solito già inclusa)
- **LibreOffice** — utilizzato per la conversione dei documenti DOCX in PDF, deve essere installato e accessibile nell'ambiente in cui viene eseguita la conversione.

## Installazione

```bash
composer require durcap2011/docxpdf
```

## Utilizzo rapido

```php
<?php
require_once 'vendor/autoload.php';

use DocxPDF\DocxPDF;

$data = [
    'testo:nome_cliente' => 'Mario Rossi',
    'testo:data'         => '25/12/2026',
    'testo:azienda'      => 'Agenzia Viaggi S.r.l.',
];

$docxPDF = new DocxPDF();
$pdfPath = $docxPDF->convert('template.docx', $data);

echo "PDF generato: $pdfPath";
```
Ora nella stessa cartella, crea il file **template.docx** ed incolla al suo interno il seguente testo:
```docx
Nome cliente: {{testo:nome_cliente}}    Data documento: {{testo:data}}
Nome azienda: {{testo:azienda}}
```
Eseguendo il codice php creato sopra, otterrai la sostituzione dei placeholder, con i dati passati alla funzione *convert*

## Formato dei placeholder

```
{{tipo:nome}}
```

- **`tipo`** — il tipo di placeholder (tabella, lista, testo, immagine, lista_numerata)
- **`nome`** — la chiave nell'array `$data`

I dati vanno passati con la chiave completa `tipo:nome`:

```php
$data = [
    'lista:materiale'  => ['Forbici', 'Colla', 'Forbici'],
    'tabella:prodotti' => [['Prodotto', 'Prezzo'], ['Laptop', '€999']],
    'testo:note'       => 'Consegna urgente',
    'immagine:logo'    => '/path/to/logo.png',
];
```

## Tipi di placeholder

### Testo semplice

```php
$data = ['testo:nome' => 'Mario Rossi'];
```

Template: `{{testo:nome}}`

### Testo formattato (Rich Text)

Passa un **array di segmenti**, ognuno con `text` e gli attributi desiderati:

```php
$data = [
    'testo:nome' => [
        ['text' => 'Mario', 'bold' => true],
        ['text' => ' Rossi', 'color' => 'FF0000', 'italic' => true],
    ],
];
```

**Attributi disponibili:** `bold`, `italic`, `underline`, `strike`, `doubleStrike`, `superscript`, `subscript`, `font`, `fontSize`, `color`, `highlight`, `shading`, `caps`, `smallCaps`, `outline`, `shadow`, `emboss`, `imprint`, `spacing`.

### Tabelle

Sintassi semplice — la prima riga è trattata come intestazione (grassetto automatico) e viene ripetuta su ogni pagina:

```php
$data = [
    'tabella:prodotti' => [
        ['Prodotto', 'Prezzo', 'Quantità'],   // Riga intestazione
        ['Laptop',   '€999',  '10'],           // Riga dati
        ['Mouse',    '€25',   '50'],
    ],
];
```

> **Nota:** questa è un'alternativa alla sintassi con `config`/`rows` documentata sotto. Entrambe le forme sono supportate.

Le celle possono contenere testo formattato:

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

#### Configurazione tabelle

Passa un array con chiave `config` e `rows` per personalizzare la tabella:

```php
$data = [
    'tabella:dati' => [
        'config' => [
            'repeatHeader'  => true,          // Ripeti intestazione su ogni pagina (default: true; la prima riga di rows diventa intestazione)
            'chunkSize'     => 15,            // Righe per chunk prima del page break
            'style'         => 'TableGrid',   // stile tabella Word (default: TableGrid)
            'align'         => 'center',      // allineamento tabella (left/center/right)
            'width'         => 9000,          // larghezza in twips
            'widthType'     => 'dxa',         // dxa | pct | auto
            'indent'        => 100,           // indentazione dal margine sinistro
            'cellSpacing'   => 10,            // spazio tra celle
            'layout'        => 'fixed',       // fixed | autofit
            'vAlign'        => 'center',      // allineamento verticale celle (top/center/bottom)
            'colWidth'      => 2000,          // larghezza colonne in twips
            'rowHeight'     => 400,           // altezza righe in twips
            'rowHeightRule' => 'atLeast',     // atLeast | exact
            'cantSplit'     => true,          // non spezzare righe tra pagine
            'headerBgColor' => 'D9E2F3',      // colore sfondo intestazione
            'borders' => [                    // bordi personalizzati
                'top'     => ['val' => 'single', 'sz' => '8', 'color' => '000000'],
                'bottom'  => ['val' => 'double', 'sz' => '4', 'color' => 'FF0000'],
                'insideH' => ['val' => 'single', 'sz' => '2', 'color' => 'CCCCCC'],
                'insideV' => ['val' => 'none'],
            ],
            'cellPadding' => [                // margini interni celle
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

#### Attributi cella

Ogni cella può essere una stringa o un array con:

```php
// Testo con colspan
['text' => 'Titolo', 'bold' => true, 'gridSpan' => 3]

// Vertical merge (rowspan)
['text' => 'Prima', 'vMerge' => 'restart']   // prima riga
['text' => 'Seconda', 'vMerge' => true]       // righe successive

// Allineamento orizzontale
['text' => 'Centrato', 'align' => 'center']
```

### Liste puntate

```php
$data = [
    'lista:elementi' => [
        'Primo elemento',
        'Secondo elemento',
        'Terzo elemento',
    ],
];
```

### Liste numerate

```php
$data = [
    'lista_numerata:passaggi' => [
        'Primo passaggio',
        'Secondo passaggio',
        'Terzo passaggio',
    ],
];
```

Gli elementi delle liste possono contenere formattazione:

```php
$data = [
    'lista:elementi' => [
        ['text' => 'Grassetto', 'bold' => true],
        ['text' => 'Rosso', 'color' => 'FF0000'],
        'Testo semplice',
    ],
];
```

### Immagini

```php
// Percorso semplice
$data = ['immagine:logo' => '/path/to/logo.png'];

// Con dimensioni
$data = [
    'immagine:logo' => [
        'path'   => '/path/to/logo.png',
        'width'  => 200,
        'height' => 100,
    ],
];
```

> **Nota:** l'inserimento effettivo dell'immagine nel documento DOCX viene gestito internamente dal convertitore (AbstractConverter). Il placeholder `{{immagine:nome}}` viene riconosciuto e l'immagine viene iniettata come drawing XML con relativa ridimensionamento. Le dimensioni `width` e `height` sono espresse in pixel.

## Convertitori

### LibreOffice (default)

Conversione generalmente fedele all'originale, compatibilmente con le funzionalità supportate da LibreOffice.

```php
use DocxPDF\DocxPDF;
use DocxPDF\LibreOfficeConverter;

// Auto-rilevamento
$docxPDF = new DocxPDF();

// Percorso esplicito (consigliato)
$converter = new LibreOfficeConverter('C:\Program Files\LibreOffice\program\soffice.exe');
$docxPDF = new DocxPDF($converter);

// Cambia convertitore dopo l'istanziazione
$docxPDF->setConverter($converter);
```

## Percorso di output

Di default il PDF viene generato nella stessa cartella del template:

```php
// Stessa cartella del template
$pdfPath = $docxPDF->convert('template.docx', $data);

// Percorso personalizzato
$pdfPath = $docxPDF->convert('template.docx', $data, 'output/rapporto.pdf');
```

## Esempi

| File | Descrizione |
|------|-------------|
| `examples/text-simple.php` | Testo semplice e formattato |
| `examples/table-simple.php` | Tabelle con celle formattate |
| `examples/list-bullet.php` | Liste puntate |
| `examples/list-numbered.php` | Liste numerate |
| `examples/image-simple.php` | Immagini |
| `examples/typed-placeholders.php` | Tipi specificati |
| `examples/multiple-placeholders.php` | Multiplo placeholder |
| `examples/header-footer.php` | Header e footer |
| `examples/nested-data.php` | Dati annidati con formattazione |

## Struttura del progetto

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

## Limitazioni note

- **LibreOffice ignora `<w:tblHeader/>`** — la ripetizione dell'intestazione su più pagine è gestita con page break forzati tra chunk di righe
- **Immagini** — l'inserimento funziona solo con il convertitore LibreOffice
I documenti forniti a LibreOffice dovrebbero essere trattati come input non attendibile e la conversione dovrebbe essere eseguita in un ambiente adeguatamente isolato quando i file provengono da utenti non fidati.

## Troubleshooting

### LibreOffice non trovato

```php
// Specifica il percorso manualmente
$converter = new LibreOfficeConverter('C:\\Program Files\\LibreOffice\\program\\soffice.exe');
$docxPDF = new DocxPDF($converter);
```

### Caratteri speciali non visualizzati

La corretta visualizzazione dei caratteri può dipendere dai font disponibili nel sistema e dalle impostazioni dell'ambiente LibreOffice.
Verifica che i font utilizzati dal documento siano installati e disponibili nell'ambiente in cui viene eseguita la conversione.

## Disclaimer

Questo package viene fornito "così com'è" e "secondo disponibilità", senza garanzie di alcun tipo, nella misura massima consentita dalla legge applicabile.
Il package può contenere bug, limitazioni o problemi di compatibilità e il risultato generato può variare in base al documento DOCX di origine, alla versione di PHP, al sistema operativo, ai font installati, alla versione di LibreOffice e ad altri fattori dell'ambiente in cui viene utilizzato.
L'autore non garantisce che il package o i documenti PDF generati siano privi di errori, completi, accurati, idonei a uno specifico scopo o compatibili con ogni ambiente o documento.
L'utilizzatore è responsabile della verifica e della validazione dei documenti generati prima del loro utilizzo in produzione o per finalità legali, finanziarie, fiscali, normative, contrattuali o comunque critiche.
Nella misura consentita dalla legge applicabile, l'autore non è responsabile per eventuali perdite, danni o conseguenze derivanti dall'utilizzo del package o dall'affidamento sui documenti generati tramite lo stesso.
Nulla di quanto previsto nel presente disclaimer intende escludere o limitare eventuali responsabilità che, ai sensi della legge applicabile, non possono essere legalmente escluse o limitate.

## Contribuire

1. Fork il progetto
2. Crea un branch per la tua feature
3. Commit le tue modifiche
4. Push al branch
5. Crea un Pull Request

## License

MIT — vedi il file `LICENSE.md`.