<?php
header('Content-Type: application/json; charset=utf-8');

error_reporting(0);
ini_set('display_errors', 0);

if (!isset($_GET['bolum']) || empty(trim($_GET['bolum']))) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Lütfen bölüm adı/harfleri girin."], JSON_UNESCAPED_UNICODE);
    exit;
}

$bolum = trim($_GET['bolum']);
$target_url = "https://wordsofwonders.net/tr/?letters=" . urlencode($bolum);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $target_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

// Sunucu engelini aşmak için detaylı Tarayıcı (User-Agent & Header) simülasyonu
$headers = [
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
    'Accept-Language: tr-TR,tr;q=0.9,en-US;q=0.8,en;q=0.7',
    'Cache-Control: no-cache',
    'Pragma: no-cache'
];

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 12);

$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (empty($html) || $httpCode !== 200) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Siteden yanıt alınamadı veya engellendi."], JSON_UNESCAPED_UNICODE);
    exit;
}

libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
$xpath = new DOMXPath($dom);

$words = [];
$wordNodes = $xpath->query("//div[contains(@class, 'words')]");
if ($wordNodes->length > 0) {
    $rawHtml = $dom->saveHTML($wordNodes->item(0));
    $lines = explode('<br>', $rawHtml);
    foreach ($lines as $line) {
        preg_match_all('/<span class="let">(.*?)<\/span>/u', $line, $matches);
        if (!empty($matches[1])) {
            $kelime = implode('', $matches[1]);
            if (!empty(trim($kelime))) {
                $words[] = trim($kelime);
            }
        }
    }
}

$crossword = [];
$rows = $xpath->query("//div[contains(@class, 'crossword')]/div[contains(@class, 'crossword-row')]");

foreach ($rows as $row) {
    $letters = $xpath->query("./div[contains(@class, 'letter')]", $row);
    $rowCells = [];
    
    foreach ($letters as $letter) {
        $class = $letter->getAttribute('class');
        $val = trim($letter->nodeValue);
        
        if (strpos($class, 'hidden') !== false || $val === 'x' || $val === '') {
            $rowCells[] = "";
        } else {
            $rowCells[] = mb_strtoupper($val, 'UTF-8');
        }
    }
    
    if (!empty($rowCells)) {
        $crossword[] = $rowCells;
    }
}

// Veri gelmediyse hata fırlat
if (empty($words) && empty($crossword)) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Bu harflere/bölüme ait sonuç bulunamadı."], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    "status" => "success",
    "total_words" => count($words),
    "bolum" => $bolum,
    "kelimeler" => $words,
    "crossword_grid" => $crossword
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
