<?php
/**
 * Page template for the /line page.
 *
 * Assumption (matches your setup):
 * - WooCommerce Product category base is: /line
 * - Each "Line" is a product category rendered at /line/{category-slug}
 *
 * This page auto-lists all top-level product categories (oldest first),
 * including categories with zero products ("Now in development").
 */

defined('ABSPATH') || exit;

get_header();

$lines = [];

if (function_exists('get_terms')) {
  $lines = get_terms([
    'taxonomy'   => 'product_cat',
    'hide_empty' => false,
    'parent'     => 0,
    'orderby'    => 'term_id',
    'order'      => 'ASC',
  ]);

  if (!is_array($lines)) {
    $lines = [];
  }

  // Drop Woo's default bucket.
  $lines = array_values(array_filter($lines, function ($t) {
    return ($t instanceof WP_Term) && $t->taxonomy === 'product_cat' && $t->slug !== 'uncategorized';
  }));
}

function dna_line_kicker_number($i) {
  $n = (int)$i;
  return str_pad((string)$n, 2, '0', STR_PAD_LEFT);
}
?>

<main id="primary" class="site-main dna-line-index line-page" data-dna-template="line-index-v12.4">
  <div class="line-container">

    <header class="line-page-header" aria-label="Lines">
      <h1 class="line-page-title">LINES</h1>
    </header>

    <?php if (!empty($lines)) : ?>
      <?php foreach ($lines as $idx => $term) :
        $kicker = dna_line_kicker_number($idx + 1);
        $term_link = get_term_link($term);
        if (is_wp_error($term_link)) {
          $term_link = '';
        }

        // Pull up to 3 recent products in this category.
        $product_ids = [];
        if (class_exists('WooCommerce')) {
          $q = new WP_Query([
            'post_type'      => 'product',
            'posts_per_page' => 3,
            'post_status'    => 'publish',
            'ignore_sticky_posts' => true,
            'tax_query'      => [[
              'taxonomy' => 'product_cat',
              'field'    => 'term_id',
              'terms'    => [(int)$term->term_id],
            ]],
            'orderby'        => 'date',
            'order'          => 'DESC',
          ]);

          if ($q->have_posts()) {
            while ($q->have_posts()) {
              $q->the_post();
              $product_ids[] = get_the_ID();
            }
            wp_reset_postdata();
          }
        }

        $is_empty = empty($product_ids);
      ?>
        <section class="line-section" aria-labelledby="line-<?php echo esc_attr($kicker); ?>">
          <div class="line-kicker"><?php echo esc_html($kicker); ?></div>

          <div class="line-row">
            <h2 id="line-<?php echo esc_attr($kicker); ?>" class="line-title"><?php echo esc_html($term->name); ?></h2>
            <?php if (!empty($term_link)) : ?>
              <a class="line-link" href="<?php echo esc_url($term_link); ?>">See all</a>
            <?php endif; ?>
          </div>

          <?php if (!$is_empty) : ?>
            <div class="line-grid" role="list">
              <?php foreach ($product_ids as $pid) :
                $thumb = get_the_post_thumbnail($pid, 'large');
                $plink = get_permalink($pid);
              ?>
                <a class="line-thumb" href="<?php echo esc_url($plink); ?>" role="listitem" aria-label="<?php echo esc_attr(get_the_title($pid)); ?>">
                  <?php echo $thumb ? $thumb : '<div class="line-thumb placeholder" aria-hidden="true"></div>'; ?>
                </a>
              <?php endforeach; ?>

              <?php // If fewer than 3 products, pad placeholders for layout stability.
              for ($j = count($product_ids); $j < 3; $j++) : ?>
                <div class="line-thumb placeholder" role="listitem" aria-hidden="true"></div>
              <?php endfor; ?>
            </div>
          <?php endif; ?>

          <?php if ($is_empty) : ?>
            <div class="ln-body">
              <p class="line-dev-note">Now in development. Looking forward to meeting with you.</p>
            </div>
          <?php endif; ?>
        </section>
      <?php endforeach; ?>
    <?php else : ?>
      <section class="line-section">
        <div class="line-kicker">01</div>
        <h2 class="line-title">No Lines yet</h2>
        <div class="ln-body">
          <p class="line-dev-note">Create product categories in WooCommerce to populate this page.</p>
        </div>
      </section>
    <?php endif; ?>

  </div>
</main>

<?php get_footer(); ?>
