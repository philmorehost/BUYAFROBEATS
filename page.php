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
    <div class="panel page-content-wrap">
        <h1 class="page-title"><?php echo Core::escape($page['title']); ?></h1>
        <div class="content">
            <?php echo $page['content']; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
