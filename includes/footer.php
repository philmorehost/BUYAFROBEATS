<?php
use BAF\Core;
if (!isset($core) || !($core instanceof Core)) {
    $core = Core::get_instance();
}
?>
    <footer class="site-footer">
        <div class="f-inner">
            <div class="f-grid">
                <div class="f-col">
                    <div class="f-logo"><?php echo $core->render_logo(); ?></div>
                    <p class="f-tagline">Premium one-of-one Afrobeats instrumentals. Own the master, rule the charts. Every beat sold once, then gone forever.</p>
                </div>
                <div class="f-col">
                    <div class="f-heading">Platform</div>
                    <ul class="f-links">
                        <li><a href="<?php echo $core->get_site_url(); ?>">Market</a></li>
                        <li><button onclick="openPolicy('faq')">FAQ</button></li>
                        <li><a href="#">Leaderboard</a></li>
                    </ul>
                </div>
                <div class="f-col">
                    <div class="f-heading">Legal</div>
                    <ul class="f-links">
                        <li><button onclick="openPolicy('terms')">Terms of Service</button></li>
                        <li><button onclick="openPolicy('privacy')">Privacy Policy</button></li>
                    </ul>
                </div>
                <div class="f-col">
                    <div class="f-heading">Support</div>
                    <p class="f-tagline" style="font-size: 13px;">Need help? Reach out to our 24/7 support team.</p>
                    <a href="mailto:<?php echo Core::escape($core->setting('contact_email', 'hello@beatzaza.com')); ?>" class="btn btn-ghost" style="display: inline-block; margin-top: 10px;">
                        <?php echo Core::escape($core->setting('contact_email', 'hello@beatzaza.com')); ?>
                    </a>
                </div>
            </div>
            <div class="f-bottom">
                <div>&copy; <?php echo date('Y'); ?> <?php echo Core::escape($core->setting('site_title', 'BEATZAZA')); ?>. All rights reserved.</div>
                <div>Powered by OBV Production</div>
            </div>
        </div>
    </footer>

    <div id="toast-wrap" class="toast-wrap"></div>

    <script src="<?php echo $core->get_site_url(); ?>/assets/js/auction.js?v=3.0"></script>
    <script>
        function handleCredentialResponse(response) {
            fetch('<?php echo $core->get_site_url(); ?>/api/auth/google_verify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'credential=' + encodeURIComponent(response.credential) + '&csrf_token=<?php echo Core::csrf_token(); ?>'
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    console.error('Login failed:', data.error);
                }
            });
        }
    </script>
    <?php echo $core->render_footer_injection(); ?>
</body>
</html>
