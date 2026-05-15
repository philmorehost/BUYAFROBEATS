document.addEventListener('DOMContentLoaded', () => {
    const bidModal = document.getElementById('bid-modal');
    const bidForm = document.getElementById('bid-form');
    const toastContainer = document.getElementById('toast-container');
    const activityList = document.getElementById('activity-list');

    // Sync server time offset (optional but good)
    let serverTimeOffset = 0; 

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
        label.innerText = `Security Check: ${a} + ${b} = ?`;
        bidForm.dataset.ans = a + b;
    }

    // Timer Logic
    const updateTimers = () => {
        const nowTs = Math.floor(Date.now() / 1000);
        document.querySelectorAll('.timer').forEach(el => {
            const endsTs = el.getAttribute('data-ends-ts');
            if (!endsTs) {
                el.innerText = '30:00';
                return;
            }

            const diff = Math.max(0, parseInt(endsTs) - nowTs);
            
            if (diff <= 0) {
                el.innerText = "CLOSED";
                el.style.color = 'var(--ink-mute)';
                const card = el.closest('.card');
                if (card && !card.classList.contains('is-sold')) {
                   // Refresh after a small delay to show winner if status changed
                }
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
                const res = await fetch('api/bid', {
                    method: 'POST',
                    body: new FormData(bidForm),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                
                if (data.success) {
                    showToast(`Bid confirmed!`, 'ok');
                    bidModal.classList.remove('is-visible');
                    bidForm.reset();
                    
                    // Immediately update local timer to avoid "Closed" flicker
                    if (data.ends_at) {
                        const card = document.querySelector(`.card[data-id="${bidForm.elements['beat_id'].value}"]`);
                        if (card) {
                            const timer = card.querySelector('.timer');
                            if (timer) timer.setAttribute('data-ends-ts', data.ends_at);
                        }
                    }
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
        const evtSource = new EventSource('api/updates');
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
                    const priceVal = card.querySelector('.bid-info .val');
                    if (priceVal) priceVal.innerText = `$${Number(act.current_bid).toLocaleString()}`;
                    
                    const timer = card.querySelector('.timer');
                    // Convert date string to timestamp for the new JS logic
                    if (timer && act.ends_at) {
                        const ts = Math.floor(new Date(act.ends_at.replace(' ', 'T')).getTime() / 1000);
                        timer.setAttribute('data-ends-ts', ts);
                    }
                    
                    const count = card.querySelector('.card-meta b');
                    if (count) count.innerText = parseInt(count.innerText) + 1;
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
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            setTimeout(() => toast.remove(), 400);
        }, 4000);
    }
});
