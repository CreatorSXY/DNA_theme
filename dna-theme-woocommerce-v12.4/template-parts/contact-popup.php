<?php
/**
 * Global Contact Popup (DNA)
 * Injected on every page via footer.php.
 */
?>
<button class="dna-contact-trigger" type="button" aria-controls="dnaContactPopup" aria-expanded="false">CONTACT</button>

<div class="dna-contact-popup" id="dnaContactPopup" aria-hidden="true">
  <div class="dna-contact-panel" role="dialog" aria-modal="true" aria-label="Get in touch">
    <button class="dna-contact-close" type="button" aria-label="Close">×</button>

    <h2 class="dna-contact-title">GET IN TOUCH</h2>
    <p class="dna-contact-note">
      Leave your contact information and a brief note. We will get back to you.
    </p>

    <form class="dna-contact-form" autocomplete="on">
      <input type="text" name="dna_name" placeholder="Name" required>
      <input type="email" name="dna_email" placeholder="Email" required>
      <textarea name="dna_message" placeholder="Message" rows="4"></textarea>
      <button type="submit">SEND</button>
    </form>
  </div>
</div>
