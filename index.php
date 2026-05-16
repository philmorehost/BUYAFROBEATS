<?php
if (!file_exists(__DIR__ . '/config.php')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $base_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    header("Location: $base_url/install/");
    exit;
}
require_once __DIR__ . '/includes/Core.php';
require_once __DIR__ . '/includes/Auction.php';

use BAF\Core;
use BAF\Auction;

$core = Core::get_instance();
$auction = new Auction($core);

// Maintenance is handled via api/cron.php for performance
// $auction->check_for_winners();
// $auction->cleanup_sold_beats();

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
                    <div class="stat-pill"><span class="k">Timer</span><span class="v">Starts on first bid</span></div>
                </div>

                <div style="margin-top: 32px; display: flex; gap: 12px;">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="admin/index" class="btn btn-primary" style="padding: 12px 24px; font-weight: 600;">Enter My Studio →</a>
                    <?php else: ?>
                        <a href="register" class="btn btn-primary" style="padding: 12px 24px; font-weight: 600;">Join the Auction →</a>
                        <a href="login" class="btn" style="padding: 12px 24px; font-weight: 600;">Sign In</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="filter-row">
                <form action="index" method="GET" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; flex: 1;">
                    <div class="search">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--ink-mute)"><circle cx="11" cy="11" r="7"/><path d="m20 20-3-3"/></svg>
                        <input name="search" placeholder="Search title or genre…" value="<?php echo Core::escape($search); ?>">
                    </div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <?php
                        $genres = ['All', 'Afrobeats', 'Amapiano', 'Afro-Swing', 'Afro-House', 'Afro-Fusion', 'Afro-Pop'];
                        foreach ($genres as $g): ?>
                            <a href="?genre=<?php echo urlencode($g); ?>" class="chip <?php echo $genre === $g ? 'is-active' : ''; ?>"><?php echo $g; ?></a>
                        <?php endforeach; ?>
                    </div>
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
                                <button type="button" class="play" aria-label="Play" data-sample="api/serve?id=<?php echo $beat['id']; ?>">
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
                                         <div class="v timer" data-ends="<?php echo $beat['ends_at'] ? strtotime($beat['ends_at']) : ''; ?>"><?php echo empty($beat['top_bidder']) ? 'Waiting' : '...'; ?></div>
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
                                        <button type="button" class="btn btn-primary open-bid" data-id="<?php echo $beat['id']; ?>" data-beat="<?php echo htmlspecialchars(json_encode($beat)); ?>">Place bid</button>
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
                <div id="leaderboard-items">
                    <?php foreach ($leaderboard as $i => $lb): ?>
                        <div class="lb-item" data-beat-id="<?php echo $lb['id']; ?>">
                            <div class="lb-rank"><?php echo str_pad($i+1, 2, '0', STR_PAD_LEFT); ?></div>
                            <div class="lb-cover" style="background: oklch(0.3 0.08 <?php echo (crc32($lb['title']) % 360); ?>)"></div>
                            <div class="lb-info">
                                <div class="lb-title"><?php echo Core::escape($lb['title']); ?></div>
                                <div class="lb-sub"><span class="bid-count"><?php echo $core->db()->query("SELECT COUNT(*) FROM bids WHERE beat_id = {$lb['id']}")->fetchColumn(); ?> bids</span></div>
                            </div>
                            <div class="lb-bid">
                                <div class="amt">$<?php echo number_format($lb['current_bid'], 0); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="lb-activity">
                    <h4>Live activity</h4>
                    <div id="activity-list" style="margin-bottom: 24px;">
                        <?php foreach ($activity as $act): ?>
                            <div class="activity-item fade-in">
                                <span class="dot"></span>
                                <span><b>@<?php echo Core::escape($act['user_handle']); ?></b> <?php echo Core::escape($act['message']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="house-rules" style="background: var(--bg-2); border: 1px solid var(--line); border-radius: 16px; padding: 16px;">
                        <h5 style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--ink-mute); margin-bottom: 12px;">House Rules</h5>
                        <ul style="margin: 0; padding: 0; list-style: none; font-size: 11px; color: var(--ink-dim); line-height: 1.5;">
                            <li style="margin-bottom: 8px;"><b>1. Ownership:</b> Winner receives 100% rights, master WAV, stems, and signed license.</li>
                            <li style="margin-bottom: 8px;"><b>2. Credit:</b> Every release must include the "Produced by OBV" credit in metadata.</li>
                            <li style="margin-bottom: 8px;"><b>3. Window:</b> Files must be downloaded within 7 days of purchase confirmation.</li>
                            <li><b>4. Breach:</b> Failure to credit is a material breach and may result in license revocation.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- Bid Modal -->
<div id="bid-modal" class="backdrop">
    <div class="modal">
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <h3 style="margin:0">Place a bid</h3>
            <div id="modal-beat-title" style="font-family:'JetBrains Mono'; font-size:12px; color:var(--accent)"></div>
        </div>
        <p class="lead" style="font-size:12px; margin-bottom:16px;">Highest bid at zero wins. Timer extends by 2 mins if a bid lands late.</p>
        
        <div id="bid-history-container" style="margin-bottom: 18px; max-height: 120px; overflow-y: auto; background: var(--bg-2); border-radius: 12px; padding: 12px; display:none;">
            <div style="font-size: 9px; color: var(--ink-mute); margin-bottom: 8px; text-transform: uppercase; font-weight:700;">Recent Bids</div>
            <div id="bid-history-list" style="font-size:12px;"></div>
        </div>

        <form id="bid-form">
            <input type="hidden" name="beat_id" id="modal-beat-id">
            <input type="hidden" name="csrf_token" value="<?php echo Core::csrf_token(); ?>">
            <div class="field">
                <label>Your bid (USD)</label>
                <input type="number" name="amount" id="modal-amount" step="5" required>
                <div class="hint" id="min-bid-hint">Minimum bid: $0</div>
            </div>
            <div class="row2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="field"><label>Your handle</label><input type="text" name="handle" placeholder="@yourname" required></div>
                <div class="field"><label>Email</label><input type="email" name="email" placeholder="you@example.com" required></div>
            </div>
            <div class="field">
                <label id="captcha-label">Security: 0 + 0 = ?</label>
                <input type="number" name="captcha_ans" placeholder="Enter answer" required>
            </div>
            <div class="actions" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-ghost" id="close-modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Place Bid →</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . "/includes/footer.php"; ?>
