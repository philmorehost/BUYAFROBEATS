<?php
require_once __DIR__ . '/includes/Core.php';
require_once __DIR__ . '/includes/CMS.php';

use BAF\Core;
use BAF\CMS;

$core = Core::get_instance();
$cms = new CMS($core);

$slug = $_GET['slug'] ?? '';
$page = $cms->get_page($slug);

if (!$page || $page['is_external']) {
    header('Location: index.php');
    exit;
}

$page_seo = [
    'title' => $page['meta_title'] ?: $page['title'],
    'description' => $page['meta_description'],
    'keywords' => $page['meta_keywords']
];

include __DIR__ . '/includes/header.php';
?>

<div class="page">
    <div class="panel" style="max-width: 900px; margin: 0 auto;">
        <h1 style="font-size: 42px; letter-spacing: -0.02em; margin: 0 0 24px;"><?php echo Core::escape($page['title']); ?></h1>
        <div class="content" style="line-height: 1.7; color: var(--ink-dim); font-size: 17px;">
            <?php echo $page['content']; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
