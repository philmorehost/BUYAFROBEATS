<?php
require_once __DIR__ . '/includes/Core.php';
require_once __DIR__ . '/includes/Auction.php';

use BAF\Core;
use BAF\Auction;

$core = Core::get_instance();
$auction = new Auction($core);

$genre = $_GET['genre'] ?? 'All';
$search = $_GET['search'] ?? '';
$beats = $auction->get_live_beats($genre, $search);
$leaderboard = $auction->get_leaderboard(6);

$current_tab = 'market';
include __DIR__ . '/includes/header.php';
?>

<main class="page">
    <div class="hero-layout">
        <section class="hero">
            <div class="eyebrow mono">
                <span class="live-dot"></span>
                LIVE NOW · BIDDING OPEN
            </div>
            <h1>One-of-one beats.<br><em>Highest bid wins.</em></h1>
            <p>Every beat here is mine. Bid, outbid, lose sleep. When the 30-minute timer hits zero, the file ships to the winner's inbox and the beat <em style="color:var(--accent); font-style:normal;">vanishes</em> from this site forever.</p>
            
            <div class="hero-stats">
                <div class="stat-pill">
                    <span class="k">Open</span>
                    <span class="v accent"><?php echo count($beats); ?></span>
                </div>
                <div class="stat-pill">
                    <span class="k">Active Bids</span>
                    <span class="v">124</span>
                </div>
                <div class="stat-pill">
                    <span class="k">Sold</span>
                    <span class="v">7</span>
                </div>
                <div class="stat-pill">
                    <span class="k">Timer</span>
                    <span class="v">30:00 from first bid</span>
                </div>
            </div>
        </section>

        <aside class="top-sidebar">
            <div class="leaderboard">
                <div class="lb-head">
                    <h3><span class="live-dot" style="background:var(--ok)"></span> Leaderboard</h3>
                    <span class="hint">TOP 6 · LIVE</span>
                </div>
                
                <div class="lb-list">
                    <?php if (empty($leaderboard)): ?>
                        <div class="lb-empty mono" style="font-size: 12px; color: var(--ink-mute); padding: 40px 0; text-align: center;">No live auctions right now.</div>
                    <?php endif; ?>
                    <?php foreach ($leaderboard as $index => $lb): ?>
                        <div class="lb-item">
                            <div class="lb-rank">0<?php echo $index + 1; ?></div>
                            <div class="lb-cover" style="background: var(--bg-3) url('api/serve.php?beat_id=<?php echo $lb['id']; ?>&type=cover') center/cover;"></div>
                            <div class="lb-info">
                                <div class="lb-title ink-bright"><?php echo Core::escape($lb['title']); ?></div>
                                <div class="lb-sub"><?php echo count($auction->get_bids($lb['id'])); ?> bids</div>
                            </div>
                            <div class="lb-bid">
                                <div class="amt">$<?php echo number_format($lb['current_bid'], 0); ?></div>
                                <div class="lb-sub timer" data-ends="<?php echo strtotime($lb['ends_at']); ?>" style="text-align: right;">--:--</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
    </div>

    <div class="layout">
        <div class="main-content">
            <div class="filter-row">
                <div class="search" style="margin-bottom: 20px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--ink-mute)" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" id="search-input" placeholder="Search title or genre..." value="<?php echo Core::escape($search); ?>" onkeyup="if(event.key==='Enter') window.location.href='?search='+encodeURIComponent(this.value)">
                </div>
                <div class="genres">
                    <a href="?genre=All" class="chip <?php echo $genre === 'All' ? 'is-active' : ''; ?>">All</a>
                    <a href="?genre=Afrobeats" class="chip <?php echo $genre === 'Afrobeats' ? 'is-active' : ''; ?>">Afrobeats</a>
                    <a href="?genre=Amapiano" class="chip <?php echo $genre === 'Amapiano' ? 'is-active' : ''; ?>">Amapiano</a>
                    <a href="?genre=Afro-Swing" class="chip <?php echo $genre === 'Afro-Swing' ? 'is-active' : ''; ?>">Afro-Swing</a>
                </div>
            </div>

            <div class="grid" id="auction-grid">
                <?php if (empty($beats)): ?>
                    <div class="empty" style="grid-column: 1/-1; text-align: center; padding: 100px 0; border: 1px dashed var(--line); border-radius: 20px;">
                        <h3 class="ink-bright">No open auctions</h3>
                        <p class="ink-dim">Check back soon — new drops come fast.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($beats as $beat): 
                        $ends_at_ts = $beat['ends_at'] ? strtotime($beat['ends_at']) : 0;
                    ?>
                        <article class="card" data-id="<?php echo $beat['id']; ?>">
                            <div class="cover">
                                <div class="status live">LIVE</div>
                                <button class="play" onclick="togglePlay(<?php echo $beat['id']; ?>, 'api/serve.php?beat_id=<?php echo $beat['id']; ?>&type=audio')">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                </button>
                                <div class="label mono"><?php echo Core::escape($beat['genre']); ?> · <?php echo $beat['bpm']; ?> BPM</div>
                            </div>
                            <div class="card-content">
                                <div class="card-title ink-bright"><?php echo Core::escape($beat['title']); ?></div>
                                <div class="card-meta mono"><?php echo Core::escape($beat['key_sig']); ?> · <?php echo $beat['duration']; ?></div>
                                
                                <div class="auction-info">
                                    <div class="auction-stat">
                                        <div class="label">Top Bid</div>
                                        <div class="value accent ink-bright"><?php echo $beat['top_bidder'] ? '$' . number_format($beat['current_bid'], 0) : '—'; ?></div>
                                    </div>
                                    <div class="auction-stat">
                                        <div class="label">Time Left</div>
                                        <div class="value timer ink-bright" data-ends="<?php echo $ends_at_ts; ?>">--:--</div>
                                    </div>
                                </div>

                                <div style="margin-top: 16px;">
                                    <button class="btn btn-primary" style="width: 100%; justify-content: center;" onclick='openBidModal(<?php echo json_encode($beat); ?>)'>
                                        Place Bid
                                    </button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <aside class="sidebar">
            <div class="lb-activity">
                <h4>Live Activity</h4>
                <div id="activity-feed">
                    <!-- Populated by SSE -->
                </div>
            </div>

            <div class="info-block" style="background: rgba(255,159,0,0.05); border-color: rgba(255,159,0,0.2);">
                <h4>If you win</h4>
                <ul>
                    <li><b>It's yours, 100%.</b> Full ownership — release, sync, sample, sell, no royalties.</li>
                    <li>Credit <b>"Produced by OBV"</b> on every platform. One line, that's it.</li>
                    <li><b>7 days to download.</b> Files wipe from our servers after a week.</li>
                    <li>No credit = breach of contract.</li>
                </ul>
            </div>
        </aside>
    </div>
</main>

<!-- Bid Modal -->
<div id="bid-modal" class="backdrop" style="display:none">
    <div class="modal">
        <h3>Confirm your bid</h3>
        <p class="lead">By bidding, you agree to pay within 24 hours if you win. High bids are final.</p>
        
        <div class="line-item">
            <div class="mini-cover" id="bid-mini-cover"></div>
            <div>
                <div class="t" id="bid-beat-title">Beat Title</div>
                <div class="s" id="bid-beat-meta">Genre · BPM</div>
            </div>
            <div class="amt">
                <div class="sml">Your Bid</div>
                <div class="big" id="bid-display-amount">$0.00</div>
            </div>
        </div>

        <form id="bid-form">
            <input type="hidden" name="beat_id" id="bid-beat-id">
            <input type="hidden" name="csrf_token" value="<?php echo Core::csrf_token(); ?>">
            
            <div class="bid-quick">
                <button type="button" onclick="adjustBid(5)">+$5</button>
                <button type="button" onclick="adjustBid(25)">+$25</button>
                <button type="button" onclick="adjustBid(100)">+$100</button>
            </div>

            <div class="field">
                <label>Bid Amount ($)</label>
                <input type="number" name="amount" id="bid-amount-input" step="5" required oninput="document.getElementById('bid-display-amount').innerText = '$' + Number(this.value).toLocaleString()">
            </div>

            <div class="house-rules">
                <div class="hr-h">House Rules</div>
                <ul>
                    <li>Payment via <b>Crypto (BTC/ETH/USDT)</b> only.</li>
                    <li>Exclusive 24-hour payment window.</li>
                    <li>Access to files shared via <b>Google Drive</b> for 7 days.</li>
                </ul>
            </div>

            <div class="actions">
                <button type="button" class="btn btn-ghost" onclick="closeBidModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="bid-submit-btn">Confirm Bid</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
