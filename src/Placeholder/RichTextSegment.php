<?php

declare(strict_types=1);

namespace DocxPDF\Placeholder;

class RichTextSegment
{
    public static function isSegmentArray($value): bool
    {
        return is_array($value) && isset($value[0]) && is_array($value[0]) && array_key_exists('text', $value[0]);
    }

    public static function toXmlString(array $segments): string
    {
        $xml = '';
        foreach ($segments as $segment) {
            $text = $segment['text'] ?? '';
            $rPr = self::buildRPr($segment);
            $xml .= '<w:r>';
            if ($rPr !== '') {
                $xml .= '<w:rPr>' . $rPr . '</w:rPr>';
            }
            $xml .= '<w:t xml:space="preserve">' . htmlspecialchars($text) . '</w:t>';
            $xml .= '</w:r>';
        }
        return $xml;
    }

    public static function toHtmlString(array $segments): string
    {
        $html = '';
        foreach ($segments as $segment) {
            $text = htmlspecialchars($segment['text'] ?? '');
            $tag = self::buildHtmlTag($segment);
            if ($tag !== null) {
                $html .= $tag['open'] . $text . $tag['close'];
            } else {
                $html .= $text;
            }
        }
        return $html;
    }

    private static function buildRPr(array $s): string
    {
        $xml = '';

        if (!empty($s['font'])) {
            $font = (string)$s['font'];
            // Valida che il nome font contenga solo caratteri sicuri
            if (preg_match('/^[a-zA-Z0-9\s\-\'\.]+$/', $font) && strlen($font) <= 100) {
                $font = htmlspecialchars($font, ENT_QUOTES);
                $xml .= '<w:rFonts w:ascii="' . $font . '" w:hAnsi="' . $font . '"/>';
            }
        }

        if (!empty($s['bold'])) {
            $xml .= '<w:b/>';
        }

        if (!empty($s['doubleStrike'])) {
            $xml .= '<w:dstrike/>';
        } elseif (!empty($s['strike'])) {
            $xml .= '<w:strike/>';
        }

        if (!empty($s['italic'])) {
            $xml .= '<w:i/>';
        }

        if (isset($s['caps']) && $s['caps']) {
            $xml .= '<w:caps/>';
        } elseif (isset($s['smallCaps']) && $s['smallCaps']) {
            $xml .= '<w:smallCaps/>';
        }

        if (isset($s['spacing'])) {
            $xml .= '<w:spacing w:val="' . (int)$s['spacing'] . '"/>';
        }

        if (isset($s['fontSize'])) {
            $halfPoints = (int)$s['fontSize'] * 2;
            $xml .= '<w:sz w:val="' . $halfPoints . '"/>';
            $xml .= '<w:szCs w:val="' . $halfPoints . '"/>';
        }

        if (!empty($s['color'])) {
            $color = strtoupper(ltrim($s['color'], '#'));
            // Valida che il colore sia alfanumerico (hex) per prevenire XML injection
            if (preg_match('/^[0-9A-F]{3,8}$/', $color)) {
                $xml .= '<w:color w:val="' . $color . '"/>';
            }
        }

        if (isset($s['underline'])) {
            $val = is_string($s['underline']) ? $s['underline'] : 'single';
            $xml .= '<w:u w:val="' . htmlspecialchars($val) . '"/>';
        }

        if (isset($s['highlight'])) {
            $xml .= '<w:highlight w:val="' . htmlspecialchars($s['highlight']) . '"/>';
        }

        if (isset($s['shading'])) {
            $fill = strtoupper(ltrim($s['shading'], '#'));
            // Valida che il colore sia alfanumerico (hex) per prevenire XML injection
            if (preg_match('/^[0-9A-F]{3,8}$/', $fill)) {
                $xml .= '<w:shd w:val="clear" w:color="auto" w:fill="' . $fill . '"/>';
            }
        }

        if (!empty($s['outline'])) {
            $xml .= '<w:outline/>';
        }

        if (!empty($s['shadow'])) {
            $xml .= '<w:shadow/>';
        }

        if (!empty($s['emboss'])) {
            $xml .= '<w:emboss/>';
        }

        if (!empty($s['imprint'])) {
            $xml .= '<w:imprint/>';
        }

        if (!empty($s['superscript'])) {
            $xml .= '<w:vertAlign w:val="superscript"/>';
        } elseif (!empty($s['subscript'])) {
            $xml .= '<w:vertAlign w:val="subscript"/>';
        }

        return $xml;
    }

    /**
     * Valida e sanitizza un valore CSS per prevenire injection.
     *
     * @param string $value Valore CSS da sanitizzare.
     * @return string Valore sanitizzato.
     */
    private static function sanitizeCssValue(string $value): string
    {
        // Rimuovi caratteri null byte
        $value = str_replace("\0", '', $value);

        // Rimuovi parentesi graffe (possibili injection CSS)
        $value = str_replace(['{', '}'], '', $value);

        // Rimuovi backslash
        $value = str_replace('\\', '', $value);

        // Rimuovi caratteri di controllo
        $value = preg_replace('/[\x00-\x1f\x7f]/', '', $value);

        // Rimuovi event handler e espressioni JavaScript
        $value = preg_replace('/(expression|javascript|vbscript|data):/i', '', $value);

        // Limita la lunghezza
        $value = substr($value, 0, 100);

        return trim($value);
    }

    private static function buildHtmlTag(array $s): ?array
    {
        if (!empty($s['superscript'])) {
            return ['open' => '<sup>', 'close' => '</sup>'];
        }
        if (!empty($s['subscript'])) {
            return ['open' => '<sub>', 'close' => '</sub>'];
        }

        $openTags = [];
        $closeTags = [];

        if (!empty($s['bold'])) {
            $openTags[] = '<b>';
            $closeTags[] = '</b>';
        }
        if (!empty($s['italic'])) {
            $openTags[] = '<i>';
            $closeTags[] = '</i>';
        }
        if (!empty($s['strike'])) {
            $openTags[] = '<s>';
            $closeTags[] = '</s>';
        }
        if (isset($s['underline'])) {
            $openTags[] = '<u>';
            $closeTags[] = '</u>';
        }
        if (isset($s['fontSize']) || isset($s['font']) || isset($s['color']) || isset($s['shading']) || isset($s['highlight'])) {
            $style = [];
            if (isset($s['fontSize'])) {
                // Valida che fontSize sia numerico
                $fontSize = filter_var($s['fontSize'], FILTER_VALIDATE_INT);
                if ($fontSize !== false && $fontSize > 0 && $fontSize <= 200) {
                    $style[] = 'font-size:' . $fontSize . 'pt';
                }
            }
            if (isset($s['font'])) {
                // Sanitizza font name
                $font = self::sanitizeCssValue((string)$s['font']);
                if ($font !== '' && preg_match('/^[a-zA-Z0-9\s\-\',.]+$/', $font)) {
                    $style[] = 'font-family:' . $font;
                }
            }
            if (isset($s['color'])) {
                // Valida colore hex
                $color = ltrim((string)$s['color'], '#');
                if (preg_match('/^[0-9a-fA-F]{3,8}$/', $color)) {
                    $style[] = 'color:#' . $color;
                }
            }
            if (isset($s['shading'])) {
                // Valida colore hex
                $shading = ltrim((string)$s['shading'], '#');
                if (preg_match('/^[0-9a-fA-F]{3,8}$/', $shading)) {
                    $style[] = 'background-color:#' . $shading;
                }
            }
            if (isset($s['highlight'])) {
                // Sanitizza highlight value
                $highlight = self::sanitizeCssValue((string)$s['highlight']);
                if ($highlight !== '' && preg_match('/^[a-zA-Z0-9\-]+$/', $highlight)) {
                    $style[] = 'background-color:' . $highlight;
                }
            }
            if (!empty($style)) {
                $openTags[] = '<span style="' . htmlspecialchars(implode(';', $style), ENT_QUOTES) . '">';
                $closeTags[] = '</span>';
            }
        }

        if (empty($openTags)) {
            return null;
        }

        return ['open' => implode('', $openTags), 'close' => implode('', array_reverse($closeTags))];
    }
}
