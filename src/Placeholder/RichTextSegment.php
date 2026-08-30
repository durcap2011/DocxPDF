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
            $font = htmlspecialchars($s['font']);
            $xml .= '<w:rFonts w:ascii="' . $font . '" w:hAnsi="' . $font . '"/>';
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
            $xml .= '<w:color w:val="' . $color . '"/>';
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
            $xml .= '<w:shd w:val="clear" w:color="auto" w:fill="' . $fill . '"/>';
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
                $style[] = 'font-size:' . $s['fontSize'] . 'pt';
            }
            if (isset($s['font'])) {
                $style[] = 'font-family:' . $s['font'];
            }
            if (isset($s['color'])) {
                $style[] = 'color:#' . ltrim($s['color'], '#');
            }
            if (isset($s['shading'])) {
                $style[] = 'background-color:#' . ltrim($s['shading'], '#');
            }
            if (isset($s['highlight'])) {
                $style[] = 'background-color:' . $s['highlight'];
            }
            $openTags[] = '<span style="' . implode(';', $style) . '">';
            $closeTags[] = '</span>';
        }

        if (empty($openTags)) {
            return null;
        }

        return ['open' => implode('', $openTags), 'close' => implode('', array_reverse($closeTags))];
    }
}
