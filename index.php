<?php
if (!file_exists(__DIR__ . '/config.php')) {
    header('Location: ./install/');
    exit;
}
require_once __DIR__ . '/includes/Core.php';
require_once __DIR__ . '/includes/Auction.php';

use BAF\Core;
use BAF\Auction;

$core = Core::get_instance();
$auction = new Auction($core);

// Check for expired auctions and process winners
$auction->check_for_winners();
$auction->cleanup_sold_beats();

$genre = $_GET['genre'] ?? 'All';
$search = $_GET['search'] ?? '';

$beats = $auction->get_live_beats($genre, $search);
$leaderboard = $auction->get_leaderboard();
$activity = $core->db()->query("SELECT * FROM activity ORDER BY created_at DESC LIMIT 6")->fetchAll();

$live_count = count($beats);
$total_bids = $core->db()->query("SELECT COUNT(*) FROM bids")->fetchColumn();

include __DIR__ . '/includes/header.php';
?>

<div class="page">
    <div class="layout">
        <main>
            <div class="hero">
                <div class="eyebrow"><span class="live-dot" style="background: var(--accent);"></span> Live now · bidding open</div>
                <h1>One-of-one beats. <em>Highest bid wins.</em></h1>
                <p>Every beat here is mine. Bid, outbid, lose sleep. When the 30-minute timer hits zero, the file ships to the winner's inbox and the beat <em style="color:var(--accent); font-style:normal">vanishes</em> from this site forever.</p>
                <div class="hero-stats">
                    <div class="stat-pill"><span class="k">Open</span><span class="v accent"><?php echo $live_count; ?></span></div>
                    <div class="stat-pill"><span class="k">Active bids</span><span class="v"><?php echo $total_bids; ?></span></div>
                    <div class="stat-pill"><span class="k">Timer</span><span class="v">30:00 from first bid</span></div>
                </div>
            </div>

            <div class="filter-row">
                <form action="index.php" method="GET" style="display: contents;">
                    <div class="search">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--ink-mute)"><circle cx="11" cy="11" r="7"/><path d="m20 20-3-3"/></svg>
                        <input name="search" placeholder="Search title or genre…" value="<?php echo Core::escape($search); ?>">
                    </div>
                    <?php 
                    $genres = ['All', 'Afrobeats', 'Amapiano', 'Afro-Swing', 'Afro-House', 'Afro-Fusion', 'Afro-Pop'];
                    foreach ($genres as $g): ?>
                        <a href="?genre=<?php echo urlencode($g); ?>" class="chip <?php echo $genre === $g ? 'is-active' : ''; ?>"><?php echo $g; ?></a>
                    <?php endforeach; ?>
                </form>
            </div>

            <?php if (empty($beats)): ?>
                <div class="empty" style="border: 1px dashed var(--line); border-radius: 18px; padding: 60px 20px; text-align:center; color: var(--ink-dim);">
                    <h3 style="color: var(--ink); font-weight: 600; margin: 0 0 6px; letter-spacing: -0.01em;">No open auctions</h3>
                    <p>Check back soon — new drops come fast.</p>
                </div>
            <?php else: ?>
                <div class="grid">
                    <?php foreach ($beats as $beat): 
                        $timeLeft = $beat['ends_at'] ? strtotime($beat['ends_at']) - time() : null;
                        $status = $beat['status'];
                        if ($status === 'live') {
                            if ($beat['ends_at'] && $timeLeft <= 0) $status = 'ending';
                            elseif ($timeLeft && $timeLeft < 300) $status = 'ending';
                            elseif ($core->db()->query("SELECT COUNT(*) FROM bids WHERE beat_id = {$beat['id']}")->fetchColumn() >= 4) $status = 'hot';
                        }
                    ?>
                        <div class="card <?php echo ($status === 'hot' || $status === 'ending') ? 'is-hot' : ''; ?> <?php echo $status === 'sold' ? 'is-sold' : ''; ?>" data-id="<?php echo $beat['id']; ?>">
                            <div class="cover">
                                <!-- Waveform/Cover SVG logic -->
                                <svg class="stripes" viewBox="0 0 100 100" preserveAspectRatio="none">
                                    <defs>
                                        <linearGradient id="g-<?php echo $beat['id']; ?>" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" stop-color="oklch(0.3 0.08 <?php echo (crc32($beat['title']) % 360); ?>)" />
                                            <stop offset="100%" stop-color="oklch(0.22 0.06 <?php echo ((crc32($beat['title']) * 7) % 360); ?>)" />
                                        </linearGradient>
                                    </defs>
                                    <rect width="100" height="100" fill="url(#g-<?php echo $beat['id']; ?>)" />
                                </svg>
                                <span class="status <?php echo $status; ?>"><?php echo strtoupper($status); ?></span>
                                <span class="label"><?php echo $beat['duration']; ?></span>
                                <button class="play" aria-label="Play">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                </button>
                            </div>
                            <div class="card-body">
                                <div>
                                    <div class="title"><?php echo Core::escape($beat['title']); ?></div>
                                    <div class="producer"><?php echo $beat['bpm']; ?> BPM · <?php echo $beat['key_sig']; ?> · <?php echo $beat['genre']; ?></div>
                                </div>
                                <div class="wave">
                                    <?php for($i=0;$i<32;$i++): ?>
                                        <div class="bar" style="height: <?php echo rand(4, 28); ?>px"></div>
                                    <?php endfor; ?>
                                </div>
                                <div class="auction-row">
                                    <div><div class="k">Current bid</div><div class="v accent">$<?php echo number_format($beat['current_bid'], 2); ?></div></div>
                                    <div><div class="k"><?php echo empty($beat['top_bidder']) ? 'Auction clock' : 'Time left'; ?></div>
                                         <div class="v timer" data-ends="<?php echo $beat['ends_at']; ?>"><?php echo empty($beat['top_bidder']) ? '30:00' : '...'; ?></div>
                                    </div>
                                </div>
                                <div class="card-actions">
                                    <div class="bidstats">
                                        <?php if ($beat['status'] === 'sold'): ?>
                                            Sold to <b><?php echo Core::escape($beat['top_bidder']); ?></b>
                                        <?php elseif (empty($beat['top_bidder'])): ?>
                                            No bids yet · starts at <b>$<?php echo number_format($beat['starting_bid'], 2); ?></b>
                                        <?php else: ?>
                                            <b><?php echo $core->db()->query("SELECT COUNT(*) FROM bids WHERE beat_id = {$beat['id']}")->fetchColumn(); ?></b> bids · top <b><?php echo Core::escape($beat['top_bidder']); ?></b>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($beat['status'] === 'live'): ?>
                                        <button class="btn btn-primary open-bid" data-beat='<?php echo json_encode($beat); ?>'>Place bid</button>
                                    <?php else: ?>
                                        <button class="btn btn-primary" disabled>SOLD</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>

        <aside>
            <div class="leaderboard">
                <div class="lb-head">
                    <h3><span class="live-dot"></span> Leaderboard</h3>
                    <span class="hint">TOP 6 · LIVE</span>
                </div>
                <?php foreach ($leaderboard as $i => $lb): ?>
                    <div class="lb-item">
                        <div class="lb-rank"><?php echo str_pad($i+1, 2, '0', STR_PAD_LEFT); ?></div>
                        <div class="lb-cover" style="background: oklch(0.3 0.08 <?php echo (crc32($lb['title']) % 360); ?>)"></div>
                        <div class="lb-info">
                            <div class="lb-title"><?php echo Core::escape($lb['title']); ?></div>
                            <div class="lb-sub"><span><?php echo $core->db()->query("SELECT COUNT(*) FROM bids WHERE beat_id = {$lb['id']}")->fetchColumn(); ?> bids</span></div>
                        </div>
                        <div class="lb-bid">
                            <div class="amt">$<?php echo number_format($lb['current_bid'], 2); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="lb-activity">
                    <h4>Live activity</h4>
                    <div id="activity-list">
                        <?php foreach ($activity as $act): ?>
                            <div class="activity-item">
                                <span class="dot"></span>
                                <span><b><?php echo Core::escape($act['user_handle']); ?></b> <?php echo Core::escape($act['message']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- Bid Modal -->
<div id="bid-modal" class="backdrop">
    <div class="modal">
        <h3>Place a bid</h3>
        <p class="lead">Highest bid at zero wins. Timer extends by 2 minutes if a bid lands in the final 2.</p>
        <div id="modal-beat-info" class="line-item" style="display: flex; gap: 14px; align-items: center; padding: 12px; border: 1px solid var(--line); border-radius: 12px; margin-bottom: 18px; background: var(--bg);">
            <!-- Dynamic content -->
        </div>
        <form id="bid-form">
            <input type="hidden" name="beat_id" id="modal-beat-id">
            <input type="hidden" name="csrf_token" value="<?php echo Core::csrf_token(); ?>">
            <input type="text" name="website_url" style="display:none !important" tabindex="-1" autocomplete="off">
            <div class="field">
                <label>Your bid (USD)</label>
                <input type="number" name="amount" id="modal-amount" step="5" required>
            </div>
            <div class="row2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="field"><label>Your handle</label><input type="text" name="handle" placeholder="@yourname" required></div>
                <div class="field"><label>Email</label><input type="email" name="email" placeholder="you@example.com" required></div>
            </div>
            <div class="actions" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-ghost" id="close-modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Place Bid →</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
