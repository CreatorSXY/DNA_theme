<?php
/**
 * Template Name: Montessori (Line Landing)
 */

get_header();

if ( have_posts() ) :
  while ( have_posts() ) : the_post();

    $has_content = trim( wp_strip_all_tags( get_the_content() ) ) !== '';
    ?>
    <main id="primary" class="site-main dna-line-landing">

      <section class="ml-hero">
        <div class="ml-container">
          <header class="ml-header">
            <h1 class="ml-title"><?php echo esc_html( get_the_title() ); ?></h1>
          </header>

          <div class="ml-story">
            <?php if ( $has_content ) : ?>
              <div class="ml-story-content">
                <?php the_content(); ?>
              </div>
            <?php else : ?>
              <div class="ml-story-content">
                <p>
                  We designed this Montessori Line by going back to the originals — <strong>Golden Beads</strong> and
                  <strong>the Pink Tower</strong> — not to replicate the materials, but to translate their ideas into a
                  visual language for everyday life.
                </p>
                <p>
                  Golden Beads represent clarity and rhythm: one unit at a time, turning complexity into something
                  understandable. The Pink Tower is about proportion, focus, and growth through order — each layer
                  smaller, each step deliberate. We reimagined the Pink Tower as a gentle, expressive character, and
                  used the beads as quiet visual markers throughout the designs. Playful, but never childish.
                </p>
                <p>
                  This line is our way of keeping the beauty of learning close at hand — calm, restrained, and meant to
                  last.
                </p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <?php
      if ( class_exists( 'WooCommerce' ) ) :

        $per_page = 12;
        $preferred_slug = 'montessori-line';
        $fallback_slug  = 'montessori';

        $term = get_term_by( 'slug', $preferred_slug, 'product_cat' );
        if ( ! $term || is_wp_error( $term ) ) {
          $term = get_term_by( 'slug', $fallback_slug, 'product_cat' );
        }

        if ( $term && ! is_wp_error( $term ) ) :

          $q = new WP_Query( array(
            'post_type'           => 'product',
            'post_status'         => 'publish',
            'posts_per_page'      => $per_page,
            'ignore_sticky_posts' => true,
            'tax_query'           => array(
              array(
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => array( (int) $term->term_id ),
              ),
            ),
            'orderby'             => 'date',
            'order'               => 'DESC',
            'no_found_rows'       => true,
          ) );
          ?>
          <section class="ml-products">
            <div class="ml-container">
              <h2 class="ml-subtitle"><?php echo esc_html__( 'Products', 'your-textdomain' ); ?></h2>

              <?php if ( $q->have_posts() ) : ?>
                <div class="ml-grid">
                  <?php while ( $q->have_posts() ) : $q->the_post();
                    $product_id = get_the_ID();
                    $permalink  = get_permalink( $product_id );
                    $title      = get_the_title( $product_id );
                    ?>
                    <article class="ml-card">
                      <a class="ml-card-link" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
                        <div class="ml-media">
                          <?php if ( has_post_thumbnail( $product_id ) ) : ?>
                            <?php
                              echo get_the_post_thumbnail(
                                $product_id,
                                'woocommerce_thumbnail',
                                array( 'class' => 'ml-product-img', 'loading' => 'lazy' )
                              ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            ?>
                          <?php else : ?>
                            <div class="ml-product-placeholder" aria-hidden="true"></div>
                          <?php endif; ?>
                        </div>
                        <h3 class="ml-product-title"><?php echo esc_html( $title ); ?></h3>
                      </a>
                    </article>
                  <?php endwhile; ?>
                </div>
              <?php else : ?>
                <p class="ml-empty"><?php echo esc_html__( 'No products found in this collection yet.', 'your-textdomain' ); ?></p>
              <?php endif; ?>
            </div>
          </section>
          <?php
          wp_reset_postdata();

        else : ?>
          <section class="ml-products">
            <div class="ml-container">
              <p class="ml-empty">
                <?php echo esc_html__( 'Collection category not found. Please create a product category with slug "montessori-line" (or "montessori") and assign products to it.', 'your-textdomain' ); ?>
              </p>
            </div>
          </section>
        <?php endif; ?>

      <?php else : ?>
        <section class="ml-products">
          <div class="ml-container">
            <p class="ml-empty"><?php echo esc_html__( 'WooCommerce is not active.', 'your-textdomain' ); ?></p>
          </div>
        </section>
      <?php endif; ?>

      

    </main>
    <?php

  endwhile;
endif;

get_footer();