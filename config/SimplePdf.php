<?php

declare(strict_types=1);

class SimplePdf
{
    public static function download(string $filename, array $lines): never
    {
        // Binary PDF output must start at byte 0 with %PDF and no warnings/notices.
        @ini_set('display_errors', '0');
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        if (class_exists('Dompdf\\Dompdf')) {
            self::downloadWithDompdf($filename, $lines);
        }

        self::downloadWithLegacyWriter($filename, $lines);
    }

    private static function downloadWithDompdf(string $filename, array $lines): never
    {
        $safeFilename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'document.pdf';

        $normalizedLines = array_map([
            self::class,
            'normalizeTextLine',
        ], $lines);
        $normalizedLines = array_values(array_filter($normalizedLines, static fn (string $line): bool => $line !== ''));

        $title = $normalizedLines !== [] ? array_shift($normalizedLines) : 'Document ITAM';
        $bodyHtml = self::renderStyledHtmlBody($normalizedLines);
        $generatedAt = date('d/m/Y H:i');

        $html = '<!doctype html><html><head><meta charset="UTF-8"><style>'
            . '@page { margin: 24px; }'
            . 'body{font-family:DejaVu Sans,Arial,sans-serif;font-size:11px;line-height:1.45;color:#3a3a3a;margin:0;background:#f7f7f7;}'
            . '.sheet{border:1px solid #d9d9d9;border-radius:8px;padding:0;overflow:hidden;background:#ffffff;}'
            . '.head{background:#b3001b;color:#fff;padding:14px 16px;}'
            . '.head-title{margin:0;font-size:16px;font-weight:700;}'
            . '.head-sub{margin:4px 0 0 0;font-size:10px;opacity:.95;}'
            . '.content{padding:14px 16px 10px 16px;background:#fff;}'
            . '.section{margin:12px 0 6px 0;padding:6px 8px;background:#efefef;border-left:4px solid #b3001b;font-weight:700;font-size:11px;color:#4a4a4a;}'
            . '.row{margin:0 0 6px 0;}'
            . '.kv{margin:0 0 6px 0;padding:6px 8px;background:#fafafa;border:1px solid #e0e0e0;border-radius:4px;}'
            . '.kv-label{font-weight:700;color:#595959;}'
            . '.kv-value{color:#2f2f2f;}'
            . '.grid{margin:0 0 8px 0;}'
            . '.chip{display:inline-block;margin:0 6px 6px 0;padding:5px 8px;border:1px solid #d3d3d3;border-radius:12px;background:#f2f2f2;font-size:10px;color:#3f3f3f;}'
            . '.divider{height:1px;background:#dcdcdc;margin:10px 0;}'
            . '</style></head><body>'
            . '<div class="sheet">'
            . '<div class="head">'
            . '<h1 class="head-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>'
            . '<p class="head-sub">EquityBCDC ITAM | Genere le ' . htmlspecialchars($generatedAt, ENT_QUOTES, 'UTF-8') . '</p>'
            . '</div>'
            . '<div class="content">'
            . $bodyHtml
            . '</div>'
            . '</div>'
            . '</body></html>';

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdf = $dompdf->output();

        if (headers_sent()) {
            http_response_code(500);
            exit('Impossible de generer le PDF: entetes deja envoyes.');
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . (string) strlen($pdf));

        echo $pdf;
        exit;
    }

    private static function renderStyledHtmlBody(array $lines): string
    {
        $chunks = [];

        foreach ($lines as $line) {
            $escaped = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');

            if (preg_match('/^[-=]{3,}$/', $line) === 1) {
                $chunks[] = '<div class="divider"></div>';
                continue;
            }

            if (preg_match('/^---\s*(.+?)\s*---$/', $line, $matches) === 1) {
                $chunks[] = '<div class="section">' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '</div>';
                continue;
            }

            if (str_contains($line, ' | ')) {
                $parts = array_values(array_filter(array_map('trim', explode('|', $line)), static fn (string $p): bool => $p !== ''));
                if ($parts !== []) {
                    $chips = array_map(static fn (string $p): string => '<span class="chip">' . htmlspecialchars($p, ENT_QUOTES, 'UTF-8') . '</span>', $parts);
                    $chunks[] = '<div class="grid">' . implode('', $chips) . '</div>';
                    continue;
                }
            }

            if (preg_match('/^([^:]{2,60}):\s*(.+)$/', $line, $matches) === 1) {
                $chunks[] = '<div class="kv"><span class="kv-label">'
                    . htmlspecialchars(trim($matches[1]), ENT_QUOTES, 'UTF-8')
                    . ':</span> <span class="kv-value">'
                    . htmlspecialchars(trim($matches[2]), ENT_QUOTES, 'UTF-8')
                    . '</span></div>';
                continue;
            }

            $chunks[] = '<p class="row">' . $escaped . '</p>';
        }

        return implode('', $chunks);
    }

    private static function normalizeTextLine(mixed $line): string
    {
        $text = (string) $line;
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', ' ', $text) ?? '';
        $text = preg_replace('/\s+/', ' ', $text) ?? '';
        return trim($text);
    }

    private static function downloadWithLegacyWriter(string $filename, array $lines): never
    {
        $content = self::buildContent($lines);
        $stream = "BT\n/F1 12 Tf\n50 790 Td\n" . $content . "ET\n";

        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Count 1 /Kids [3 0 R] >>';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n" . $obj . "\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $pdf .= 'xref\n0 ' . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= 'trailer\n<< /Size ' . (count($objects) + 1) . ' /Root 1 0 R >>\n';
        $pdf .= 'startxref\n' . $xrefPos . "\n%%EOF";

        $safeFilename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'document.pdf';

        if (headers_sent()) {
            http_response_code(500);
            exit('Impossible de generer le PDF: entetes deja envoyes.');
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . (string) strlen($pdf));

        echo $pdf;
        exit;
    }

    private static function buildContent(array $lines): string
    {
        $commands = [];
        $first = true;

        foreach ($lines as $line) {
            $safe = self::pdfEscape(self::toLatin((string) $line));
            if ($first) {
                $commands[] = '(' . $safe . ') Tj';
                $first = false;
                continue;
            }

            $commands[] = '0 -18 Td (' . $safe . ') Tj';
        }

        return implode("\n", $commands) . "\n";
    }

    private static function pdfEscape(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private static function toLatin(string $text): string
    {
        if (function_exists('iconv')) {
            $out = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
            if ($out !== false) {
                return $out;
            }
        }

        return preg_replace('/[^\x20-\x7E]/', '', $text) ?? '';
    }
}

