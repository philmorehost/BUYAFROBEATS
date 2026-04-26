<?php
require_once __DIR__ . '/includes/Core.php';
use BAF\Core;

$core = Core::get_instance();
$faqs = $core->db()->query("SELECT * FROM faqs ORDER BY sort_order ASC, created_at DESC")->fetchAll();

$page_seo = [
    'title' => 'Frequently Asked Questions — ' . $core->setting('site_title', 'BUYAFROBEATS'),
    'description' => 'Find answers to common questions about our beat auctions and licensing.'
];

include __DIR__ . '/includes/header.php';
?>

<div class="page">
    <div class="faq-wrap">
        <h1 class="faq-title">Frequently Asked Questions</h1>
        
        <?php if (empty($faqs)): ?>
            <div class="panel" style="text-align:center; padding: 60px;">
                <p style="color:var(--ink-mute)">No questions found. Check back later!</p>
            </div>
        <?php else: ?>
            <div class="faq-list">
                <?php foreach ($faqs as $f): ?>
                    <div class="panel">
                        <h3 class="faq-q">Q: <?php echo Core::escape($f['question']); ?></h3>
                        <p class="faq-a">A: <?php echo nl2br(Core::escape($f['answer'])); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
