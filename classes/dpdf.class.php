<?php
/**
 * @package dotproject
 * @subpackage utilities
 *
 * DotPdf — Dompdf-backed drop-in replacement for the Cezpdf subset used in
 * dotProject's table-based PDF reports.
 *
 * API coverage:
 *   __construct($paper, $orientation)
 *   ezSetCmMargins($top, $right, $bottom, $left)
 *   selectFont($afmPath)          — bold/regular tracking only
 *   ezText($text, $fontSize)
 *   ezTable($data, $columns, $title, $options)
 *   ezStream($filename)           — stream PDF to browser
 *   ezOutput()                    — return PDF binary string
 *
 * All text is re-encoded to UTF-8 on the way in so callers that use
 * safe_utf8_decode() (which produces ISO-8859-1) still render correctly.
 */

if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}

require_once DP_BASE_DIR . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class DotPdf
{
    private $paper   = 'A4';
    private $orient  = 'portrait';
    private $margins = [28, 56, 42, 42]; // pt: top, right, bottom, left (≈1cm,2cm,1.5cm,1.5cm)
    private $bold    = false;
    private $html    = '';

    function __construct($paper = 'A4', $orientation = 'portrait') {
        $this->paper  = strtoupper($paper ?: 'A4');
        $this->orient = (strtolower((string)$orientation) === 'landscape') ? 'landscape' : 'portrait';
    }

    /**
     * Set page margins in centimetres.
     * ezpdf convention: top, right, bottom, left.
     */
    function ezSetCmMargins($top, $right, $bottom, $left) {
        $cm2pt = 28.35;
        $this->margins = [
            (int) round($top    * $cm2pt),
            (int) round($right  * $cm2pt),
            (int) round($bottom * $cm2pt),
            (int) round($left   * $cm2pt),
        ];
    }

    /**
     * Track bold/regular from the AFM font path name.
     * Actual font selection is handled by Dompdf's built-in font engine.
     */
    function selectFont($fontPath) {
        $this->bold = (strpos((string)$fontPath, 'Bold') !== false
                    || strpos((string)$fontPath, 'bold') !== false);
    }

    /**
     * Append a text block to the document.
     */
    function ezText($text, $fontSize = 10) {
        $fontSize = max(6, (int)($fontSize ?: 10));
        $weight   = $this->bold ? 'bold' : 'normal';
        $safe     = nl2br(htmlspecialchars($this->_utf8((string)$text), ENT_QUOTES, 'UTF-8'));
        $this->html .= '<p style="font-size:' . $fontSize . 'pt;font-weight:' . $weight
                     . ';margin:0 0 3pt;">' . $safe . '</p>' . "\n";
    }

    /**
     * Append a data table.
     *
     * $columns may contain HTML (e.g. "<b>Name</b>"); $data cells are plain text.
     * Recognised $options keys: fontSize, showLines, showHeadings, cols (per-column
     * width + justification), shaded.
     */
    function ezTable($data, $columns = null, $title = null, $options = []) {
        $fontSize  = isset($options['fontSize'])     ? (int)$options['fontSize']     : 9;
        $showLines = isset($options['showLines'])    ? (int)$options['showLines']    : 2;
        $showHdrs  = isset($options['showHeadings']) ? (int)$options['showHeadings'] : 1;
        $colDefs   = isset($options['cols'])         ? (array)$options['cols']       : [];
        $shaded    = isset($options['shaded'])       ? (int)$options['shaded']       : 0;

        $border    = $showLines >= 1 ? '1px solid #aaa' : 'none';

        $html = '<table style="width:100%;border-collapse:collapse;font-size:'
              . $fontSize . 'pt;margin:6pt 0;">' . "\n";

        if (!empty($title)) {
            $html .= '<caption style="text-align:left;font-weight:bold;padding:0 0 3pt;">'
                   . htmlspecialchars($this->_utf8((string)$title), ENT_QUOTES, 'UTF-8')
                   . '</caption>' . "\n";
        }

        if ($showHdrs && !empty($columns) && is_array($columns)) {
            $html .= '<thead><tr>';
            foreach ($columns as $i => $col) {
                $style = 'background:#dde;padding:3pt 4pt;border:' . $border . ';';
                $style .= $this->_colStyle($colDefs, $i);
                $html .= '<th style="' . $style . '">' . $this->_utf8((string)$col) . '</th>';
            }
            $html .= '</tr></thead>' . "\n";
        }

        $html .= '<tbody>';
        foreach ((array)$data as $ri => $row) {
            $bg = ($shaded && ($ri % 2 === 1)) ? 'background:#f5f5f5;' : '';
            $html .= '<tr style="' . $bg . '">';
            if (is_array($row)) {
                foreach ($row as $ci => $cell) {
                    $style = 'padding:2pt 4pt;border:' . $border . ';'
                           . $this->_colStyle($colDefs, $ci);
                    $html .= '<td style="' . $style . '">'
                           . htmlspecialchars($this->_utf8((string)$cell), ENT_QUOTES, 'UTF-8')
                           . '</td>';
                }
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>' . "\n";
        $this->html .= $html;
    }

    /**
     * Render and stream the PDF to the browser.
     */
    function ezStream($filename = 'document.pdf') {
        $this->_dompdf()->stream($filename, ['Attachment' => 0]);
    }

    /**
     * Render and return the PDF as a binary string.
     */
    function ezOutput() {
        return $this->_dompdf()->output();
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function _dompdf() {
        $opts = new Options();
        $opts->set('defaultFont', 'Helvetica');
        $opts->set('isHtml5ParserEnabled', true);
        $opts->set('isRemoteEnabled', false);

        $dom = new Dompdf($opts);

        list($mt, $mr, $mb, $ml) = $this->margins;
        $doc = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>'
             . '@page{margin:' . $mt . 'pt ' . $mr . 'pt ' . $mb . 'pt ' . $ml . 'pt;}'
             . 'body{font-family:Helvetica,Arial,sans-serif;font-size:10pt;margin:0;padding:0;}'
             . 'p{margin:0;}table{width:100%;border-collapse:collapse;}'
             . '</style></head><body>'
             . $this->html
             . '</body></html>';

        $dom->loadHtml($doc);
        $dom->setPaper($this->paper, $this->orient);
        $dom->render();
        return $dom;
    }

    private function _colStyle(array $colDefs, $i) {
        $style = '';
        $def   = $colDefs[$i] ?? [];
        if (!empty($def['width'])) {
            $style .= 'width:' . (int)$def['width'] . 'px;';
        }
        if (!empty($def['justification'])) {
            $style .= 'text-align:' . htmlspecialchars($def['justification']) . ';';
        }
        return $style;
    }

    /**
     * Ensure a string is valid UTF-8.
     * Callers that use safe_utf8_decode() produce ISO-8859-1; re-encode here.
     */
    private function _utf8($str) {
        if (mb_check_encoding($str, 'UTF-8')) {
            return $str;
        }
        return mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
    }
}
