<?php
require_once __DIR__ . '/../includes/Core.php';
require_once __DIR__ . '/../includes/Auction.php';

use BAF\Core;
use BAF\Auction;

$core = Core::get_instance();

// Auth Check
if (!$core->is_admin()) {
    header('Location: ../index.php');
    exit;
}

$auction = new Auction($core);
$beats = $auction->get_live_beats();

// Stats
$total_rev = $core->db()->query("SELECT SUM(price) FROM sales WHERE payment_status = 'completed'")->fetchColumn() ?: 0;
$sold_count = $core->db()->query("SELECT COUNT(*) FROM sales WHERE payment_status = 'completed'")->fetchColumn();
$live_count = count($beats);
$avg_sale = $sold_count > 0 ? $total_rev / $sold_count : 0;

$sales = $core->db()->query("SELECT s.*, b.title as beat_title 
                             FROM sales s 
                             JOIN beats b ON s.beat_id = b.id 
                             ORDER BY sold_at DESC")->fetchAll();

$current_tab = 'studio';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Studio — <?php echo Core::escape($core->setting('site_title', 'BEATZAZA')); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=3.0">
    <style>
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 40px; }
        .stat-card { background: var(--bg-2); border: 1px solid var(--line); padding: 20px; border-radius: var(--radius-lg); }
        .stat-card .k { font-family:'JetBrains Mono', monospace; font-size: 11px; color: var(--ink-mute); text-transform: uppercase; letter-spacing: 0.1em; }
        .stat-card .v { font-size: 32px; font-weight: 700; margin-top: 8px; }
        .stat-card .v.accent { color: var(--accent); }
        
        .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
        .section-header h2 { font-size: 24px; letter-spacing: -0.02em; }
        
        .log-table { width: 100%; border-collapse: collapse; background: var(--bg-2); border: 1px solid var(--line); border-radius: var(--radius-lg); overflow: hidden; }
        .log-table th, .log-table td { padding: 16px; text-align: left; border-bottom: 1px solid var(--line); }
        .log-table th { font-family: 'JetBrains Mono', monospace; font-size: 11px; color: var(--ink-mute); text-transform: uppercase; background: var(--bg-3); }
        .log-table tr:last-child td { border-bottom: 0; }
        
        .badge { display: inline-flex; padding: 4px 10px; border-radius: var(--radius-full); font-family: 'JetBrains Mono', monospace; font-size: 10px; font-weight: 600; text-transform: uppercase; }
        .badge.pending { background: color-mix(in oklab, var(--accent) 15%, transparent); color: var(--accent); border: 1px solid color-mix(in oklab, var(--accent) 30%, var(--line)); }
        .badge.completed { background: color-mix(in oklab, var(--ok) 15%, transparent); color: var(--ok); border: 1px solid color-mix(in oklab, var(--ok) 30%, var(--line)); }
        .badge.expired { background: color-mix(in oklab, var(--danger) 15%, transparent); color: var(--danger); border: 1px solid color-mix(in oklab, var(--danger) 30%, var(--line)); }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a href="../index.php" class="logo">
                <?php echo $core->render_logo(); ?> <span style="opacity: 0.5; margin-left: 8px; font-weight: 400;">Studio</span>
            </a>
            <nav class="tabs">
                <a href="index.php" class="tab is-active">Overview</a>
                <a href="upload.php" class="tab">+ Upload Beat</a>
                <a href="settings.php" class="tab">Settings</a>
            </nav>
            <div class="spacer"></div>
            <div class="user-pill mono">
                <?php echo Core::escape($_SESSION['user_handle']); ?>
            </div>
        </div>
    </header>

    <main class="page">
        <div class="admin-banner"><span class="live-dot"></span> Studio Session Active</div>
        
        <div class="stat-grid">
            <div class="stat-card"><div class="k">Live Auctions</div><div class="v"><?php echo $live_count; ?></div></div>
            <div class="stat-card"><div class="k">Beats Sold</div><div class="v"><?php echo $sold_count; ?></div></div>
            <div class="stat-card"><div class="k">Total Revenue</div><div class="v accent">$<?php echo number_format($total_rev, 0); ?></div></div>
            <div class="stat-card"><div class="k">Avg. Sale</div><div class="v">$<?php echo number_format($avg_sale, 0); ?></div></div>
        </div>

        <section style="margin-bottom: 60px;">
            <div class="section-header">
                <h2>Live Catalog</h2>
                <a href="upload.php" class="btn btn-primary">+ New Auction</a>
            </div>

            <?php if (empty($beats)): ?>
                <div class="empty">
                    <h3>No live auctions</h3>
                    <p>Beats uploaded will appear here once an auction starts.</p>
                </div>
            <?php else: ?>
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>Beat Details</th>
                            <th>Current Bid</th>
                            <th>Time Left</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($beats as $b): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600;"><?php echo Core::escape($b['title']); ?></div>
                                    <div class="mono" style="font-size: 11px; color: var(--ink-mute);"><?php echo $b['genre']; ?> · <?php echo $b['bpm']; ?> BPM</div>
                                </td>
                                <td class="mono">$<?php echo number_format($b['current_bid'], 0); ?></td>
                                <td class="mono"><?php echo $b['ends_at'] ?: 'Not started'; ?></td>
                                <td>
                                    <a href="edit.php?id=<?php echo $b['id']; ?>" class="btn" style="padding: 6px 12px; font-size: 12px;">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <section>
            <div class="section-header">
                <h2>Sales History</h2>
            </div>

            <?php if (empty($sales)): ?>
                <div class="empty">
                    <h3>No sales records</h3>
                    <p>Once an auction is won, it will appear here for tracking.</p>
                </div>
            <?php else: ?>
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Beat</th>
                            <th>Winner</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Sold At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales as $s): ?>
                            <tr>
                                <td class="mono" style="font-size: 11px; color: var(--ink-mute);"><?php echo $s['delivery_id']; ?></td>
                                <td style="font-weight: 500;"><?php echo Core::escape($s['beat_title']); ?></td>
                                <td>
                                    <div class="mono" style="font-size: 13px;"><?php echo Core::escape($s['winner_handle']); ?></div>
                                    <div class="mono" style="font-size: 11px; color: var(--ink-mute);"><?php echo $s['winner_email']; ?></div>
                                </td>
                                <td class="mono" style="font-weight: 700; color: var(--accent);">$<?php echo number_format($s['price'], 0); ?></td>
                                <td>
                                    <span class="badge <?php echo $s['payment_status']; ?>"><?php echo $s['payment_status']; ?></span>
                                </td>
                                <td class="mono" style="font-size: 12px; color: var(--ink-dim);"><?php echo date('M d, H:i', strtotime($s['sold_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>

    <footer class="site-footer">
        <div class="ft-brand"><b>BEATZAZA STUDIO</b> v3.0</div>
    </footer>
</body>
</html>
