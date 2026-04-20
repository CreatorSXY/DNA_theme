<?php
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
  <div class="header-inner container header-3col">

    <div class="header-left">
      <a class="logo-link" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Home">
        <?php if (function_exists('the_custom_logo') && has_custom_logo()) : ?>
          <?php the_custom_logo(); ?>
        <?php else : ?>
          <span class="logo-fallback">DNA</span>
        <?php endif; ?>
      </a>
    </div>

    <div class="header-center">
      <a class="wordmark" href="<?php echo esc_url(home_url('/')); ?>">
        <span>Design n’</span>
        <span>Aesthetics</span>
      </a>
    </div>

    <div class="header-right">
      <nav class="main-nav" aria-label="Primary">
        <?php
          wp_nav_menu([
            'theme_location' => 'primary',
            'container'      => false,
            'menu_class'     => 'nav-links',
            'fallback_cb'    => 'dna_fallback_menu',
            'depth'          => 1,
          ]);
        ?>
      </nav>

      <button class="menu-toggle" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="nav-drawer">
        <span></span><span></span><span></span>
      </button>
    </div>

  </div>

  <div id="nav-drawer" class="nav-drawer" hidden>
    <div class="nav-drawer-inner container">
      <?php
        wp_nav_menu([
          'theme_location' => 'primary',
          'container'      => false,
          'menu_class'     => 'drawer-links',
          'fallback_cb'    => 'dna_fallback_menu',
          'depth'          => 1,
        ]);
      ?>
    </div>
  </div>
</header>

<div class="dna-shipping-banner" role="region" aria-label="Shipping promotion">
  <div class="dna-shipping-banner__inner container">
    <span class="dna-shipping-banner__text">Free shipping on orders over $50!</span>
    <button class="dna-shipping-banner__close" type="button" aria-label="Dismiss shipping promotion">×</button>
  </div>
</div>
