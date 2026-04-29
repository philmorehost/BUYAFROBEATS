<?php
require_once __DIR__ . '/includes/Core.php';
use BAF\Core;

$core = Core::get_instance();
$site_title = $core->setting('site_title', 'BUYAFROBEATS');

$page_seo = [
    'title' => 'Terms and Conditions — ' . $site_title,
    'description' => 'Rules and regulations for participating in our beat auctions.'
];

include __DIR__ . '/includes/header.php';
?>

<div class="page">
    <div class="panel page-content-wrap">
        <h1 class="page-title">Terms and Conditions</h1>
        <div class="content">
            <p>Last updated: <?php echo date('F d, Y'); ?></p>

            <p>By using the <?php echo Core::escape($site_title); ?> platform, you agree to comply with and be bound by the following terms and conditions. Please read them carefully before participating in any auctions.</p>

            <h2>1. One-of-One Exclusivity</h2>
            <p>Every beat listed on this platform is a <strong>one-of-one</strong> original creation. Once an auction is finalized and the beat is sold, it is removed from the marketplace and will never be sold again. The winner acquires exclusive rights to the master recording and underlying composition.</p>

            <h2>2. Bidding Rules</h2>
            <ul>
                <li><strong>Binding Bids:</strong> Every bid placed is a binding contract. By placing a bid, you agree to pay the specified amount if you are the highest bidder when the clock hits zero.</li>
                <li><strong>Minimum Increments:</strong> All bids must meet the minimum increment requirements (typically $5.00 above the current bid).</li>
                <li><strong>Anti-Snipe Policy:</strong> To ensure fairness, any bid placed within the final 2 minutes of an auction will automatically extend the timer by an additional 2 minutes. This continues until no further bids are placed.</li>
            </ul>

            <h2>3. Payment and Delivery</h2>
            <p>Upon winning an auction, you will receive an automated email with payment instructions and/or a download link. </p>
            <ul>
                <li><strong>24-Hour Window:</strong> In line with our storage policy, the purchased audio files are hosted for <strong>24 hours</strong> after the auction ends. You MUST download your files within this window.</li>
                <li><strong>Permanent Deletion:</strong> After 24 hours, the audio files are permanently deleted from our server to ensure the exclusivity of the purchase. We do not maintain backups once the deletion occurs.</li>
            </ul>

            <h2>4. Rights and Licensing</h2>
            <p>The sale of a beat grants the purchaser an <strong>Exclusive License</strong>. This allows the purchaser to use the beat for commercial purposes including streaming, radio, and synchronized media. The original producer retains attribution rights as the author of the work.</p>

            <h2>5. Refunds and Cancellations</h2>
            <p>Due to the digital nature of the products and the immediate transfer of exclusive rights, all sales are final. No refunds or cancellations will be honored once an auction has concluded.</p>

            <h2>6. Prohibited Activity</h2>
            <p>We reserve the right to ban handles and email addresses associated with fraudulent activity, including but not limited to shill bidding, non-payment, or attempted manipulation of the auction system.</p>

            <h2>7. Limitation of Liability</h2>
            <p><?php echo Core::escape($site_title); ?> is not responsible for technical failures, internet outages, or other issues that may prevent a bid from being registered or a file from being downloaded within the 24-hour window.</p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
