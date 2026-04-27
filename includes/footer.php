<footer class="footer">
    <div class="footer-inner">
        <div class="footer-grid">
            <div class="footer-col brand">
                <div class="footer-brand">
                    <div class="logo"><?php echo $core->render_logo(); ?></div>
                    <p>One-of-one beats. Exclusive rights. The clock is ticking.</p>
                    <div class="social-links">
                        <?php if($ig = $core->setting('social_instagram')): ?>
                            <a href="<?php echo Core::escape($ig); ?>" target="_blank">Instagram</a>
                        <?php endif; ?>
                        <?php if($tw = $core->setting('social_twitter')): ?>
                            <a href="<?php echo Core::escape($tw); ?>" target="_blank">Twitter</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="footer-col">
                <div class="footer-nav">
                    <h4>Marketplace</h4>
                    <a href="index.php">Browse All</a>
                    <a href="index.php?genre=Afrobeats">Afrobeats</a>
                    <a href="index.php?genre=Amapiano">Amapiano</a>
                </div>
            </div>

            <div class="footer-col">
                <div class="footer-nav">
                    <h4>Company</h4>
                    <a href="faqs.php">FAQs</a>
                    <?php 
                    require_once __DIR__ . '/CMS.php';
                    $cms_footer = new \BAF\CMS($core);
                    foreach ($cms_footer->get_all_pages() as $fp): 
                        $href = $fp['is_external'] ? $fp['external_url'] : "page.php?slug=".$fp['slug'];
                    ?>
                        <a href="<?php echo Core::escape($href); ?>"><?php echo Core::escape($fp['title']); ?></a>
                    <?php endforeach; ?>
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
                <a href="privacy.php">Privacy</a> · <a href="terms.php">Terms</a>
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
            const res = await fetch('api/subscribe.php', { method: 'POST', body: formData });
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
