<div id="legal-modal" class="backdrop">
    <div class="modal" style="width: 800px; max-width: 95vw;">
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="margin:0">Policy Center</h2>
            <div style="cursor:pointer" onclick="closeLegalModal()">✕</div>
        </div>
        
        <div class="tabs" style="margin-bottom: 24px;">
            <div class="tab is-active" onclick="showLegalTab('terms', this)">Terms of Service</div>
            <div class="tab" onclick="showLegalTab('privacy', this)">Privacy</div>
            <div class="tab" onclick="showLegalTab('faq', this)">FAQ</div>
        </div>

        <div id="legal-content" style="max-height: 60vh; overflow-y: auto; font-size: 14px; line-height: 1.6; color: var(--ink-2); padding-right: 12px;">
            <!-- Terms Tab -->
            <div id="tab-terms" class="legal-tab">
                <h3>1. Auction Rules</h3>
                <p>All bids are final and binding. Once a bid is placed, it cannot be retracted. The highest bid at the moment the timer hits zero wins the auction.</p>
                
                <h3>2. Payment Cascade & Re-offering</h3>
                <p>If the winning bidder fails to complete payment within the designated window (default 24 hours), BEATZAZA reserves the right to privately re-offer the instrumental to other participants in the auction at our sole discretion. No bidder is entitled to information regarding previous payment outcomes.</p>

                <h3>3. Deliverables & License</h3>
                <p>Upon confirmed payment, the buyer is granted an exclusive license as defined in the full License Agreement. Deliverables (Master, Stems) are shared via Google Drive for a period of 7 days.</p>
            </div>

            <!-- Privacy Tab -->
            <div id="tab-privacy" class="legal-tab" style="display:none">
                <h3>Data Collection</h3>
                <p>We collect your email and handle during authentication to manage bids and file delivery. We do not sell your data to third parties.</p>
                <h3>Payment Processing</h3>
                <p>Payments are handled by Plisio. We do not store your wallet private keys or sensitive financial data.</p>
            </div>

            <!-- FAQ Tab -->
            <div id="tab-faq" class="legal-tab" style="display:none">
                <h4>How do I get my files?</h4>
                <p>After your crypto payment is confirmed, you will receive an email with direct download links from Google Drive.</p>
                <h4>What if I miss the 7-day window?</h4>
                <p>Contact support at the email provided in your receipt to request a re-issue.</p>
                <h4>Is the credit required?</h4>
                <p>Yes. Every release using the beat must credit "Produced by OBV". This is a material condition of the license.</p>
            </div>
        </div>
    </div>
</div>

<script>
function openLegalModal(tab = 'terms') {
    document.getElementById('legal-modal').classList.add('show');
    const tabEl = document.querySelector(`.tab[onclick*="${tab}"]`);
    showLegalTab(tab, tabEl);
}
function closeLegalModal() {
    document.getElementById('legal-modal').classList.remove('show');
}
function showLegalTab(tabId, el) {
    document.querySelectorAll('.legal-tab').forEach(t => t.style.display = 'none');
    document.getElementById('tab-' + tabId).style.display = 'block';
    
    document.querySelectorAll('#legal-modal .tab').forEach(t => t.classList.remove('is-active'));
    el.classList.add('is-active');
}
</script>
