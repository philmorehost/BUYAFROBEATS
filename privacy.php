<?php
require_once __DIR__ . '/includes/Core.php';
use BAF\Core;

$core = Core::get_instance();
$site_title = $core->setting('site_title', 'BUYAFROBEATS');

$page_seo = [
    'title' => 'Privacy Policy — ' . $site_title,
    'description' => 'Learn how we handle your data and protect your privacy.'
];

include __DIR__ . '/includes/header.php';
?>

<div class="page">
    <div class="panel page-content-wrap">
        <h1 class="page-title">Privacy Policy</h1>
        <div class="content">
            <p>Last updated: <?php echo date('F d, Y'); ?></p>

            <p>At <?php echo Core::escape($site_title); ?>, we value your privacy and are committed to protecting your personal data. This policy outlines how we collect, use, and safeguard your information when you use our auction platform.</p>

            <h2>1. Information We Collect</h2>
            <p>We collect information only when necessary to facilitate auctions and deliver your purchases:</p>
            <ul>
                <li><strong>Public Handles:</strong> When you place a bid, your chosen handle (e.g., @yourname) is displayed publicly on the leaderboard and activity feed.</li>
                <li><strong>Email Addresses:</strong> We collect your email address to notify you if you win an auction and to deliver the purchased beat files.</li>
                <li><strong>Technical Data:</strong> We may collect your IP address for security purposes to prevent fraudulent bidding activity.</li>
            </ul>

            <h2>2. How We Use Your Data</h2>
            <p>Your data is used strictly for the following purposes:</p>
            <ul>
                <li>Facilitating the bidding process and identifying the highest bidder.</li>
                <li>Sending winning notifications and download links.</li>
                <li>Preventing "sniping" or bot-driven activity to ensure fair auctions.</li>
                <li>Maintaining a log of sales for administrative purposes.</li>
            </ul>

            <h2>3. Data Retention</h2>
            <p>We believe in data minimization. In line with our file deletion policy, purchased beats are hosted for <strong>24 hours</strong> before being permanently removed from our server. Sales records including winner handles and prices are retained for administrative purposes and to provide "social proof" of previous sales on our leaderboard for a limited time.</p>

            <h2>4. Third Parties</h2>
            <p>We do not sell, trade, or otherwise transfer your personal information to outside parties. This does not include trusted third parties who assist us in operating our website (such as email delivery services), so long as those parties agree to keep this information confidential.</p>

            <h2>5. Security</h2>
            <p>We implement a variety of security measures to maintain the safety of your personal information. However, please note that no method of transmission over the internet is 100% secure.</p>

            <h2>6. Your Rights</h2>
            <p>You may request to have your email address removed from our records or unsubscribe from any mailing lists at any time by contacting us.</p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
