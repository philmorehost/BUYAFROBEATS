<?php
require_once __DIR__ . '/../includes/Core.php';
require_once __DIR__ . '/../includes/Auction.php';

use BAF\Core;
use BAF\Auction;

$core = Core::get_instance();
if (!$core->is_admin()) {
    header('Location: login.php');
    exit;
}

$auction = new Auction($core);
$beats = $auction->get_live_beats();

// Stats
$total_rev = $core->db()->query("SELECT SUM(price) FROM sales")->fetchColumn() ?: 0;
$sold_count = $core->db()->query("SELECT COUNT(*) FROM sales")->fetchColumn();
$live_count = count($beats);
$avg_sale = $sold_count > 0 ? $total_rev / $sold_count : 0;

$sales = $core->db()->query("SELECT s.*, b.title as beat_title FROM sales s JOIN beats b ON s.beat_id = b.id ORDER BY sold_at DESC")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <title>My Studio — <?php echo Core::escape($core->setting('site_title', 'BUYAFROBEATS')); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-banner { display: inline-flex; align-items: center; gap: 8px; font-family:'JetBrains Mono', monospace; font-size: 10px; color: var(--accent); background: color-mix(in oklab, var(--accent) 12%, transparent); border: 1px solid color-mix(in oklab, var(--accent) 40%, var(--line)); padding: 5px 10px; border-radius: 999px; margin-bottom: 16px; letter-spacing: 0.08em; text-transform: uppercase; }
        .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 32px; }
        .stat-card { background: var(--bg-2); border: 1px solid var(--line); padding: 14px 16px; border-radius: 14px; }
        .stat-card .k { font-family:'JetBrains Mono', monospace; font-size: 10px; color: var(--ink-mute); text-transform: uppercase; letter-spacing: 0.08em; }
        .stat-card .v { font-size: 24px; font-weight: 600; margin-top: 4px; }
        .stat-card .v.accent { color: var(--accent); }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-inner">
        <a href="../index.php" class="logo"><span class="dot"></span><?php echo $core->render_logo(); ?><span class="sub">/ studio</span></a>
        <div class="tabs">
            <a href="index.php" class="tab is-active">Dashboard</a>
            <a href="upload.php" class="tab">+ Upload Beat</a>
            <a href="settings.php" class="tab">Settings</a>
        </div>
        <div class="spacer"></div>
        <div class="counter"><b>Logged in as <?php echo $_SESSION['username']; ?></b></div>
        <a href="logout.php" class="tab" style="font-size: 11px;">Logout</a>
    </div>
</div>

<div class="page">
    <div class="admin-banner"><span style="width:6,height:6,borderRadius:'50%',background:'var(--accent)',display:'inline-block'"></span> Admin · Only you see this</div>
    
    <div style="display:flex; align-items: center; justify-content:space-between; margin-bottom: 24px;">
        <h2 style="margin:0; font-size:28px; letter-spacing:-0.02em;">Studio Overview</h2>
        <a href="upload.php" class="btn btn-primary">+ Upload new beat</a>
    </div>

    <div class="stat-grid">
        <div class="stat-card"><div class="k">Live Auctions</div><div class="v"><?php echo $live_count; ?></div></div>
        <div class="stat-card"><div class="k">Beats Sold</div><div class="v"><?php echo $sold_count; ?></div></div>
        <div class="stat-card"><div class="k">Total Revenue</div><div class="v accent">$<?php echo number_format($total_rev, 2); ?></div></div>
        <div class="stat-card"><div class="k">Avg. Sale</div><div class="v">$<?php echo number_format($avg_sale, 2); ?></div></div>
    </div>

    <h3 style="font-size:14px; margin:0 0 12px; font-family:'JetBrains Mono', monospace; text-transform:uppercase; color:var(--ink-mute)">Live Catalog</h3>
    <?php if (empty($beats)): ?>
        <div class="empty" style="margin-bottom:32px; border: 1px dashed var(--line); border-radius: 18px; padding: 40px; text-align:center;">
            <h3>Nothing live</h3>
            <p>Upload your next beat to open the next auction.</p>
        </div>
    <?php else: ?>
        <table class="log-table" style="margin-bottom:32px;">
            <thead><tr><th>Beat</th><th>Current Bid</th><th>Bids</th><th>Top Bidder</th><th>Time Left</th></tr></thead>
            <tbody>
                <?php foreach ($beats as $b): ?>
                    <tr>
                        <td><b><?php echo Core::escape($b['title']); ?></b> <span class="mono" style="color:var(--ink-mute); font-size:11px">· <?php echo $b['genre']; ?></span></td>
                        <td class="mono">$<?php echo number_format($b['current_bid'], 2); ?></td>
                        <td class="mono"><?php echo $core->db()->query("SELECT COUNT(*) FROM bids WHERE beat_id = {$b['id']}")->fetchColumn(); ?></td>
                        <td class="mono" style="font-size:12px"><?php echo $b['top_bidder'] ?: '—'; ?></td>
                        <td class="mono" style="font-size:12px"><?php echo $b['ends_at'] ?: 'Not started'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h3 style="font-size:14px; margin:0 0 12px; font-family:'JetBrains Mono', monospace; text-transform:uppercase; color:var(--ink-mute)">Sales Log</h3>
    <?php if (empty($sales)): ?>
        <div class="empty" style="border: 1px dashed var(--line); border-radius: 18px; padding: 40px; text-align:center;">
            <h3>No sales yet</h3>
            <p>When an auction ends, the delivery record lands here.</p>
        </div>
    <?php else: ?>
        <table class="log-table">
            <thead><tr><th>Delivery ID</th><th>Beat</th><th>Winner</th><th>Price</th><th>Date</th></tr></thead>
            <tbody>
                <?php foreach ($sales as $s): ?>
                    <tr>
                        <td class="mono" style="color:var(--ink-mute)"><?php echo $s['delivery_id']; ?></td>
                        <td><b><?php echo Core::escape($s['beat_title']); ?></b></td>
                        <td class="mono" style="font-size:12px"><?php echo $s['winner_handle']; ?><br><span style="color:var(--ink-mute); font-size:11px"><?php echo $s['winner_email']; ?></span></td>
                        <td class="mono" style="color:var(--accent)">$<?php echo number_format($s['price'], 2); ?></td>
                        <td class="mono" style="font-size:12px; color:var(--ink-dim)"><?php echo date('M d · H:i', strtotime($s['sold_at'])); ?></td>
                        <td style="text-align:right">
                            <a href="../api/download.php?token=<?php echo $s['download_token']; ?>" class="btn" style="font-size:10px; padding: 4px 8px;">Download</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>
