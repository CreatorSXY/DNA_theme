<?php
/*
Template Name: B2B Services
*/
get_header();
?>

<main class="page b2b-page">
  <div class="container">
    <?php
    $b2b_hero_image_id = function_exists( 'dna_get_theme_image_id' ) ? dna_get_theme_image_id( 'b2b_hero' ) : 0;
    $b2b_hero_image_html = '';
    if ( $b2b_hero_image_id && function_exists( 'dna_render_original_attachment_image' ) ) {
      $b2b_hero_image_html = dna_render_original_attachment_image(
        $b2b_hero_image_id,
        [
          'class'    => 'media-placeholder__img',
          'loading'  => 'eager',
          'decoding' => 'async',
          'alt'      => dna_attachment_alt_from_context( $b2b_hero_image_id, 'B2B hero visual' ),
        ]
      );
    }
    ?>
    <?php dna_render_rank_math_breadcrumbs(); ?>
    <header class="b2b-hero" aria-labelledby="b2b-hero-title" data-dna-reveal data-dna-reveal-order="1">
      <div class="b2b-hero-plane media-placeholder media-placeholder--light<?php echo $b2b_hero_image_html !== '' ? ' has-media' : ''; ?>" data-dna-depth aria-hidden="true">
        <?php if ( $b2b_hero_image_html !== '' ) : ?>
          <?php echo $b2b_hero_image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <?php else : ?>
          <span class="media-placeholder__label">B2B Hero Visual Placeholder</span>
        <?php endif; ?>
      </div>
      <p class="b2b-kicker">B2B Services</p>
      <h1 class="b2b-seo-title">Custom Merch Design &amp; Production</h1>
      <h2 id="b2b-hero-title" class="b2b-hero-title b2b-hero-pricing">
        <span class="b2b-hero-metric">25%</span> Where We Create.<br>
        <span class="b2b-hero-metric">0%</span> Where We Don&rsquo;t.
      </h2>
      <p class="b2b-lead">
        We only charge where we create value.
      </p>
      <div class="b2b-prose b2b-hero-body">
        <p>25% covers the work we actively manage - design development, sampling, supplier coordination, and production oversight.</p>
        <p>0% means no additional markup on logistics, duties, or taxes.</p>
      </div>
      <div class="b2b-hero-actions" id="dna-b2b-hero-actions">
        <button
          class="btn-primary b2b-start-project"
          type="button"
          data-dna-b2b-open
          aria-controls="dnaB2BWizard"
          aria-haspopup="dialog"
        >
          Start Your Project
        </button>
        <a class="btn-ghost" href="mailto:hello@designnaesthetics.com">Start with Email</a>
      </div>
    </header>

    <section class="b2b-section b2b-why" aria-labelledby="b2b-why" data-dna-reveal data-dna-reveal-order="2">
      <h2 id="b2b-why" class="b2b-section-title">Why</h2>
      <div class="b2b-prose">
        <p>Many teams can describe their brand clearly in decks, flyers, and website copy, but the real-world expression never reaches the same clarity.</p>
        <p>Visual language often gets fragmented between design, sourcing, and production. The result is products that feel inconsistent with the original idea.</p>
        <p>Without a system that connects concept to execution, your brand message stays theoretical instead of becoming a reliable customer experience.</p>
      </div>
    </section>

    <section class="b2b-section b2b-steps" aria-labelledby="b2b-how" data-dna-reveal data-dna-reveal-order="3">
      <h2 id="b2b-how" class="b2b-section-title">How</h2>
      <div class="b2b-prose">
        <p>Our process combines design development, sampling, supplier coordination, production oversight, and delivery support into one accountable workflow.</p>
      </div>
      <div class="b2b-steps-grid">
        <article class="b2b-step">
          <div class="b2b-step-number">01</div>
          <div class="b2b-step-body">
            <h3>Inquiry</h3>
            <p>Brief goals, style direction, quantity target, and timeline.</p>
          </div>
        </article>
        <article class="b2b-step">
          <div class="b2b-step-number">02</div>
          <div class="b2b-step-body">
            <h3>Alignment</h3>
            <p>We lock scope, references, production route, and quality standards.</p>
          </div>
        </article>
        <article class="b2b-step">
          <div class="b2b-step-number">03</div>
          <div class="b2b-step-body">
            <h3>Deliver &amp; Support</h3>
            <p>Production, logistics handoff, and follow-through after launch.</p>
          </div>
        </article>
      </div>
    </section>

    <section class="b2b-section b2b-case-entry" aria-labelledby="b2b-case-entry-title" data-dna-reveal data-dna-reveal-order="4">
      <h2 id="b2b-case-entry-title" class="b2b-section-title">Case Study</h2>
      <?php
      $case_image_ids = function_exists( 'dna_b2b_case_images_ids' ) ? dna_b2b_case_images_ids() : [];
      $b2b_case_images = array_slice( $case_image_ids, 0, 3 );
      ?>
      <div class="b2b-case-entry-placeholders">
        <?php for ( $index = 0; $index < 3; $index++ ) : ?>
          <?php
          $attachment_id = isset( $b2b_case_images[ $index ] ) ? absint( $b2b_case_images[ $index ] ) : 0;
          $image_html = $attachment_id
            ? wp_get_attachment_image(
              $attachment_id,
              'large',
              false,
              [
                'class'    => 'b2b-case-image',
                'loading'  => 'lazy',
                'decoding' => 'async',
                'alt'      => dna_attachment_alt_from_context( $attachment_id, 'B2B case study visual' ),
              ]
            )
            : '';
          ?>
          <figure class="b2b-case-placeholder">
            <?php if ( $image_html ) : ?>
              <?php echo wp_kses_post( $image_html ); ?>
            <?php else : ?>
              <span><?php echo esc_html( sprintf( 'Image Placeholder %02d', $index + 1 ) ); ?></span>
            <?php endif; ?>
          </figure>
        <?php endfor; ?>
      </div>
      <div class="b2b-case-entry-actions">
        <a class="btn-ghost b2b-case-link" href="<?php echo esc_url( home_url( '/case-study/' ) ); ?>">View Case Study</a>
        <a class="btn-ghost b2b-case-link" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Contact</a>
      </div>
    </section>

    <section class="b2b-section b2b-faq" aria-labelledby="b2b-faq" data-dna-reveal data-dna-reveal-order="5">
      <h2 id="b2b-faq" class="b2b-section-title">FAQ</h2>
      <?php $faq_content = trim((string) get_the_content()); ?>
      <div class="b2b-faq-content">
        <?php if ($faq_content !== '') : ?>
          <?php echo apply_filters('the_content', $faq_content); ?>
        <?php else : ?>
          <p>Add your Rank Math FAQ block in this page editor to publish structured FAQ schema.</p>
        <?php endif; ?>
      </div>
    </section>
  </div>
</main>

<button
  class="b2b-floating-cta"
  type="button"
  data-dna-b2b-floating
  data-dna-b2b-open
  aria-controls="dnaB2BWizard"
  aria-haspopup="dialog"
  hidden
>
  Start Your Project
</button>

<div class="dna-b2b-wizard" id="dnaB2BWizard" hidden aria-hidden="true">
  <div class="dna-b2b-wizard__backdrop" data-dna-b2b-close></div>
  <div class="dna-b2b-wizard__dialog" role="dialog" aria-modal="true" aria-labelledby="dnaB2BWizardTitle">
    <div class="dna-b2b-wizard__shell">
      <header class="dna-b2b-wizard__header">
        <div class="dna-b2b-wizard__header-copy">
          <span class="dna-b2b-wizard__eyebrow">B2B Guided Intake</span>
          <h2 class="dna-b2b-wizard__title" id="dnaB2BWizardTitle">Start Your Project</h2>
        </div>
        <button class="dna-b2b-wizard__close" type="button" data-dna-b2b-close aria-label="Close intake form">×</button>
      </header>

      <div class="dna-b2b-wizard__progress" aria-hidden="true">
        <span class="dna-b2b-wizard__progress-fill" id="dnaB2BWizardProgress"></span>
      </div>

      <div class="dna-b2b-wizard__error" id="dnaB2BWizardError" aria-live="polite"></div>

      <div class="dna-b2b-wizard__body" id="dnaB2BWizardBody"></div>

      <footer class="dna-b2b-wizard__footer">
        <button class="btn-ghost dna-b2b-wizard__back" id="dnaB2BWizardBack" type="button">Back</button>
        <button class="btn-primary dna-b2b-wizard__next" id="dnaB2BWizardNext" type="button">Next</button>
      </footer>
    </div>
  </div>
</div>

<?php get_footer(); ?>
