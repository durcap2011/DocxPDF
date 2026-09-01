<?php

declare(strict_types=1);

namespace durcap2011\DocxPDF\Placeholder;

class TablePlaceholder extends AbstractPlaceholder
{
    /**
     * Valida e sanitizza un valore attributo XML.
     *
     * @param string $value Valore da validare.
     * @param int $maxLen Lunghezza massima.
     * @return string Valore sanitizzato.
     */
    private static function sanitizeXmlAttribute(string $value, int $maxLen = 100): string
    {
        // Rimuovi caratteri null byte
        $value = str_replace("\0", '', $value);

        // Rimuovi caratteri di controllo (tranne tab, newline, CR)
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value);

        // Escapa i caratteri XML base
        $value = str_replace(
            ['&', '<', '>', "'", '"'],
            ['&amp;', '&lt;', '&gt;', '&apos;', '&quot;'],
            $value
        );

        // Limita la lunghezza
        $value = substr($value, 0, $maxLen);

        return trim($value);
    }

    /**
     * Valida che un colore sia un valore hex valido.
     *
     * @param string $color Colore da validare.
     * @return string Colore validato o vuoto se invalido.
     */
    private static function validateColor(string $color): string
    {
        // Rimuovi #
        $color = ltrim($color, '#');

        // Valida formato hex (3, 6, o 8 caratteri)
        if (preg_match('/^[0-9a-fA-F]{3,8}$/', $color)) {
            return strtoupper($color);
        }

        return '';
    }

    /**
     * Valida che un valore sia un intero positivo.
     *
     * @param mixed $value Valore da validare.
     * @param int $min Valore minimo.
     * @param int $max Valore massimo.
     * @return int Valore validato.
     */
    private static function validateInt($value, int $min = 0, int $max = 100000): int
    {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        if ($value === false || $value < $min || $value > $max) {
            return $min;
        }
        return $value;
    }

    public function getType(): string
    {
        return 'table';
    }

    public function toXmlString(): string
    {
        if (!is_array($this->value) || empty($this->value)) {
            return '';
        }

        [$rows, $config] = $this->parseValue();
        $repeatHeader = $config['repeatHeader'] ?? true;
        $chunkSize = $config['chunkSize'] ?? 20;

        $headerRow = null;
        if ($repeatHeader && count($rows) > 1) {
            $headerRow = $rows[0];
            $rows = array_values(array_slice($rows, 1));
        }

        if ($headerRow === null || count($rows) <= $chunkSize) {
            return $this->buildTable($rows, $headerRow, $config);
        }

        $xml = '';
        $chunks = array_chunk($rows, $chunkSize);
        foreach ($chunks as $chunkIndex => $chunk) {
            if ($chunkIndex > 0) {
                $xml .= '<w:p>'
                    . '<w:r><w:br w:type="page"/></w:r>'
                    . '</w:p>';
            }
            $xml .= $this->buildTable($chunk, $headerRow, $config);
        }

        return $xml;
    }

    public function toHtmlString(): string
    {
        if (!is_array($this->value) || empty($this->value)) {
            return '';
        }

        [$rows, $config] = $this->parseValue();

        $style = [];
        if (isset($config['align'])) {
            $style[] = 'margin: 0 auto';
        }
        $html = '<table border="1" cellpadding="5" cellspacing="0"';
        if (!empty($style)) {
            $html .= ' style="' . implode(';', $style) . '"';
        }
        $html .= '>';

        foreach ($rows as $rowIndex => $row) {
            $html .= '<tr>';
            if (is_array($row)) {
                foreach ($row as $cell) {
                    $tag = $rowIndex === 0 ? 'th' : 'td';
                    $cellSegments = $this->normalizeCell($cell);
                    if ($cellSegments !== null) {
                        $html .= "<$tag>" . RichTextSegment::toHtmlString($cellSegments) . "</$tag>";
                    } else {
                        $html .= "<$tag>" . htmlspecialchars((string)$cell) . "</$tag>";
                    }
                }
            }
            $html .= '</tr>';
        }
        $html .= '</table>';
        return $html;
    }

    private function buildTable(array $rows, ?array $headerRow, array $config): string
    {
        $xml = '<w:tbl>';
        $xml .= '<w:tblPr>';
        $xml .= $this->buildTblPr($config);
        $xml .= '</w:tblPr>';

        if ($headerRow !== null) {
            $xml .= $this->buildRow($headerRow, true, $config);
        }

        foreach ($rows as $row) {
            $xml .= $this->buildRow($row, false, $config);
        }

        $xml .= '</w:tbl>';
        return $xml;
    }

    private function buildTblPr(array $config): string
    {
        $xml = '';

        if (isset($config['style'])) {
            $style = self::sanitizeXmlAttribute((string)$config['style']);
            if ($style !== '') {
                $xml .= '<w:tblStyle w:val="' . $style . '"/>';
            } else {
                $xml .= '<w:tblStyle w:val="TableGrid"/>';
            }
        } else {
            $xml .= '<w:tblStyle w:val="TableGrid"/>';
        }

        // Table width
        if (isset($config['width'])) {
            $width = self::validateInt($config['width'], 0, 100000);
            $type = self::sanitizeXmlAttribute($config['widthType'] ?? 'dxa', 10);
            $xml .= '<w:tblW w:w="' . $width . '" w:type="' . $type . '"/>';
        } else {
            $xml .= '<w:tblW w:w="0" w:type="auto"/>';
        }

        // Table indentation from left margin
        if (isset($config['indent'])) {
            $indent = self::validateInt($config['indent'], 0, 100000);
            $xml .= '<w:tblInd w:w="' . $indent . '" w:type="dxa"/>';
        }

        // Cell spacing
        if (isset($config['cellSpacing'])) {
            $spacing = self::validateInt($config['cellSpacing'], 0, 1000);
            $xml .= '<w:tblCellSpacing w:w="' . $spacing . '" w:type="dxa"/>';
        }

        // Table layout
        if (isset($config['layout'])) {
            $layout = self::sanitizeXmlAttribute((string)$config['layout'], 20);
            // Whitelist: solo valori consentiti
            $allowedLayouts = ['fixed', 'autofit'];
            if (in_array($layout, $allowedLayouts, true)) {
                $xml .= '<w:tblLayout w:type="' . $layout . '"/>';
            }
        }

        // Table borders
        $borders = $config['borders'] ?? null;
        $xml .= $this->buildBorders($borders);

        // Cell margins (tblCellMar)
        if (isset($config['cellPadding']) && is_array($config['cellPadding'])) {
            $xml .= '<w:tblCellMar>';
            $cp = $config['cellPadding'];
            if (isset($cp['top'])) {
                $xml .= '<w:top w:w="' . self::validateInt($cp['top'], 0, 10000) . '" w:type="dxa"/>';
            }
            if (isset($cp['left'])) {
                $xml .= '<w:left w:w="' . self::validateInt($cp['left'], 0, 10000) . '" w:type="dxa"/>';
            }
            if (isset($cp['bottom'])) {
                $xml .= '<w:bottom w:w="' . self::validateInt($cp['bottom'], 0, 10000) . '" w:type="dxa"/>';
            }
            if (isset($cp['right'])) {
                $xml .= '<w:right w:w="' . self::validateInt($cp['right'], 0, 10000) . '" w:type="dxa"/>';
            }
            $xml .= '</w:tblCellMar>';
        }

        // Horizontal alignment
        if (isset($config['align'])) {
            $alignMap = [
                'left' => 'start',
                'center' => 'center',
                'right' => 'end',
                'start' => 'start',
                'end' => 'end',
            ];
            $align = self::sanitizeXmlAttribute((string)$config['align'], 10);
            $val = $alignMap[$align] ?? 'start';
            $xml .= '<w:jc w:val="' . $val . '"/>';
        }

        // Cell vertical alignment default
        if (isset($config['vAlign'])) {
            $vAlign = self::sanitizeXmlAttribute((string)$config['vAlign'], 10);
            $allowedVAligns = ['top', 'center', 'bottom'];
            if (in_array($vAlign, $allowedVAligns, true)) {
                $xml .= '<w:vAlign w:val="' . $vAlign . '"/>';
            }
        }

        // Table look
        if (!empty($config['repeatHeader'])) {
            $xml .= '<w:tblLook w:firstRow="1" w:lastRow="0" w:firstColumn="0" w:lastColumn="0" w:noHBand="0" w:noVBand="0"/>';
        }

        return $xml;
    }

    private function buildBorders(?array $borders): string
    {
        $defaults = [
            'val' => 'single',
            'sz' => '4',
            'space' => '0',
            'color' => 'auto',
        ];

        // Whitelist per i valori di bordo consentiti
        $allowedBorderVals = [
            'single', 'double', 'thick', 'thinner', 'thicker',
            'none', 'hidden', 'dot', 'dash', 'dashDot',
            'dashDotDot', 'triple', 'thinThick', 'thickThin',
            'thinThickThin', 'thickThinThick', 'none2',
        ];

        if ($borders === null) {
            // Default borders: all single
            $xml = '<w:tblBorders>';
            foreach (['top', 'left', 'bottom', 'right', 'insideH', 'insideV'] as $edge) {
                $xml .= '<w:' . $edge . ' w:val="' . $defaults['val']
                    . '" w:sz="' . $defaults['sz']
                    . '" w:space="' . $defaults['space']
                    . '" w:color="' . $defaults['color'] . '"/>';
            }
            $xml .= '</w:tblBorders>';
            return $xml;
        }

        // Per-edge custom borders
        $xml = '<w:tblBorders>';
        foreach (['top', 'left', 'bottom', 'right', 'insideH', 'insideV'] as $edge) {
            if (isset($borders[$edge])) {
                $b = array_merge($defaults, (array)$borders[$edge]);

                // Valida e sanitizza i valori
                $val = self::sanitizeXmlAttribute((string)$b['val'], 20);
                if (!in_array($val, $allowedBorderVals, true)) {
                    $val = $defaults['val'];
                }

                $sz = self::validateInt($b['sz'], 0, 96);
                $space = self::validateInt($b['space'], 0, 31);
                $color = self::validateColor((string)$b['color']);
                if ($color === '') {
                    $color = $defaults['color'];
                }

                $xml .= '<w:' . $edge . ' w:val="' . $val
                    . '" w:sz="' . $sz
                    . '" w:space="' . $space
                    . '" w:color="' . $color . '"/>';
            } else {
                $xml .= '<w:' . $edge . ' w:val="' . $defaults['val']
                    . '" w:sz="' . $defaults['sz']
                    . '" w:space="' . $defaults['space']
                    . '" w:color="' . $defaults['color'] . '"/>';
            }
        }
        $xml .= '</w:tblBorders>';
        return $xml;
    }

    private function buildRow(array $row, bool $isHeader, array $config): string
    {
        $xml = '<w:tr>';

        // Row properties
        $xml .= '<w:trPr>';
        if ($isHeader) {
            $xml .= '<w:tblHeader/>';
        }
        if (isset($config['rowHeight'])) {
            $rowHeight = self::validateInt($config['rowHeight'], 0, 100000);
            $rule = self::sanitizeXmlAttribute($config['rowHeightRule'] ?? 'atLeast', 10);
            $allowedRules = ['atLeast', 'exact', 'auto'];
            if (!in_array($rule, $allowedRules, true)) {
                $rule = 'atLeast';
            }
            $xml .= '<w:trHeight w:val="' . $rowHeight . '" w:hRule="' . $rule . '"/>';
        }
        if (!empty($config['cantSplit'])) {
            $xml .= '<w:cantSplit/>';
        }
        $xml .= '</w:trPr>';

        foreach ($row as $cell) {
            $xml .= '<w:tc>';
            $xml .= '<w:tcPr>';

            if ($isHeader) {
                $xml .= '<w:tblHeader/>';
            }

            // Cell width
            if (isset($config['colWidth'])) {
                $colWidth = self::validateInt($config['colWidth'], 0, 100000);
                $xml .= '<w:tcW w:w="' . $colWidth . '" w:type="dxa"/>';
            }

            // Cell vertical alignment
            if (isset($config['vAlign'])) {
                $vAlign = self::sanitizeXmlAttribute((string)$config['vAlign'], 10);
                $allowedVAligns = ['top', 'center', 'bottom'];
                if (in_array($vAlign, $allowedVAligns, true)) {
                    $xml .= '<w:vAlign w:val="' . $vAlign . '"/>';
                }
            }

            // Header row shading
            if ($isHeader && isset($config['headerBgColor'])) {
                $color = self::validateColor((string)$config['headerBgColor']);
                if ($color !== '') {
                    $xml .= '<w:shd w:val="clear" w:color="auto" w:fill="' . $color . '"/>';
                }
            }

            // Cell vertical merge
            if (isset($cell['vMerge'])) {
                if ($cell['vMerge'] === 'restart' || $cell['vMerge'] === true) {
                    $xml .= '<w:vMerge w:val="restart"/>';
                } else {
                    $xml .= '<w:vMerge/>';
                }
            }

            // Cell grid span
            if (isset($cell['gridSpan'])) {
                $gridSpan = self::validateInt($cell['gridSpan'], 1, 64);
                $xml .= '<w:gridSpan w:val="' . $gridSpan . '"/>';
            }

            $xml .= '</w:tcPr>';

            $xml .= '<w:p>';

            // Paragraph alignment from cell
            if (isset($cell['align'])) {
                $cellAlign = self::sanitizeXmlAttribute((string)$cell['align'], 10);
                $allowedAligns = ['left', 'center', 'right', 'start', 'end', 'both'];
                if (in_array($cellAlign, $allowedAligns, true)) {
                    $xml .= '<w:pPr><w:jc w:val="' . $cellAlign . '"/></w:pPr>';
                }
            }

            $cellSegments = $this->normalizeCell($cell);
            if ($cellSegments !== null) {
                $xml .= RichTextSegment::toXmlString($cellSegments);
            } else {
                $xml .= '<w:r>';
                if ($isHeader) {
                    $xml .= '<w:rPr><w:b/></w:rPr>';
                }
                $xml .= '<w:t>' . htmlspecialchars((string)$cell, ENT_QUOTES) . '</w:t>';
                $xml .= '</w:r>';
            }
            $xml .= '</w:p>';
            $xml .= '</w:tc>';
        }
        $xml .= '</w:tr>';
        return $xml;
    }

    private function parseValue(): array
    {
        if (isset($this->value['config']) && is_array($this->value['config'])) {
            $config = $this->value['config'];
            $rows = $this->value['rows'] ?? [];
            return [$rows, $config];
        }

        return [$this->value, []];
    }

    private static function normalizeCell($cell): ?array
    {
        if (!is_array($cell)) {
            return null;
        }

        if (isset($cell['text'])) {
            return [$cell];
        }

        if (isset($cell[0]) && is_array($cell[0]) && isset($cell[0]['text'])) {
            return $cell;
        }

        return null;
    }
}
