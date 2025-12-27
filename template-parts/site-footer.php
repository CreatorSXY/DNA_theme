<?php
/**
 * Shared site footer — styled to match front page.
 */
?>
<footer class="site-footer site-footer-home">
  <div class="container footer-inner">
    <div class="footer-left">
      <div class="footer-title">Design n’ Aesthetics</div>
      <div class="footer-note">All designs published are original creations by DNA or commissioned designers; all rights reserved.</div>
      <div class="footer-copy">© <?php echo esc_html( date( 'Y' ) ); ?> Design n’ Aesthetics. All rights reserved.</div>
    </div>
    <div class="footer-right">
      <a href="<?php echo esc_url( home_url( '/philosophy/' ) ); ?>">Our philosophy</a>
      <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">Privacy policy</a>
    </div>
  </div>
</footer>
