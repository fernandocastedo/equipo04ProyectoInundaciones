<?php
$html = file_get_contents('test.html');
$html = preg_replace('/<script\b[^>]*>.*?<\/script>/si', '', $html);
$html = preg_replace('/<style\b[^>]*>.*?<\/style>/si', '', $html);

$dom = new \DOMDocument();
$dom->loadHTML($html, LIBXML_NOERROR);

$body = $dom->getElementsByTagName('body')->item(0);
$count = 0;

foreach ($body->childNodes as $child) {
    if ($child->nodeType == XML_ELEMENT_NODE) {
        $count++;
        echo "Root Element: " . $child->tagName . " -> " . substr(trim($dom->saveHTML($child)), 0, 50) . "\n";
    }
}
echo "Total Root Nodes: " . $count . "\n";
