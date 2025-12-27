<?php
/**
 * DNA Theme (v5) — WooCommerce-first, minimal.
 */

add_action('after_setup_theme', function () {
  add_theme_support('title-tag');
  add_theme_support('post-thumbnails');
  add_theme_support('custom-logo', [
    'height'      => 80,
    'width'       => 240,
    'flex-height' => true,
    'flex-width'  => true,
  ]);
  add_theme_support('woocommerce');

  register_nav_menus([
    'primary' => __('Primary Menu', 'dna'),
  ]);
});

/**
 * Disable default WooCommerce CSS (we fully theme Shop/Product/Cart/Checkout).
 */
add_filter('woocommerce_enqueue_styles', '__return_empty_array');

/**
 * Enqueue theme CSS/JS with cache-busting versions.
 */
add_action('wp_enqueue_scripts', function () {
  $base = get_template_directory();
  $uri  = get_template_directory_uri();

  $main_rel = '/assets/css/styles.css';
  $wc_rel   = '/assets/css/woocommerce.css';
  $js_rel   = '/assets/js/main.js';

  $main_abs = $base . $main_rel;
  $wc_abs   = $base . $wc_rel;
  $js_abs   = $base . $js_rel;

  wp_enqueue_style('dna-styles', $uri . $main_rel, [], file_exists($main_abs) ? filemtime($main_abs) : null);

  $line_rel = '/assets/css/line.css';
  $line_abs = $base . $line_rel;
  $line_should_load = is_page('line') || (function_exists('is_product_category') && is_product_category());

  if (class_exists('WooCommerce')) {
    wp_enqueue_style('dna-woocommerce', $uri . $wc_rel, ['dna-styles'], file_exists($wc_abs) ? filemtime($wc_abs) : null);
    if (is_shop() || is_product_category() || is_product_tag()) {
      $shop_rel = '/assets/css/shop.css';
      $shop_abs = $base . $shop_rel;
      wp_enqueue_style('dna-shop', $uri . $shop_rel, ['dna-woocommerce'], file_exists($shop_abs) ? filemtime($shop_abs) : null);
    }
  }

  // Line styling is required for:
  // - /line landing page (page slug: line)
  // - ALL product category archives (your product category base is /line)
  if ($line_should_load) {
    $line_deps = ['dna-styles'];
    if (function_exists('is_product_category') && is_product_category() && wp_style_is('dna-shop', 'enqueued')) {
      $line_deps = ['dna-shop'];
    }
    wp_enqueue_style(
      'dna-line',
      $uri . $line_rel,
      $line_deps,
      file_exists($line_abs) ? filemtime($line_abs) : null
    );
  }
  wp_enqueue_script('dna-main', $uri . $js_rel, [], file_exists($js_abs) ? filemtime($js_abs) : null, true);

  $acct_js_rel = '/assets/js/account.js';
  $acct_js_abs = $base . $acct_js_rel;
  if (function_exists('is_account_page') && is_account_page()) {
    wp_enqueue_script('dna-account', $uri . $acct_js_rel, [], file_exists($acct_js_abs) ? filemtime($acct_js_abs) : null, true);
  }

  $var_js_rel = '/assets/js/variations.js';
  $var_js_abs = $base . $var_js_rel;
  if (function_exists('is_product') && is_product()) {
    wp_enqueue_script('dna-variations', $uri . $var_js_rel, [], file_exists($var_js_abs) ? filemtime($var_js_abs) : null, true);
  }

  $cp_css_rel = '/assets/css/contact-popup.css';
  $cp_js_rel  = '/assets/js/contact-popup.js';
  $cp_css_abs = $base . $cp_css_rel;
  $cp_js_abs  = $base . $cp_js_rel;

  wp_enqueue_style('dna-contact-popup', $uri . $cp_css_rel, ['dna-styles'], file_exists($cp_css_abs) ? filemtime($cp_css_abs) : null);
  wp_enqueue_script('dna-contact-popup', $uri . $cp_js_rel, [], file_exists($cp_js_abs) ? filemtime($cp_js_abs) : null, true);
}, 99);

/**
 * Body classes.
 */
add_filter('body_class', function ($classes) {
  if (is_front_page()) $classes[] = 'dna-home';
  if (class_exists('WooCommerce')) $classes[] = 'dna-has-woo';
  if (function_exists('is_product_category') && is_product_category()) $classes[] = 'line-page';
  return $classes;
});

/**
 * Cart count helper + live fragments.
 */
function dna_get_cart_count() {
  if (!class_exists('WooCommerce') || !function_exists('WC')) return 0;
  $cart = WC()->cart;
  if (!$cart) return 0;
  return (int) $cart->get_cart_contents_count();
}

add_filter('wp_nav_menu_objects', function ($items, $args) {
  if (!class_exists('WooCommerce')) return $items;
  $cart_url = untrailingslashit(wc_get_cart_url());
  foreach ($items as $item) {
    $item_url = untrailingslashit($item->url);
    if ($item_url === $cart_url && strpos($item->title, 'dna-cart-count') === false) {
      $item->title .= ' <span class="dna-cart-count">(' . dna_get_cart_count() . ')</span>';
    }
  }
  return $items;
}, 10, 2);

add_filter('woocommerce_add_to_cart_fragments', function ($fragments) {
  $fragments['span.dna-cart-count'] = '<span class="dna-cart-count">(' . dna_get_cart_count() . ')</span>';
  return $fragments;
});

add_filter('privacy_policy_url', function ($url) {
  return home_url('/privacy-policy/');
});

add_filter('wc_add_to_cart_message_html', function ($message, $products, $show_qty) {
  $cart_url = esc_url(wc_get_cart_url());
  $message = preg_replace('/<a[^>]*class="[^"]*wc-forward[^"]*"[^>]*>.*?<\/a>/i', '', $message);
  if (stripos($message, 'dna-cart-link') === false) {
    $message = preg_replace('/\byour cart\b/i', 'your <a class="dna-cart-link" href="' . $cart_url . '">cart</a>', $message, 1);
  }
  return trim($message);
}, 10, 3);

/**
 * My account: registration entry + privacy consent.
 */
add_action('woocommerce_register_form_start', function () {
  if (is_user_logged_in()) return;

  echo '<div class="dna-account-intro" aria-label="Account registration">';
  echo '<div class="dna-account-intro__text">';
  echo '<div class="dna-account-intro__eyebrow">New here?</div>';
  echo '<h2 class="dna-account-intro__title">Create an account</h2>';
  echo '<p class="dna-account-intro__note">Save your details for faster checkout and view your order history.</p>';
  echo '</div>';
  echo '</div>';
});

add_action('woocommerce_register_form', function () {
  if (is_user_logged_in()) return;
  $checked = !empty($_POST['dna_privacy_policy']) ? ' checked' : '';
  echo '<p class="form-row form-row-wide dna-privacy-consent">';
  echo '<label class="woocommerce-form__label woocommerce-form__label-for-checkbox">';
  echo '<input type="checkbox" name="dna_privacy_policy" value="1" required' . $checked . '>';
  echo '<span>I agree to the <a href="' . esc_url(home_url('/privacy-policy/')) . '" target="_blank" rel="noopener">privacy policy</a>.</span>';
  echo '</label>';
  echo '</p>';
});

add_filter('woocommerce_registration_errors', function ($errors, $username, $email) {
  if (empty($_POST['dna_privacy_policy'])) {
    $errors->add('dna_privacy_policy', __('Please agree to the privacy policy to register.', 'dna'));
  }
  return $errors;
}, 10, 3);

add_filter('woocommerce_enable_myaccount_registration', '__return_true');
add_filter('option_woocommerce_enable_myaccount_registration', function () { return 'yes'; });

add_filter('woocommerce_register_form_tag', function ($tag) {
  if (strpos($tag, 'id=') !== false) return $tag;
  $updated = preg_replace('/<form\b(?![^>]*\bid=)/', '<form id="dna-register"', $tag, 1);
  return $updated ?: $tag;
});

/**
 * My account: custom endpoints + menu.
 */
add_action('init', function () {
  if (!class_exists('WooCommerce')) return;
  add_rewrite_endpoint('billing-shipping', EP_ROOT | EP_PAGES);
  add_rewrite_endpoint('return-exchange', EP_ROOT | EP_PAGES);
});

add_filter('woocommerce_get_query_vars', function ($vars) {
  $vars['billing-shipping'] = 'billing-shipping';
  $vars['return-exchange'] = 'return-exchange';
  return $vars;
});

add_filter('woocommerce_account_menu_items', function ($items) {
  unset($items['downloads'], $items['edit-address'], $items['payment-methods']);

  $output = [];
  $inserted = false;
  foreach ($items as $key => $label) {
    $output[$key] = $label;
    if (!$inserted && in_array($key, ['orders', 'dashboard'], true)) {
      $output['billing-shipping'] = __('Billing & Shipping', 'dna');
      $output['return-exchange'] = __('Return & Exchange', 'dna');
      $inserted = true;
    }
  }

  if (!$inserted) {
    $output['billing-shipping'] = __('Billing & Shipping', 'dna');
    $output['return-exchange'] = __('Return & Exchange', 'dna');
  }

  return $output;
}, 99);

add_action('woocommerce_account_billing-shipping_endpoint', function () {
  echo '<div class="dna-account-stack">';
  wc_get_template('myaccount/my-address.php');
  wc_get_template('myaccount/payment-methods.php');
  echo '</div>';
});

add_action('woocommerce_account_return-exchange_endpoint', function () {
  echo '<div class="dna-account-return">';
  echo '<h2>Return &amp; Exchange</h2>';
  echo '<p>Need to start a return or exchange? Review our policy and email us with your order details.</p>';
  echo '<a class="dna-account-return__link" href="' . esc_url(home_url('/refund_returns/')) . '">View refund &amp; returns policy</a>';
  echo '</div>';
});


/**
 * Header menu fallback — minimal + on-brand.
 */
function dna_fallback_menu($args = []) {
  $links = [
    ['label' => 'Shop All', 'url' => home_url('/shop/')],
    ['label' => 'Lines', 'url' => home_url('/line/')],
    ['label' => 'Philosophy', 'url' => home_url('/philosophy/')],
  ];

  if (class_exists('WooCommerce')) {
    $links[] = ['label' => 'Cart', 'url' => wc_get_cart_url(), 'cart' => true];
  }

  $menu_class = isset($args['menu_class']) ? esc_attr($args['menu_class']) : 'nav-links';
  echo '<ul class="' . $menu_class . '">';
  foreach ($links as $l) {
    $label = $l['label'];
    if (!empty($l['cart'])) {
      $label .= ' <span class="dna-cart-count">(' . dna_get_cart_count() . ')</span>';
    }
    echo '<li><a href="' . esc_url($l['url']) . '">' . wp_kses_post($label) . '</a></li>';
  }
  echo '</ul>';
}

/**
 * WooCommerce wrappers: replace default wrappers with DNA markup.
 */
add_action('init', function () {
  if (!class_exists('WooCommerce')) return;

  remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
  remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);
  remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);

  add_action('woocommerce_before_main_content', function () {
    if (is_shop() || is_product_category() || is_product_tag()) { return; }
    echo '<div class="dna-wc">';
    echo '<header class="dna-wc-hero"><div class="container">';
    if (is_shop()) {
      echo '<h1 class="dna-wc-title">Shop</h1>';
    } elseif (is_product_category()) {
      echo '<h1 class="dna-wc-title">' . single_term_title('', false) . '</h1>';
    } elseif (is_product_tag()) {
      echo '<h1 class="dna-wc-title">' . single_term_title('', false) . '</h1>';
    } elseif (is_cart()) {
      echo '<h1 class="dna-wc-title">Cart</h1>';
    } elseif (is_checkout()) {
      echo '<h1 class="dna-wc-title">Checkout</h1>';
    } elseif (is_account_page()) {
      echo '<h1 class="dna-wc-title">Account</h1>';
    } else {
      echo '<h1 class="dna-wc-title">' . wp_get_document_title() . '</h1>';
    }
    echo '</div></header>';
    echo '<section class="dna-wc-body"><div class="container">';
  }, 10);

  add_action('woocommerce_after_main_content', function () {
    if (is_shop() || is_product_category() || is_product_tag()) { return; }
    echo '</div></section></div>';
  }, 10);
});

/**
 * Remove breadcrumbs for a cleaner look.
 */
add_action('init', function () {
  remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
});


// Remove payment request buttons (Apple/Google Pay) from product pages.
add_action('init', function () {
  if (!class_exists('WooCommerce')) return;
  remove_action('woocommerce_after_add_to_cart_button', 'wc_stripe_payment_request_button', 20);
  remove_action('woocommerce_after_add_to_cart_button', 'wcpay_payment_request_button', 20);
  remove_action('woocommerce_after_add_to_cart_button', 'wcpay_payment_request_button_html', 20);
});

// Remove link wrapper on single product gallery images (avoid jump to raw file)
add_filter('woocommerce_single_product_image_thumbnail_html', function($html){
  if (is_product()){
    $html = preg_replace('/<a[^>]*>(\s*<img[^>]+>\s*)<\/a>/i', '$1', $html);
  }
  return $html;
}, 99);

/**
 * Related products — DNA rebuild
 * - Avoid Woo's default template quirks & hover conflicts
 * - Render clean markup that matches the homepage grid language
 */
add_action('init', function(){
  if (!class_exists('WooCommerce')) return;
  remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);
  add_action('woocommerce_after_single_product_summary', 'dna_output_related_products', 20);
});

function dna_output_related_products(){
  if (!is_product()) return;

  $product_id = get_the_ID();
  $related_ids = wc_get_related_products($product_id, 3);
  if (empty($related_ids)) return;

  echo '<section class="dna-related" aria-label="Related products">';
  echo '<div class="dna-related__head">';
  echo '<h2 class="dna-related__title">Related products</h2>';
  echo '<a class="dna-related__link" href="' . esc_url(wc_get_page_permalink('shop')) . '">See all <span aria-hidden="true">→</span></a>';
  echo '</div>';

  echo '<ul class="dna-related__grid">';
  foreach ($related_ids as $rid){
    $p = wc_get_product($rid);
    if (!$p) continue;
    $permalink = get_permalink($rid);
    $img_id = $p->get_image_id();

    echo '<li class="dna-related__card">';
    echo '<a class="dna-related__a" href="' . esc_url($permalink) . '">';
    if ($img_id){
      // Use a non-cropped size (large/full) to preserve the artwork aspect.
      echo wp_get_attachment_image($img_id, 'large', false, [
        'class' => 'dna-related__img',
        'loading' => 'lazy',
      ]);
    } else {
      echo '<div class="dna-related__img dna-related__img--ph" aria-hidden="true"></div>';
    }
    echo '<div class="dna-related__meta">';
    echo '<div class="dna-related__name">' . esc_html($p->get_name()) . '</div>';
    echo '<div class="dna-related__price">' . wp_kses_post($p->get_price_html()) . '</div>';
    echo '</div>';
    echo '</a>';
    echo '</li>';
  }
  echo '</ul>';
  echo '</section>';
}


/**
 * Disable responsive srcset on Shop/Category/Tag archives so the browser doesn't pick 300x300.
 */
add_filter('wp_calculate_image_srcset', function ($sources, $size_array, $image_src, $image_meta, $attachment_id) {
  if (is_shop() || is_product_category() || is_product_tag()) {
    return false;
  }
  return $sources;
}, 10, 5);

/**
 * Force /line/{slug} to resolve as WooCommerce product categories,
 * even if a WordPress page exists with the same slug.
 */
add_action('parse_request', function ($wp) {
  if (!class_exists('WooCommerce')) {
    return;
  }

  $permalinks = function_exists('wc_get_permalink_structure') ? wc_get_permalink_structure() : [];
  $base = isset($permalinks['category_base']) && $permalinks['category_base'] !== ''
    ? trim($permalinks['category_base'], '/')
    : 'product-category';

  $path = trim($wp->request, '/');
  if ($path === '' || $path === $base) {
    return;
  }

  if (strpos($path, $base . '/') !== 0) {
    return;
  }

  $remainder = substr($path, strlen($base) + 1);
  if ($remainder === '') {
    return;
  }

  $segments = array_filter(explode('/', $remainder));
  if (empty($segments)) {
    return;
  }

  $slug = end($segments);
  $term = get_term_by('slug', $slug, 'product_cat');
  if (!$term || is_wp_error($term)) {
    return;
  }

  $wp->query_vars['post_type'] = 'product';
  $wp->query_vars['taxonomy'] = 'product_cat';
  $wp->query_vars['term'] = $slug;
  $wp->query_vars['product_cat'] = $slug;

  $wp->is_page = false;
  $wp->is_singular = false;
  $wp->is_single = false;
  $wp->is_home = false;
  $wp->is_tax = true;
  $wp->is_archive = true;
});



// No legacy hard-redirects. Your Lines are managed fully via WooCommerce categories.


/**
 * Homepage: allow selecting 3 featured products for the front page.
 */
function dna_get_product_choices_for_customizer() {
	$choices = array( 0 => __( '— Select a product —', 'dna' ) );

	if ( ! post_type_exists( 'product' ) ) {
		return $choices;
	}

	$products = get_posts( array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'numberposts'    => 200,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'suppress_filters' => false,
	) );

	foreach ( $products as $p ) {
		$choices[ (int) $p->ID ] = wp_strip_all_tags( get_the_title( $p ) );
	}

	return $choices;
}

function dna_customize_register_homepage( $wp_customize ) {
	$wp_customize->add_section( 'dna_homepage', array(
		'title'    => __( 'Homepage', 'dna' ),
		'priority' => 30,
	) );

	for ( $i = 1; $i <= 3; $i++ ) {
		$setting_id = 'dna_home_product_' . $i;

		$wp_customize->add_setting( $setting_id, array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
		) );

		$wp_customize->add_control( $setting_id, array(
			'label'   => sprintf( __( 'Homepage product %d', 'dna' ), $i ),
			'section' => 'dna_homepage',
			'type'    => 'select',
			'choices' => dna_get_product_choices_for_customizer(),
		) );
	}
}
add_action( 'customize_register', 'dna_customize_register_homepage' );

