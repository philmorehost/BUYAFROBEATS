<footer class="footer">
    <div class="footer-inner">
        <div class="footer-grid">
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

            <div class="footer-nav">
                <h4>Marketplace</h4>
                <a href="index.php">Browse All</a>
                <a href="index.php?genre=Afrobeats">Afrobeats</a>
                <a href="index.php?genre=Amapiano">Amapiano</a>
            </div>

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
        
        <div class="footer-bottom">
            <span>&copy; <?php echo date('Y'); ?> <?php echo Core::escape($core->setting('site_title', 'BUYAFROBEATS')); ?>. All rights reserved.</span>
            <div class="footer-legal">
                <a href="index.php">Privacy</a> · <a href="index.php">Terms</a>
            </div>
        </div>
    </div>
</footer>

<style>
    .footer { background: var(--bg-2); border-top: 1px solid var(--line); margin-top: 80px; padding: 60px 0 30px; }
    .footer-inner { max-width: 1500px; margin: 0 auto; padding: 0 28px; }
    .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 2fr; gap: 40px; margin-bottom: 60px; }
    .footer-brand .logo { margin-bottom: 16px; }
    .footer-brand p { color: var(--ink-mute); font-size: 14px; max-width: 24ch; line-height: 1.6; }
    .footer-nav h4, .footer-newsletter h4 { font-family: 'JetBrains Mono', monospace; font-size: 11px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--accent); margin-bottom: 20px; }
    .footer-nav a { display: block; color: var(--ink-dim); text-decoration: none; font-size: 14px; margin-bottom: 10px; transition: color 0.2s; }
    .footer-nav a:hover { color: var(--ink); }
    .footer-newsletter p { color: var(--ink-mute); font-size: 14px; margin-bottom: 16px; }
    .newsletter-field { display: flex; gap: 8px; }
    .newsletter-field input { background: var(--bg); border: 1px solid var(--line); border-radius: 8px; padding: 10px 14px; color: var(--ink); flex: 1; font-size: 14px; outline: none; }
    .newsletter-field button { background: var(--accent); color: var(--accent-ink); border: 0; border-radius: 8px; padding: 10px 16px; font-weight: 600; cursor: pointer; }
    .footer-bottom { border-top: 1px solid var(--line); padding-top: 30px; display: flex; justify-content: space-between; font-size: 12px; color: var(--ink-mute); }
    .footer-legal a { color: inherit; text-decoration: none; }
    .social-links { display: flex; gap: 12px; margin-top: 20px; }
    .social-links a { font-size: 12px; color: var(--ink-dim); text-decoration: none; border: 1px solid var(--line); padding: 4px 10px; border-radius: 6px; }
</style>

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
