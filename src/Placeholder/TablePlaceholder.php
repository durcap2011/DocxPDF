<?php

declare(strict_types=1);

namespace DocxPDF\Placeholder;

class TablePlaceholder extends AbstractPlaceholder
{
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
            $xml .= '<w:tblStyle w:val="' . htmlspecialchars($config['style']) . '"/>';
        } else {
            $xml .= '<w:tblStyle w:val="TableGrid"/>';
        }

        // Table width
        if (isset($config['width'])) {
            $type = $config['widthType'] ?? 'dxa';
            $xml .= '<w:tblW w:w="' . (int)$config['width'] . '" w:type="' . $type . '"/>';
        } else {
            $xml .= '<w:tblW w:w="0" w:type="auto"/>';
        }

        // Table indentation from left margin
        if (isset($config['indent'])) {
            $xml .= '<w:tblInd w:w="' . (int)$config['indent'] . '" w:type="dxa"/>';
        }

        // Cell spacing
        if (isset($config['cellSpacing'])) {
            $xml .= '<w:tblCellSpacing w:w="' . (int)$config['cellSpacing'] . '" w:type="dxa"/>';
        }

        // Table layout
        if (isset($config['layout'])) {
            $xml .= '<w:tblLayout w:type="' . htmlspecialchars($config['layout']) . '"/>';
        }

        // Table borders
        $borders = $config['borders'] ?? null;
        $xml .= $this->buildBorders($borders);

        // Cell margins (tblCellMar)
        if (isset($config['cellPadding']) && is_array($config['cellPadding'])) {
            $xml .= '<w:tblCellMar>';
            $cp = $config['cellPadding'];
            if (isset($cp['top'])) {
                $xml .= '<w:top w:w="' . (int)$cp['top'] . '" w:type="dxa"/>';
            }
            if (isset($cp['left'])) {
                $xml .= '<w:left w:w="' . (int)$cp['left'] . '" w:type="dxa"/>';
            }
            if (isset($cp['bottom'])) {
                $xml .= '<w:bottom w:w="' . (int)$cp['bottom'] . '" w:type="dxa"/>';
            }
            if (isset($cp['right'])) {
                $xml .= '<w:right w:w="' . (int)$cp['right'] . '" w:type="dxa"/>';
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
            $val = $alignMap[$config['align']] ?? $config['align'];
            $xml .= '<w:jc w:val="' . $val . '"/>';
        }

        // Cell vertical alignment default
        if (isset($config['vAlign'])) {
            $xml .= '<w:vAlign w:val="' . htmlspecialchars($config['vAlign']) . '"/>';
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
                $xml .= '<w:' . $edge . ' w:val="' . htmlspecialchars($b['val'])
                    . '" w:sz="' . (int)$b['sz']
                    . '" w:space="' . (int)$b['space']
                    . '" w:color="' . htmlspecialchars($b['color']) . '"/>';
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
            $rule = $config['rowHeightRule'] ?? 'atLeast';
            $xml .= '<w:trHeight w:val="' . (int)$config['rowHeight'] . '" w:hRule="' . $rule . '"/>';
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
                $xml .= '<w:tcW w:w="' . (int)$config['colWidth'] . '" w:type="dxa"/>';
            }

            // Cell vertical alignment
            if (isset($config['vAlign'])) {
                $xml .= '<w:vAlign w:val="' . htmlspecialchars($config['vAlign']) . '"/>';
            }

            // Header row shading
            if ($isHeader && isset($config['headerBgColor'])) {
                $color = strtoupper(ltrim($config['headerBgColor'], '#'));
                $xml .= '<w:shd w:val="clear" w:color="auto" w:fill="' . $color . '"/>';
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
                $xml .= '<w:gridSpan w:val="' . (int)$cell['gridSpan'] . '"/>';
            }

            $xml .= '</w:tcPr>';

            $xml .= '<w:p>';

            // Paragraph alignment from cell
            if (isset($cell['align'])) {
                $xml .= '<w:pPr><w:jc w:val="' . htmlspecialchars($cell['align']) . '"/></w:pPr>';
            }

            $cellSegments = $this->normalizeCell($cell);
            if ($cellSegments !== null) {
                $xml .= RichTextSegment::toXmlString($cellSegments);
            } else {
                $xml .= '<w:r>';
                if ($isHeader) {
                    $xml .= '<w:rPr><w:b/></w:rPr>';
                }
                $xml .= '<w:t>' . htmlspecialchars((string)$cell) . '</w:t>';
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
