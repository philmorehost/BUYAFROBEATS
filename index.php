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


$genre = $_GET['genre'] ?? 'All';
$search = $_GET['search'] ?? '';

$beats = $auction->get_live_beats($genre, $search);
$leaderboard = $auction->get_leaderboard();
$activity = $core->db()->query("SELECT * FROM activity ORDER BY created_at DESC LIMIT 6")->fetchAll();

// Pre-load bid counts
$beat_ids = array_column($beats, 'id');
$bid_counts = [];
if (!empty($beat_ids)) {
    $placeholders = implode(',', $beat_ids);
    $stmt = $core->db()->query("SELECT beat_id, COUNT(*) as bid_count FROM bids WHERE beat_id IN ($placeholders) GROUP BY beat_id");
    foreach ($stmt->fetchAll() as $row) {
        $bid_counts[$row['beat_id']] = $row['bid_count'];
    }
}

$live_count = count($beats);
$total_bids = $core->db()->query("SELECT COUNT(*) FROM bids")->fetchColumn();

include __DIR__ . '/includes/header.php';
?>

<div class="page">
    <div class="layout">
        <main>
            <section class="hero">
                <div class="eyebrow"><span class="live-dot"></span> Live marketplace</div>
                <h1>One-of-one <em>exclusive</em> beats.</h1>
                <p>Own the sound. Every beat is a limited masterpiece. Once the auction ends, the files are delivered and the listing is purged forever. No leases, no compromises.</p>
                
                <div class="hero-stats">
                    <div class="stat-pill"><span class="k">Live drops</span><span class="v accent"><?php echo $live_count; ?></span></div>
                    <div class="stat-pill"><span class="k">Total bids</span><span class="v"><?php echo number_format($total_bids); ?></span></div>
                    <div class="stat-pill"><span class="k">Window</span><span class="v">30m</span></div>
                </div>

                <div style="margin-top: 48px; display: flex; gap: 16px;">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="admin/index" class="btn btn-primary">Open Studio →</a>
                    <?php else: ?>
                        <a href="register" class="btn btn-primary">Start Bidding</a>
                        <a href="login" class="btn">Artist Login</a>
                    <?php endif; ?>
                </div>
            </section>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; gap: 20px; flex-wrap: wrap;">
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <?php
                    $genres = ['All', 'Afrobeats', 'Amapiano', 'Afro-Swing', 'Afro-House', 'Afro-Pop'];
                    foreach ($genres as $g): ?>
                        <a href="?genre=<?php echo urlencode($g); ?>" class="chip <?php echo $genre === $g ? 'is-active' : ''; ?>"><?php echo $g; ?></a>
                    <?php endforeach; ?>
                </div>
                <form action="index" method="GET" class="search" style="max-width: 300px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3-3"/></svg>
                    <input name="search" placeholder="Search beats..." value="<?php echo Core::escape($search); ?>">
                </form>
            </div>

            <?php if (empty($beats)): ?>
                <div class="empty">
                    <h3>No beats found</h3>
                    <p>Try a different genre or check back later for the next drop.</p>
                </div>
            <?php else: ?>
                <div class="grid">
                    <?php foreach ($beats as $beat):
                        $timeLeft = $beat['ends_at'] ? strtotime($beat['ends_at']) - time() : null;
                        $status = $beat['status'];
                        $bc = $bid_counts[$beat['id']] ?? 0;
                        if ($status === 'live') {
                            if ($beat['ends_at'] && $timeLeft <= 0) $status = 'ending';
                            elseif ($timeLeft && $timeLeft < 300) $status = 'ending';
                            elseif ($bc >= 4) $status = 'hot';
                        }
                    ?>
                        <div class="card <?php echo $status === 'sold' ? 'is-sold' : ''; ?>" data-id="<?php echo $beat['id']; ?>">
                            <div class="cover">
                                <svg class="stripes" viewBox="0 0 100 100" preserveAspectRatio="none">
                                    <defs>
                                        <linearGradient id="g-<?php echo $beat['id']; ?>" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" stop-color="oklch(0.3 0.08 <?php echo (crc32($beat['title']) % 360); ?>)" />
                                            <stop offset="100%" stop-color="oklch(0.22 0.06 <?php echo ((crc32($beat['title']) * 7) % 360); ?>)" />
                                        </linearGradient>
                                    </defs>
                                    <rect width="100" height="100" fill="url(#g-<?php echo $beat['id']; ?>)" />
                                </svg>
                                <span class="status-badge <?php echo $status; ?>"><?php echo $status; ?></span>
                                <button class="play" data-sample="<?php echo ($beat['sample_url'] || $beat['sample_path']) ? 'api/serve.php?beat_id=' . $beat['id'] . '&type=sample' : ''; ?>">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="card-title"><?php echo Core::escape($beat['title']); ?></div>
                                <div class="card-meta"><?php echo $beat['bpm']; ?> BPM · <?php echo $beat['key_sig']; ?> · <?php echo $beat['genre']; ?></div>
                                
                                <div class="waveform">
                                    <?php for($i=0;$i<32;$i++): ?>
                                        <div class="bar" style="height: <?php echo rand(20, 100); ?>%"></div>
                                    <?php endfor; ?>
                                </div>

                                <div class="bid-row">
                                    <div class="bid-info">
                                        <div class="label">Current</div>
                                        <div class="val">$<?php echo number_format($beat['current_bid'], 0); ?></div>
                                    </div>
                                    <div class="bid-timer">
                                        <div class="label">Ends in</div>
                                        <div class="val timer" data-ends-ts="<?php echo $beat['ends_at'] ? strtotime($beat['ends_at']) : ''; ?>"><?php echo empty($beat['top_bidder']) ? '30:00' : '--:--'; ?></div>
                                    </div>
                                </div>

                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <div class="card-meta"><b><?php echo $bc; ?></b> bids</div>
                                    <?php if ($beat['status'] === 'live'): 
                                        $min_bid = empty($beat['top_bidder']) ? $beat['starting_bid'] : $beat['current_bid'] + 5;
                                    ?>
                                        <button type="button" class="btn btn-primary open-bid" 
                                                data-id="<?php echo $beat['id']; ?>" 
                                                data-title="<?php echo Core::escape($beat['title']); ?>" 
                                                data-min="<?php echo $min_bid; ?>">Place Bid</button>
                                    <?php else: ?>
                                        <button class="btn" disabled>SOLD</button>
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
                <div class="lb-title"><span class="live-dot"></span> Bidding War</div>
                <div class="lb-list">
                    <?php foreach ($leaderboard as $i => $lb): ?>
                        <div class="lb-card open-bid" 
                             data-id="<?php echo $lb['id']; ?>" 
                             data-title="<?php echo Core::escape($lb['title']); ?>" 
                             data-min="<?php echo empty($lb['top_bidder']) ? $lb['starting_bid'] : $lb['current_bid'] + 5; ?>">
                            <div class="lb-rank"><?php echo $i + 1; ?></div>
                            <div class="lb-item-info">
                                <div class="lb-item-title"><?php echo Core::escape($lb['title']); ?></div>
                                <div class="lb-item-bid">$<?php echo number_format($lb['current_bid'], 0); ?></div>
                            </div>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="margin-top: 32px;">
                    <div class="lb-title" style="font-size: 14px; color: var(--ink-mute);">Recent Activity</div>
                    <div id="activity-list">
                        <?php foreach ($activity as $act): ?>
                            <div class="activity-item" style="margin-bottom: 12px; font-size: 13px;">
                                <span style="color: var(--accent);">@<?php echo Core::escape($act['user_handle']); ?></span>
                                <span style="color: var(--ink-dim);"><?php echo Core::escape($act['message']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<div id="bid-modal" class="backdrop">
    <div class="modal">
        <h2 style="margin: 0 0 8px;">Place Your Bid</h2>
        <p style="color: var(--ink-dim); font-size: 14px; margin: 0 0 24px;">Enter your details to join the auction. Min increment is $5.</p>
        
        <div id="modal-beat-info" style="background: var(--bg); border: 1px solid var(--line); padding: 16px; border-radius: 16px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
            <!-- Dynamic info -->
        </div>

        <form id="bid-form">
            <input type="hidden" name="beat_id" id="modal-beat-id">
            <input type="hidden" name="csrf_token" value="<?php echo Core::csrf_token(); ?>">
            
            <div class="field">
                <label>Bid Amount (USD)</label>
                <input type="number" name="amount" id="modal-amount" step="5" required>
            </div>
            
            <div class="row2">
                <div class="field"><label>Handle</label><input type="text" name="handle" placeholder="@name" required></div>
                <div class="field"><label>Email</label><input type="email" name="email" placeholder="email@addr.com" required></div>
            </div>

            <div class="field">
                <label id="captcha-label">Security Check</label>
                <input type="number" name="captcha_ans" placeholder="Answer" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 24px;">
                <button type="button" class="btn" id="close-modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Confirm Bid</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
