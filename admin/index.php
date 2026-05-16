<?php
require_once __DIR__ . '/../includes/Core.php';
require_once __DIR__ . '/../includes/Auction.php';

use BAF\Core;
use BAF\Auction;

$core = Core::get_instance();
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

$is_admin = $core->is_admin();
$auction = new Auction($core);

// One-time migration check for email_notifications column
try {
    $core->db()->query("SELECT email_notifications FROM users LIMIT 1");
} catch (\Exception $e) {
    $core->db()->exec("ALTER TABLE users ADD COLUMN email_notifications TINYINT(1) DEFAULT 1 AFTER role");
}

// Ensure auctions are processed when users visit the dashboard
$auction->check_for_winners();
$auction->cleanup_sold_beats();

$beats = $is_admin ? $auction->get_live_beats() : [];

// Pre-fetch bid counts to avoid N+1 queries
$bid_counts = [];
if ($is_admin && !empty($beats)) {
    $beat_ids = array_column($beats, 'id');
    if (!empty($beat_ids)) {
        $in = str_repeat('?,', count($beat_ids) - 1) . '?';
        $stmt = $core->db()->prepare("SELECT beat_id, COUNT(*) as count FROM bids WHERE beat_id IN ($in) GROUP BY beat_id");
        $stmt->execute($beat_ids);
        $bid_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}

// Stats
$total_rev = $core->db()->query("SELECT SUM(price) FROM sales")->fetchColumn() ?: 0;
$sold_count = $core->db()->query("SELECT COUNT(*) FROM sales")->fetchColumn();
$live_count = count($beats);
$avg_sale = $sold_count > 0 ? $total_rev / $sold_count : 0;

$sales = $core->db()->query("SELECT s.*, b.title as beat_title FROM sales s JOIN beats b ON s.beat_id = b.id ORDER BY sold_at DESC")->fetchAll();

// CSV Export Logic
if (isset($_GET['export']) && $is_admin) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="BUYAFROBEATS_Sales_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Delivery ID', 'Beat Title', 'Winner Handle', 'Winner Email', 'Price', 'Status', 'Sold At']);
    foreach ($sales as $s) {
        fputcsv($output, [$s['delivery_id'], $s['beat_title'], $s['winner_handle'], $s['winner_email'], $s['price'], $s['payment_status'], $s['sold_at']]);
    }
    fclose($output);
    exit;
}

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

        @media (max-width: 700px) {
            .banner-welcome { flex-direction: column; align-items: flex-start !important; gap: 16px !important; }
            .admin-header-row { flex-direction: column; align-items: flex-start !important; gap: 12px; }
            .admin-header-row h2 { font-size: 24px !important; }
        }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-inner">
        <a href="../index" class="logo"><span class="dot"></span><?php echo $core->render_logo(); ?><span class="sub">/ studio</span></a>
        <div class="tabs">
            <a href="index" class="tab is-active">Dashboard</a>
            <?php if ($is_admin): ?>
                <a href="upload" class="tab">+ Upload Beat</a>
                <a href="settings" class="tab">Settings</a>
            <?php endif; ?>
        </div>
        <div class="spacer"></div>
        <div class="counter"><b>Logged in as <?php echo $_SESSION['username']; ?></b></div>
        <a href="logout" class="tab" style="font-size: 11px;">Logout</a>
    </div>
</div>

<div class="page">
    <!-- Friendly Download Policy Notice -->
    <div class="banner-welcome" style="background: color-mix(in oklab, var(--accent) 5%, var(--bg-2)); border: 1px solid var(--line); border-radius: 18px; padding: 24px; margin-bottom: 32px; display: flex; align-items: center; gap: 24px; animation: cardIn .4s ease both;">
        <div class="banner-icon" style="width: 54px; height: 54px; border-radius: 14px; background: var(--accent); color: var(--accent-ink); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
        </div>
        <div class="banner-text" style="flex: 1;">
            <h3 style="margin: 0 0 4px; font-size: 18px; font-weight: 600; color: var(--ink); letter-spacing: -0.01em;">Welcome to your Dashboard</h3>
            <p style="margin: 0; font-size: 15px; color: var(--ink-dim); line-height: 1.6;">
                A friendly reminder: To protect the total exclusivity of our beats, all files are removed from our servers 7 days after purchase.
                <span style="color: var(--ink); font-weight: 600;">Please make sure to download and back up your new beats within 7 days.</span>
            </p>
        </div>
    </div>

    <?php if ($is_admin): ?>
    <div class="admin-banner"><span style="width:6px; height:6px; border-radius:50%; background:var(--accent); display:inline-block;"></span> Admin · Only you see this</div>
    
    <div class="admin-header-row" style="display:flex; align-items: center; justify-content:space-between; margin-bottom: 24px;">
        <h2 style="margin:0; font-size:28px; letter-spacing:-0.02em;">Studio Overview</h2>
        <a href="upload" class="btn btn-primary">+ Upload new beat</a>
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
        <div class="table-wrap" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin-bottom: 32px;">
            <table class="log-table" style="width: 100%; min-width: 800px;">
                <thead><tr><th>Beat</th><th>Current Bid</th><th>Bids</th><th>Top Bidder</th><th>Time Left</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($beats as $b): ?>
                        <tr>
                            <td><b><?php echo Core::escape($b['title']); ?></b> <span class="mono" style="color:var(--ink-mute); font-size:11px">· <?php echo $b['genre']; ?></span></td>
                            <td class="mono">$<?php echo number_format($b['current_bid'], 2); ?></td>
                            <td class="mono"><?php echo $bid_counts[$b['id']] ?? 0; ?></td>
                            <td class="mono" style="font-size:12px"><?php echo $b['top_bidder'] ?: '—'; ?></td>
                            <td class="mono" style="font-size:12px"><?php echo $b['ends_at'] ?: 'Not started'; ?></td>
                            <td style="text-align:right">
                                <a href="edit?id=<?php echo $b['id']; ?>" class="btn" style="font-size:10px; padding: 4px 8px; border: 1px solid var(--line);">Edit</a>
                                <a href="delete?id=<?php echo $b['id']; ?>" class="btn" style="font-size:10px; padding: 4px 8px; border: 1px solid var(--danger); color:var(--danger);" onclick="return confirm('Delete this beat and all associated files? This cannot be undone.')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php endif; // End Admin Check ?>

    <div style="display:flex; align-items: center; justify-content:space-between; margin:0 0 12px;">
        <h3 style="font-size:14px; margin:0; font-family:'JetBrains Mono', monospace; text-transform:uppercase; color:var(--ink-mute)"><?php echo $is_admin ? 'Sales Log' : 'Won Auctions'; ?></h3>
        <?php if ($is_admin && !empty($sales)): ?>
            <a href="?export=1" class="btn" style="font-size: 10px; padding: 5px 12px; border: 1px solid var(--line); background: transparent;">Export CSV ↓</a>
        <?php endif; ?>
    </div>
    <?php
    // If not admin, only show user's own sales and bids
    if (!$is_admin) {
        $user_email = $_SESSION['user_email'] ?? '';
        if (empty($user_email)) {
            $stmt_user = $core->db()->prepare("SELECT email FROM users WHERE id = ?");
            $stmt_user->execute([$_SESSION['user_id']]);
            $user_email = $stmt_user->fetchColumn();
            $_SESSION['user_email'] = $user_email;
        }

        // Get Sales
        $sales_stmt = $core->db()->prepare("SELECT s.*, b.title as beat_title FROM sales s JOIN beats b ON s.beat_id = b.id WHERE s.winner_email = ? ORDER BY sold_at DESC");
        $sales_stmt->execute([$user_email]);
        $sales = $sales_stmt->fetchAll();

        // Get Bids (Active or Past)
        $bids_stmt = $core->db()->prepare("
            SELECT b.*, be.title as beat_title, be.status as beat_status, be.top_bidder, be.current_bid as beat_current_bid
            FROM bids b 
            JOIN beats be ON b.beat_id = be.id 
            WHERE b.bidder_email = ? 
            GROUP BY b.beat_id 
            ORDER BY b.created_at DESC
        ");
        $bids_stmt->execute([$user_email]);
        $my_bidding = $bids_stmt->fetchAll();

        // Get User Preferences
        $stmt_pref = $core->db()->prepare("SELECT email_notifications FROM users WHERE id = ?");
        $stmt_pref->execute([$_SESSION['user_id']]);
        $email_pref = $stmt_pref->fetchColumn();
    }

    if (empty($sales)): ?>
        <div class="empty" style="border: 1px dashed var(--line); border-radius: 18px; padding: 40px; text-align:center; margin-bottom:32px;">
            <h3>No won auctions yet</h3>
            <p>When you win an auction, your delivery record will appear here.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin-bottom:32px;">
            <table class="log-table" style="width: 100%; min-width: 800px;">
                <thead><tr><th>Delivery ID</th><th>Beat</th><th>Winner</th><th>Price</th><th>Date</th><th style="text-align:right">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($sales as $s): ?>
                        <tr>
                            <td class="mono" style="color:var(--ink-mute)"><?php echo $s['delivery_id']; ?></td>
                            <td><b><?php echo Core::escape($s['beat_title']); ?></b></td>
                            <td class="mono" style="font-size:12px"><?php echo $s['winner_handle']; ?><br><span style="color:var(--ink-mute); font-size:11px"><?php echo $s['winner_email']; ?></span></td>
                            <td class="mono" style="color:var(--accent)">$<?php echo number_format($s['price'], 2); ?></td>
                            <td class="mono" style="font-size:12px; color:var(--ink-dim)"><?php echo date('M d · H:i', strtotime($s['sold_at'])); ?></td>
                            <td style="text-align:right">
                                <?php if ($s['payment_status'] === 'completed'): ?>
                                    <a href="../api/download?token=<?php echo $s['download_token']; ?>" class="btn btn-primary" style="font-size:10px; padding: 4px 12px;">Download HQ</a>
                                <?php else: ?>
                                    <a href="../pay?id=<?php echo $s['delivery_id']; ?>" class="btn" style="font-size:10px; padding: 4px 12px;">Pay Now</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if (!$is_admin): ?>
        <h3 style="font-size:14px; margin:0 0 12px; font-family:'JetBrains Mono', monospace; text-transform:uppercase; color:var(--ink-mute)">My Bidding Activity</h3>
        <?php if (empty($my_bidding)): ?>
            <div class="empty" style="border: 1px dashed var(--line); border-radius: 18px; padding: 40px; text-align:center;">
                <h3>No bids placed</h3>
                <p>When you bid on a beat, your activity will be tracked here.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table class="log-table" style="width: 100%; min-width: 800px;">
                    <thead><tr><th>Beat</th><th>Status</th><th>Your High Bid</th><th>Current High</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($my_bidding as $b): 
                            $is_top = ($b['bidder_handle'] === $b['top_bidder']);
                            $is_live = ($b['beat_status'] === 'live');
                        ?>
                            <tr>
                                <td><b><?php echo Core::escape($b['beat_title']); ?></b></td>
                                <td><span class="status <?php echo $b['beat_status']; ?>" style="font-size:10px; padding:2px 6px; border-radius:4px;"><?php echo strtoupper($b['beat_status']); ?></span></td>
                                <td class="mono">$<?php echo number_format($b['amount'], 2); ?></td>
                                <td class="mono">$<?php echo number_format($b['beat_current_bid'], 2); ?></td>
                                <td>
                                    <?php if ($is_live): 
                                        $is_ended = ($b['ends_at'] && strtotime($b['ends_at']) <= time());
                                    ?>
                                        <?php if ($is_top): ?>
                                            <?php if ($is_ended): ?>
                                                <span style="color:var(--accent); font-weight:bold; font-size:11px;">♛ PENDING WIN</span>
                                            <?php else: ?>
                                                <span style="color:var(--accent); font-weight:bold; font-size:11px;">★ TOP BIDDER</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php if ($is_ended): ?>
                                                <span style="color:var(--ink-mute); font-size:11px;">AUCTION ENDED</span>
                                            <?php else: ?>
                                                <span style="color:var(--danger); font-size:11px;">OUTBID</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if ($b['beat_status'] === 'sold' && $is_top): ?>
                                            <span style="color:var(--accent); font-weight:bold; font-size:11px;">✔ WON</span>
                                        <?php else: ?>
                                            <span style="color:var(--ink-mute); font-size:11px;">ENDED</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right">
                                    <?php if ($is_live): ?>
                                        <a href="../index" class="btn" style="font-size:10px; padding: 4px 12px;">View Auction</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Account Settings for Users -->
        <div style="margin-top: 48px; padding: 24px; background: var(--bg-2); border: 1px solid var(--line); border-radius: 18px;">
            <h3 style="font-size:14px; margin:0 0 16px; font-family:'JetBrains Mono', monospace; text-transform:uppercase; color:var(--ink-mute)">Account Settings</h3>
            <div style="display:flex; align-items:center; justify-content:space-between; gap:20px;">
                <div>
                    <div style="font-weight:600; font-size:15px; margin-bottom:4px;">Email Notifications</div>
                    <div style="font-size:13px; color:var(--ink-dim);">Receive alerts when you are outbid or win an auction.</div>
                </div>
                <div style="display:flex; align-items:center; gap:12px;">
                    <select id="email-pref-toggle" style="background:var(--bg); border:1px solid var(--line); color:var(--ink); padding:8px 12px; border-radius:8px; font-family:inherit; font-size:13px; cursor:pointer;">
                        <option value="1" <?php echo $email_pref ? 'selected' : ''; ?>>Enabled</option>
                        <option value="0" <?php echo !$email_pref ? 'selected' : ''; ?>>Disabled</option>
                    </select>
                </div>
            </div>
        </div>

        <script>
            document.getElementById('email-pref-toggle').addEventListener('change', async function() {
                const val = this.value;
                const fd = new FormData();
                fd.append('email_notifications', val);
                fd.append('csrf_token', '<?php echo Core::csrf_token(); ?>');

                try {
                    const res = await fetch('api/update_profile.php', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await res.json();
                    if (data.success) {
                        // Success toast or subtle feedback
                        this.style.borderColor = 'var(--ok)';
                        setTimeout(() => this.style.borderColor = '', 1000);
                    } else {
                        alert('Error: ' + data.error);
                    }
                } catch (err) {
                    console.error(err);
                    alert('Failed to update settings.');
                }
            });
        </script>
    <?php endif; ?>
</div>

</body>
</html>
