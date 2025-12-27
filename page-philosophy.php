<?php
/**
 * Template Name: Philosophy
 */

get_header();
?>

<main id="primary" class="site-main page philosophy-page">

  <section class="ph-hero">
    <div class="container">
      <div class="ph-wrap">
        <h1 class="ph-title"><?php echo esc_html( strtoupper( get_the_title() ?: 'Philosophy' ) ); ?></h1>

        <div class="ph-body">
        <p>
          We are a design and manufacturing studio.<br>
          Not an agency, not a marketplace — a studio that designs, produces, and delivers.
        </p>

        <p>
          Our strength lies in design.<br>
          You can see it in the products themselves: calm proportions, clear structure,
          and details that hold up over time.
        </p>

        <p>
          We design across a wide range of forms — apparel, drinkware, small objects,
          printed matter, even refrigerator magnets.<br>
          This breadth is intentional. Every company needs objects that represent them,
          and we design those objects to feel considered, not disposable.
        </p>

        <p>
          Our principle is simple: <strong>less is more</strong>.<br>
          Design should remove noise, not add to it. The same thinking shapes our products,
          and this website.
        </p>

        <div class="ph-divider" aria-hidden="true"></div>

        <p class="ph-cta">
          If this way of thinking resonates with you,<br>
          we would be glad to continue the conversation.
        </p>
        </div>
      </div>
    </div>
  </section>

</main>

<?php get_footer(); ?>