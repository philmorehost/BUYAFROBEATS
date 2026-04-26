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
    <div style="max-width: 800px; margin: 0 auto;">
        <h1 style="font-size: 42px; letter-spacing: -0.02em; margin: 0 0 40px; text-align:center;">Frequently Asked Questions</h1>
        
        <?php if (empty($faqs)): ?>
            <div class="panel" style="text-align:center; padding: 60px;">
                <p style="color:var(--ink-mute)">No questions found. Check back later!</p>
            </div>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:20px;">
                <?php foreach ($faqs as $f): ?>
                    <div class="panel">
                        <h3 style="font-size: 18px; margin: 0 0 12px; color: var(--accent);">Q: <?php echo Core::escape($f['question']); ?></h3>
                        <p style="line-height:1.6; color:var(--ink-dim); margin:0;">A: <?php echo nl2br(Core::escape($f['answer'])); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
