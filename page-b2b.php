<?php
/*
Template Name: B2B Services
*/
get_header();
?>

<main class="page b2b-page">
  <div class="container">
    <header class="b2b-hero">
      <h1 class="page-title">B2B Services</h1>
      <p class="b2b-lead">
        We partner with brands, schools, and retailers to build thoughtful objects and
        apparel. DNA handles the full process so your team can stay focused on vision.
      </p>
    </header>

    <section class="b2b-grid">
      <article class="b2b-card">
        <h2>Design</h2>
        <p>
          Collaborative art direction, product strategy, and visual systems that match
          your brand. We translate ideas into production-ready assets.
        </p>
      </article>

      <article class="b2b-card">
        <h2>Production</h2>
        <p>
          End-to-end manufacturing with vetted partners. Materials, sampling, and
          quality control managed with a clear, reliable timeline.
        </p>
      </article>

      <article class="b2b-card">
        <h2>Worry-Free Service</h2>
        <p>
          Transparent updates, logistics support, and post-launch coordination so every
          handoff is smooth and accountable.
        </p>
      </article>
    </section>

    <section class="b2b-section b2b-steps" aria-labelledby="b2b-how">
      <h2 id="b2b-how" class="b2b-section-title">How It Works</h2>
      <div class="b2b-steps-grid">
        <article class="b2b-step">
          <div class="b2b-step-number">01</div>
          <div class="b2b-step-body">
            <h3>Inquiry</h3>
            <p>Short brief, goals, timeline, budget range.</p>
          </div>
        </article>
        <article class="b2b-step">
          <div class="b2b-step-number">02</div>
          <div class="b2b-step-body">
            <h3>Alignment</h3>
            <p>We confirm scope, references, and standards.</p>
          </div>
        </article>
        <article class="b2b-step">
          <div class="b2b-step-number">03</div>
          <div class="b2b-step-body">
            <h3>Sample &amp; Produce</h3>
            <p>Sampling, revisions, production scheduling.</p>
          </div>
        </article>
        <article class="b2b-step">
          <div class="b2b-step-number">04</div>
          <div class="b2b-step-body">
            <h3>Delivery &amp; Support</h3>
            <p>Logistics coordination and post-launch support.</p>
          </div>
        </article>
      </div>
    </section>

    <section class="b2b-section b2b-types" aria-labelledby="b2b-types">
      <h2 id="b2b-types" class="b2b-section-title">Selected Project Types</h2>
      <ul class="b2b-list">
        <li>Montessori-inspired apparel &amp; objects</li>
        <li>School / campus merchandise</li>
        <li>Educational gifts &amp; retail items</li>
        <li>Small-batch brand launches</li>
      </ul>
    </section>

    <section class="b2b-section b2b-fit" aria-labelledby="b2b-fit">
      <h2 id="b2b-fit" class="b2b-section-title">Engagement &amp; Fit</h2>
      <div class="b2b-fit-list">
        <p>We work best with teams that value restraint, consistency, and long-term coherence.</p>
        <p>Typical engagements range from design-only to end-to-end production.</p>
        <p>We prefer clear timelines over rush orders; quality is controlled through sampling and checkpoints.</p>
      </div>
    </section>

    <section class="b2b-section b2b-faq" aria-labelledby="b2b-faq">
      <h2 id="b2b-faq" class="b2b-section-title">FAQ</h2>
      <div class="b2b-faq-list">
        <details>
          <summary><span class="faq-q">Q:</span> Can you do design only?</summary>
          <p><span class="faq-a">A:</span> Yes. We can deliver production-ready assets and specs.</p>
        </details>
        <details>
          <summary><span class="faq-q">Q:</span> Can you produce from an existing design?</summary>
          <p><span class="faq-a">A:</span> Yes, after a quick technical review.</p>
        </details>
        <details>
          <summary><span class="faq-q">Q:</span> What categories do you support?</summary>
          <p><span class="faq-a">A:</span> Apparel, small objects, and retail-ready goods.</p>
        </details>
        <details>
          <summary><span class="faq-q">Q:</span> Do you ship internationally?</summary>
          <p><span class="faq-a">A:</span> Yes, we can coordinate logistics and handoff.</p>
        </details>
        <details>
          <summary><span class="faq-q">Q:</span> How do we start?</summary>
          <p><span class="faq-a">A:</span> Email us with goals, timeline, quantity range, and references.</p>
        </details>
      </div>
    </section>

    <section class="b2b-cta">
      <h2>Start a Project</h2>
      <p>
        Share your goals, timeline, and budget. We will propose a tailored plan and
        keep everything moving from concept to delivery.
      </p>
      <div class="b2b-cta-actions">
        <a class="btn-primary" href="mailto:hello@designnaesthetics.com">Start with Email</a>
        <a class="btn-ghost" href="<?php echo esc_url( home_url( '/philosophy/' ) ); ?>">View Philosophy</a>
      </div>
    </section>
  </div>
</main>

<?php get_footer(); ?>
