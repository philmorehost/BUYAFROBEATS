<footer class="footer">
    <div class="footer-inner">
        <div class="footer-grid">
            <div class="footer-col brand">
                <div class="footer-brand">
                    <div class="logo"><?php echo $core->render_logo(); ?></div>
                    <p>One-of-one beats. Exclusive rights. The clock is ticking.</p>
                    <div class="social-links">
                        <?php if($ig = $core->setting('social_instagram')): ?>
                            <a href="<?php echo Core::escape($ig); ?>" target="_blank" title="Instagram">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                            </a>
                        <?php endif; ?>
                        <?php if($tw = $core->setting('social_twitter')): ?>
                            <a href="<?php echo Core::escape($tw); ?>" target="_blank" title="Twitter">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.84 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                            </a>
                        <?php endif; ?>
                        <a href="#" title="WhatsApp">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M17.472 14.382c-.301-.15-1.767-.872-2.04-.971-.272-.099-.47-.15-.67.15-.199.301-.77 1.05-.94 1.25-.17.199-.34.22-.64.07-1.125-.56-2.122-1.076-2.954-1.802-.736-.639-1.22-1.428-1.36-1.67-.14-.241-.01-.371.11-.49.11-.109.241-.281.361-.421.121-.14.161-.241.241-.401.08-.16.04-.301-.02-.451-.06-.15-.47-1.13-.645-1.551-.169-.411-.339-.351-.47-.351-.129 0-.279-.011-.429-.011-.15 0-.39.06-.59.281-.2.221-.771.751-.771 1.832 0 1.08.79 2.121.9 2.271.11.15 1.551 2.372 3.75 3.321.52.221.93.351 1.25.451.52.161.99.141 1.37.081.42-.061 1.29-.531 1.47-1.04.181-.51.181-.941.131-1.04-.05-.099-.19-.15-.49-.301zM12 22.12c-1.82 0-3.6-.481-5.16-1.39l-.37-.21-3.84 1.01 1.03-3.74-.23-.37A9.851 9.851 0 011.88 12c0-5.441 4.43-9.871 9.87-9.871s9.87 4.43 9.87 9.87c0 5.441-4.43 9.871-9.87 9.871zM12 0C5.373 0 0 5.373 0 12c0 2.12.55 4.18 1.59 6L0 24l6.19-1.63A11.94 11.94 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            <div class="footer-col">
                <div class="footer-nav">
                    <h4>Marketplace</h4>
                    <a href="index">Browse All</a>
                    <a href="index?genre=Afrobeats">Afrobeats</a>
                    <a href="index?genre=Amapiano">Amapiano</a>
                </div>
            </div>

            <div class="footer-col">
                <div class="footer-nav">
                    <h4>Company</h4>
                    <a href="/faqs">FAQs</a>
                    <a href="/privacy">Privacy Policy</a>
                    <a href="/terms">Terms & Conditions</a>
                    <?php 
                    try {
                        if ($core->db()) {
                            require_once __DIR__ . '/CMS.php';
                            $cms_footer = new \BAF\CMS($core);
                            $footer_pages = $cms_footer->get_all_pages();
                            foreach ($footer_pages as $fp):
                                $href = $fp['is_external'] ? $fp['external_url'] : "page?slug=".$fp['slug'];
                        ?>
                            <a href="<?php echo Core::escape($href); ?>"><?php echo Core::escape($fp['title']); ?></a>
                        <?php
                            endforeach;
                        }
                    } catch (\Exception $e) {}
                    ?>
                </div>
            </div>

            <div class="footer-col newsletter">
                <div class="footer-newsletter">
                    <h4>Join the Drop List</h4>
                    <p>Get notified as soon as a new beat goes live.</p>
                    <form id="newsletter-form" class="newsletter-field">
                        <input type="email" name="email" placeholder="you@email.com" required>
                        <button type="submit">Join →</button>
                    </form>
                    <div id="newsletter-msg" style="font-size:11px; margin-top:8px;"></div>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <span>&copy; <?php echo date('Y'); ?> <?php echo Core::escape($core->setting('site_title', 'BUYAFROBEATS')); ?>. All rights reserved.</span>
            <div class="footer-legal">
                <a href="/privacy">Privacy</a> · <a href="/terms">Terms</a>
            </div>
        </div>
    </div>
</footer>


<script>
    document.getElementById('newsletter-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const msg = document.getElementById('newsletter-msg');
        const formData = new FormData(form);
        
        try {
            const res = await fetch('api/subscribe', { method: 'POST', body: formData });
            const data = await res.json();
            msg.innerText = data.message || data.error;
            msg.style.color = data.success ? 'var(--ok)' : 'var(--danger)';
            if (data.success) form.reset();
        } catch (err) {
            msg.innerText = 'Network error';
        }
    });
</script>

<div id="toast-container" class="toast-wrap"></div>
<?php echo $core->render_footer_injection(); ?>
<script src="assets/js/auction.js"></script>
</body>
</html>
