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

    // Global Click Listener for Player and Modal
    document.addEventListener('click', (e) => {
        // Player Logic
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
                showToast('No sample available.', 'error');
                return;
            }

            stopAudio();
            currentAudio = new Audio(sampleUrl);
            currentAudio.play().catch(err => {
                showToast('Playback failed. Check your connection.', 'error');
                console.error(err);
            });
            
            card.classList.add('is-playing');
            playBtn.querySelector('svg').innerHTML = '<path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>';

            // Limit to 20 seconds
            audioTimer = setTimeout(() => {
                stopAudio();
                showToast('Sample preview ended (20s limit).', 'ok');
            }, 20000);

            currentAudio.onended = stopAudio;
            return;
        }

        // Modal Logic
        const trigger = e.target.closest('.open-bid') || e.target.closest('.lb-item');
        if (trigger) {
            const data = trigger.dataset;
            if (data.id) {
                document.getElementById('modal-beat-id').value = data.id;
                document.getElementById('modal-amount').value = data.min;
                document.getElementById('modal-amount').min = data.min;
                document.getElementById('modal-beat-info').innerHTML = `<strong>${data.title}</strong> <span class="spacer"></span> <span class="mono">Min: $${data.min}</span>`;
                
                refreshCaptcha();
                bidModal.classList.add('is-visible');
            }
        }

        // Close Modal
        if (e.target === bidModal || e.target.id === 'close-modal' || e.target.closest('#close-modal')) {
            bidModal.classList.remove('is-visible');
        }
    });

    function refreshCaptcha() {
        const label = document.getElementById('captcha-label');
        if (!label) return;
        const a = Math.floor(Math.random() * 10) + 1;
        const b = Math.floor(Math.random() * 10) + 1;
        label.innerText = `Security: ${a} + ${b} = ?`;
        bidForm.dataset.ans = a + b;
    }

    // Timer Logic
    const updateTimers = () => {
        document.querySelectorAll('.timer, [data-ends]').forEach(el => {
            const ends = el.getAttribute('data-ends');
            if (!ends) return;

            // Format for cross-browser compatibility (replace space with T for ISO)
            const endsAt = new Date(ends.replace(' ', 'T')).getTime();
            const diff = Math.max(0, Math.floor((endsAt - Date.now()) / 1000));
            
            if (isNaN(endsAt) || diff <= 0) {
                el.innerText = "CLOSED";
                el.classList.add('danger');
                return;
            }

            const m = Math.floor(diff / 60);
            const s = diff % 60;
            el.innerText = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
            
            if (diff < 120) el.classList.add('danger');
            else el.classList.remove('danger');
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
                showToast("Security check failed.", "error");
                refreshCaptcha();
                return;
            }

            const formData = new FormData(bidForm);
            const submitBtn = bidForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            const originalText = submitBtn.innerText;
            submitBtn.innerText = "Placing...";
            
            try {
                const res = await fetch('api/bid.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                
                if (data.success) {
                    showToast(`Bid placed successfully!`, 'ok');
                    bidModal.classList.remove('is-visible');
                    bidForm.reset();
                } else {
                    showToast(data.error || 'Failed to place bid', 'error');
                }
            } catch (err) {
                showToast('Network error', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerText = originalText;
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
            item.innerHTML = `<span class="dot"></span><span><b>@${act.user_handle}</b> ${act.message}</span>`;
            activityList.prepend(item);
            if (activityList.children.length > 5) activityList.lastChild.remove();
            
            if (act.type === 'bid') {
                showToast(`New bid on ${act.title}: $${act.current_bid}`, 'ok');
            }
        });

        // Optional: Update UI based on SSE updates
        evtSource.addEventListener('update', (e) => {
            const data = JSON.parse(e.data);
            // This can be used to update all cards at once if needed
        });
    } catch(err) { console.warn("SSE failed", err); }

    // Toast Helper
    function showToast(msg, kind) {
        const toast = document.createElement('div');
        toast.className = 'toast show';
        toast.style.background = kind === 'ok' ? 'var(--ok)' : 'var(--danger)';
        toast.innerHTML = `<span class="tdot" style="background:#fff"></span> ${msg}`;
        toastContainer.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 400);
        }, 4000);
    }
});
