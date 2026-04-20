<?php
/*
Template Name: Contact
*/
get_header();
$status = isset($_GET['contact_status']) ? sanitize_key(wp_unslash($_GET['contact_status'])) : '';
?>

<main id="primary" class="page contact-page">
  <div class="container">
    <?php dna_render_rank_math_breadcrumbs(); ?>

    <h1 class="page-title">Contact</h1>

    <section class="contact-intro" aria-labelledby="contact-intro-title">
      <h2 id="contact-intro-title">Start Your Project Brief</h2>
      <p>
        Share your design and production scope, timeline, and product direction.
        We support merch design development, sampling, supplier coordination,
        and delivery execution for both consumer and B2B projects.
      </p>
      <p>
        Direct email: <a href="mailto:hello@designnaesthetics.com">hello@designnaesthetics.com</a>
      </p>
    </section>

    <?php if ($status === 'sent') : ?>
      <p class="contact-status is-success" role="status">Thanks. Your message has been sent.</p>
    <?php elseif ($status === 'validation_error') : ?>
      <p class="contact-status is-error" role="alert">Please complete name, valid email, and message.</p>
    <?php elseif ($status === 'mail_error') : ?>
      <p class="contact-status is-error" role="alert">We could not send your message right now. Please try again or email us directly.</p>
    <?php elseif ($status === 'nonce_error') : ?>
      <p class="contact-status is-error" role="alert">Security check failed. Please refresh and submit again.</p>
    <?php endif; ?>

    <section class="contact-form-wrap" aria-labelledby="contact-form-title">
      <h2 id="contact-form-title">Send Us a Message</h2>
      <form class="contact-form" method="post" action="<?php echo dna_contact_form_action_url(); ?>">
        <input type="hidden" name="action" value="dna_contact_submit">
        <?php wp_nonce_field('dna_contact_submit', 'dna_contact_nonce'); ?>

        <label for="contact_name">Name</label>
        <input id="contact_name" name="contact_name" type="text" required>

        <label for="contact_email">Email</label>
        <input id="contact_email" name="contact_email" type="email" required>

        <label for="contact_company">Company (Optional)</label>
        <input id="contact_company" name="contact_company" type="text">

        <label for="contact_message">Project Brief</label>
        <textarea id="contact_message" name="contact_message" rows="7" required></textarea>

        <button class="btn-primary" type="submit">Send Message</button>
      </form>
    </section>
  </div>
</main>

<?php get_footer(); ?>
