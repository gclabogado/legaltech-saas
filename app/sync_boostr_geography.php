<?php
declare(strict_types=1);

/**
 * Sincroniza geografía de Chile desde Boostr API.
 * Uso:
 *   php app/sync_boostr_geography.php
 */

function fetchJson(string $url): ?array
{
    $raw = null;
    $status = 0;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: Tu Estudio Juridico/1.0 (+https://example.com)'
            ],
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => true
        ]);

        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Accept: application/json\r\nUser-Agent: Tu Estudio Juridico/1.0 (+https://example.com)\r\n",
                'timeout' => 8
            ]
        ]);
        $raw = @file_get_contents($url, false, $context);
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $status = (int)$m[1];
        }
    }

    if ($status !== 200 || !is_string($raw) || trim($raw) === '') {
        return null;
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return null;
    }
    return $decoded;
}

function extractRows(array $payload): array
{
    if (isset($payload['data']) && is_array($payload['data'])) {
        return $payload['data'];
    }
    $isList = array_keys($payload) === range(0, count($payload) - 1);
    return $isList ? $payload : [];
}

function normalizeRegion(string $region): string
{
    $region = trim($region);
    $key = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $region) ?: $region);
    $key = preg_replace('/[^a-z0-9]+/', ' ', $key);
    $key = trim((string)$key);
    if ($key === "lib gral bernardo o higgins") {
        return "Libertador General Bernardo O'Higgins";
    }
    if ($key === "aysen del general carlos ibanez del campo") {
        return "Aysén";
    }
    return $region;
}

function normalizeComunas(array $rows): array
{
    $out = [];
    $seen = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $comuna = trim((string)($row['comuna'] ?? $row['name'] ?? $row['nombre'] ?? ''));
        if ($comuna === '') {
            continue;
        }
        $cut = trim((string)($row['cut'] ?? $row['code'] ?? $row['id'] ?? ''));
        $key = $cut !== '' ? $cut : mb_strtolower($comuna, 'UTF-8');
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $out[] = [
            'cut' => $cut,
            'comuna' => $comuna,
            'provincia' => trim((string)($row['provincia'] ?? $row['province'] ?? $row['province_name'] ?? '')),
            'region' => normalizeRegion((string)($row['region'] ?? $row['region_name'] ?? '')),
            'lat' => isset($row['lat']) && is_numeric($row['lat']) ? (float)$row['lat'] : null,
            'lng' => isset($row['lng']) && is_numeric($row['lng']) ? (float)$row['lng'] : null
        ];
    }
    return $out;
}

$targets = [
    'regions' => 'https://api.boostr.cl/geography/regions.json',
    'provinces' => 'https://api.boostr.cl/geography/provinces.json',
    'communes' => 'https://api.boostr.cl/geography/communes.json',
];

$baseDir = dirname(__DIR__) . '/public/data';
if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
    fwrite(STDERR, "No se pudo crear directorio: $baseDir\n");
    exit(1);
}

foreach ($targets as $name => $url) {
    $payload = fetchJson($url);
    if ($payload === null) {
        fwrite(STDERR, "No se pudo descargar $name desde $url\n");
        continue;
    }

    $rows = extractRows($payload);
    if ($name === 'communes') {
        $rows = normalizeComunas($rows);
    }

    $path = $baseDir . '/boostr_' . $name . '.json';
    file_put_contents($path, json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    echo "OK $name -> $path (" . count($rows) . " registros)\n";

    if ($name === 'communes') {
        $mainPath = $baseDir . '/chile_comunas_boostr.json';
        file_put_contents($mainPath, json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        echo "OK communes main -> $mainPath\n";
    }
}
