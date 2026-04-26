document.addEventListener('DOMContentLoaded', () => {
    const bidModal = document.getElementById('bid-modal');
    const bidForm = document.getElementById('bid-form');
    const toastContainer = document.getElementById('toast-container');
    const activityList = document.getElementById('activity-list');

    // SVG Generation Helpers
    const hash = (s) => { 
        let h = 2166136261; 
        for (let i=0; i<s.length; i++){ h ^= s.charCodeAt(i); h = (h*16777619) >>> 0; } 
        return h; 
    };

    const generateCover = (id, title, genre) => {
        const h = hash(id + title);
        const hue1 = h % 360; 
        const hue2 = (h * 7) % 360; 
        const angle = (h % 180) - 90;
        const sc = 4 + (h % 4);
        
        let stripesHtml = '';
        for(let i=0; i<sc; i++) {
            const off = (i/sc)*100;
            const w = (100/sc) * 0.45;
            stripesHtml += `<rect x="${off}" y="0" width="${w}" height="100" fill="oklch(0.40 0.10 ${hue1 + i*20})" opacity="0.35" />`;
        }

        return `
            <svg class="stripes" viewBox="0 0 100 100" preserveAspectRatio="none">
                <defs>
                    <linearGradient id="g-${id}" gradientTransform="rotate(${angle})">
                        <stop offset="0%" stop-color="oklch(0.30 0.08 ${hue1})" />
                        <stop offset="100%" stop-color="oklch(0.22 0.06 ${hue2})" />
                    </linearGradient>
                </defs>
                <rect width="100" height="100" fill="url(#g-${id})" />
                ${stripesHtml}
                <text x="8" y="94" fill="oklch(0.95 0.01 85)" opacity="0.9" font-size="7" font-family="JetBrains Mono, monospace" letter-spacing="0.5">${genre.toUpperCase()}</text>
            </svg>
        `;
    };

    const generateWaveform = (seed) => {
        const h = hash(seed);
        let barsHtml = '';
        for(let i=0; i<32; i++) {
            const v = Math.sin((h + i*13)*0.013)*0.5 + 0.5;
            const height = (0.2 + v*0.8) * 28 + 4;
            barsHtml += `<div class="bar" style="height: ${height}px"></div>`;
        }
        return barsHtml;
    };

    // Initialize existing cards
    document.querySelectorAll('.card').forEach(card => {
        const id = card.getAttribute('data-id');
        const title = card.querySelector('.title').innerText;
        const genre = card.querySelector('.producer').innerText.split(' · ').pop();
        
        const coverContainer = card.querySelector('.cover');
        const existingSvg = coverContainer.querySelector('.stripes');
        if (existingSvg) existingSvg.outerHTML = generateCover(id, title, genre);
        
        const waveContainer = card.querySelector('.wave');
        waveContainer.innerHTML = generateWaveform(id);
    });

    // Timer Logic
    const updateTimers = () => {
        document.querySelectorAll('.timer').forEach(el => {
            const ends = el.getAttribute('data-ends');
            if (!ends) return;

            const diff = Math.max(0, Math.floor((new Date(ends).getTime() - Date.now()) / 1000));
            if (diff <= 0) {
                el.innerText = "Ending...";
                el.classList.add('danger');
                return;
            }

            const m = Math.floor(diff / 60);
            const s = diff % 60;
            el.innerText = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
            
            if (diff < 300) el.classList.add('danger');
            else el.classList.remove('danger');
        });
    };
    setInterval(updateTimers, 1000);
    updateTimers();

    // Modal Logic
    document.querySelectorAll('.open-bid').forEach(btn => {
        btn.addEventListener('click', () => {
            const beat = JSON.parse(btn.getAttribute('data-beat'));
            document.getElementById('modal-beat-id').value = beat.id;
            document.getElementById('modal-amount').min = (parseFloat(beat.current_bid) || parseFloat(beat.starting_bid)) + (beat.top_bidder ? 5 : 0);
            document.getElementById('modal-amount').value = document.getElementById('modal-amount').min;
            
            document.getElementById('modal-beat-info').innerHTML = `
                <div style="font-weight:600">${beat.title}</div>
                <div style="flex:1"></div>
                <div style="text-align:right">
                    <div style="font-size:10px; color:var(--ink-mute)">CURRENT BID</div>
                    <div style="font-weight:700; color:var(--accent)">$${beat.current_bid}</div>
                </div>
            `;
            bidModal.classList.add('is-visible');
        });
    });

    document.getElementById('close-modal').addEventListener('click', () => {
        bidModal.classList.remove('is-visible');
    });

    // Bid Submission
    bidForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(bidForm);
        
        try {
            const res = await fetch('api/bid.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if (data.success) {
                showToast(`Bid placed successfully!`, 'ok');
                bidModal.classList.remove('is-visible');
                // Refresh will be handled by SSE or manual if stream fails
            } else {
                showToast(data.error || 'Failed to place bid', 'error');
            }
        } catch (err) {
            showToast('Network error', 'error');
        }
    });

    // SSE Integration
    const evtSource = new EventSource('api/stream.php');
    
    evtSource.addEventListener('activity', (e) => {
        const act = JSON.parse(e.data);
        const item = document.createElement('div');
        item.className = 'activity-item';
        item.style.animation = 'toastIn 0.3s ease';
        item.innerHTML = `<span class="dot"></span><span><b>${act.user_handle}</b> ${act.message}</span>`;
        activityList.prepend(item);
        if (activityList.children.length > 8) activityList.lastChild.remove();
        
        if (act.type === 'bid') {
            showToast(`${act.user_handle} bid $${act.amount} on a beat!`, 'ok');
        }
    });

    evtSource.addEventListener('update', (e) => {
        const beats = JSON.parse(e.data);
        beats.forEach(b => {
            const card = document.querySelector(`.card[data-id="${b.id}"]`);
            if (card) {
                const timerEl = card.querySelector('.timer');
                const prevEnds = timerEl.getAttribute('data-ends');
                
                // Clock Extension Detection
                if (prevEnds && b.ends_at && new Date(b.ends_at) > new Date(prevEnds)) {
                    showToast(`Clock extended on "${card.querySelector('.title').innerText}"!`, 'ok');
                    card.classList.add('is-extended');
                    setTimeout(() => card.classList.remove('is-extended'), 2000);
                }

                card.querySelector('.v.accent').innerText = `$${parseFloat(b.current_bid).toFixed(2)}`;
                timerEl.setAttribute('data-ends', b.ends_at);
                
                const diff = Math.max(0, Math.floor((new Date(b.ends_at).getTime() - Date.now()) / 1000));
                const statusEl = card.querySelector('.status');
                if (diff > 0 && diff < 120) {
                    statusEl.innerText = 'ENDING';
                    statusEl.className = 'status ending';
                    card.classList.add('is-hot');
                }

                const stats = card.querySelector('.bidstats');
                stats.innerHTML = `top <b>${b.top_bidder}</b>`;
            }
        });
    });

    // Toast Helper
    function showToast(msg, kind) {
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.innerHTML = `<span class="tdot" style="background:${kind==='ok'?'var(--ok)':'var(--danger)'}"></span> ${msg}`;
        toastContainer.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }
});
