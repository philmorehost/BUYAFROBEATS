<?php
use BAF\Core;

if (!isset($core) || !($core instanceof Core)) {
    $core = Core::get_instance();
}
?>
<footer class="site-f">
    <div class="f-inner">
        <div class="f-grid">
            <div class="f-brand">
                <div class="f-logo"><?php echo $core->render_logo(); ?></div>
                <p class="f-tagline">Premium one-of-one afrobeats marketplace. Exclusive rights only. The clock is always ticking.</p>
            </div>

            <div class="f-col">
                <h4 class="f-heading">Marketplace</h4>
                <ul class="f-links">
                    <li><a href="index">Browse All</a></li>
                    <li><a href="index?genre=Afrobeats">Afrobeats</a></li>
                    <li><a href="index?genre=Amapiano">Amapiano</a></li>
                </ul>
            </div>

            <div class="f-col">
                <h4 class="f-heading">Company</h4>
                <ul class="f-links">
                    <li><a href="faqs">FAQs</a></li>
                    <li><a href="privacy">Privacy Policy</a></li>
                    <li><a href="terms">Terms & Conditions</a></li>
                </ul>
            </div>

            <div class="f-news">
                <h4 class="f-heading">Join the Drop List</h4>
                <p>Get notified as soon as a new beat goes live.</p>
                <form id="f-news-form" class="f-news-field" style="display: flex; gap: 8px;">
                    <input type="email" name="email" placeholder="you@email.com" required style="flex:1; padding:8px; border-radius:4px; border:1px solid var(--line); background:var(--bg); color:var(--ink);">
                    <button type="submit" class="btn btn-primary" style="padding:8px 16px;">Join →</button>
                </form>
            </div>
        </div>
        
        <div class="f-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo Core::escape($core->setting('site_title', 'BUYAFROBEATS')); ?>. All rights reserved.</p>
            <p><a href="privacy">Privacy</a> · <a href="terms">Terms</a></p>
        </div>
    </div>
</footer>

<div id="toast-container"></div>
<?php echo $core->render_footer_injection(); ?>
<script src="assets/js/auction.js?v=1.6"></script>
</body>
</html>
