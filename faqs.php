<?php
require_once __DIR__ . '/includes/Core.php';
use BAF\Core;

$core = Core::get_instance();
$db_faqs = $core->db()->query("SELECT * FROM faqs ORDER BY sort_order ASC, created_at DESC")->fetchAll();

// Default articles if DB is empty
$default_faqs = [
    [
        'question' => 'How do auctions work?',
        'answer' => 'Each beat starts at a minimum bid. Once the first bid is placed, a 30-minute timer begins. The highest bidder at the end of the timer wins the exclusive rights to the beat.'
    ],
    [
        'question' => 'What is the "Anti-Snipe" policy?',
        'answer' => 'To ensure fairness, any bid placed within the last 2 minutes of an auction will extend the timer by another 2 minutes. This prevents users from winning at the last second without giving others a chance to respond.'
    ],
    [
        'question' => 'What rights do I get when I win?',
        'answer' => 'Winning an auction grants you an **Exclusive License**. You own the master recording and can use it for commercial purposes (streaming, radio, sync, etc.). The beat is removed from the website and never sold again.'
    ],
    [
        'question' => 'How do I pay for my beat?',
        'answer' => 'We use **Plisio** for secure payments. After winning, you will receive an email with a payment link. You can pay using various cryptocurrencies. Once payment is confirmed, your download link will be activated.'
    ],
    [
        'question' => 'Why did my download link expire?',
        'answer' => 'To maintain exclusivity and security, we only host purchased files for **24 hours** after the auction ends. You must download your beat within this window. After 24 hours, the file is permanently deleted from our servers.'
    ],
    [
        'question' => 'Can I get a refund?',
        'answer' => 'Due to the digital nature of the products and the immediate transfer of exclusive rights, all sales are final and non-refundable.'
    ]
];

$display_faqs = !empty($db_faqs) ? $db_faqs : $default_faqs;

$page_seo = [
    'title' => 'FAQs — ' . $core->setting('site_title', 'BUYAFROBEATS'),
    'description' => 'Find answers to common questions about our beat auctions, licensing, and payments.'
];

include __DIR__ . '/includes/header.php';
?>

<div class="page">
    <div class="faq-wrap">
        <h1 class="faq-title">Frequently Asked Questions</h1>
        
        <div class="accordion">
            <?php foreach ($display_faqs as $i => $f): ?>
                <div class="accordion-item" id="faq-<?php echo $i; ?>">
                    <div class="accordion-header" onclick="toggleAccordion(<?php echo $i; ?>)">
                        <h3><?php echo Core::escape($f['question']); ?></h3>
                        <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </div>
                    <div class="accordion-content">
                        <p class="faq-a"><?php echo nl2br(Core::escape($f['answer'])); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top: 40px; text-align: center; color: var(--ink-dim);">
            <p>Still have questions? <a href="terms.php" style="color: var(--accent);">Read our full Terms & Conditions</a></p>
        </div>
    </div>
</div>

<script>
function toggleAccordion(index) {
    const items = document.querySelectorAll('.accordion-item');
    const clickedItem = document.getElementById('faq-' + index);
    const isActive = clickedItem.classList.contains('is-active');

    items.forEach(item => item.classList.remove('is-active'));

    if (!isActive) {
        clickedItem.classList.add('is-active');
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
