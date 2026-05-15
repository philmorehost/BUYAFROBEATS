document.addEventListener('DOMContentLoaded', () => {
    const bidModal = document.getElementById('bid-modal');
    const bidForm = document.getElementById('bid-form');
    const toastContainer = document.getElementById('toast-container');
    const activityList = document.getElementById('activity-list');

    // Player Logic
    let currentAudio = null;
    let audioTimer = null;

    const stopAudio = () => {
        if (currentAudio) {
            currentAudio.pause();
            currentAudio = null;
        }
        if (audioTimer) {
            clearTimeout(audioTimer);
            audioTimer = null;
        }
        document.querySelectorAll('.card').forEach(c => c.classList.remove('is-playing'));
        document.querySelectorAll('.play svg').forEach(s => s.innerHTML = '<path d="M8 5v14l11-7z"/>');
    };

    // Global Click Listener
    document.addEventListener('click', (e) => {
        // Player
        const playBtn = e.target.closest('.play');
        if (playBtn) {
            e.stopPropagation();
            const card = playBtn.closest('.card');
            const sampleUrl = playBtn.getAttribute('data-sample');

            if (card.classList.contains('is-playing')) {
                stopAudio();
                return;
            }

            if (!sampleUrl) {
                showToast('Sample not available.', 'error');
                return;
            }

            stopAudio();
            currentAudio = new Audio(sampleUrl);
            currentAudio.play().catch(err => {
                showToast('Playback blocked or failed.', 'error');
            });
            
            card.classList.add('is-playing');
            playBtn.querySelector('svg').innerHTML = '<path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>';

            audioTimer = setTimeout(() => stopAudio(), 30000); // 30s preview
            currentAudio.onended = stopAudio;
            return;
        }

        // Modal triggers
        const trigger = e.target.closest('.open-bid');
        if (trigger) {
            const data = trigger.dataset;
            if (data.id) {
                document.getElementById('modal-beat-id').value = data.id;
                document.getElementById('modal-amount').value = data.min;
                document.getElementById('modal-amount').min = data.min;
                document.getElementById('modal-beat-info').innerHTML = `
                    <div style="font-weight:700; font-size:16px;">${data.title}</div>
                    <div style="font-family:'JetBrains Mono',monospace; color:var(--accent); font-weight:700;">Min: $${data.min}</div>
                `;
                
                refreshCaptcha();
                bidModal.classList.add('is-visible');
            }
        }

        // Modal Close
        if (e.target === bidModal || e.target.id === 'close-modal' || e.target.closest('#close-modal')) {
            bidModal.classList.remove('is-visible');
        }
    });

    function refreshCaptcha() {
        const label = document.getElementById('captcha-label');
        if (!label) return;
        const a = Math.floor(Math.random() * 9) + 1;
        const b = Math.floor(Math.random() * 9) + 1;
        label.innerText = `Security: ${a} + ${b} = ?`;
        bidForm.dataset.ans = a + b;
    }

    // Timer Logic
    const updateTimers = () => {
        document.querySelectorAll('.timer').forEach(el => {
            const ends = el.getAttribute('data-ends');
            if (!ends) {
                el.innerText = '30:00';
                return;
            }

            const endsAt = new Date(ends.replace(' ', 'T')).getTime();
            const diff = Math.max(0, Math.floor((endsAt - Date.now()) / 1000));
            
            if (isNaN(endsAt) || diff <= 0) {
                el.innerText = "CLOSED";
                el.closest('.card')?.classList.add('is-sold');
                return;
            }

            const m = Math.floor(diff / 60);
            const s = diff % 60;
            el.innerText = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
            
            if (diff < 120) el.style.color = 'var(--danger)';
            else el.style.color = '';
        });
    };
    setInterval(updateTimers, 1000);
    updateTimers();

    // Bid Submission
    if (bidForm) {
        bidForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const ans = bidForm.elements['captcha_ans'].value;
            if (ans != bidForm.dataset.ans) {
                showToast("Captcha failed.", "error");
                refreshCaptcha();
                return;
            }

            const submitBtn = bidForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerText = "Processing...";
            
            try {
                const res = await fetch('api/bid.php', {
                    method: 'POST',
                    body: new FormData(bidForm),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                
                if (data.success) {
                    showToast(`Bid confirmed!`, 'ok');
                    bidModal.classList.remove('is-visible');
                    bidForm.reset();
                } else {
                    showToast(data.error || 'Bid failed', 'error');
                }
            } catch (err) {
                showToast('Connection error', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerText = "Confirm Bid";
            }
        });
    }

    // SSE Integration
    try {
        const evtSource = new EventSource('api/updates.php');
        evtSource.addEventListener('activity', (e) => {
            const act = JSON.parse(e.data);
            if (!activityList) return;
            const item = document.createElement('div');
            item.className = 'activity-item fade-in';
            item.innerHTML = `<span style="color:var(--accent)">@${act.user_handle}</span> <span style="color:var(--ink-dim)">${act.message}</span>`;
            activityList.prepend(item);
            if (activityList.children.length > 6) activityList.lastChild.remove();
            
            if (act.type === 'bid') {
                showToast(`New bid: $${act.current_bid} on ${act.title}`, 'ok');
                
                // Update specific card UI
                const card = document.querySelector(`.card[data-id="${act.beat_id}"]`);
                if (card) {
                    card.querySelector('.val').innerText = `$${Number(act.current_bid).toLocaleString()}`;
                    card.querySelector('.timer').setAttribute('data-ends', act.ends_at);
                    card.querySelector('.card-meta b').innerText = parseInt(card.querySelector('.card-meta b').innerText) + 1;
                }
            }
        });
    } catch(err) {}

    function showToast(msg, kind) {
        const toast = document.createElement('div');
        toast.className = 'toast show';
        toast.style.cssText = `
            position: fixed; bottom: 32px; right: 32px; 
            background: var(--bg-3); border: 1px solid var(--line); 
            padding: 16px 24px; border-radius: 16px; color: #fff; 
            font-size: 14px; font-weight: 600; z-index: 2000;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            border-left: 4px solid ${kind === 'ok' ? 'var(--ok)' : 'var(--danger)'};
        `;
        toast.innerText = msg;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }
});
