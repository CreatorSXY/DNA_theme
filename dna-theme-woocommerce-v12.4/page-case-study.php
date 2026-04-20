<?php
/*
 * Slug-specific template for /case-study
 */
get_header();
?>

<main class="page case-study-page">
  <div class="container">
    <?php
    $case_hero_image_id = function_exists( 'dna_get_theme_image_id' ) ? dna_get_theme_image_id( 'case_hero' ) : 0;
    $case_hero_slide_ids = function_exists( 'dna_get_theme_image_ids' ) ? dna_get_theme_image_ids( 'case_hero_slides' ) : [];
    $case_hero_slides = [];

    if ( ! empty( $case_hero_slide_ids ) && function_exists( 'dna_render_original_attachment_image' ) ) {
      foreach ( $case_hero_slide_ids as $index => $slide_id ) {
        $slide_id = absint( $slide_id );
        if ( $slide_id < 1 ) {
          continue;
        }
        $slide_html = dna_render_original_attachment_image(
          $slide_id,
          [
            'class'         => 'case-study-hero-slide__img',
            'loading'       => $index === 0 ? 'eager' : 'lazy',
            'fetchpriority' => $index === 0 ? 'high' : 'auto',
            'decoding'      => 'async',
            'alt'           => dna_attachment_alt_from_context( $slide_id, 'Case study hero visual' ),
          ]
        );
        if ( $slide_html !== '' ) {
          $case_hero_slides[] = [
            'html'        => $slide_html,
            'placeholder' => false,
          ];
        }
      }
    }

    if ( empty( $case_hero_slides ) && $case_hero_image_id && function_exists( 'dna_render_original_attachment_image' ) ) {
      $fallback_html = dna_render_original_attachment_image(
        $case_hero_image_id,
        [
          'class'         => 'case-study-hero-slide__img',
          'loading'       => 'eager',
          'fetchpriority' => 'high',
          'decoding'      => 'async',
          'alt'           => dna_attachment_alt_from_context( $case_hero_image_id, 'Case study hero visual' ),
        ]
      );
      if ( $fallback_html !== '' ) {
        $case_hero_slides[] = [
          'html'        => $fallback_html,
          'placeholder' => false,
        ];
      }
    }

    while ( count( $case_hero_slides ) < 3 ) {
      $case_hero_slides[] = [
        'html'        => '',
        'placeholder' => true,
      ];
    }
    $case_hero_has_media = ! empty( array_filter( $case_hero_slides, static function ( $slide ) {
      return empty( $slide['placeholder'] );
    } ) );
    ?>
    <?php dna_render_rank_math_breadcrumbs(); ?>
    <header class="case-study-hero" aria-labelledby="case-study-title" data-dna-reveal data-dna-reveal-order="1">
      <p class="case-study-kicker">Case Study</p>
      <h1 id="case-study-title" class="case-study-title">The Pink Tower Series</h1>
      <p class="case-study-lead">
        A transparent deal record of how a concept-first direction became a shipped, multi-category product system.
      </p>
      <div class="case-study-hero-carousel<?php echo $case_hero_has_media ? ' has-media' : ''; ?>" data-case-hero-carousel data-dna-depth>
        <div class="case-study-hero-carousel__viewport" data-case-hero-viewport aria-label="Case study hero images" tabindex="0">
          <div class="case-study-hero-carousel__track" data-case-hero-track>
            <?php foreach ( $case_hero_slides as $index => $slide ) : ?>
              <figure
                class="case-study-hero-slide<?php echo ! empty( $slide['placeholder'] ) ? ' is-placeholder' : ''; ?>"
                data-case-hero-slide
                data-slide-index="<?php echo esc_attr( (string) $index ); ?>"
              >
                <?php if ( empty( $slide['placeholder'] ) && ! empty( $slide['html'] ) ) : ?>
                  <?php echo $slide['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php else : ?>
                  <span class="case-study-hero-slide__placeholder-label"><?php echo esc_html( sprintf( 'Hero Placeholder %02d', $index + 1 ) ); ?></span>
                <?php endif; ?>
              </figure>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="case-study-hero-carousel__controls">
          <button type="button" class="case-study-hero-carousel__control" data-case-hero-prev aria-label="Previous hero image">Previous</button>
          <button type="button" class="case-study-hero-carousel__control" data-case-hero-next aria-label="Next hero image">Next</button>
        </div>
      </div>
    </header>

    <section class="case-study-section" aria-labelledby="case-study-process" data-dna-reveal data-dna-reveal-order="2">
      <h2 id="case-study-process" class="case-study-section-title">Process</h2>
      <div class="case-study-prose">
        <p>
          We translated brand intent into production-ready output across apparel, accessories, print, and packaging. The scope included concept design, artwork development, supplier communication, sampling for quality checks, and production supervision.
        </p>
        <p>
          Final delivery covered 200 sweatshirts, 280 T-shirts, 320 caps, 580 decor magnets, 300 tote bags, and 4,000 flyers, with labels and bag systems aligned for a consistent retail presentation.
        </p>
      </div>
    </section>

    <section class="case-study-section" aria-labelledby="case-study-pricing" data-dna-reveal data-dna-reveal-order="3">
      <h2 id="case-study-pricing" class="case-study-section-title">Pricing (USD)</h2>
      <div class="case-study-prose">
        <p>
          Line-by-line estimate from the deal worksheet, normalized to USD for direct review.
        </p>
        <div class="case-study-pricing-table-wrap">
          <table class="case-study-pricing-table">
            <thead>
              <tr>
                <th scope="col">Line Item</th>
                <th scope="col">Units</th>
                <th scope="col">Unit Price (USD)</th>
                <th scope="col">Total Price (USD)</th>
              </tr>
            </thead>
            <tbody>
              <tr class="case-study-group-row"><td colspan="4">Design</td></tr>
              <tr><td>Design - The Pink Tower (A)</td><td class="num">3</td><td class="num">$80.00</td><td class="num">$240.00</td></tr>
              <tr><td>Design - The Pink Tower (B)</td><td class="num">2</td><td class="num">$80.00</td><td class="num">$160.00</td></tr>
              <tr><td>Design - Golden Beads</td><td class="num">2</td><td class="num">$80.00</td><td class="num">$160.00</td></tr>
              <tr><td>Design - Flyer</td><td class="num">1</td><td class="num">$80.00</td><td class="num">$80.00</td></tr>
              <tr><td>Design - PopArt Maria</td><td class="num">4</td><td class="num">$80.00</td><td class="num">$320.00</td></tr>
              <tr><td>Design - KFC - AMS</td><td class="num">3</td><td class="num">$30.00</td><td class="num">$90.00</td></tr>
              <tr><td>Design - Montessori's</td><td class="num">3</td><td class="num">$30.00</td><td class="num">$90.00</td></tr>
              <tr><td>Design - Montessori 3-star</td><td class="num">2</td><td class="num">$30.00</td><td class="num">$60.00</td></tr>
              <tr class="case-study-group-row"><td colspan="4">Manufacture / Sampling / Packaging</td></tr>
              <tr><td>Manufacture (T)</td><td class="num">280</td><td class="num">$8.4042</td><td class="num">$2,353.18</td></tr>
              <tr><td>Manufacture (H)</td><td class="num">200</td><td class="num">$15.2145</td><td class="num">$3,042.90</td></tr>
              <tr><td>Sampling (Cap)</td><td class="num">2</td><td class="num">$28.98</td><td class="num">$57.96</td></tr>
              <tr><td>Manufacture (Cap)</td><td class="num">320</td><td class="num">$3.9123</td><td class="num">$1,251.94</td></tr>
              <tr><td>Manufacture (Magnets)</td><td class="num">580</td><td class="num">$0.2536</td><td class="num">$147.07</td></tr>
              <tr><td>Manufacture (Tote Bag)</td><td class="num">300</td><td class="num">$1.0626</td><td class="num">$318.78</td></tr>
              <tr><td>Manufacture (Plastic Bag S)</td><td class="num">300</td><td class="num">$0.1014</td><td class="num">$30.43</td></tr>
              <tr><td>Manufacture (Plastic Bag L)</td><td class="num">300</td><td class="num">$0.1183</td><td class="num">$35.50</td></tr>
              <tr><td>Manufacture (Label)</td><td class="num">860</td><td class="num">$0.0265</td><td class="num">$22.75</td></tr>
              <tr><td>Manufacture (Paper Bag S)</td><td class="num">500</td><td class="num">$0.0840</td><td class="num">$42.02</td></tr>
              <tr><td>Manufacture (Paper Bag L)</td><td class="num">500</td><td class="num">$0.0913</td><td class="num">$45.64</td></tr>
              <tr><td>Manufacture (Flyer)</td><td class="num">4000</td><td class="num">$0.0395</td><td class="num">$157.94</td></tr>
              <tr class="case-study-group-row"><td colspan="4">Logistics / Margin / Total</td></tr>
              <tr><td>Shipping + Tariff</td><td class="num">379</td><td class="num">$2.7737</td><td class="num">$1,051.25</td></tr>
              <tr><td>Margin (25%)</td><td class="num">1</td><td class="num">$2,176.51</td><td class="num">$2,176.51</td></tr>
              <tr class="is-total"><td>Final Total</td><td class="num">1</td><td class="num">$11,933.82</td><td class="num">$11,933.82</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section class="case-study-section case-study-gallery" aria-labelledby="case-study-gallery" data-dna-reveal data-dna-reveal-order="4">
      <h2 id="case-study-gallery" class="case-study-section-title">Visuals</h2>
      <?php $case_image_ids = function_exists( 'dna_b2b_case_images_ids' ) ? dna_b2b_case_images_ids() : []; ?>
      <div class="case-study-gallery-grid">
        <?php if ( ! empty( $case_image_ids ) ) : ?>
          <?php foreach ( $case_image_ids as $attachment_id ) : ?>
            <?php
            $attachment_id = absint( $attachment_id );
            if ( $attachment_id < 1 ) {
              continue;
            }
            $image_html = wp_get_attachment_image(
              $attachment_id,
              'large',
              false,
              [
                'class'    => 'case-study-image',
                'loading'  => 'lazy',
                'decoding' => 'async',
                'alt'      => dna_attachment_alt_from_context( $attachment_id, 'Case study visual' ),
              ]
            );
            ?>
            <figure class="case-study-image-placeholder">
              <?php if ( $image_html ) : ?>
                <?php echo wp_kses_post( $image_html ); ?>
              <?php else : ?>
                <span><?php echo esc_html( sprintf( 'Image Placeholder %d', $attachment_id ) ); ?></span>
              <?php endif; ?>
            </figure>
          <?php endforeach; ?>
        <?php else : ?>
          <?php for ( $index = 0; $index < 3; $index++ ) : ?>
            <figure class="case-study-image-placeholder"><span><?php echo esc_html( sprintf( 'Image Placeholder %02d', $index + 1 ) ); ?></span></figure>
          <?php endfor; ?>
        <?php endif; ?>
      </div>
    </section>

    <section class="case-study-section case-study-back" data-dna-reveal data-dna-reveal-order="5">
      <a class="btn-ghost" href="<?php echo esc_url( home_url( '/b2b/' ) ); ?>">Back to B2B</a>
    </section>
  </div>
</main>

<?php get_footer(); ?>
