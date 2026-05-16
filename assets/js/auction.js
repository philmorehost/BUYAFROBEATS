/**
 * BEATZAZA v3.0 — Auction Controller
 */

const state = {
    playingId: null,
    audio: new Audio(),
    currentBidBeat: null,
    csrfToken: document.querySelector('input[name="csrf_token"]')?.value || '',
};

// Initialize Timers
function updateTimers() {
    document.querySelectorAll('.timer').forEach(el => {
        const endsAt = parseInt(el.dataset.ends);
        if (!endsAt) {
            el.innerText = '—';
            return;
        }

        const now = Math.floor(Date.now() / 1000);
        const diff = endsAt - now;

        if (diff <= 0) {
            el.innerText = '00:00';
            el.classList.add('danger');
            return;
        }

        const m = Math.floor(diff / 60);
        const s = diff % 60;
        el.innerText = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        
        if (diff < 300) {
            el.classList.add('danger');
        } else {
            el.classList.remove('danger');
        }
    });
}
setInterval(updateTimers, 1000);
updateTimers();

// Audio Handling
function togglePlay(id, url) {
    if (state.playingId === id) {
        state.audio.pause();
        state.playingId = null;
        updatePlayIcons();
        return;
    }

    state.playingId = id;
    state.audio.src = url;
    state.audio.play().catch(e => console.error('Playback failed', e));
    updatePlayIcons();
}

function updatePlayIcons() {
    document.querySelectorAll('.play svg').forEach(svg => {
        svg.innerHTML = '<path d="M8 5v14l11-7z"/>'; // Play icon
    });
    
    if (state.playingId) {
        const card = document.querySelector(`.card[data-id="${state.playingId}"]`);
        if (card) {
            const svg = card.querySelector('.play svg');
            svg.innerHTML = '<path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>'; // Pause icon
        }
    }
}

// Bidding
function openBidModal(beat) {
    state.currentBidBeat = beat;
    document.getElementById('bid-beat-id').value = beat.id;
    document.getElementById('bid-beat-title').innerText = beat.title;
    document.getElementById('bid-beat-meta').innerText = `${beat.genre} · ${beat.bpm} BPM`;
    
    const minBid = beat.top_bidder ? Number(beat.current_bid) + 5 : Number(beat.starting_bid);
    document.getElementById('bid-amount-input').value = minBid;
    document.getElementById('bid-amount-input').min = minBid;
    document.getElementById('bid-display-amount').innerText = '$' + minBid.toLocaleString();
    
    document.getElementById('bid-modal').style.display = 'flex';
}

function closeBidModal() {
    document.getElementById('bid-modal').style.display = 'none';
}

function adjustBid(inc) {
    const input = document.getElementById('bid-amount-input');
    input.value = Number(input.value) + inc;
    input.dispatchEvent(new Event('input'));
}

document.getElementById('bid-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('bid-submit-btn');
    btn.disabled = true;
    btn.innerText = 'Submitting...';

    const formData = new FormData(this);
    
    fetch('api/bid.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Bid placed successfully!');
            closeBidModal();
            // Optional: Fetch latest data or wait for SSE
        } else {
            if (data.error === 'AUTH_REQUIRED') {
                showToast('Please sign in to bid.', 'error');
                // Could trigger Google Sign-in prompt here
            } else {
                showToast(data.error || 'Failed to place bid.', 'error');
            }
        }
    })
    .catch(err => {
        showToast('Network error. Try again.', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerText = 'Confirm Bid';
    });
});

// SSE for Live Updates
function initUpdates() {
    const evtSource = new EventSource('api/updates.php');
    evtSource.onmessage = function(e) {
        const data = JSON.parse(e.data);
        if (data.type === 'bid') {
            updateCard(data.beat_id, data.amount, data.top_bidder, data.ends_at);
            pushActivity(data.handle, data.amount, data.beat_title);
        } else if (data.type === 'won') {
            removeCard(data.beat_id);
            showToast(`${data.handle} won ${data.beat_title}!`, 'hot');
        }
    };
}
// initUpdates(); // Uncomment when api/updates.php is ready

function updateCard(id, amount, bidder, endsAt) {
    const card = document.querySelector(`.card[data-id="${id}"]`);
    if (card) {
        card.querySelector('.auction-stat:first-child .value').innerText = '$' + Number(amount).toLocaleString();
        const timer = card.querySelector('.timer');
        timer.dataset.ends = Math.floor(new Date(endsAt).getTime() / 1000);
        
        card.classList.add('is-bumped');
        setTimeout(() => card.classList.remove('is-bumped'), 800);
    }
}

function removeCard(id) {
    const card = document.querySelector(`.card[data-id="${id}"]`);
    if (card) {
        card.style.opacity = '0';
        card.style.transform = 'scale(0.9)';
        setTimeout(() => card.remove(), 400);
    }
}

// UI Helpers
function showToast(msg, kind = 'ok') {
    const wrap = document.getElementById('toast-wrap');
    const toast = document.createElement('div');
    toast.className = `toast ${kind}`;
    toast.innerHTML = `<span class="tdot"></span><span>${msg}</span>`;
    wrap.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(20px)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

function pushActivity(handle, amount, title) {
    const feed = document.getElementById('activity-feed');
    const item = document.createElement('div');
    item.className = 'activity-item';
    item.innerHTML = `<span class="dot"></span><span><b>${handle}</b> bid <b>$${Number(amount).toLocaleString()}</b> on ${title}</span>`;
    feed.prepend(item);
    if (feed.children.length > 10) feed.lastChild.remove();
}

function openPolicy(type) {
    // Basic modal or redirect for now
    window.location.href = type + '.php';
}
