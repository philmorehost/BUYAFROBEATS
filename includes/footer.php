<?php
use BAF\Core;

if (!isset($core) || !($core instanceof Core)) {
    $core = Core::get_instance();
}
/**
 * BULLETPROOF FOOTER
 * This footer is designed to be resilient against empty database settings,
 * ad-blockers, and CMS errors.
 */
?>
<footer class="site-f">
    <div class="f-inner">
        <div class="f-grid">
            <!-- Brand Section -->
            <div class="f-col f-brand-wrap">
                <div class="f-logo"><?php echo $core->render_logo(); ?></div>
                <p class="f-tagline">One-of-one beats. Exclusive rights. The clock is ticking.</p>
                <div class="f-social">
                    <?php if($ig = $core->setting('social_instagram')): ?>
                        <a href="<?php echo \BAF\Core::escape($ig); ?>" target="_blank" aria-label="Instagram">IG</a>
                    <?php endif; ?>
                    <?php if($tw = $core->setting('social_twitter')): ?>
                        <a href="<?php echo \BAF\Core::escape($tw); ?>" target="_blank" aria-label="Twitter">TW</a>
                    <?php endif; ?>
                    <a href="#" aria-label="WhatsApp">WA</a>
                </div>
            </div>

            <!-- Marketplace Links -->
            <div class="f-col">
                <div class="f-nav-group">
                    <h4 class="f-heading">Marketplace</h4>
                    <ul class="f-links">
                        <li><a href="index.php">Browse All</a></li>
                        <li><a href="index.php?genre=Afrobeats">Afrobeats</a></li>
                        <li><a href="index.php?genre=Amapiano">Amapiano</a></li>
                    </ul>
                </div>
            </div>

            <!-- Company Links -->
            <div class="f-col">
                <div class="f-nav-group">
                    <h4 class="f-heading">Company</h4>
                    <ul class="f-links">
                        <li><a href="faqs.php">FAQs</a></li>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                        <li><a href="terms.php">Terms & Conditions</a></li>
                        <?php 
                        try {
                            if ($core->db()) {
                                @require_once __DIR__ . '/CMS.php';
                                if (class_exists('\BAF\CMS')) {
                                    $cms_f = new \BAF\CMS($core);
                                    $dynamic_p = $cms_f->get_all_pages();
                                    foreach ($dynamic_p as $p) {
                                        $url = $p['is_external'] ? $p['external_url'] : "page?slug=".$p['slug'];
                                        echo '<li><a href="'.\BAF\Core::escape($url).'">'.\BAF\Core::escape($p['title']).'</a></li>';
                                    }
                                }
                            }
                        } catch (\Throwable $e) { /* Fail silently to protect footer structure */ }
                        ?>
                    </ul>
                </div>
            </div>

            <!-- Newsletter Section -->
            <div class="f-col f-news-wrap">
                <div class="f-news">
                    <h4 class="f-heading">Join the Drop List</h4>
                    <p>Get notified as soon as a new beat goes live.</p>
                    <form id="f-news-form" class="f-news-field">
                        <input type="email" name="email" placeholder="you@email.com" required>
                        <button type="submit">Join →</button>
                    </form>
                    <div id="f-news-msg" style="font-size:11px; margin-top:8px;"></div>
                </div>
            </div>
        </div>
        
        <div class="f-bottom">
            <span>&copy; <?php echo date('Y'); ?> <?php echo \BAF\Core::escape($core->setting('site_title', 'BUYAFROBEATS')); ?>. All rights reserved.</span>
            <div class="f-legal-links">
                <a href="faqs.php">FAQs</a> · <a href="privacy.php">Privacy</a> · <a href="terms.php">Terms</a>
            </div>
        </div>
    </div>
</footer>

<?php include __DIR__ . '/../components/legal_modal.php'; ?>

<script>
    // Newsletter Logic
    document.getElementById('f-news-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const msg = document.getElementById('f-news-msg');
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
