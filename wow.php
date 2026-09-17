<?php
header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['bolum']) || empty($_GET['bolum'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Lütfen 'bolum' parametresi belirtin."], JSON_UNESCAPED_UNICODE);
    exit;
}

$bolum = trim($_GET['bolum']);
$target_url = "https://wordsofwonders.net/tr/?letters=" . urlencode($bolum);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $target_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$html = curl_exec($ch);
curl_close($ch);

if (empty($html)) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Veri çekilemedi."], JSON_UNESCAPED_UNICODE);
    exit;
}

libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
$xpath = new DOMXPath($dom);

// 1. KELİME LİSTESİ
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

// 2. MATRİS IZGARASINI SATIR SATIR TOPLAMA
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

// 3. JSON ÇIKTISI (Satır ve sütun dizilimini birebir görsele uygun basar)
echo json_encode([
    "status" => "success",
    "total_words" => count($words),
    "bolum" => $bolum,
    "kelimeler" => $words,
    "crossword_grid" => $crossword
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);