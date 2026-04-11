<?php
/**
 * PHP reverse proxy for genealogy-viz.fly.dev
 * Forwards /genealogy-viz/* keeping the prefix, because the Express app
 * serves all static files and API routes under /genealogy-viz/ on Fly.
 */

$target = 'https://genealogy-viz.fly.dev';

// Keep the full URI including /genealogy-viz/ prefix
$uri = $_SERVER['REQUEST_URI'];

$url = $target . $uri;

// Forward request
$method = $_SERVER['REQUEST_METHOD'];
$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_HEADER         => true,
    CURLOPT_CUSTOMREQUEST  => $method,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT        => 30,
]);

// Forward body for POST/PUT/PATCH
if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
}

// Forward request headers (replace Host)
$headers = [];
$skip = ['host', 'connection', 'transfer-encoding', 'te'];
foreach (getallheaders() as $name => $value) {
    if (!in_array(strtolower($name), $skip)) {
        $headers[] = "$name: $value";
    }
}
$headers[] = 'Host: genealogy-viz.fly.dev';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSz  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

$rawHeaders = substr($response, 0, $headerSz);
$body       = substr($response, $headerSz);

// Forward response headers, rewriting Location back to /genealogy-viz/
$skipResp = ['transfer-encoding', 'connection', 'content-length'];
foreach (explode("\r\n", $rawHeaders) as $line) {
    if (preg_match('/^Location:\s*(.+)$/i', $line, $m)) {
        $loc = str_replace('https://genealogy-viz.fly.dev', '/genealogy-viz', trim($m[1]));
        header("Location: $loc", true);
        continue;
    }
    if (strpos($line, ':') !== false) {
        [$name, $val] = explode(':', $line, 2);
        if (!in_array(strtolower(trim($name)), $skipResp)) {
            header("$name:$val", false);
        }
    }
}

http_response_code($httpCode);
echo $body;
