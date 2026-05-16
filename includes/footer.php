<?php
use BAF\Core;
if (!isset($core) || !($core instanceof Core)) {
    $core = Core::get_instance();
}
?>
    <footer class="site-footer">
        <div class="ft-brand">
            <b><?php echo Core::escape($core->setting('site_title', 'BEATZAZA')); ?></b> — Premium One-of-One Auctions
        </div>
        <div class="ft-links">
            <button onclick="openPolicy('terms')">Terms</button>
            <button onclick="openPolicy('privacy')">Privacy</button>
            <button onclick="openPolicy('faq')">FAQ</button>
        </div>
        <div class="ft-contact">
            Support: <a href="mailto:<?php echo Core::escape($core->setting('contact_email', 'hello@beatzaza.com')); ?>"><?php echo Core::escape($core->setting('contact_email', 'hello@beatzaza.com')); ?></a>
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
