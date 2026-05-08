<?php
header("Content-Type: application/xml; charset=utf-8");

$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $base_path;
$base_url = rtrim($base_url, '/\\');

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

// Home
echo '  <url>' . PHP_EOL;
echo '    <loc>' . $base_url . '/</loc>' . PHP_EOL;
echo '    <priority>1.0</priority>' . PHP_EOL;
echo '  </url>' . PHP_EOL;

// Internal pages
$pages = ['login', 'register', 'faqs', 'privacy', 'terms'];
foreach ($pages as $p) {
    echo '  <url>' . PHP_EOL;
    echo '    <loc>' . $base_url . '/' . $p . '</loc>' . PHP_EOL;
    echo '    <priority>0.8</priority>' . PHP_EOL;
    echo '  </url>' . PHP_EOL;
}

echo '</urlset>';
