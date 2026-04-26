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
                <a href="index.php">Privacy</a> · <a href="index.php">Terms</a>
            </div>
        </div>
    </div>
</footer>

<style>
    .footer { background: #1a1a1a; background: var(--bg-2); border-top: 1px solid var(--line); margin-top: 80px; padding: 80px 0 40px; }
    .footer-inner { max-width: 1400px; margin: 0 auto; padding: 0 40px; }
    .footer-grid { display: flex; flex-wrap: wrap; gap: 40px; margin-bottom: 60px; justify-content: space-between; }
    
    .footer-col { flex: 1; min-width: 200px; }
    .footer-col.brand { flex: 1.5; min-width: 280px; }
    .footer-col.newsletter { flex: 1.5; min-width: 300px; }
    
    .footer-brand .logo { margin-bottom: 24px; font-size: 20px; }
    .footer-brand p { color: var(--ink-mute); font-size: 15px; max-width: 32ch; line-height: 1.7; margin-bottom: 28px; }
    
    .footer h4 { font-family: 'JetBrains Mono', monospace; font-size: 11px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--accent); margin: 0 0 24px; font-weight: 600; }
    .footer-nav a { display: block; color: var(--ink-dim); text-decoration: none; font-size: 15px; margin-bottom: 14px; transition: all 0.2s; }
    .footer-nav a:hover { color: var(--accent); padding-left: 4px; }
    
    .footer-newsletter p { color: var(--ink-mute); font-size: 15px; margin-bottom: 20px; line-height: 1.6; }
    .newsletter-field { display: flex; gap: 10px; background: var(--bg); border: 1px solid var(--line); padding: 6px; border-radius: 12px; }
    .newsletter-field input { background: transparent; border: 0; padding: 10px 14px; color: var(--ink); flex: 1; font-size: 15px; outline: none; min-width: 0; }
    .newsletter-field button { background: var(--accent); color: var(--accent-ink); border: 0; border-radius: 8px; padding: 10px 20px; font-weight: 600; cursor: pointer; white-space: nowrap; }
    
    .footer-bottom { border-top: 1px solid var(--line); padding-top: 40px; display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: var(--ink-mute); }
    .footer-legal { display: flex; gap: 24px; }
    .footer-legal a { color: inherit; text-decoration: none; }
    
    .social-links { display: flex; gap: 12px; }
    .social-links a { display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; border: 1px solid var(--line); border-radius: 10px; color: var(--ink-dim); text-decoration: none; transition: all 0.2s; }
    .social-links a:hover { border-color: var(--accent); color: var(--accent); transform: translateY(-3px); }

    @media (max-width: 800px) {
        .footer-grid { flex-direction: column; gap: 40px; }
        .footer-col { width: 100%; flex: none; }
        .footer-bottom { flex-direction: column; gap: 20px; text-align: center; }
        .footer-legal { justify-content: center; }
        .newsletter-field { flex-direction: column; }
    }
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
