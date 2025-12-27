<?php
/**
 * Template Name: DNA Line Landing
 * Description: Shared landing template for /line/{line} pages.
 */

get_header();

if ( have_posts() ) :
  while ( have_posts() ) : the_post();

    $slug = get_post_field('post_name', get_the_ID());
    $override = get_post_meta(get_the_ID(), 'dna_line_category', true);
    $cat_slug = $override ? sanitize_title($override) : sanitize_title($slug);

    $raw_text = trim( preg_replace('/\s+/', ' ', wp_strip_all_tags( get_the_content() ) ) );
    $lead = '';
    $body = '';
    if ($raw_text !== '') {
      // Split into lead + body by sentence-ish chunks (simple, robust).
      $parts = preg_split('/\n\s*\n|\r\n\s*\r\n/', get_the_content());
      $plain_parts = array_values(array_filter(array_map(function($p){
        return trim(wp_strip_all_tags($p));
      }, (array)$parts)));
      $lead = $plain_parts[0] ?? '';
      if (count($plain_parts) > 1) {
        $body = implode("\n\n", array_slice($plain_parts, 1));
      }
    }

    // Products from category that matches the page slug (or override).
    $products = [];
    if ( class_exists('WooCommerce') && $cat_slug ) {
      $q = new WP_Query([
        'post_type'      => 'product',
        'posts_per_page' => 3,
        'post_status'    => 'publish',
        'tax_query'      => [[
          'taxonomy' => 'product_cat',
          'field'    => 'slug',
          'terms'    => [$cat_slug],
        ]],
      ]);
      if ($q->have_posts()) {
        while ($q->have_posts()) { $q->the_post();
          $products[] = get_the_ID();
        }
        wp_reset_postdata();
      }
    }
    ?>
    <main id="primary" class="site-main dna-line-landing line-page">

      <section class="ml-hero">
        <div class="ml-container">
          <header class="ml-header">
            <h1 class="ml-title"><?php the_title(); ?></h1>

            <?php if ($lead) : ?>
              <p class="ml-lead"><?php echo esc_html($lead); ?></p>
            <?php endif; ?>

            <?php if ($body) : ?>
              <div class="ln-body">
                <?php foreach (preg_split('/\n\n+/', $body) as $para) : $para = trim($para); if (!$para) continue; ?>
                  <p><?php echo esc_html($para); ?></p>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </header>
        </div>
      </section>

      <section class="ml-products">
        <div class="ml-container">
          <h2>Products</h2>

          <div class="ml-grid">
            <?php if (!empty($products)) : ?>
              <?php foreach ($products as $pid) :
                $permalink = get_permalink($pid);
                $title     = get_the_title($pid);
                $thumb     = get_the_post_thumbnail($pid, 'large');
              ?>
                <a class="ml-card" href="<?php echo esc_url($permalink); ?>">
                  <div class="ml-thumb"><?php echo $thumb ?: '<div class="ml-thumb placeholder"></div>'; ?></div>
                  <div class="ml-product-title"><?php echo esc_html($title); ?></div>
                </a>
              <?php endforeach; ?>
            <?php else : ?>
              <?php for ($i=0;$i<3;$i++): ?>
                <div class="ml-card ml-empty" aria-hidden="true">
                  <div class="ml-thumb placeholder"></div>
                  <div class="ml-product-title">Coming soon</div>
                </div>
              <?php endfor; ?>
            <?php endif; ?>
          </div>
        </div>
      </section>

    </main>
    <?php
  endwhile;
endif;

get_footer();
