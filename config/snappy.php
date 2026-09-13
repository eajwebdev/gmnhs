<?php

/**
 * Locate wkhtmltopdf: WKHTMLTOPDF_BINARY wins, otherwise the standard install
 * locations are tried, then a plain "wkhtmltopdf" on PATH. Paths with spaces
 * are quoted because Snappy passes the binary straight to the shell.
 */
$wkhtmltopdf = (function () {
    $configured = trim((string) env('WKHTMLTOPDF_BINARY', ''), " \"'");

    $candidates = array_filter([
        $configured,
        'C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltopdf.exe',
        'C:\\Program Files (x86)\\wkhtmltopdf\\bin\\wkhtmltopdf.exe',
        '/usr/local/bin/wkhtmltopdf',
        '/usr/bin/wkhtmltopdf',
    ]);

    foreach ($candidates as $path) {
        if (is_file($path)) {
            return str_contains($path, ' ') ? '"'.$path.'"' : $path;
        }
    }

    return $configured !== '' ? $configured : 'wkhtmltopdf';
})();

return [

    'pdf' => [
        'enabled' => true,
        'binary' => $wkhtmltopdf,
        'timeout' => 1200,
        'options' => [
            'enable-local-file-access' => true,
            'lowquality' => true,
            'no-stop-slow-scripts' => true,
            'disable-smart-shrinking' => true,
        ],
        'env' => [],
    ],

];
