<?php
use BAF\Core;

if (!isset($core) || !($core instanceof Core)) {
    $core = Core::get_instance();
}
?>
<footer class="site-f">
    <div class="f-inner">
        <div class="f-grid">
            <div class="f-brand-wrap">
                <div class="f-logo"><?php echo $core->setting('site_title', 'BEATZAZA'); ?>.COM</div>
                <p class="f-tagline">Premium one-of-one afrobeats marketplace. Exclusive rights only. The clock is always ticking.</p>
                
                <div style="display: flex; gap: 12px; margin-top: 24px;">
                    <a href="#" class="btn" style="padding: 8px 12px; font-size: 11px;">Instagram</a>
                    <a href="#" class="btn" style="padding: 8px 12px; font-size: 11px;">Twitter</a>
                </div>
            </div>

            <div class="f-col">
                <h4 class="f-heading">Marketplace</h4>
                <ul class="f-links">
                    <li><a href="index">All Beats</a></li>
                    <li><a href="index?genre=Afrobeats">Afrobeats</a></li>
                    <li><a href="index?genre=Amapiano">Amapiano</a></li>
                </ul>
            </div>

            <div class="f-col">
                <h4 class="f-heading">Legal</h4>
                <ul class="f-links">
                    <li><a href="faqs">FAQs</a></li>
                    <li><a href="privacy">Privacy Policy</a></li>
                    <li><a href="terms">Terms & Conditions</a></li>
                </ul>
            </div>

            <div class="f-news-wrap">
                <h4 class="f-heading">The Drop List</h4>
                <p>Join 2,000+ artists getting notified of new drops.</p>
                <form id="f-news-form" class="f-news-field">
                    <input type="email" name="email" placeholder="email@address.com" required>
                    <button type="submit">Join</button>
                </form>
                <div id="f-news-msg" style="font-size:11px; margin-top:10px;"></div>
            </div>
        </div>
        
        <div class="f-bottom">
            <span>&copy; <?php echo date('Y'); ?> <?php echo \BAF\Core::escape($core->setting('site_title', 'BEATZAZA')); ?>. All rights reserved.</span>
            <span>Handcrafted for Afrobeats Excellence.</span>
        </div>
    </div>
</footer>

<div id="toast-container"></div>
<?php echo $core->render_footer_injection(); ?>
<script src="assets/js/auction.js?v=1.4"></script>
</body>
</html>
