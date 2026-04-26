<?php
require_once __DIR__ . '/includes/Core.php';
require_once __DIR__ . '/includes/CMS.php';
require_once __DIR__ . '/includes/Auction.php';

use BAF\Core;
use BAF\CMS;
use BAF\Auction;

$core = Core::get_instance();
$cms = new CMS($core);
$auction = new Auction($core);

header('Content-Type: application/xml; charset=utf-8');

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
$base_url = rtrim($base_url, '/\\');

// Home
echo '  <url><loc>' . $base_url . '/index.php</loc><priority>1.0</priority></url>' . PHP_EOL;

// CMS Pages
foreach ($cms->get_all_pages() as $page) {
    if (!$page['is_external']) {
        echo '  <url><loc>' . $base_url . '/page.php?slug=' . $page['slug'] . '</loc><priority>0.8</priority></url>' . PHP_EOL;
    }
}

// Beats (if you have individual beat pages, but currently you only have the home page with auctions)
// For now, just add FAQs
echo '  <url><loc>' . $base_url . '/faqs.php</loc><priority>0.5</priority></url>' . PHP_EOL;

echo '</urlset>';
