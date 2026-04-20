<?php
/**
 * DNA Theme (v19.4) — WooCommerce-first, minimal.
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
 * Woo archives: keep 18 products per page on shop/category/tag.
 */
add_filter('loop_shop_per_page', function ($per_page) {
  if (is_admin()) {
    return $per_page;
  }

  if (
    (function_exists('is_shop') && is_shop()) ||
    (function_exists('is_product_category') && is_product_category()) ||
    (function_exists('is_product_tag') && is_product_tag())
  ) {
    return 18;
  }

  return $per_page;
}, 20);

/**
 * Rank Math breadcrumbs helper (theme-level, optional).
 * - Shows only when Rank Math breadcrumb API exists.
 * - Hidden on home/cart/checkout/account by default.
 */
function dna_render_rank_math_breadcrumbs() {
  if (!function_exists('rank_math_the_breadcrumbs')) {
    return;
  }

  if (is_front_page() || is_home()) {
    return;
  }

  if ((function_exists('is_cart') && is_cart()) ||
      (function_exists('is_checkout') && is_checkout()) ||
      (function_exists('is_account_page') && is_account_page())) {
    return;
  }

  echo '<div class="dna-rankmath-breadcrumbs" aria-label="Breadcrumb">';
  rank_math_the_breadcrumbs();
  echo '</div>';
}

/**
 * Add a stable parent crumb for service-related pages without changing URLs.
 */
add_filter('rank_math/frontend/breadcrumb/items', function ($crumbs) {
  if (!is_array($crumbs) || count($crumbs) < 2) {
    return $crumbs;
  }

  if (!is_page(['b2b', 'case-study', 'contact'])) {
    return $crumbs;
  }

  $parent_label = 'Services';
  $parent_url = home_url('/b2b/');

  foreach ($crumbs as $crumb) {
    if (!is_array($crumb)) {
      continue;
    }
    $label = isset($crumb[0]) ? (string) $crumb[0] : (string) ($crumb['label'] ?? '');
    $url = isset($crumb[1]) ? (string) $crumb[1] : (string) ($crumb['url'] ?? '');
    if ($label === $parent_label && untrailingslashit($url) === untrailingslashit($parent_url)) {
      return $crumbs;
    }
  }

  array_splice($crumbs, 1, 0, [[ $parent_label, $parent_url ]]);
  return $crumbs;
}, 20);

/**
 * Image alt fallbacks for product/attachment outputs in custom templates.
 */
function dna_image_alt_from_context($post_id, $context = '') {
  $post_id = absint($post_id);
  $thumb_id = $post_id ? get_post_thumbnail_id($post_id) : 0;
  $attachment_alt = $thumb_id ? trim((string) get_post_meta($thumb_id, '_wp_attachment_image_alt', true)) : '';
  if ($attachment_alt !== '') {
    return $attachment_alt;
  }

  $title = $post_id ? trim((string) wp_strip_all_tags(get_the_title($post_id))) : '';
  $context = trim((string) wp_strip_all_tags($context));
  if ($title !== '' && $context !== '') {
    return $title . ' - ' . $context;
  }
  if ($title !== '') {
    return $title;
  }
  return $context !== '' ? $context : 'Design n\' Aesthetics product image';
}

function dna_attachment_alt_from_context($attachment_id, $context = '') {
  $attachment_id = absint($attachment_id);
  $attachment_alt = $attachment_id ? trim((string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true)) : '';
  if ($attachment_alt !== '') {
    return $attachment_alt;
  }

  $title = $attachment_id ? trim((string) wp_strip_all_tags(get_the_title($attachment_id))) : '';
  $context = trim((string) wp_strip_all_tags($context));
  if ($title !== '' && $context !== '') {
    return $title . ' - ' . $context;
  }
  if ($title !== '') {
    return $title;
  }
  return $context !== '' ? $context : 'Design n\' Aesthetics image';
}

function dna_get_original_attachment_url( $attachment_id ) {
  $attachment_id = absint( $attachment_id );
  if ( $attachment_id < 1 ) {
    return '';
  }

  $src = '';
  if ( function_exists( 'wp_get_original_image_url' ) ) {
    $src = (string) wp_get_original_image_url( $attachment_id );
  }

  if ( $src === '' ) {
    $src = (string) wp_get_attachment_image_url( $attachment_id, 'full' );
  }

  if ( $src === '' ) {
    $src = (string) wp_get_attachment_url( $attachment_id );
  }

  return $src;
}

function dna_render_original_attachment_image( $attachment_id, $attrs = [] ) {
  $attachment_id = absint( $attachment_id );
  if ( $attachment_id < 1 ) {
    return '';
  }

  $src = dna_get_original_attachment_url( $attachment_id );
  if ( $src === '' ) {
    return '';
  }

  $allowed_attrs = [ 'class', 'alt', 'loading', 'fetchpriority', 'decoding' ];
  $pairs = [];

  foreach ( $allowed_attrs as $key ) {
    if ( ! isset( $attrs[ $key ] ) || $attrs[ $key ] === '' ) {
      continue;
    }
    $pairs[] = $key . '="' . esc_attr( (string) $attrs[ $key ] ) . '"';
  }

  return '<img src="' . esc_url( $src ) . '" ' . implode( ' ', $pairs ) . '>';
}

/**
 * Theme image slots (WP admin editable).
 */
function dna_theme_images_option_name() {
  return 'dna_theme_images';
}

function dna_theme_images_defaults() {
  return [
    'home_hero'        => 0,
    'b2b_hero'         => 0,
    'case_hero'        => 0,
    'shop_hero'        => 0,
    'line_hero'        => 0,
    'case_hero_slides' => [],
  ];
}

function dna_sanitize_attachment_ids( $ids ) {
  $clean = [];
  $seen = [];

  if ( ! is_array( $ids ) ) {
    return $clean;
  }

  foreach ( $ids as $id ) {
    $id = absint( $id );
    if ( $id < 1 || isset( $seen[ $id ] ) ) {
      continue;
    }
    $seen[ $id ] = true;
    $clean[] = $id;
  }

  return $clean;
}

function dna_sanitize_theme_images( $input ) {
  $defaults = dna_theme_images_defaults();
  $clean = $defaults;

  if ( ! is_array( $input ) ) {
    return $clean;
  }

  foreach ( array_keys( $defaults ) as $key ) {
    if ( $key === 'case_hero_slides' ) {
      $clean[ $key ] = dna_sanitize_attachment_ids(
        isset( $input[ $key ] ) && is_array( $input[ $key ] ) ? $input[ $key ] : []
      );
      continue;
    }
    $clean[ $key ] = absint( $input[ $key ] ?? 0 );
  }

  return $clean;
}

function dna_get_theme_images_config() {
  $defaults = dna_theme_images_defaults();
  $raw = get_option( dna_theme_images_option_name(), null );

  if ( $raw === null ) {
    add_option( dna_theme_images_option_name(), $defaults, '', false );
    return $defaults;
  }

  if ( ! is_array( $raw ) ) {
    update_option( dna_theme_images_option_name(), $defaults, false );
    return $defaults;
  }

  $config = dna_sanitize_theme_images( $raw );
  if ( $config !== $raw ) {
    update_option( dna_theme_images_option_name(), $config, false );
  }

  return $config;
}

function dna_get_theme_image_id( $slot ) {
  $config = dna_get_theme_images_config();
  $value = $config[ $slot ] ?? 0;
  if ( is_array( $value ) ) {
    return 0;
  }
  return absint( $value );
}

function dna_get_theme_image_ids( $slot ) {
  $config = dna_get_theme_images_config();
  if ( ! isset( $config[ $slot ] ) ) {
    return [];
  }
  return dna_sanitize_attachment_ids( is_array( $config[ $slot ] ) ? $config[ $slot ] : [] );
}

add_action( 'admin_init', function () {
  register_setting(
    'dna_theme_images_group',
    dna_theme_images_option_name(),
    [
      'type'              => 'array',
      'sanitize_callback' => 'dna_sanitize_theme_images',
      'default'           => dna_theme_images_defaults(),
    ]
  );
} );

add_action( 'admin_menu', function () {
  add_theme_page(
    __( 'DNA Images', 'dna' ),
    __( 'DNA Images', 'dna' ),
    'manage_options',
    'dna-theme-images',
    'dna_render_theme_images_admin_page'
  );
} );

add_action( 'admin_enqueue_scripts', function ( $hook_suffix ) {
  if ( ! in_array( $hook_suffix, [ 'appearance_page_dna-theme-images', 'theme_page_dna-theme-images' ], true ) ) {
    return;
  }

  wp_enqueue_media();
} );

function dna_render_theme_images_admin_page() {
  if ( ! current_user_can( 'manage_options' ) ) {
    return;
  }

  if ( function_exists( 'wp_enqueue_media' ) ) {
    wp_enqueue_media();
  }

  $option_name = dna_theme_images_option_name();
  $config = dna_get_theme_images_config();
  $case_hero_slide_ids = dna_sanitize_attachment_ids(
    isset( $config['case_hero_slides'] ) && is_array( $config['case_hero_slides'] ) ? $config['case_hero_slides'] : []
  );

  $slots = [
    'home_hero' => [
      'label' => __( 'Home Hero Image', 'dna' ),
      'desc'  => __( 'Homepage first-screen visual.', 'dna' ),
    ],
    'b2b_hero' => [
      'label' => __( 'B2B Hero Image', 'dna' ),
      'desc'  => __( 'B2B page top visual.', 'dna' ),
    ],
    'case_hero' => [
      'label' => __( 'Case Study Hero Image', 'dna' ),
      'desc'  => __( 'Case Study top visual.', 'dna' ),
    ],
    'shop_hero' => [
      'label' => __( 'Shop Hero Image', 'dna' ),
      'desc'  => __( 'Shop archive top visual.', 'dna' ),
    ],
    'line_hero' => [
      'label' => __( 'Line/Category Hero Image', 'dna' ),
      'desc'  => __( 'Line/category archive top visual.', 'dna' ),
    ],
  ];
  ?>
  <div class="wrap">
    <h1><?php esc_html_e( 'DNA Images', 'dna' ); ?></h1>
    <p><?php esc_html_e( 'Directly manage key front-end images. Unset slots fall back to placeholders automatically.', 'dna' ); ?></p>
    <p>
      <a class="button button-secondary" href="<?php echo esc_url( admin_url( 'themes.php?page=dna-b2b-pricing' ) ); ?>">
        <?php esc_html_e( 'Open Case Images (B2B Pricing)', 'dna' ); ?>
      </a>
    </p>

    <form method="post" action="options.php">
      <?php settings_fields( 'dna_theme_images_group' ); ?>
      <table class="widefat striped dna-theme-images-table">
        <thead>
          <tr>
            <th><?php esc_html_e( 'Slot', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Preview', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Attachment ID', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Actions', 'dna' ); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ( $slots as $slot_key => $slot_meta ) : ?>
            <?php
            $attachment_id = absint( $config[ $slot_key ] ?? 0 );
            $thumb = $attachment_id ? wp_get_attachment_image( $attachment_id, 'thumbnail', false, [ 'loading' => 'lazy', 'decoding' => 'async' ] ) : '';
            ?>
            <tr>
              <td>
                <strong><?php echo esc_html( $slot_meta['label'] ); ?></strong>
                <p class="description"><?php echo esc_html( $slot_meta['desc'] ); ?></p>
              </td>
              <td>
                <div class="dna-theme-image-preview" id="<?php echo esc_attr( 'dna-theme-image-preview-' . $slot_key ); ?>">
                  <?php if ( $thumb ) : ?>
                    <?php echo wp_kses_post( $thumb ); ?>
                  <?php else : ?>
                    <span><?php esc_html_e( 'No image selected', 'dna' ); ?></span>
                  <?php endif; ?>
                </div>
              </td>
              <td>
                <input
                  type="hidden"
                  id="<?php echo esc_attr( 'dna-theme-image-input-' . $slot_key ); ?>"
                  name="<?php echo esc_attr( $option_name . '[' . $slot_key . ']' ); ?>"
                  value="<?php echo esc_attr( (string) $attachment_id ); ?>"
                >
                <code id="<?php echo esc_attr( 'dna-theme-image-id-' . $slot_key ); ?>">
                  <?php echo $attachment_id > 0 ? esc_html( (string) $attachment_id ) : 'N/A'; ?>
                </code>
              </td>
              <td>
                <button
                  type="button"
                  class="button dna-theme-image-select"
                  data-slot="<?php echo esc_attr( $slot_key ); ?>"
                >
                  <?php echo $attachment_id > 0 ? esc_html__( 'Replace Image', 'dna' ) : esc_html__( 'Select Image', 'dna' ); ?>
                </button>
                <button
                  type="button"
                  class="button button-link-delete dna-theme-image-remove"
                  data-slot="<?php echo esc_attr( $slot_key ); ?>"
                  <?php disabled( $attachment_id < 1 ); ?>
                >
                  <?php esc_html_e( 'Remove', 'dna' ); ?>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <h2><?php esc_html_e( 'Case Study Hero Slides', 'dna' ); ?></h2>
      <p><?php esc_html_e( 'Add images for the /case-study hero carousel. Order controls the carousel sequence. If this list is empty, the page falls back to "Case Study Hero Image".', 'dna' ); ?></p>
      <ul id="dna-theme-case-hero-slides-list" class="dna-theme-case-slides-list">
        <?php foreach ( $case_hero_slide_ids as $attachment_id ) : ?>
          <?php
          $thumb_url = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
          $full_url = wp_get_attachment_url( $attachment_id );
          ?>
          <li class="dna-theme-case-slide-item" data-attachment-id="<?php echo esc_attr( (string) $attachment_id ); ?>">
            <input type="hidden" name="<?php echo esc_attr( $option_name . '[case_hero_slides][]' ); ?>" value="<?php echo esc_attr( (string) $attachment_id ); ?>">
            <div class="dna-theme-case-slide-thumb">
              <?php if ( $thumb_url ) : ?>
                <img src="<?php echo esc_url( $thumb_url ); ?>" alt="">
              <?php else : ?>
                <span><?php esc_html_e( 'No preview', 'dna' ); ?></span>
              <?php endif; ?>
            </div>
            <div class="dna-theme-case-slide-meta">
              <strong>#<?php echo esc_html( (string) $attachment_id ); ?></strong>
              <?php if ( $full_url ) : ?>
                <span><?php echo esc_html( wp_parse_url( $full_url, PHP_URL_PATH ) ?: '' ); ?></span>
              <?php endif; ?>
            </div>
            <div class="dna-theme-case-slide-actions">
              <button type="button" class="button button-small dna-theme-case-slide-up">↑</button>
              <button type="button" class="button button-small dna-theme-case-slide-down">↓</button>
              <button type="button" class="button button-small dna-theme-case-slide-remove"><?php esc_html_e( 'Remove', 'dna' ); ?></button>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
      <p>
        <button type="button" class="button" id="dna-theme-case-slides-add"><?php esc_html_e( 'Add Images', 'dna' ); ?></button>
      </p>

      <?php submit_button( __( 'Save Images', 'dna' ) ); ?>
    </form>
  </div>

  <style>
    .dna-theme-images-table th,
    .dna-theme-images-table td {
      vertical-align: middle;
    }
    .dna-theme-image-preview {
      width: 96px;
      height: 96px;
      border: 1px solid #dcdcde;
      background: #f6f7f7;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }
    .dna-theme-image-preview img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .dna-theme-image-preview span {
      font-size: 12px;
      color: #646970;
      text-align: center;
      padding: 0 8px;
      line-height: 1.3;
    }
    .dna-theme-images-table .description {
      margin-top: 6px;
    }
    .dna-theme-case-slides-list {
      margin: 16px 0 0;
      padding: 0;
      list-style: none;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 12px;
    }
    .dna-theme-case-slide-item {
      border: 1px solid #dcdcde;
      background: #fff;
      padding: 10px;
      display: grid;
      grid-template-columns: 76px minmax(0, 1fr) auto;
      gap: 10px;
      align-items: center;
    }
    .dna-theme-case-slide-thumb {
      width: 76px;
      height: 76px;
      border: 1px solid #dcdcde;
      background: #f6f7f7;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }
    .dna-theme-case-slide-thumb img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .dna-theme-case-slide-thumb span {
      font-size: 11px;
      color: #646970;
      text-align: center;
      line-height: 1.2;
      padding: 0 6px;
    }
    .dna-theme-case-slide-meta {
      min-width: 0;
      font-size: 13px;
      color: #1d2327;
      line-height: 1.45;
      display: grid;
      gap: 4px;
    }
    .dna-theme-case-slide-meta span {
      color: #646970;
      word-break: break-word;
    }
    .dna-theme-case-slide-actions {
      display: flex;
      gap: 6px;
      align-items: center;
    }
  </style>

  <script>
    (function () {
      const optionName = <?php echo wp_json_encode( $option_name ); ?>;

      function getSlotNodes(slot) {
        return {
          input: document.getElementById('dna-theme-image-input-' + slot),
          preview: document.getElementById('dna-theme-image-preview-' + slot),
          idLabel: document.getElementById('dna-theme-image-id-' + slot),
          removeBtn: document.querySelector('.dna-theme-image-remove[data-slot="' + slot + '"]'),
          selectBtn: document.querySelector('.dna-theme-image-select[data-slot="' + slot + '"]')
        };
      }

      function setSlotPreview(slot, attachmentId, imageUrl) {
        const nodes = getSlotNodes(slot);
        if (!nodes.input || !nodes.preview || !nodes.idLabel || !nodes.removeBtn || !nodes.selectBtn) {
          return;
        }

        nodes.input.value = attachmentId > 0 ? String(attachmentId) : '0';
        nodes.idLabel.textContent = attachmentId > 0 ? String(attachmentId) : 'N/A';

        if (attachmentId > 0 && imageUrl) {
          nodes.preview.innerHTML = '<img src="' + imageUrl + '" alt="">';
          nodes.removeBtn.disabled = false;
          nodes.selectBtn.textContent = 'Replace Image';
        } else {
          nodes.preview.innerHTML = '<span>No image selected</span>';
          nodes.removeBtn.disabled = true;
          nodes.selectBtn.textContent = 'Select Image';
        }
      }

      function toAttachmentId(value) {
        const parsed = parseInt(value, 10);
        if (!Number.isFinite(parsed) || parsed < 1) return 0;
        return parsed;
      }

      function hasCaseSlide(list, attachmentId) {
        return !!list.querySelector('[data-attachment-id="' + attachmentId + '"]');
      }

      function createCaseSlideActionButton(label, className) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'button button-small ' + className;
        button.textContent = label;
        return button;
      }

      function createCaseSlideItem(attachmentId, thumbUrl, fullUrl) {
        const item = document.createElement('li');
        item.className = 'dna-theme-case-slide-item';
        item.setAttribute('data-attachment-id', String(attachmentId));

        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = optionName + '[case_hero_slides][]';
        hidden.value = String(attachmentId);
        item.appendChild(hidden);

        const thumb = document.createElement('div');
        thumb.className = 'dna-theme-case-slide-thumb';
        if (thumbUrl) {
          const img = document.createElement('img');
          img.src = thumbUrl;
          img.alt = '';
          thumb.appendChild(img);
        } else {
          const span = document.createElement('span');
          span.textContent = 'No preview';
          thumb.appendChild(span);
        }
        item.appendChild(thumb);

        const meta = document.createElement('div');
        meta.className = 'dna-theme-case-slide-meta';
        const idStrong = document.createElement('strong');
        idStrong.textContent = '#' + String(attachmentId);
        meta.appendChild(idStrong);
        if (fullUrl) {
          const path = document.createElement('span');
          try {
            path.textContent = new URL(fullUrl, window.location.origin).pathname || fullUrl;
          } catch (error) {
            path.textContent = fullUrl;
          }
          meta.appendChild(path);
        }
        item.appendChild(meta);

        const actions = document.createElement('div');
        actions.className = 'dna-theme-case-slide-actions';
        actions.appendChild(createCaseSlideActionButton('↑', 'dna-theme-case-slide-up'));
        actions.appendChild(createCaseSlideActionButton('↓', 'dna-theme-case-slide-down'));
        actions.appendChild(createCaseSlideActionButton('Remove', 'dna-theme-case-slide-remove'));
        item.appendChild(actions);

        return item;
      }

      document.addEventListener('click', function (event) {
        const selectBtn = event.target.closest('.dna-theme-image-select');
        if (selectBtn) {
          const slot = selectBtn.getAttribute('data-slot');
          if (!slot) return;

          if (typeof wp === 'undefined' || !wp.media) {
            window.alert('Media Library failed to load. Please refresh and try again.');
            return;
          }

          const frame = wp.media({
            title: 'Select image',
            button: { text: 'Use this image' },
            multiple: false,
            library: { type: 'image' }
          });

          frame.on('select', function () {
            const selected = frame.state().get('selection').first();
            if (!selected) return;
            const data = selected.toJSON ? selected.toJSON() : {};
            const attachmentId = parseInt(data.id, 10) || 0;
            if (!attachmentId) return;

            let imageUrl = '';
            if (data.sizes && data.sizes.thumbnail && data.sizes.thumbnail.url) {
              imageUrl = data.sizes.thumbnail.url;
            } else if (data.url) {
              imageUrl = data.url;
            }

            setSlotPreview(slot, attachmentId, imageUrl);
          });

          frame.open();
          return;
        }

        const removeBtn = event.target.closest('.dna-theme-image-remove');
        if (removeBtn) {
          const slot = removeBtn.getAttribute('data-slot');
          if (!slot) return;
          setSlotPreview(slot, 0, '');
        }

        const caseSlidesList = document.getElementById('dna-theme-case-hero-slides-list');
        if (!caseSlidesList) return;

        const addSlidesBtn = event.target.closest('#dna-theme-case-slides-add');
        if (addSlidesBtn) {
          if (typeof wp === 'undefined' || !wp.media) {
            window.alert('Media Library failed to load. Please refresh and try again.');
            return;
          }

          const frame = wp.media({
            title: 'Select case hero slides',
            button: { text: 'Use selected images' },
            multiple: true,
            library: { type: 'image' }
          });

          frame.on('select', function () {
            const selection = frame.state().get('selection');
            if (!selection) return;

            selection.each(function (model) {
              const data = model && model.toJSON ? model.toJSON() : {};
              const attachmentId = toAttachmentId(data.id);
              if (!attachmentId || hasCaseSlide(caseSlidesList, attachmentId)) return;

              let thumbUrl = '';
              if (data.sizes && data.sizes.thumbnail && data.sizes.thumbnail.url) {
                thumbUrl = data.sizes.thumbnail.url;
              } else if (data.url) {
                thumbUrl = data.url;
              }
              const fullUrl = data.url || '';
              caseSlidesList.appendChild(createCaseSlideItem(attachmentId, thumbUrl, fullUrl));
            });
          });

          frame.open();
          return;
        }

        const removeCaseSlideBtn = event.target.closest('.dna-theme-case-slide-remove');
        if (removeCaseSlideBtn) {
          const item = removeCaseSlideBtn.closest('.dna-theme-case-slide-item');
          if (item) item.remove();
          return;
        }

        const upCaseSlideBtn = event.target.closest('.dna-theme-case-slide-up');
        if (upCaseSlideBtn) {
          const item = upCaseSlideBtn.closest('.dna-theme-case-slide-item');
          if (item && item.previousElementSibling) {
            caseSlidesList.insertBefore(item, item.previousElementSibling);
          }
          return;
        }

        const downCaseSlideBtn = event.target.closest('.dna-theme-case-slide-down');
        if (downCaseSlideBtn) {
          const item = downCaseSlideBtn.closest('.dna-theme-case-slide-item');
          if (item && item.nextElementSibling) {
            caseSlidesList.insertBefore(item.nextElementSibling, item);
          }
        }
      });
    })();
  </script>
  <?php
}

/**
 * Disable default WooCommerce CSS (we fully theme Shop/Product/Cart/Checkout).
 */
add_filter('woocommerce_enqueue_styles', '__return_empty_array');

/**
 * Lightweight Rank Math CSV updater.
 * Purpose: reliably write Rank Math product meta without paid import plugins.
 */
add_action('admin_menu', function () {
  add_management_page(
    __('DNA Rank Math Import', 'dna'),
    __('DNA Rank Math Import', 'dna'),
    'manage_woocommerce',
    'dna-rank-math-import',
    'dna_render_rank_math_import_admin_page'
  );
});

function dna_rank_math_import_normalize_header($header) {
  $header = preg_replace('/^\xEF\xBB\xBF/u', '', (string) $header);
  $header = strtolower(trim($header));
  return preg_replace('/\s+/', ' ', $header);
}

function dna_rank_math_import_detect_delimiter($file_path) {
  $handle = fopen($file_path, 'r');
  if (!$handle) {
    return ',';
  }

  $line = fgets($handle);
  fclose($handle);
  if (!is_string($line) || $line === '') {
    return ',';
  }

  $counts = [
    ',' => substr_count($line, ','),
    ';' => substr_count($line, ';'),
    "\t" => substr_count($line, "\t"),
  ];
  arsort($counts);
  $delimiter = (string) key($counts);
  return $delimiter === '' ? ',' : $delimiter;
}

function dna_rank_math_import_get_value(array $row, array $keys) {
  foreach ($keys as $key) {
    if (!array_key_exists($key, $row)) {
      continue;
    }
    return trim((string) $row[$key]);
  }
  return '';
}

function dna_rank_math_import_update_meta_pair($post_id, $private_key, $public_key, $value) {
  update_post_meta($post_id, $private_key, $value);
  update_post_meta($post_id, $public_key, $value);
}

function dna_rank_math_import_process_file($file_path) {
  $report = [
    'total_rows' => 0,
    'updated' => 0,
    'skipped' => 0,
    'failed' => 0,
    'rows' => [],
    'error' => '',
  ];

  if (!is_string($file_path) || $file_path === '' || !file_exists($file_path)) {
    $report['error'] = 'Uploaded file was not found.';
    return $report;
  }

  $delimiter = dna_rank_math_import_detect_delimiter($file_path);
  $handle = fopen($file_path, 'r');
  if (!$handle) {
    $report['error'] = 'Unable to open the CSV file.';
    return $report;
  }

  $headers = fgetcsv($handle, 0, $delimiter);
  if (!is_array($headers) || empty($headers)) {
    fclose($handle);
    $report['error'] = 'CSV header row is missing or invalid.';
    return $report;
  }

  $normalized_headers = [];
  foreach ($headers as $index => $header) {
    $normalized = dna_rank_math_import_normalize_header($header);
    if ($normalized === '') {
      $normalized = 'col_' . $index;
    }
    $normalized_headers[$index] = $normalized;
  }

  $title_keys = ['meta:_rank_math_title', '_rank_math_title'];
  $seo_description_keys = ['meta:_rank_math_description', '_rank_math_description'];
  $focus_keys = ['meta:_rank_math_focus_keyword', '_rank_math_focus_keyword'];
  $short_description_keys = ['short description', 'short_description', 'post_excerpt', 'excerpt'];
  $long_description_keys = ['description', 'long description', 'long_description', 'post_content', 'content'];
  $id_keys = ['id'];
  $sku_keys = ['sku'];

  $has_any_supported_column = false;
  foreach ($normalized_headers as $header) {
    if (
      in_array($header, $title_keys, true) ||
      in_array($header, $seo_description_keys, true) ||
      in_array($header, $focus_keys, true) ||
      in_array($header, $short_description_keys, true) ||
      in_array($header, $long_description_keys, true)
    ) {
      $has_any_supported_column = true;
      break;
    }
  }

  if (!$has_any_supported_column) {
    fclose($handle);
    $report['error'] = 'No supported columns found. Include at least one of: meta:_rank_math_title, meta:_rank_math_description, meta:_rank_math_focus_keyword, Short description, Description.';
    return $report;
  }

  $line_number = 1;
  while (($values = fgetcsv($handle, 0, $delimiter)) !== false) {
    $line_number++;
    if (!is_array($values)) {
      continue;
    }

    $row_assoc = [];
    foreach ($normalized_headers as $index => $header_key) {
      $row_assoc[$header_key] = array_key_exists($index, $values) ? $values[$index] : '';
    }

    $row_has_content = false;
    foreach ($row_assoc as $value) {
      if (trim((string) $value) !== '') {
        $row_has_content = true;
        break;
      }
    }
    if (!$row_has_content) {
      continue;
    }

    $report['total_rows']++;

    $id_value = dna_rank_math_import_get_value($row_assoc, $id_keys);
    $sku_value = dna_rank_math_import_get_value($row_assoc, $sku_keys);

    $product_id = absint($id_value);
    if ($product_id < 1 && $sku_value !== '' && function_exists('wc_get_product_id_by_sku')) {
      $product_id = absint(wc_get_product_id_by_sku($sku_value));
    }

    if ($product_id < 1) {
      $report['skipped']++;
      $report['rows'][] = [
        'line' => $line_number,
        'id' => '',
        'sku' => $sku_value,
        'status' => 'Skipped',
        'message' => 'Missing valid ID/SKU match.',
      ];
      continue;
    }

    if (get_post_type($product_id) !== 'product') {
      $report['skipped']++;
      $report['rows'][] = [
        'line' => $line_number,
        'id' => (string) $product_id,
        'sku' => $sku_value,
        'status' => 'Skipped',
        'message' => 'Matched post is not a product.',
      ];
      continue;
    }

    $updated_fields = [];
    $readback_notes = [];
    $title_value = dna_rank_math_import_get_value($row_assoc, $title_keys);
    $seo_description_value = dna_rank_math_import_get_value($row_assoc, $seo_description_keys);
    $focus_value = dna_rank_math_import_get_value($row_assoc, $focus_keys);
    $short_description_value = dna_rank_math_import_get_value($row_assoc, $short_description_keys);
    $long_description_value = dna_rank_math_import_get_value($row_assoc, $long_description_keys);

    if ($short_description_value !== '' || $long_description_value !== '') {
      $post_update_args = ['ID' => $product_id];

      if ($short_description_value !== '') {
        $post_update_args['post_excerpt'] = wp_kses_post($short_description_value);
        $updated_fields[] = 'post_excerpt(Short description)';
      }

      if ($long_description_value !== '') {
        $post_update_args['post_content'] = wp_kses_post($long_description_value);
        $updated_fields[] = 'post_content(Description)';
      }

      $update_result = wp_update_post($post_update_args, true);
      if (is_wp_error($update_result)) {
        $report['failed']++;
        $report['rows'][] = [
          'line' => $line_number,
          'id' => (string) $product_id,
          'sku' => $sku_value,
          'status' => 'Failed',
          'message' => 'Description update failed: ' . $update_result->get_error_message(),
        ];
        continue;
      }
    }

    if ($title_value !== '') {
      $clean_title = sanitize_text_field($title_value);
      dna_rank_math_import_update_meta_pair($product_id, '_rank_math_title', 'rank_math_title', $clean_title);
      $updated_fields[] = '_rank_math_title/rank_math_title';
    }
    if ($seo_description_value !== '') {
      $clean_description = sanitize_textarea_field($seo_description_value);
      dna_rank_math_import_update_meta_pair($product_id, '_rank_math_description', 'rank_math_description', $clean_description);
      $updated_fields[] = '_rank_math_description/rank_math_description';
    }
    if ($focus_value !== '') {
      $clean_focus = sanitize_text_field($focus_value);
      dna_rank_math_import_update_meta_pair($product_id, '_rank_math_focus_keyword', 'rank_math_focus_keyword', $clean_focus);
      $updated_fields[] = '_rank_math_focus_keyword/rank_math_focus_keyword';
    }

    if (empty($updated_fields)) {
      $report['skipped']++;
      $report['rows'][] = [
        'line' => $line_number,
        'id' => (string) $product_id,
        'sku' => $sku_value,
        'status' => 'Skipped',
        'message' => 'No non-empty supported values in this row.',
      ];
      continue;
    }

    clean_post_cache($product_id);
    wp_cache_delete($product_id, 'posts');
    do_action('rank_math/flush_cache');

    if ($title_value !== '') {
      $saved_private_title = trim((string) get_post_meta($product_id, '_rank_math_title', true));
      $saved_public_title = trim((string) get_post_meta($product_id, 'rank_math_title', true));
      $readback_notes[] = 'seo_title=' . ($saved_private_title !== '' || $saved_public_title !== '' ? 'ok' : 'empty');
    }

    if ($seo_description_value !== '') {
      $saved_private_description = trim((string) get_post_meta($product_id, '_rank_math_description', true));
      $saved_public_description = trim((string) get_post_meta($product_id, 'rank_math_description', true));
      $readback_notes[] = 'seo_description=' . ($saved_private_description !== '' || $saved_public_description !== '' ? 'ok' : 'empty');
    }

    if ($focus_value !== '') {
      $saved_private_focus = trim((string) get_post_meta($product_id, '_rank_math_focus_keyword', true));
      $saved_public_focus = trim((string) get_post_meta($product_id, 'rank_math_focus_keyword', true));
      $readback_notes[] = 'focus_keyword=' . ($saved_private_focus !== '' || $saved_public_focus !== '' ? 'ok' : 'empty');
    }

    if ($short_description_value !== '') {
      $saved_short_description = trim((string) get_post_field('post_excerpt', $product_id));
      $readback_notes[] = 'short_description=' . ($saved_short_description !== '' ? 'ok' : 'empty');
    }

    if ($long_description_value !== '') {
      $saved_long_description = trim((string) get_post_field('post_content', $product_id));
      $readback_notes[] = 'long_description=' . ($saved_long_description !== '' ? 'ok' : 'empty');
    }

    $report['updated']++;
    $report['rows'][] = [
      'line' => $line_number,
      'id' => (string) $product_id,
      'sku' => $sku_value,
      'status' => 'Updated',
      'message' => 'Updated: ' . implode(', ', $updated_fields) .
        (!empty($readback_notes) ? ' | Readback ' . implode(', ', $readback_notes) : ''),
    ];
  }

  fclose($handle);
  return $report;
}

function dna_render_rank_math_import_admin_page() {
  if (!current_user_can('manage_woocommerce')) {
    return;
  }

  $report = null;
  $error_message = '';

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dna_rank_math_import_submit'])) {
    check_admin_referer('dna_rank_math_import_action', 'dna_rank_math_import_nonce');

    if (!isset($_FILES['dna_rank_math_csv']) || !is_array($_FILES['dna_rank_math_csv'])) {
      $error_message = 'No CSV file was uploaded.';
    } else {
      $file = $_FILES['dna_rank_math_csv'];
      $upload_error = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;

      if ($upload_error !== UPLOAD_ERR_OK) {
        $error_message = 'Upload failed. Please try again.';
      } else {
        $tmp_path = isset($file['tmp_name']) ? (string) $file['tmp_name'] : '';
        $report = dna_rank_math_import_process_file($tmp_path);
        if (!empty($report['error'])) {
          $error_message = (string) $report['error'];
        }
      }
    }
  }
  ?>
  <div class="wrap">
    <h1><?php esc_html_e('DNA Rank Math Import', 'dna'); ?></h1>
    <p><?php esc_html_e('Upload a CSV to update product SEO fields and WooCommerce short/long descriptions by product ID (or SKU fallback).', 'dna'); ?></p>
    <p>
      <strong><?php esc_html_e('Supported CSV columns:', 'dna'); ?></strong>
      <code>ID</code>,
      <code>SKU</code>,
      <code>meta:_rank_math_title</code> or <code>_rank_math_title</code>,
      <code>meta:_rank_math_description</code> or <code>_rank_math_description</code>,
      <code>meta:_rank_math_focus_keyword</code> or <code>_rank_math_focus_keyword</code>,
      <code>Short description</code> (or <code>post_excerpt</code>),
      <code>Description</code> (or <code>post_content</code>).
    </p>

    <?php if ($error_message !== '') : ?>
      <div class="notice notice-error"><p><?php echo esc_html($error_message); ?></p></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <?php wp_nonce_field('dna_rank_math_import_action', 'dna_rank_math_import_nonce'); ?>
      <table class="form-table" role="presentation">
        <tr>
          <th scope="row"><label for="dna_rank_math_csv"><?php esc_html_e('CSV File', 'dna'); ?></label></th>
          <td>
            <input type="file" id="dna_rank_math_csv" name="dna_rank_math_csv" accept=".csv,text/csv" required>
          </td>
        </tr>
      </table>
      <p class="submit">
        <button type="submit" name="dna_rank_math_import_submit" class="button button-primary"><?php esc_html_e('Run Import', 'dna'); ?></button>
      </p>
    </form>

    <?php if (is_array($report)) : ?>
      <h2><?php esc_html_e('Import Summary', 'dna'); ?></h2>
      <ul>
        <li><?php echo esc_html('Rows processed: ' . (string) ($report['total_rows'] ?? 0)); ?></li>
        <li><?php echo esc_html('Updated: ' . (string) ($report['updated'] ?? 0)); ?></li>
        <li><?php echo esc_html('Skipped: ' . (string) ($report['skipped'] ?? 0)); ?></li>
        <li><?php echo esc_html('Failed: ' . (string) ($report['failed'] ?? 0)); ?></li>
      </ul>

      <h2><?php esc_html_e('Row Results', 'dna'); ?></h2>
      <div style="max-height:420px; overflow:auto; border:1px solid #dcdcde; background:#fff;">
        <table class="widefat striped">
          <thead>
            <tr>
              <th><?php esc_html_e('Line', 'dna'); ?></th>
              <th><?php esc_html_e('Product ID', 'dna'); ?></th>
              <th><?php esc_html_e('SKU', 'dna'); ?></th>
              <th><?php esc_html_e('Status', 'dna'); ?></th>
              <th><?php esc_html_e('Message', 'dna'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (($report['rows'] ?? []) as $row) : ?>
              <tr>
                <td><?php echo esc_html((string) ($row['line'] ?? '')); ?></td>
                <td><?php echo esc_html((string) ($row['id'] ?? '')); ?></td>
                <td><?php echo esc_html((string) ($row['sku'] ?? '')); ?></td>
                <td><?php echo esc_html((string) ($row['status'] ?? '')); ?></td>
                <td><?php echo esc_html((string) ($row['message'] ?? '')); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
  <?php
}

/**
 * Disable WordPress big image auto-scaling for future uploads.
 * This prevents generating `-scaled` files as the default "full" source.
 */
add_filter('big_image_size_threshold', '__return_false');

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

  if (is_page_template('page-b2b.php') || is_page('b2b')) {
    $b2b_css_rel = '/assets/css/b2b-wizard.css';
    $b2b_js_rel  = '/assets/js/b2b-wizard.js';
    $b2b_css_abs = $base . $b2b_css_rel;
    $b2b_js_abs  = $base . $b2b_js_rel;

    wp_enqueue_style('dna-b2b-wizard', $uri . $b2b_css_rel, ['dna-styles'], file_exists($b2b_css_abs) ? filemtime($b2b_css_abs) : null);
    wp_enqueue_script('dna-b2b-wizard', $uri . $b2b_js_rel, [], file_exists($b2b_js_abs) ? filemtime($b2b_js_abs) : null, true);

    wp_add_inline_script(
      'dna-b2b-wizard',
      'window.dnaB2BConfig = ' . wp_json_encode([
        'restUrl'      => esc_url_raw(rest_url('dna/v1/b2b-request')),
        'nonce'        => wp_create_nonce('dna_b2b_request'),
        'accept'       => dna_b2b_upload_accept_attr(),
        'maxFiles'     => dna_b2b_upload_max_files(),
        'maxFileSize'  => dna_b2b_upload_max_file_size(),
        'merchOptions' => dna_b2b_merch_options(),
        'catalogItems' => dna_b2b_catalog_items_for_frontend(),
        'shippingModes' => dna_b2b_shipping_modes_for_frontend(),
        'logoDesignTiers' => dna_b2b_logo_design_tiers_for_frontend(),
        'merchDesignTiers' => dna_b2b_merch_design_tiers_for_frontend(),
        'promoCodes' => dna_b2b_promo_codes_for_frontend(),
      ]) . ';',
      'before'
    );
  }
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

function dna_primary_menu_links() {
  $links = [
    [ 'label' => 'Shop', 'url' => home_url('/shop/') ],
    [ 'label' => 'B2B', 'url' => home_url('/b2b/') ],
  ];

  $cart_url = home_url('/cart/');
  if (class_exists('WooCommerce') && function_exists('wc_get_cart_url')) {
    $cart_url = wc_get_cart_url();
  }
  $links[] = [ 'label' => 'Cart', 'url' => $cart_url, 'cart' => true ];

  $account_url = home_url('/my-account/');
  if (class_exists('WooCommerce') && function_exists('wc_get_page_permalink')) {
    $wc_account_url = wc_get_page_permalink('myaccount');
    if (is_string($wc_account_url) && $wc_account_url !== '') {
      $account_url = $wc_account_url;
    }
  }
  $links[] = [ 'label' => 'My Account', 'url' => $account_url ];

  return $links;
}

function dna_render_primary_menu_items_html() {
  $html = '';
  foreach (dna_primary_menu_links() as $link) {
    $label = $link['label'];
    if (!empty($link['cart'])) {
      $label .= ' <span class="dna-cart-count">(' . dna_get_cart_count() . ')</span>';
    }
    $html .= '<li class="menu-item"><a href="' . esc_url($link['url']) . '">' . wp_kses_post($label) . '</a></li>';
  }
  return $html;
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

add_filter('wp_nav_menu_items', function ($items, $args) {
  if (!isset($args->theme_location) || $args->theme_location !== 'primary') {
    return $items;
  }
  return dna_render_primary_menu_items_html();
}, 20, 2);

add_filter('woocommerce_add_to_cart_fragments', function ($fragments) {
  $fragments['span.dna-cart-count'] = '<span class="dna-cart-count">(' . dna_get_cart_count() . ')</span>';
  return $fragments;
});

add_filter('privacy_policy_url', function ($url) {
  return home_url('/privacy-policy/');
});

function dna_contact_form_action_url() {
  return esc_url(admin_url('admin-post.php'));
}

function dna_contact_mail_recipient() {
  return 'hello@designnaesthetics.com';
}

function dna_handle_contact_submission() {
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    wp_safe_redirect(home_url('/contact/'));
    exit;
  }

  $origin = wp_get_referer();
  if (!$origin) {
    $origin = home_url('/contact/');
  }

  if (empty($_POST['dna_contact_nonce']) || !wp_verify_nonce(wp_unslash($_POST['dna_contact_nonce']), 'dna_contact_submit')) {
    wp_safe_redirect(add_query_arg('contact_status', 'nonce_error', $origin));
    exit;
  }

  $name = sanitize_text_field(wp_unslash($_POST['contact_name'] ?? ''));
  $email = sanitize_email(wp_unslash($_POST['contact_email'] ?? ''));
  $company = sanitize_text_field(wp_unslash($_POST['contact_company'] ?? ''));
  $message = sanitize_textarea_field(wp_unslash($_POST['contact_message'] ?? ''));

  if ($name === '' || !is_email($email) || $message === '') {
    wp_safe_redirect(add_query_arg('contact_status', 'validation_error', $origin));
    exit;
  }

  $subject = 'Contact Request: ' . $name;
  $lines = [
    'Name: ' . $name,
    'Email: ' . $email,
    'Company: ' . ($company !== '' ? $company : '-'),
    '',
    'Message:',
    $message,
    '',
    'Source: Website contact page',
  ];
  $headers = [ 'Reply-To: ' . $name . ' <' . $email . '>' ];

  $sent = wp_mail(dna_contact_mail_recipient(), $subject, implode("\n", $lines), $headers);
  wp_safe_redirect(add_query_arg('contact_status', $sent ? 'sent' : 'mail_error', $origin));
  exit;
}

add_action('admin_post_nopriv_dna_contact_submit', 'dna_handle_contact_submission');
add_action('admin_post_dna_contact_submit', 'dna_handle_contact_submission');

add_filter('wc_add_to_cart_message_html', function ($message, $products, $show_qty) {
  $cart_url = esc_url(wc_get_cart_url());
  $message = preg_replace('/<a[^>]*class="[^"]*wc-forward[^"]*"[^>]*>.*?<\/a>/i', '', $message);
  if (stripos($message, 'dna-cart-link') === false) {
    $message = preg_replace('/\byour cart\b/i', 'your <a class="dna-cart-link" href="' . $cart_url . '">cart</a>', $message, 1);
  }
  return trim($message);
}, 10, 3);

/**
 * Stock privacy + purchase guard:
 * - Hide stock messages from customers.
 * - Block purchasing when stock quantity is 0.
 */
function dna_product_has_zero_stock($product) {
  if (!$product || !is_a($product, 'WC_Product')) return false;

  if ($product->managing_stock()) {
    $qty = $product->get_stock_quantity();
    if ($qty !== null && (float) $qty <= 0) {
      return true;
    }
  }

  return $product->get_stock_status() === 'outofstock';
}

add_filter('woocommerce_get_stock_html', function ($html, $product) {
  return '';
}, 20, 2);

add_filter('woocommerce_get_availability_text', function ($text, $product) {
  return '';
}, 20, 2);

add_filter('woocommerce_available_variation', function ($data, $product, $variation) {
  if (isset($data['availability_html'])) $data['availability_html'] = '';
  if (isset($data['availability'])) $data['availability'] = '';
  return $data;
}, 20, 3);

add_filter('woocommerce_is_purchasable', function ($purchasable, $product) {
  if (dna_product_has_zero_stock($product)) {
    return false;
  }
  return $purchasable;
}, 20, 2);

add_filter('woocommerce_variation_is_purchasable', function ($purchasable, $product) {
  if (dna_product_has_zero_stock($product)) {
    return false;
  }
  return $purchasable;
}, 20, 2);

add_filter('woocommerce_add_to_cart_validation', function ($passed, $product_id, $quantity, $variation_id = 0) {
  if (!function_exists('wc_get_product')) return $passed;
  $target_id = $variation_id ? (int) $variation_id : (int) $product_id;
  $product = wc_get_product($target_id);
  if (!$product) return $passed;

  if (dna_product_has_zero_stock($product)) {
    wc_add_notice(__('This item is currently unavailable.', 'dna'), 'error');
    return false;
  }

  return $passed;
}, 20, 4);

function dna_single_add_to_cart_or_out_of_stock_button() {
  global $product;
  if (!$product || !is_a($product, 'WC_Product')) return;

  if (dna_product_has_zero_stock($product)) {
    echo '<button type="button" class="single_add_to_cart_button button alt disabled dna-out-of-stock-button" disabled aria-disabled="true">' . esc_html__('Out of stock', 'dna') . '</button>';
    return;
  }

  if (function_exists('woocommerce_template_single_add_to_cart')) {
    woocommerce_template_single_add_to_cart();
  }
}

add_action('init', function () {
  if (!class_exists('WooCommerce')) return;
  remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
  add_action('woocommerce_single_product_summary', 'dna_single_add_to_cart_or_out_of_stock_button', 30);
}, 20);

/**
 * Checkout: show free-shipping threshold hint below shipping rows when needed.
 */
function dna_checkout_discounted_subtotal() {
  if (!function_exists('WC')) return 0.0;
  $cart = WC()->cart;
  if (!$cart) return 0.0;

  // Discounted item subtotal (excluding shipping).
  $subtotal = (float) $cart->get_subtotal();
  $discount = (float) $cart->get_discount_total();
  return max(0.0, $subtotal - $discount);
}

function dna_should_show_free_shipping_threshold_note($threshold = 50.0) {
  if (!function_exists('WC')) return false;
  $cart = WC()->cart;
  if (!$cart || !$cart->needs_shipping()) return false;
  return dna_checkout_discounted_subtotal() < (float) $threshold;
}

add_action('woocommerce_review_order_after_shipping', function () {
  if (!dna_should_show_free_shipping_threshold_note(50.0)) return;
  echo '<tr class="dna-shipping-threshold-note"><th scope="row"></th><td>' . esc_html__('Free shipping on orders over $50', 'dna') . '</td></tr>';
});

/**
 * Checkout Block fallback:
 * In Store API contexts, append the threshold hint to the first shipping method label.
 */
add_filter('woocommerce_package_rates', function ($rates, $package) {
  if (!defined('REST_REQUEST') || !REST_REQUEST) return $rates;

  $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
  $is_store_api = strpos($request_uri, '/wc/store/') !== false || strpos($request_uri, 'rest_route=/wc/store/') !== false;
  if (!$is_store_api) return $rates;
  if (!dna_should_show_free_shipping_threshold_note(50.0)) return $rates;

  $note = 'Free shipping on orders over $50';
  foreach ($rates as $rate_id => $rate) {
    if (!$rate || !is_object($rate) || !method_exists($rate, 'get_label')) {
      continue;
    }

    $current_label = (string) $rate->get_label();
    if (strpos($current_label, $note) !== false) {
      break;
    }

    $updated_label = $current_label . ' - ' . $note;
    if (method_exists($rate, 'set_label')) {
      $rate->set_label($updated_label);
    } else {
      $rate->label = $updated_label;
    }
    $rates[$rate_id] = $rate;
    break;
  }

  return $rates;
}, 20, 2);

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
 * Header menu fallback — fixed minimalist navigation.
 */
function dna_fallback_menu($args = []) {
  $menu_class = isset($args['menu_class']) ? esc_attr($args['menu_class']) : 'nav-links';
  echo '<ul class="' . $menu_class . '">';
  echo dna_render_primary_menu_items_html();
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
    echo '<main id="primary" class="site-main dna-wc">';
    if (!(function_exists('is_product') && is_product())) {
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
    }
    echo '<section class="dna-wc-body"><div class="container">';
  }, 10);

  add_action('woocommerce_after_main_content', function () {
    if (is_shop() || is_product_category() || is_product_tag()) { return; }
    echo '</div></section></main>';
  }, 10);
});

/**
 * Remove breadcrumbs for a cleaner look.
 */
add_action('init', function () {
  remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
});

/**
 * Optional breadcrumbs on WooCommerce single product pages.
 * Keep WooCommerce core schema pipeline untouched.
 */
add_action('woocommerce_before_main_content', function () {
  if (!function_exists('is_product') || !is_product()) {
    return;
  }
  dna_render_rank_math_breadcrumbs();
}, 11);


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
 * Ensure single-product gallery images always have a meaningful alt fallback.
 */
add_filter('wp_get_attachment_image_attributes', function ($attr, $attachment) {
  if (!function_exists('is_product') || !is_product()) {
    return $attr;
  }

  $current_alt = isset($attr['alt']) ? trim((string) $attr['alt']) : '';
  if ($current_alt !== '') {
    return $attr;
  }

  global $product;
  $product_name = ($product instanceof WC_Product) ? trim((string) $product->get_name()) : '';
  $context = $product_name !== '' ? $product_name . ' product image' : 'Product image';
  $attr['alt'] = dna_attachment_alt_from_context((int) $attachment->ID, $context);

  return $attr;
}, 10, 2);

/**
 * Single-product editorial content below the buy box.
 * Uses existing Woo fields only: long description, attributes, taxonomy, and internal links.
 */
function dna_product_primary_category_term($product_id) {
  $terms = get_the_terms($product_id, 'product_cat');
  if (!is_array($terms) || empty($terms)) {
    return null;
  }

  foreach ($terms as $term) {
    if ($term instanceof WP_Term && $term->parent !== 0) {
      return $term;
    }
  }

  return ($terms[0] instanceof WP_Term) ? $terms[0] : null;
}

function dna_product_editorial_detail_items($product) {
  if (!($product instanceof WC_Product)) {
    return [];
  }

  $items = [];

  foreach ($product->get_attributes() as $attribute) {
    if (!is_object($attribute) || !method_exists($attribute, 'get_visible') || !$attribute->get_visible()) {
      continue;
    }

    $label = trim((string) wc_attribute_label($attribute->get_name()));
    if ($label === '') {
      continue;
    }

    if ($attribute->is_taxonomy()) {
      $values = wc_get_product_terms($product->get_id(), $attribute->get_name(), ['fields' => 'names']);
    } else {
      $values = $attribute->get_options();
    }

    $values = array_filter(array_map(static function ($value) {
      return trim(wp_strip_all_tags((string) $value));
    }, is_array($values) ? $values : []));

    if (empty($values)) {
      continue;
    }

    $items[] = [
      'label' => $label,
      'value' => implode(', ', $values),
    ];
  }

  $primary_term = dna_product_primary_category_term($product->get_id());
  if ($primary_term instanceof WP_Term) {
    $items[] = [
      'label' => 'Line',
      'value' => $primary_term->name,
    ];
  }

  return $items;
}

function dna_product_editorial_links($product_id) {
  $links = [];

  $primary_term = dna_product_primary_category_term($product_id);
  if ($primary_term instanceof WP_Term) {
    $primary_term_link = get_term_link($primary_term);
    if (!is_wp_error($primary_term_link) && is_string($primary_term_link) && $primary_term_link !== '') {
    $links[] = [
      'url' => $primary_term_link,
      'label' => 'Explore more from ' . $primary_term->name,
    ];
    }
  }

  $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '';
  if (is_string($shop_url) && $shop_url !== '') {
    $links[] = [
      'url' => $shop_url,
      'label' => 'Browse the full shop',
    ];
  }

  $b2b_page = get_page_by_path('b2b');
  if ($b2b_page instanceof WP_Post && $b2b_page->post_status === 'publish') {
    $links[] = [
      'url' => get_permalink($b2b_page),
      'label' => 'Start a custom merch project',
    ];
  }

  return array_values(array_filter($links, static function ($link) {
    return !empty($link['url']) && !empty($link['label']);
  }));
}

function dna_render_product_editorial_content() {
  if (!function_exists('is_product') || !is_product()) {
    return;
  }

  global $product;
  if (!($product instanceof WC_Product)) {
    return;
  }

  $description = trim((string) $product->get_description());
  $detail_items = dna_product_editorial_detail_items($product);
  $links = dna_product_editorial_links($product->get_id());

  if ($description === '' && empty($detail_items) && empty($links)) {
    return;
  }

  echo '<section class="dna-product-editorial" aria-label="Product details and internal links">';

  if ($description !== '') {
    echo '<section class="dna-product-editorial__section dna-product-editorial__section--about">';
    echo '<div class="dna-product-editorial__row">';
    echo '<div class="dna-product-editorial__rail">';
    echo '<h2 class="dna-product-editorial__title">About ' . esc_html($product->get_name()) . '</h2>';
    echo '</div>';
    echo '<div class="dna-product-editorial__panel">';
    echo '<div class="dna-product-editorial__content">';
    echo apply_filters('the_content', $description);
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</section>';
  }

  if (!empty($detail_items)) {
    echo '<section class="dna-product-editorial__section dna-product-editorial__section--details">';
    echo '<div class="dna-product-editorial__row">';
    echo '<div class="dna-product-editorial__rail">';
    echo '<h2 class="dna-product-editorial__title">Design / Material / Fit / Use</h2>';
    echo '</div>';
    echo '<div class="dna-product-editorial__panel">';
    echo '<dl class="dna-product-editorial__details">';

    foreach ($detail_items as $item) {
      echo '<div class="dna-product-editorial__detail">';
      echo '<dt>' . esc_html($item['label']) . '</dt>';
      echo '<dd>' . esc_html($item['value']) . '</dd>';
      echo '</div>';
    }

    echo '</dl>';
    echo '</div>';
    echo '</div>';
    echo '</section>';
  }

  if (!empty($links)) {
    echo '<section class="dna-product-editorial__section dna-product-editorial__section--notes">';
    echo '<div class="dna-product-editorial__row">';
    echo '<div class="dna-product-editorial__rail">';
    echo '<h2 class="dna-product-editorial__title">Shipping / Production Notes</h2>';
    echo '</div>';
    echo '<div class="dna-product-editorial__panel">';
    echo '<p class="dna-product-editorial__note">Lead time, delivery charges, and production scope are confirmed by order size and destination. Use the links below to continue in this line, review the full shop, or start a custom request.</p>';
    echo '<ul class="dna-product-editorial__links">';

    foreach ($links as $link) {
      echo '<li><a href="' . esc_url($link['url']) . '">' . esc_html($link['label']) . '</a></li>';
    }

    echo '</ul>';
    echo '</div>';
    echo '</div>';
    echo '</section>';
  }

  echo '</section>';
}

add_action('woocommerce_after_single_product_summary', 'dna_render_product_editorial_content', 5);

add_filter('woocommerce_product_tabs', function ($tabs) {
  if (!function_exists('is_product') || !is_product()) {
    return $tabs;
  }

  global $product;
  if ($product instanceof WC_Product && trim((string) $product->get_description()) !== '') {
    unset($tabs['description']);
  }

  return $tabs;
}, 20);

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
        'alt' => dna_attachment_alt_from_context($img_id, $p->get_name() . ' related product image'),
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
/**
 * B2B intake wizard.
 */
function dna_b2b_merch_options() {
  $options = [];
  $config = dna_b2b_get_pricing_config();

  foreach ( $config['catalog_items'] as $item ) {
    if ( empty( $item['enabled'] ) ) {
      continue;
    }

    if ( empty( $item['id'] ) || empty( $item['label'] ) ) {
      continue;
    }

    $options[ $item['id'] ] = $item['label'];
  }

  $options['other'] = 'Other merch';

  return $options;
}

function dna_b2b_pricing_option_name() {
  return 'dna_b2b_pricing_config';
}

function dna_b2b_pricing_defaults() {
  return [
    'catalog_items'  => [
      [ 'id' => 't_shirts', 'label' => 'T-shirts', 'unit_cost' => 4.0, 'unit_weight' => 0.25, 'enabled' => 1 ],
      [ 'id' => 'hoodies', 'label' => 'Hoodies', 'unit_cost' => 8.0, 'unit_weight' => 0.55, 'enabled' => 1 ],
      [ 'id' => 'caps', 'label' => 'Caps', 'unit_cost' => 3.5, 'unit_weight' => 0.18, 'enabled' => 1 ],
      [ 'id' => 'decor_magnets', 'label' => 'Decor magnets', 'unit_cost' => 1.2, 'unit_weight' => 0.08, 'enabled' => 1 ],
      [ 'id' => 'mugs', 'label' => 'Mugs', 'unit_cost' => 2.8, 'unit_weight' => 0.42, 'enabled' => 1 ],
      [ 'id' => 'canvas_bags', 'label' => 'Canvas bags', 'unit_cost' => 2.3, 'unit_weight' => 0.20, 'enabled' => 1 ],
      [ 'id' => 'keychains', 'label' => 'Keychains', 'unit_cost' => 1.4, 'unit_weight' => 0.05, 'enabled' => 1 ],
      [ 'id' => 'paper_bags', 'label' => 'Paper bags', 'unit_cost' => 0.6, 'unit_weight' => 0.06, 'enabled' => 1 ],
    ],
    'shipping_modes' => [
      [ 'id' => 'sea', 'label' => 'Sea', 'rate_per_kg' => 1.2, 'lead_days' => 30, 'enabled' => 1 ],
      [ 'id' => 'air', 'label' => 'Air', 'rate_per_kg' => 3.8, 'lead_days' => 10, 'enabled' => 1 ],
    ],
    'logo_design_tiers' => [
      [ 'id' => 'logo_execution', 'label' => 'Execution Layer', 'details' => 'Includes up to 5 revision rounds. Additional revisions are billed at the same hourly rate. Work follows predefined direction. Strategic repositioning is not included.', 'cost' => 30, 'days' => 2, 'enabled' => 1 ],
      [ 'id' => 'logo_system', 'label' => 'System Layer', 'details' => 'Includes 8-10 revision rounds depending on project scope. Adjustments within the defined system are supported. Direction resets outside approved scope are quoted separately.', 'cost' => 50, 'days' => 4, 'enabled' => 1 ],
      [ 'id' => 'logo_strategic', 'label' => 'Strategic Layer', 'details' => 'Iterative refinement without fixed revision limits (within approved scope). Direction evolves collaboratively until alignment is achieved.', 'cost' => 80, 'days' => 6, 'enabled' => 1 ],
    ],
    'merch_design_tiers' => [
      [ 'id' => 'merch_execution', 'label' => 'Execution Layer', 'details' => 'Includes up to 5 revision rounds. Additional revisions are billed at the same hourly rate. Work follows predefined direction. Strategic repositioning is not included.', 'cost' => 30, 'days' => 2, 'enabled' => 1 ],
      [ 'id' => 'merch_system', 'label' => 'System Layer', 'details' => 'Includes 8-10 revision rounds depending on project scope. Adjustments within the defined system are supported. Direction resets outside approved scope are quoted separately.', 'cost' => 50, 'days' => 3, 'enabled' => 1 ],
      [ 'id' => 'merch_strategic', 'label' => 'Strategic Layer', 'details' => 'Iterative refinement without fixed revision limits (within approved scope). Direction evolves collaboratively until alignment is achieved.', 'cost' => 80, 'days' => 4, 'enabled' => 1 ],
    ],
    'promo_codes' => [],
    'case_images' => [],
  ];
}

function dna_b2b_get_pricing_config() {
  $defaults = dna_b2b_pricing_defaults();
  $raw = get_option( dna_b2b_pricing_option_name(), null );

  if ( $raw === null ) {
    add_option( dna_b2b_pricing_option_name(), $defaults, '', false );
    return $defaults;
  }

  if ( ! is_array( $raw ) ) {
    update_option( dna_b2b_pricing_option_name(), $defaults, false );
    return $defaults;
  }

  $config = dna_b2b_sanitize_pricing_config( $raw );
  if ( ! isset( $config['catalog_items'] ) ) {
    $config['catalog_items'] = [];
  }
  if ( ! isset( $config['shipping_modes'] ) ) {
    $config['shipping_modes'] = [];
  }
  if ( ! isset( $config['logo_design_tiers'] ) ) {
    $config['logo_design_tiers'] = [];
  }
  if ( ! isset( $config['merch_design_tiers'] ) ) {
    $config['merch_design_tiers'] = [];
  }
  if ( ! isset( $config['promo_codes'] ) ) {
    $config['promo_codes'] = [];
  }
  if ( ! isset( $config['case_images'] ) ) {
    $config['case_images'] = [];
  }

  // Backfill newly introduced config sections for older saved options.
  $backfill = false;
  foreach ( [ 'logo_design_tiers', 'merch_design_tiers', 'promo_codes', 'case_images' ] as $key ) {
    if ( ! array_key_exists( $key, $raw ) ) {
      $config[ $key ] = $defaults[ $key ];
      $backfill = true;
    }
  }
  if ( $backfill ) {
    update_option( dna_b2b_pricing_option_name(), $config, false );
  }

  return $config;
}

function dna_b2b_catalog_items_index() {
  $index = [];
  $config = dna_b2b_get_pricing_config();
  foreach ( $config['catalog_items'] as $item ) {
    if ( empty( $item['enabled'] ) || empty( $item['id'] ) ) {
      continue;
    }
    $index[ $item['id'] ] = $item;
  }
  return $index;
}

function dna_b2b_catalog_items_for_frontend() {
  $items = [];
  foreach ( dna_b2b_catalog_items_index() as $item ) {
    $items[] = [
      'id'         => $item['id'],
      'label'      => $item['label'],
      'unitCost'   => (float) $item['unit_cost'],
      'unitWeight' => (float) $item['unit_weight'],
      'enabled'    => 1,
    ];
  }
  return $items;
}

function dna_b2b_shipping_modes_for_frontend() {
  $modes = [];
  $config = dna_b2b_get_pricing_config();
  foreach ( $config['shipping_modes'] as $mode ) {
    if ( empty( $mode['enabled'] ) || empty( $mode['id'] ) || empty( $mode['label'] ) ) {
      continue;
    }
    $modes[] = [
      'id'      => $mode['id'],
      'label'   => $mode['label'],
      'rate'    => (float) $mode['rate_per_kg'],
      'days'    => (int) $mode['lead_days'],
      'enabled' => 1,
    ];
  }
  return $modes;
}

function dna_b2b_design_tier_rows_for_frontend( $rows, $source ) {
  $tiers = [];

  if ( ! is_array( $rows ) ) {
    return $tiers;
  }

  foreach ( $rows as $row ) {
    if ( ! is_array( $row ) ) {
      continue;
    }
    if ( empty( $row['enabled'] ) || empty( $row['id'] ) || empty( $row['label'] ) ) {
      continue;
    }

    $tiers[] = [
      'id'         => (string) $row['id'],
      'label'      => (string) $row['label'],
      'details'    => isset( $row['details'] ) ? (string) $row['details'] : '',
      'cost'       => (float) ( $row['cost'] ?? 0 ),
      'hourlyRate' => (float) ( $row['cost'] ?? 0 ),
      'days'       => (int) ( $row['days'] ?? 0 ),
      'leadDays'   => (int) ( $row['days'] ?? 0 ),
      'enabled'    => 1,
      'source'     => (string) $source,
    ];
  }

  return $tiers;
}

function dna_b2b_logo_design_tiers_for_frontend() {
  $config = dna_b2b_get_pricing_config();
  return dna_b2b_design_tier_rows_for_frontend( $config['logo_design_tiers'] ?? [], 'logo' );
}

function dna_b2b_merch_design_tiers_for_frontend() {
  $config = dna_b2b_get_pricing_config();
  return dna_b2b_design_tier_rows_for_frontend( $config['merch_design_tiers'] ?? [], 'merch' );
}

function dna_b2b_promo_codes_for_frontend() {
  $config = dna_b2b_get_pricing_config();
  $promos = [];

  if ( empty( $config['promo_codes'] ) || ! is_array( $config['promo_codes'] ) ) {
    return $promos;
  }

  foreach ( $config['promo_codes'] as $promo ) {
    if ( ! is_array( $promo ) ) {
      continue;
    }

    if ( empty( $promo['enabled'] ) || empty( $promo['code'] ) ) {
      continue;
    }

    $discount_type = (string) ( $promo['discount_type'] ?? 'percent' );
    if ( ! in_array( $discount_type, [ 'percent', 'fixed' ], true ) ) {
      $discount_type = 'percent';
    }

    $promos[] = [
      'code'          => (string) $promo['code'],
      'discountType'  => $discount_type,
      'discountValue' => (float) ( $promo['discount_value'] ?? 0 ),
      'enabled'       => 1,
    ];
  }

  return $promos;
}

function dna_b2b_case_images_ids() {
  $config = dna_b2b_get_pricing_config();
  $ids = isset( $config['case_images'] ) && is_array( $config['case_images'] ) ? $config['case_images'] : [];
  return dna_b2b_sanitize_case_image_ids( $ids );
}

function dna_b2b_sanitize_design_tier_rows( $rows ) {
  $clean = [];
  $seen = [];

  if ( ! is_array( $rows ) ) {
    return $clean;
  }

  foreach ( $rows as $row ) {
    if ( ! is_array( $row ) ) {
      continue;
    }

    $label = isset( $row['label'] ) ? sanitize_text_field( wp_unslash( (string) $row['label'] ) ) : '';
    $id_raw = isset( $row['id'] ) ? (string) $row['id'] : '';
    $id = sanitize_key( wp_unslash( $id_raw ) );
    if ( $id === '' && $label !== '' ) {
      $id = sanitize_title( $label );
    }
    if ( $id === '' || isset( $seen[ $id ] ) ) {
      continue;
    }

    $seen[ $id ] = true;
    $clean[] = [
      'id'      => $id,
      'label'   => $label !== '' ? $label : $id,
      'details' => isset( $row['details'] ) ? sanitize_textarea_field( wp_unslash( (string) $row['details'] ) ) : '',
      'cost'    => dna_b2b_decimal_value( $row['cost'] ?? 0 ),
      'days'    => absint( $row['days'] ?? 0 ),
      'enabled' => empty( $row['enabled'] ) ? 0 : 1,
    ];
  }

  return $clean;
}

function dna_b2b_sanitize_promo_rows( $rows ) {
  $clean = [];
  $seen = [];

  if ( ! is_array( $rows ) ) {
    return $clean;
  }

  foreach ( $rows as $row ) {
    if ( ! is_array( $row ) ) {
      continue;
    }

    $code_raw = isset( $row['code'] ) ? wp_unslash( (string) $row['code'] ) : '';
    $code = strtoupper( trim( sanitize_text_field( $code_raw ) ) );
    $code = preg_replace( '/[^A-Z0-9_-]/', '', $code );
    if ( $code === '' || isset( $seen[ $code ] ) ) {
      continue;
    }

    $seen[ $code ] = true;
    $discount_type = sanitize_key( (string) ( $row['discount_type'] ?? 'percent' ) );
    if ( ! in_array( $discount_type, [ 'percent', 'fixed' ], true ) ) {
      $discount_type = 'percent';
    }

    $discount_value = dna_b2b_decimal_value( $row['discount_value'] ?? 0 );
    if ( $discount_type === 'percent' ) {
      $discount_value = min( 100.0, max( 0.0, $discount_value ) );
    }

    $clean[] = [
      'code'           => $code,
      'discount_type'  => $discount_type,
      'discount_value' => $discount_value,
      'enabled'        => empty( $row['enabled'] ) ? 0 : 1,
    ];
  }

  return $clean;
}

function dna_b2b_sanitize_case_image_ids( $ids ) {
  $clean = [];
  $seen = [];

  if ( ! is_array( $ids ) ) {
    return $clean;
  }

  foreach ( $ids as $id ) {
    $id = absint( $id );
    if ( $id < 1 || isset( $seen[ $id ] ) ) {
      continue;
    }

    $seen[ $id ] = true;
    $clean[] = $id;
  }

  return $clean;
}

function dna_b2b_sanitize_pricing_config( $input ) {
  $clean = [
    'catalog_items'  => [],
    'shipping_modes' => [],
    'logo_design_tiers' => [],
    'merch_design_tiers' => [],
    'promo_codes' => [],
    'case_images' => [],
  ];

  if ( ! is_array( $input ) ) {
    return $clean;
  }

  $catalog_rows = isset( $input['catalog_items'] ) && is_array( $input['catalog_items'] ) ? $input['catalog_items'] : [];
  $seen_catalog = [];
  foreach ( $catalog_rows as $row ) {
    if ( ! is_array( $row ) ) {
      continue;
    }

    $label = isset( $row['label'] ) ? sanitize_text_field( wp_unslash( (string) $row['label'] ) ) : '';
    $id_raw = isset( $row['id'] ) ? (string) $row['id'] : '';
    $id = sanitize_key( wp_unslash( $id_raw ) );
    if ( $id === '' && $label !== '' ) {
      $id = sanitize_title( $label );
    }
    if ( $id === '' || isset( $seen_catalog[ $id ] ) ) {
      continue;
    }

    $seen_catalog[ $id ] = true;
    $clean['catalog_items'][] = [
      'id'          => $id,
      'label'       => $label !== '' ? $label : $id,
      'unit_cost'   => dna_b2b_decimal_value( $row['unit_cost'] ?? 0 ),
      'unit_weight' => dna_b2b_decimal_value( $row['unit_weight'] ?? 0, 3 ),
      'enabled'     => empty( $row['enabled'] ) ? 0 : 1,
    ];
  }

  $shipping_rows = isset( $input['shipping_modes'] ) && is_array( $input['shipping_modes'] ) ? $input['shipping_modes'] : [];
  $seen_shipping = [];
  foreach ( $shipping_rows as $row ) {
    if ( ! is_array( $row ) ) {
      continue;
    }

    $label = isset( $row['label'] ) ? sanitize_text_field( wp_unslash( (string) $row['label'] ) ) : '';
    $id_raw = isset( $row['id'] ) ? (string) $row['id'] : '';
    $id = sanitize_key( wp_unslash( $id_raw ) );
    if ( $id === '' && $label !== '' ) {
      $id = sanitize_title( $label );
    }
    if ( $id === '' || isset( $seen_shipping[ $id ] ) ) {
      continue;
    }

    $seen_shipping[ $id ] = true;
    $clean['shipping_modes'][] = [
      'id'          => $id,
      'label'       => $label !== '' ? $label : $id,
      'rate_per_kg' => dna_b2b_decimal_value( $row['rate_per_kg'] ?? 0 ),
      'lead_days'   => absint( $row['lead_days'] ?? 0 ),
      'enabled'     => empty( $row['enabled'] ) ? 0 : 1,
    ];
  }

  $clean['logo_design_tiers'] = dna_b2b_sanitize_design_tier_rows(
    isset( $input['logo_design_tiers'] ) && is_array( $input['logo_design_tiers'] ) ? $input['logo_design_tiers'] : []
  );

  $clean['merch_design_tiers'] = dna_b2b_sanitize_design_tier_rows(
    isset( $input['merch_design_tiers'] ) && is_array( $input['merch_design_tiers'] ) ? $input['merch_design_tiers'] : []
  );

  $clean['promo_codes'] = dna_b2b_sanitize_promo_rows(
    isset( $input['promo_codes'] ) && is_array( $input['promo_codes'] ) ? $input['promo_codes'] : []
  );

  $clean['case_images'] = dna_b2b_sanitize_case_image_ids(
    isset( $input['case_images'] ) && is_array( $input['case_images'] ) ? $input['case_images'] : []
  );

  return $clean;
}

add_action( 'admin_init', function () {
  register_setting(
    'dna_b2b_pricing_group',
    dna_b2b_pricing_option_name(),
    [
      'type'              => 'array',
      'sanitize_callback' => 'dna_b2b_sanitize_pricing_config',
      'default'           => dna_b2b_pricing_defaults(),
    ]
  );
} );

add_action( 'admin_menu', function () {
  add_theme_page(
    __( 'DNA B2B Pricing', 'dna' ),
    __( 'DNA B2B Pricing', 'dna' ),
    'manage_options',
    'dna-b2b-pricing',
    'dna_b2b_render_pricing_admin_page'
  );
} );

add_action( 'admin_enqueue_scripts', function ( $hook_suffix ) {
  if ( ! in_array( $hook_suffix, [ 'appearance_page_dna-b2b-pricing', 'theme_page_dna-b2b-pricing' ], true ) ) {
    return;
  }

  wp_enqueue_media();
} );

function dna_b2b_render_pricing_admin_page() {
  if ( ! current_user_can( 'manage_options' ) ) {
    return;
  }

  if ( function_exists( 'wp_enqueue_media' ) ) {
    wp_enqueue_media();
  }

  $config = dna_b2b_get_pricing_config();
  $option_name = dna_b2b_pricing_option_name();
  ?>
  <div class="wrap">
    <h1><?php esc_html_e( 'DNA B2B Pricing', 'dna' ); ?></h1>
    <p><?php esc_html_e( 'Configure item pricing, shipping modes, hourly design tiers, and promo codes used by the B2B Wizard estimate.', 'dna' ); ?></p>

    <form method="post" action="options.php">
      <?php settings_fields( 'dna_b2b_pricing_group' ); ?>

      <h2><?php esc_html_e( 'Catalog Items', 'dna' ); ?></h2>
      <table class="widefat striped" id="dna-b2b-catalog-table">
        <thead>
          <tr>
            <th><?php esc_html_e( 'ID', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Label', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Unit Cost (USD)', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Unit Weight (kg)', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Enabled', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Order', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Action', 'dna' ); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ( $config['catalog_items'] as $row_key => $item ) : ?>
            <tr>
              <td><input type="text" class="regular-text" name="<?php echo esc_attr( $option_name . '[catalog_items][' . $row_key . '][id]' ); ?>" value="<?php echo esc_attr( $item['id'] ?? '' ); ?>"></td>
              <td><input type="text" class="regular-text" name="<?php echo esc_attr( $option_name . '[catalog_items][' . $row_key . '][label]' ); ?>" value="<?php echo esc_attr( $item['label'] ?? '' ); ?>"></td>
              <td><input type="number" step="0.01" min="0" name="<?php echo esc_attr( $option_name . '[catalog_items][' . $row_key . '][unit_cost]' ); ?>" value="<?php echo esc_attr( (string) ( $item['unit_cost'] ?? 0 ) ); ?>"></td>
              <td><input type="number" step="0.001" min="0" name="<?php echo esc_attr( $option_name . '[catalog_items][' . $row_key . '][unit_weight]' ); ?>" value="<?php echo esc_attr( (string) ( $item['unit_weight'] ?? 0 ) ); ?>"></td>
              <td>
                <input type="hidden" name="<?php echo esc_attr( $option_name . '[catalog_items][' . $row_key . '][enabled]' ); ?>" value="0">
                <label><input type="checkbox" name="<?php echo esc_attr( $option_name . '[catalog_items][' . $row_key . '][enabled]' ); ?>" value="1" <?php checked( ! empty( $item['enabled'] ) ); ?>> <?php esc_html_e( 'Enabled', 'dna' ); ?></label>
              </td>
              <td>
                <button type="button" class="button button-small dna-b2b-row-up">↑</button>
                <button type="button" class="button button-small dna-b2b-row-down">↓</button>
              </td>
              <td><button type="button" class="button button-small dna-b2b-row-remove"><?php esc_html_e( 'Remove', 'dna' ); ?></button></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p><button type="button" class="button" id="dna-b2b-add-catalog"><?php esc_html_e( 'Add Catalog Item', 'dna' ); ?></button></p>

      <h2><?php esc_html_e( 'Shipping Modes', 'dna' ); ?></h2>
      <table class="widefat striped" id="dna-b2b-shipping-table">
        <thead>
          <tr>
            <th><?php esc_html_e( 'ID', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Label', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Rate (USD/kg)', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Lead Days', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Enabled', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Order', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Action', 'dna' ); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ( $config['shipping_modes'] as $row_key => $mode ) : ?>
            <tr>
              <td><input type="text" class="regular-text" name="<?php echo esc_attr( $option_name . '[shipping_modes][' . $row_key . '][id]' ); ?>" value="<?php echo esc_attr( $mode['id'] ?? '' ); ?>"></td>
              <td><input type="text" class="regular-text" name="<?php echo esc_attr( $option_name . '[shipping_modes][' . $row_key . '][label]' ); ?>" value="<?php echo esc_attr( $mode['label'] ?? '' ); ?>"></td>
              <td><input type="number" step="0.01" min="0" name="<?php echo esc_attr( $option_name . '[shipping_modes][' . $row_key . '][rate_per_kg]' ); ?>" value="<?php echo esc_attr( (string) ( $mode['rate_per_kg'] ?? 0 ) ); ?>"></td>
              <td><input type="number" step="1" min="0" name="<?php echo esc_attr( $option_name . '[shipping_modes][' . $row_key . '][lead_days]' ); ?>" value="<?php echo esc_attr( (string) ( $mode['lead_days'] ?? 0 ) ); ?>"></td>
              <td>
                <input type="hidden" name="<?php echo esc_attr( $option_name . '[shipping_modes][' . $row_key . '][enabled]' ); ?>" value="0">
                <label><input type="checkbox" name="<?php echo esc_attr( $option_name . '[shipping_modes][' . $row_key . '][enabled]' ); ?>" value="1" <?php checked( ! empty( $mode['enabled'] ) ); ?>> <?php esc_html_e( 'Enabled', 'dna' ); ?></label>
              </td>
              <td>
                <button type="button" class="button button-small dna-b2b-row-up">↑</button>
                <button type="button" class="button button-small dna-b2b-row-down">↓</button>
              </td>
              <td><button type="button" class="button button-small dna-b2b-row-remove"><?php esc_html_e( 'Remove', 'dna' ); ?></button></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p><button type="button" class="button" id="dna-b2b-add-shipping"><?php esc_html_e( 'Add Shipping Mode', 'dna' ); ?></button></p>

      <h2><?php esc_html_e( 'Logo Design Hourly Tiers', 'dna' ); ?></h2>
      <p><?php esc_html_e( 'Logo estimate baseline: 6–12 effective working hours. Effective working hours include active project execution only (no idle/admin/social time).', 'dna' ); ?></p>
      <table class="widefat striped" id="dna-b2b-logo-design-table">
        <thead>
          <tr>
            <th><?php esc_html_e( 'ID', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Label', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Details', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Hourly Rate (USD/hr)', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Lead Days', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Enabled', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Order', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Action', 'dna' ); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ( $config['logo_design_tiers'] as $row_key => $tier ) : ?>
            <tr>
              <td><input type="text" class="regular-text" name="<?php echo esc_attr( $option_name . '[logo_design_tiers][' . $row_key . '][id]' ); ?>" value="<?php echo esc_attr( $tier['id'] ?? '' ); ?>"></td>
              <td><input type="text" class="regular-text" name="<?php echo esc_attr( $option_name . '[logo_design_tiers][' . $row_key . '][label]' ); ?>" value="<?php echo esc_attr( $tier['label'] ?? '' ); ?>"></td>
              <td><textarea rows="2" class="large-text" name="<?php echo esc_attr( $option_name . '[logo_design_tiers][' . $row_key . '][details]' ); ?>"><?php echo esc_textarea( $tier['details'] ?? '' ); ?></textarea></td>
              <td><input type="number" step="0.01" min="0" name="<?php echo esc_attr( $option_name . '[logo_design_tiers][' . $row_key . '][cost]' ); ?>" value="<?php echo esc_attr( (string) ( $tier['cost'] ?? 0 ) ); ?>"></td>
              <td><input type="number" step="1" min="0" name="<?php echo esc_attr( $option_name . '[logo_design_tiers][' . $row_key . '][days]' ); ?>" value="<?php echo esc_attr( (string) ( $tier['days'] ?? 0 ) ); ?>"></td>
              <td>
                <input type="hidden" name="<?php echo esc_attr( $option_name . '[logo_design_tiers][' . $row_key . '][enabled]' ); ?>" value="0">
                <label><input type="checkbox" name="<?php echo esc_attr( $option_name . '[logo_design_tiers][' . $row_key . '][enabled]' ); ?>" value="1" <?php checked( ! empty( $tier['enabled'] ) ); ?>> <?php esc_html_e( 'Enabled', 'dna' ); ?></label>
              </td>
              <td>
                <button type="button" class="button button-small dna-b2b-row-up">↑</button>
                <button type="button" class="button button-small dna-b2b-row-down">↓</button>
              </td>
              <td><button type="button" class="button button-small dna-b2b-row-remove"><?php esc_html_e( 'Remove', 'dna' ); ?></button></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p><button type="button" class="button" id="dna-b2b-add-logo-design"><?php esc_html_e( 'Add Logo Design Tier', 'dna' ); ?></button></p>

      <h2><?php esc_html_e( 'Merch Design Hourly Tiers', 'dna' ); ?></h2>
      <p><?php esc_html_e( 'Single merch design baseline: 1–3 effective working hours per item. Effective working hours include active project execution only (no idle/admin/social time).', 'dna' ); ?></p>
      <table class="widefat striped" id="dna-b2b-merch-design-table">
        <thead>
          <tr>
            <th><?php esc_html_e( 'ID', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Label', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Details', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Hourly Rate (USD/hr)', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Lead Days', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Enabled', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Order', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Action', 'dna' ); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ( $config['merch_design_tiers'] as $row_key => $tier ) : ?>
            <tr>
              <td><input type="text" class="regular-text" name="<?php echo esc_attr( $option_name . '[merch_design_tiers][' . $row_key . '][id]' ); ?>" value="<?php echo esc_attr( $tier['id'] ?? '' ); ?>"></td>
              <td><input type="text" class="regular-text" name="<?php echo esc_attr( $option_name . '[merch_design_tiers][' . $row_key . '][label]' ); ?>" value="<?php echo esc_attr( $tier['label'] ?? '' ); ?>"></td>
              <td><textarea rows="2" class="large-text" name="<?php echo esc_attr( $option_name . '[merch_design_tiers][' . $row_key . '][details]' ); ?>"><?php echo esc_textarea( $tier['details'] ?? '' ); ?></textarea></td>
              <td><input type="number" step="0.01" min="0" name="<?php echo esc_attr( $option_name . '[merch_design_tiers][' . $row_key . '][cost]' ); ?>" value="<?php echo esc_attr( (string) ( $tier['cost'] ?? 0 ) ); ?>"></td>
              <td><input type="number" step="1" min="0" name="<?php echo esc_attr( $option_name . '[merch_design_tiers][' . $row_key . '][days]' ); ?>" value="<?php echo esc_attr( (string) ( $tier['days'] ?? 0 ) ); ?>"></td>
              <td>
                <input type="hidden" name="<?php echo esc_attr( $option_name . '[merch_design_tiers][' . $row_key . '][enabled]' ); ?>" value="0">
                <label><input type="checkbox" name="<?php echo esc_attr( $option_name . '[merch_design_tiers][' . $row_key . '][enabled]' ); ?>" value="1" <?php checked( ! empty( $tier['enabled'] ) ); ?>> <?php esc_html_e( 'Enabled', 'dna' ); ?></label>
              </td>
              <td>
                <button type="button" class="button button-small dna-b2b-row-up">↑</button>
                <button type="button" class="button button-small dna-b2b-row-down">↓</button>
              </td>
              <td><button type="button" class="button button-small dna-b2b-row-remove"><?php esc_html_e( 'Remove', 'dna' ); ?></button></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p><button type="button" class="button" id="dna-b2b-add-merch-design"><?php esc_html_e( 'Add Merch Design Tier', 'dna' ); ?></button></p>

      <h2><?php esc_html_e( 'Promo Codes', 'dna' ); ?></h2>
      <table class="widefat striped" id="dna-b2b-promo-table">
        <thead>
          <tr>
            <th><?php esc_html_e( 'Code', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Discount Type', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Discount Value', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Enabled', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Order', 'dna' ); ?></th>
            <th><?php esc_html_e( 'Action', 'dna' ); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ( $config['promo_codes'] as $row_key => $promo ) : ?>
            <tr>
              <td><input type="text" class="regular-text" name="<?php echo esc_attr( $option_name . '[promo_codes][' . $row_key . '][code]' ); ?>" value="<?php echo esc_attr( $promo['code'] ?? '' ); ?>"></td>
              <td>
                <select name="<?php echo esc_attr( $option_name . '[promo_codes][' . $row_key . '][discount_type]' ); ?>">
                  <option value="percent" <?php selected( (string) ( $promo['discount_type'] ?? 'percent' ), 'percent' ); ?>><?php esc_html_e( 'Percent', 'dna' ); ?></option>
                  <option value="fixed" <?php selected( (string) ( $promo['discount_type'] ?? 'percent' ), 'fixed' ); ?>><?php esc_html_e( 'Fixed', 'dna' ); ?></option>
                </select>
              </td>
              <td><input type="number" step="0.01" min="0" name="<?php echo esc_attr( $option_name . '[promo_codes][' . $row_key . '][discount_value]' ); ?>" value="<?php echo esc_attr( (string) ( $promo['discount_value'] ?? 0 ) ); ?>"></td>
              <td>
                <input type="hidden" name="<?php echo esc_attr( $option_name . '[promo_codes][' . $row_key . '][enabled]' ); ?>" value="0">
                <label><input type="checkbox" name="<?php echo esc_attr( $option_name . '[promo_codes][' . $row_key . '][enabled]' ); ?>" value="1" <?php checked( ! empty( $promo['enabled'] ) ); ?>> <?php esc_html_e( 'Enabled', 'dna' ); ?></label>
              </td>
              <td>
                <button type="button" class="button button-small dna-b2b-row-up">↑</button>
                <button type="button" class="button button-small dna-b2b-row-down">↓</button>
              </td>
              <td><button type="button" class="button button-small dna-b2b-row-remove"><?php esc_html_e( 'Remove', 'dna' ); ?></button></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p><button type="button" class="button" id="dna-b2b-add-promo"><?php esc_html_e( 'Add Promo Code', 'dna' ); ?></button></p>

      <h2><?php esc_html_e( 'Case Images', 'dna' ); ?></h2>
      <p><?php esc_html_e( 'Select images from Media Library. B2B page shows the first 3 images, Case Study page shows all selected images.', 'dna' ); ?></p>
      <ul id="dna-b2b-case-images-list" class="dna-b2b-case-images-list">
        <?php
        $case_image_ids = isset( $config['case_images'] ) && is_array( $config['case_images'] ) ? $config['case_images'] : [];
        foreach ( $case_image_ids as $attachment_id ) :
          $attachment_id = absint( $attachment_id );
          if ( $attachment_id < 1 ) {
            continue;
          }
          $thumb = wp_get_attachment_image( $attachment_id, 'thumbnail', false, [ 'loading' => 'lazy', 'decoding' => 'async' ] );
          $title = get_the_title( $attachment_id );
          ?>
          <li class="dna-b2b-case-image-item" data-attachment-id="<?php echo esc_attr( (string) $attachment_id ); ?>">
            <input type="hidden" name="<?php echo esc_attr( $option_name . '[case_images][]' ); ?>" value="<?php echo esc_attr( (string) $attachment_id ); ?>">
            <div class="dna-b2b-case-image-thumb">
              <?php if ( $thumb ) : ?>
                <?php echo wp_kses_post( $thumb ); ?>
              <?php else : ?>
                <span><?php echo esc_html( '#' . (string) $attachment_id ); ?></span>
              <?php endif; ?>
            </div>
            <div class="dna-b2b-case-image-meta">
              <?php echo esc_html( $title ? $title : sprintf( 'Attachment #%d', $attachment_id ) ); ?>
            </div>
            <div class="dna-b2b-case-image-actions">
              <button type="button" class="button button-small dna-b2b-case-image-up">↑</button>
              <button type="button" class="button button-small dna-b2b-case-image-down">↓</button>
              <button type="button" class="button button-small dna-b2b-case-image-remove"><?php esc_html_e( 'Remove', 'dna' ); ?></button>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
      <p><button type="button" class="button" id="dna-b2b-add-case-images"><?php esc_html_e( 'Add Images', 'dna' ); ?></button></p>

      <?php submit_button( __( 'Save Pricing Config', 'dna' ) ); ?>
    </form>
  </div>
  <style>
    #dna-b2b-case-images-list {
      margin: 12px 0;
      padding: 0;
      display: grid;
      gap: 10px;
    }
    .dna-b2b-case-image-item {
      list-style: none;
      border: 1px solid #dcdcde;
      background: #fff;
      padding: 10px;
      display: grid;
      grid-template-columns: 72px minmax(0, 1fr) auto;
      gap: 12px;
      align-items: center;
    }
    .dna-b2b-case-image-thumb {
      width: 72px;
      height: 72px;
      border: 1px solid #dcdcde;
      background: #f6f7f7;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }
    .dna-b2b-case-image-thumb img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .dna-b2b-case-image-meta {
      font-size: 13px;
      line-height: 1.5;
      color: #1d2327;
      word-break: break-word;
    }
    .dna-b2b-case-image-actions {
      display: flex;
      gap: 6px;
      align-items: center;
    }
  </style>
  <script>
    (function () {
      const optionName = <?php echo wp_json_encode( $option_name ); ?>;
      let counter = Date.now();

      function addRow(tbody, type) {
        counter += 1;
        const key = 'new_' + counter;
        const tr = document.createElement('tr');

        if (type === 'catalog_items') {
          tr.innerHTML =
            '<td><input type="text" class="regular-text" name="' + optionName + '[catalog_items][' + key + '][id]"></td>' +
            '<td><input type="text" class="regular-text" name="' + optionName + '[catalog_items][' + key + '][label]"></td>' +
            '<td><input type="number" step="0.01" min="0" name="' + optionName + '[catalog_items][' + key + '][unit_cost]" value="0"></td>' +
            '<td><input type="number" step="0.001" min="0" name="' + optionName + '[catalog_items][' + key + '][unit_weight]" value="0"></td>' +
            '<td><input type="hidden" name="' + optionName + '[catalog_items][' + key + '][enabled]" value="0"><label><input type="checkbox" name="' + optionName + '[catalog_items][' + key + '][enabled]" value="1" checked> Enabled</label></td>' +
            '<td><button type="button" class="button button-small dna-b2b-row-up">↑</button> <button type="button" class="button button-small dna-b2b-row-down">↓</button></td>' +
            '<td><button type="button" class="button button-small dna-b2b-row-remove">Remove</button></td>';
        } else if (type === 'shipping_modes') {
          tr.innerHTML =
            '<td><input type="text" class="regular-text" name="' + optionName + '[shipping_modes][' + key + '][id]"></td>' +
            '<td><input type="text" class="regular-text" name="' + optionName + '[shipping_modes][' + key + '][label]"></td>' +
            '<td><input type="number" step="0.01" min="0" name="' + optionName + '[shipping_modes][' + key + '][rate_per_kg]" value="0"></td>' +
            '<td><input type="number" step="1" min="0" name="' + optionName + '[shipping_modes][' + key + '][lead_days]" value="0"></td>' +
            '<td><input type="hidden" name="' + optionName + '[shipping_modes][' + key + '][enabled]" value="0"><label><input type="checkbox" name="' + optionName + '[shipping_modes][' + key + '][enabled]" value="1" checked> Enabled</label></td>' +
            '<td><button type="button" class="button button-small dna-b2b-row-up">↑</button> <button type="button" class="button button-small dna-b2b-row-down">↓</button></td>' +
            '<td><button type="button" class="button button-small dna-b2b-row-remove">Remove</button></td>';
        } else if (type === 'logo_design_tiers') {
          tr.innerHTML =
            '<td><input type="text" class="regular-text" name="' + optionName + '[logo_design_tiers][' + key + '][id]"></td>' +
            '<td><input type="text" class="regular-text" name="' + optionName + '[logo_design_tiers][' + key + '][label]"></td>' +
            '<td><textarea rows="2" class="large-text" name="' + optionName + '[logo_design_tiers][' + key + '][details]"></textarea></td>' +
            '<td><input type="number" step="0.01" min="0" name="' + optionName + '[logo_design_tiers][' + key + '][cost]" value="0"></td>' +
            '<td><input type="number" step="1" min="0" name="' + optionName + '[logo_design_tiers][' + key + '][days]" value="0"></td>' +
            '<td><input type="hidden" name="' + optionName + '[logo_design_tiers][' + key + '][enabled]" value="0"><label><input type="checkbox" name="' + optionName + '[logo_design_tiers][' + key + '][enabled]" value="1" checked> Enabled</label></td>' +
            '<td><button type="button" class="button button-small dna-b2b-row-up">↑</button> <button type="button" class="button button-small dna-b2b-row-down">↓</button></td>' +
            '<td><button type="button" class="button button-small dna-b2b-row-remove">Remove</button></td>';
        } else if (type === 'merch_design_tiers') {
          tr.innerHTML =
            '<td><input type="text" class="regular-text" name="' + optionName + '[merch_design_tiers][' + key + '][id]"></td>' +
            '<td><input type="text" class="regular-text" name="' + optionName + '[merch_design_tiers][' + key + '][label]"></td>' +
            '<td><textarea rows="2" class="large-text" name="' + optionName + '[merch_design_tiers][' + key + '][details]"></textarea></td>' +
            '<td><input type="number" step="0.01" min="0" name="' + optionName + '[merch_design_tiers][' + key + '][cost]" value="0"></td>' +
            '<td><input type="number" step="1" min="0" name="' + optionName + '[merch_design_tiers][' + key + '][days]" value="0"></td>' +
            '<td><input type="hidden" name="' + optionName + '[merch_design_tiers][' + key + '][enabled]" value="0"><label><input type="checkbox" name="' + optionName + '[merch_design_tiers][' + key + '][enabled]" value="1" checked> Enabled</label></td>' +
            '<td><button type="button" class="button button-small dna-b2b-row-up">↑</button> <button type="button" class="button button-small dna-b2b-row-down">↓</button></td>' +
            '<td><button type="button" class="button button-small dna-b2b-row-remove">Remove</button></td>';
        } else if (type === 'promo_codes') {
          tr.innerHTML =
            '<td><input type="text" class="regular-text" name="' + optionName + '[promo_codes][' + key + '][code]"></td>' +
            '<td><select name="' + optionName + '[promo_codes][' + key + '][discount_type]"><option value="percent">Percent</option><option value="fixed">Fixed</option></select></td>' +
            '<td><input type="number" step="0.01" min="0" name="' + optionName + '[promo_codes][' + key + '][discount_value]" value="0"></td>' +
            '<td><input type="hidden" name="' + optionName + '[promo_codes][' + key + '][enabled]" value="0"><label><input type="checkbox" name="' + optionName + '[promo_codes][' + key + '][enabled]" value="1" checked> Enabled</label></td>' +
            '<td><button type="button" class="button button-small dna-b2b-row-up">↑</button> <button type="button" class="button button-small dna-b2b-row-down">↓</button></td>' +
            '<td><button type="button" class="button button-small dna-b2b-row-remove">Remove</button></td>';
        }

        tbody.appendChild(tr);
      }

      function toAttachmentId(value) {
        const parsed = parseInt(value, 10);
        if (!Number.isFinite(parsed) || parsed < 1) return 0;
        return parsed;
      }

      function hasCaseImage(list, attachmentId) {
        return !!list.querySelector('[data-attachment-id="' + attachmentId + '"]');
      }

      function createCaseActionButton(label, className) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'button button-small ' + className;
        button.textContent = label;
        return button;
      }

      function createCaseImageItem(attachmentId, thumbUrl, title) {
        const item = document.createElement('li');
        item.className = 'dna-b2b-case-image-item';
        item.setAttribute('data-attachment-id', String(attachmentId));

        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = optionName + '[case_images][]';
        hiddenInput.value = String(attachmentId);
        item.appendChild(hiddenInput);

        const thumb = document.createElement('div');
        thumb.className = 'dna-b2b-case-image-thumb';
        if (thumbUrl) {
          const image = document.createElement('img');
          image.src = thumbUrl;
          image.alt = '';
          thumb.appendChild(image);
        } else {
          const fallback = document.createElement('span');
          fallback.textContent = '#' + attachmentId;
          thumb.appendChild(fallback);
        }
        item.appendChild(thumb);

        const meta = document.createElement('div');
        meta.className = 'dna-b2b-case-image-meta';
        meta.textContent = title && String(title).trim() ? title : ('Attachment #' + attachmentId);
        item.appendChild(meta);

        const actions = document.createElement('div');
        actions.className = 'dna-b2b-case-image-actions';
        actions.appendChild(createCaseActionButton('↑', 'dna-b2b-case-image-up'));
        actions.appendChild(createCaseActionButton('↓', 'dna-b2b-case-image-down'));
        actions.appendChild(createCaseActionButton('Remove', 'dna-b2b-case-image-remove'));
        item.appendChild(actions);

        return item;
      }

      function bindTable(table) {
        if (!table) return;
        table.addEventListener('click', function (event) {
          const removeBtn = event.target.closest('.dna-b2b-row-remove');
          if (removeBtn) {
            const row = removeBtn.closest('tr');
            if (row) row.remove();
            return;
          }
          const upBtn = event.target.closest('.dna-b2b-row-up');
          if (upBtn) {
            const row = upBtn.closest('tr');
            if (row && row.previousElementSibling) {
              row.parentNode.insertBefore(row, row.previousElementSibling);
            }
            return;
          }
          const downBtn = event.target.closest('.dna-b2b-row-down');
          if (downBtn) {
            const row = downBtn.closest('tr');
            if (row && row.nextElementSibling) {
              row.parentNode.insertBefore(row.nextElementSibling, row);
            }
          }
        });
      }

      const catalogTable = document.getElementById('dna-b2b-catalog-table');
      const shippingTable = document.getElementById('dna-b2b-shipping-table');
      const logoDesignTable = document.getElementById('dna-b2b-logo-design-table');
      const merchDesignTable = document.getElementById('dna-b2b-merch-design-table');
      const promoTable = document.getElementById('dna-b2b-promo-table');
      const caseImagesList = document.getElementById('dna-b2b-case-images-list');
      bindTable(catalogTable);
      bindTable(shippingTable);
      bindTable(logoDesignTable);
      bindTable(merchDesignTable);
      bindTable(promoTable);

      const addCatalog = document.getElementById('dna-b2b-add-catalog');
      if (addCatalog && catalogTable) {
        addCatalog.addEventListener('click', function () {
          addRow(catalogTable.querySelector('tbody'), 'catalog_items');
        });
      }

      const addShipping = document.getElementById('dna-b2b-add-shipping');
      if (addShipping && shippingTable) {
        addShipping.addEventListener('click', function () {
          addRow(shippingTable.querySelector('tbody'), 'shipping_modes');
        });
      }

      const addLogoDesign = document.getElementById('dna-b2b-add-logo-design');
      if (addLogoDesign && logoDesignTable) {
        addLogoDesign.addEventListener('click', function () {
          addRow(logoDesignTable.querySelector('tbody'), 'logo_design_tiers');
        });
      }

      const addMerchDesign = document.getElementById('dna-b2b-add-merch-design');
      if (addMerchDesign && merchDesignTable) {
        addMerchDesign.addEventListener('click', function () {
          addRow(merchDesignTable.querySelector('tbody'), 'merch_design_tiers');
        });
      }

      const addPromo = document.getElementById('dna-b2b-add-promo');
      if (addPromo && promoTable) {
        addPromo.addEventListener('click', function () {
          addRow(promoTable.querySelector('tbody'), 'promo_codes');
        });
      }

      if (caseImagesList) {
        caseImagesList.addEventListener('click', function (event) {
          const removeBtn = event.target.closest('.dna-b2b-case-image-remove');
          if (removeBtn) {
            const item = removeBtn.closest('.dna-b2b-case-image-item');
            if (item) item.remove();
            return;
          }

          const upBtn = event.target.closest('.dna-b2b-case-image-up');
          if (upBtn) {
            const item = upBtn.closest('.dna-b2b-case-image-item');
            if (item && item.previousElementSibling) {
              item.parentNode.insertBefore(item, item.previousElementSibling);
            }
            return;
          }

          const downBtn = event.target.closest('.dna-b2b-case-image-down');
          if (downBtn) {
            const item = downBtn.closest('.dna-b2b-case-image-item');
            if (item && item.nextElementSibling) {
              item.parentNode.insertBefore(item.nextElementSibling, item);
            }
          }
        });
      }

      const addCaseImages = document.getElementById('dna-b2b-add-case-images');
      if (addCaseImages && caseImagesList) {
        addCaseImages.addEventListener('click', function () {
          if (typeof wp === 'undefined' || !wp.media) {
            window.alert('Media Library failed to load. Please refresh this page and try again.');
            return;
          }

          const frame = wp.media({
            title: 'Select Case Images',
            button: { text: 'Use Selected Images' },
            multiple: true,
            library: { type: 'image' }
          });

          frame.on('select', function () {
            const selection = frame.state().get('selection');
            selection.each(function (attachment) {
              const data = attachment.toJSON ? attachment.toJSON() : {};
              const attachmentId = toAttachmentId(data.id);
              if (!attachmentId || hasCaseImage(caseImagesList, attachmentId)) {
                return;
              }

              let thumbUrl = '';
              if (data.sizes && data.sizes.thumbnail && data.sizes.thumbnail.url) {
                thumbUrl = data.sizes.thumbnail.url;
              } else if (data.url) {
                thumbUrl = data.url;
              }

              caseImagesList.appendChild(
                createCaseImageItem(attachmentId, thumbUrl, data.title || '')
              );
            });
          });

          frame.open();
        });
      }
    })();
  </script>
  <?php
}

function dna_b2b_upload_max_files() {
  return 5;
}

function dna_b2b_upload_max_file_size() {
  return 10 * MB_IN_BYTES;
}

function dna_b2b_upload_accept_attr() {
  return '.pdf,.png,.jpg,.jpeg,.webp,.zip';
}

function dna_b2b_allowed_upload_mimes() {
  return [
    'pdf'          => 'application/pdf',
    'png'          => 'image/png',
    'jpg|jpeg|jpe' => 'image/jpeg',
    'webp'         => 'image/webp',
    'zip'          => 'application/zip',
  ];
}

function dna_b2b_request_type_label( $value ) {
  if ( $value === 'logo' ) {
    return 'Logo design';
  }

  if ( $value === 'merchandise' ) {
    return 'Merchandise';
  }

  return '';
}

function dna_b2b_logo_mode_label( $value ) {
  if ( $value === 'has_sketch' ) {
    return 'Preliminary sketch provided';
  }

  if ( $value === 'from_scratch' ) {
    return 'Start from scratch';
  }

  return '';
}

function dna_b2b_merch_service_label( $value ) {
  $labels = [
    'production_only'       => 'Production Only',
    'design_and_production' => 'Design + Production',
    'design_only'           => 'Design Only',
  ];

  return $labels[ $value ] ?? '';
}

function dna_b2b_parse_list_field( $value ) {
  if ( is_array( $value ) ) {
    return $value;
  }

  if ( ! is_string( $value ) || $value === '' ) {
    return [];
  }

  $decoded = json_decode( wp_unslash( $value ), true );
  if ( is_array( $decoded ) ) {
    return $decoded;
  }

  return array_filter( array_map( 'trim', explode( ',', wp_unslash( $value ) ) ) );
}

function dna_b2b_parse_map_field( $value ) {
  if ( is_array( $value ) ) {
    return $value;
  }

  if ( ! is_string( $value ) || $value === '' ) {
    return [];
  }

  $decoded = json_decode( wp_unslash( $value ), true );
  return is_array( $decoded ) ? $decoded : [];
}

function dna_b2b_decimal_value( $value, $precision = 2 ) {
  if ( is_string( $value ) ) {
    $value = str_replace( ',', '', $value );
  }

  $number = is_numeric( $value ) ? (float) $value : 0.0;
  if ( $number < 0 ) {
    $number = 0.0;
  }

  return round( $number, $precision );
}

function dna_b2b_normalize_estimate_items( $items ) {
  if ( ! is_array( $items ) ) {
    return [];
  }

  $normalized = [];
  foreach ( $items as $item ) {
    if ( ! is_array( $item ) ) {
      continue;
    }

    $id = sanitize_key( wp_unslash( (string) ( $item['id'] ?? ( $item['item_id'] ?? '' ) ) ) );
    $label = sanitize_text_field( wp_unslash( (string) ( $item['label'] ?? '' ) ) );
    if ( $id === '' && $label === '' ) {
      continue;
    }

    $normalized[] = [
      'id'              => $id,
      'label'           => $label !== '' ? $label : $id,
      'qty'             => absint( $item['qty'] ?? ( $item['quantity'] ?? 0 ) ),
      'unit_cost'       => dna_b2b_decimal_value( $item['unit_cost'] ?? ( $item['unitCost'] ?? 0 ) ),
      'unit_weight'     => dna_b2b_decimal_value( $item['unit_weight'] ?? ( $item['unitWeight'] ?? 0 ), 3 ),
      'line_production' => dna_b2b_decimal_value( $item['line_production'] ?? ( $item['lineProduction'] ?? 0 ) ),
      'line_weight'     => dna_b2b_decimal_value( $item['line_weight'] ?? ( $item['lineWeight'] ?? 0 ), 3 ),
      'auto_priced'     => empty( $item['auto_priced'] ) && empty( $item['autoPriced'] ) ? 0 : 1,
    ];
  }

  return $normalized;
}

function dna_b2b_normalize_estimate_shipping( $shipping ) {
  if ( ! is_array( $shipping ) ) {
    $shipping = [];
  }

  $id = sanitize_key( wp_unslash( (string) ( $shipping['id'] ?? ( $shipping['shipping_id'] ?? '' ) ) ) );
  $label = sanitize_text_field( wp_unslash( (string) ( $shipping['label'] ?? '' ) ) );

  return [
    'id'    => $id,
    'label' => $label !== '' ? $label : $id,
    'rate'  => dna_b2b_decimal_value( $shipping['rate'] ?? ( $shipping['shipping_rate'] ?? 0 ) ),
    'days'  => absint( $shipping['days'] ?? ( $shipping['shipping_days'] ?? 0 ) ),
  ];
}

function dna_b2b_normalize_estimate_design_package( $design_package ) {
  if ( ! is_array( $design_package ) ) {
    $design_package = [];
  }

  $source = sanitize_key( (string) ( $design_package['source'] ?? '' ) );
  if ( ! in_array( $source, [ 'logo', 'merch', 'none' ], true ) ) {
    $source = 'none';
  }

  return [
    'id'              => sanitize_key( wp_unslash( (string) ( $design_package['id'] ?? '' ) ) ),
    'label'           => sanitize_text_field( wp_unslash( (string) ( $design_package['label'] ?? '' ) ) ),
    'details'         => sanitize_textarea_field( wp_unslash( (string) ( $design_package['details'] ?? '' ) ) ),
    'cost'            => dna_b2b_decimal_value( $design_package['cost'] ?? 0 ),
    'days'            => absint( $design_package['days'] ?? 0 ),
    'hourly_rate'     => dna_b2b_decimal_value( $design_package['hourly_rate'] ?? ( $design_package['hourlyRate'] ?? 0 ) ),
    'effective_hours' => dna_b2b_decimal_value( $design_package['effective_hours'] ?? ( $design_package['effectiveHours'] ?? 0 ), 1 ),
    'source'          => $source,
  ];
}

function dna_b2b_normalize_estimate_promo( $promo ) {
  if ( ! is_array( $promo ) ) {
    $promo = [];
  }

  $code_raw = wp_unslash( (string) ( $promo['code'] ?? '' ) );
  $code = strtoupper( trim( sanitize_text_field( $code_raw ) ) );
  $code = preg_replace( '/[^A-Z0-9_-]/', '', $code );

  $type = sanitize_key( (string) ( $promo['type'] ?? ( $promo['discount_type'] ?? '' ) ) );
  if ( ! in_array( $type, [ 'percent', 'fixed' ], true ) ) {
    $type = '';
  }

  return [
    'code'            => $code,
    'type'            => $type,
    'value'           => dna_b2b_decimal_value( $promo['value'] ?? ( $promo['discount_value'] ?? 0 ) ),
    'discount_amount' => dna_b2b_decimal_value( $promo['discount_amount'] ?? ( $promo['discountAmount'] ?? 0 ) ),
  ];
}

function dna_b2b_normalize_estimate( $estimate ) {
  if ( ! is_array( $estimate ) ) {
    $estimate = [];
  }

  $design_option = $estimate['design_option'] ?? ( $estimate['designOption'] ?? '' );
  $shipping_mode = $estimate['shipping_mode'] ?? ( $estimate['shippingMode'] ?? '' );
  $items = dna_b2b_normalize_estimate_items( dna_b2b_parse_list_field( $estimate['items'] ?? [] ) );
  $shipping = dna_b2b_normalize_estimate_shipping( dna_b2b_parse_map_field( $estimate['shipping'] ?? [] ) );
  $design_package = dna_b2b_normalize_estimate_design_package( dna_b2b_parse_map_field( $estimate['design_package'] ?? [] ) );
  $promo = dna_b2b_normalize_estimate_promo( dna_b2b_parse_map_field( $estimate['promo'] ?? [] ) );
  $discount = dna_b2b_decimal_value( $estimate['discount'] ?? ( $estimate['discount_amount'] ?? ( $promo['discount_amount'] ?? 0 ) ) );

  return [
    'design_option'   => sanitize_key( wp_unslash( (string) $design_option ) ),
    'shipping_mode'   => sanitize_key( wp_unslash( (string) $shipping_mode ) ),
    'design_cost'     => dna_b2b_decimal_value( $estimate['design_cost'] ?? ( $estimate['designCost'] ?? 0 ) ),
    'design_hourly_rate' => dna_b2b_decimal_value( $estimate['design_hourly_rate'] ?? ( $estimate['designHourlyRate'] ?? 0 ) ),
    'design_effective_hours' => dna_b2b_decimal_value( $estimate['design_effective_hours'] ?? ( $estimate['designEffectiveHours'] ?? 0 ), 1 ),
    'design_days'     => absint( $estimate['design_days'] ?? ( $estimate['designDays'] ?? 0 ) ),
    'quantity'        => absint( $estimate['quantity'] ?? 0 ),
    'unit_cost'       => dna_b2b_decimal_value( $estimate['unit_cost'] ?? ( $estimate['unitCost'] ?? 0 ) ),
    'unit_weight'     => dna_b2b_decimal_value( $estimate['unit_weight'] ?? ( $estimate['unitWeight'] ?? 0 ), 3 ),
    'production_days' => absint( $estimate['production_days'] ?? ( $estimate['productionDays'] ?? 0 ) ),
    'shipping_rate'   => dna_b2b_decimal_value( $estimate['shipping_rate'] ?? ( $estimate['shippingRate'] ?? 0 ) ),
    'shipping_days'   => absint( $estimate['shipping_days'] ?? ( $estimate['shippingDays'] ?? 0 ) ),
    'production'      => dna_b2b_decimal_value( $estimate['production'] ?? 0 ),
    'logistics'       => dna_b2b_decimal_value( $estimate['logistics'] ?? 0 ),
    'margin'          => dna_b2b_decimal_value( $estimate['margin'] ?? 0 ),
    'discount'        => $discount,
    'total'           => dna_b2b_decimal_value( $estimate['total'] ?? 0 ),
    'total_days'      => absint( $estimate['total_days'] ?? ( $estimate['totalDays'] ?? 0 ) ),
    'items'           => $items,
    'shipping'        => $shipping,
    'design_package'  => $design_package,
    'promo'           => $promo,
  ];
}

function dna_b2b_money( $value ) {
  return '$' . number_format_i18n( (float) $value, 2 );
}

function dna_b2b_estimate_design_label( $value ) {
  $value = sanitize_key( (string) $value );
  if ( $value === '' ) {
    return 'N/A';
  }
  if ( $value === 'none' ) {
    return 'No Design Service';
  }

  $config = dna_b2b_get_pricing_config();
  $tier_sets = [
    $config['logo_design_tiers'] ?? [],
    $config['merch_design_tiers'] ?? [],
  ];
  foreach ( $tier_sets as $rows ) {
    if ( ! is_array( $rows ) ) {
      continue;
    }
    foreach ( $rows as $row ) {
      if ( empty( $row['enabled'] ) || empty( $row['id'] ) ) {
        continue;
      }
      if ( sanitize_key( (string) $row['id'] ) === $value ) {
        $label = isset( $row['label'] ) ? trim( (string) $row['label'] ) : '';
        return $label !== '' ? $label : $value;
      }
    }
  }

  $labels = [
    'none' => 'No Design Service',
    'lite' => 'Logo Lite',
    'core' => 'Logo Core',
    'plus' => 'Logo Plus',
  ];

  return $labels[ $value ] ?? $value;
}

function dna_b2b_estimate_shipping_label( $value ) {
  $value = sanitize_key( (string) $value );
  if ( $value === '' ) {
    return 'N/A';
  }

  $config = dna_b2b_get_pricing_config();
  if ( ! empty( $config['shipping_modes'] ) && is_array( $config['shipping_modes'] ) ) {
    foreach ( $config['shipping_modes'] as $mode ) {
      if ( empty( $mode['enabled'] ) || empty( $mode['id'] ) ) {
        continue;
      }

      if ( sanitize_key( (string) $mode['id'] ) === $value ) {
        $label = isset( $mode['label'] ) ? trim( (string) $mode['label'] ) : '';
        return $label !== '' ? $label : $value;
      }
    }
  }

  $labels = [
    'sea' => 'Sea',
    'air' => 'Air',
  ];

  return $labels[ $value ] ?? $value;
}

function dna_b2b_requires_quantities( $service ) {
  return in_array( $service, [ 'production_only', 'design_and_production' ], true );
}

function dna_b2b_normalize_files( $file_param ) {
  if ( empty( $file_param ) ) {
    return [];
  }

  $normalized = [];

  if ( is_array( $file_param ) && isset( $file_param['name'] ) ) {
    if ( is_array( $file_param['name'] ) ) {
      foreach ( $file_param['name'] as $index => $name ) {
        $normalized[] = [
          'name'     => $name,
          'type'     => $file_param['type'][ $index ] ?? '',
          'tmp_name' => $file_param['tmp_name'][ $index ] ?? '',
          'error'    => $file_param['error'][ $index ] ?? UPLOAD_ERR_NO_FILE,
          'size'     => $file_param['size'][ $index ] ?? 0,
        ];
      }
    } else {
      $normalized[] = $file_param;
    }
  }

  return array_values( array_filter( $normalized, function ( $file ) {
    $name = isset( $file['name'] ) ? trim( (string) $file['name'] ) : '';
    $error = isset( $file['error'] ) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;

    return $name !== '' && $error !== UPLOAD_ERR_NO_FILE;
  } ) );
}

function dna_b2b_validate_files( $files ) {
  $errors = [];
  $allowed_mimes = dna_b2b_allowed_upload_mimes();

  if ( count( $files ) > dna_b2b_upload_max_files() ) {
    $errors['uploads'] = 'Please upload no more than ' . dna_b2b_upload_max_files() . ' files.';
    return $errors;
  }

  foreach ( $files as $file ) {
    $error_code = isset( $file['error'] ) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
    if ( $error_code !== UPLOAD_ERR_OK ) {
      $errors['uploads'] = 'One or more files could not be uploaded. Please try again.';
      return $errors;
    }

    $size = isset( $file['size'] ) ? (int) $file['size'] : 0;
    if ( $size > dna_b2b_upload_max_file_size() ) {
      $errors['uploads'] = 'Each file must be ' . size_format( dna_b2b_upload_max_file_size() ) . ' or smaller.';
      return $errors;
    }

    $tmp_name = isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';
    $name = isset( $file['name'] ) ? (string) $file['name'] : '';
    $check = wp_check_filetype_and_ext( $tmp_name, $name, $allowed_mimes );

    if ( empty( $check['ext'] ) || empty( $check['type'] ) ) {
      $errors['uploads'] = 'Only PDF, JPG, PNG, WEBP, and ZIP files are supported.';
      return $errors;
    }
  }

  return $errors;
}

function dna_b2b_validate_submission_request( WP_REST_Request $request ) {
  $params = $request->get_params();
  $raw_files = $request->get_file_params();
  $files = dna_b2b_normalize_files( $raw_files['uploads'] ?? [] );
  $errors = [];

  $honeypot = isset( $params['company_website'] ) ? trim( sanitize_text_field( wp_unslash( $params['company_website'] ) ) ) : '';
  if ( $honeypot !== '' ) {
    return new WP_Error(
      'dna_b2b_spam_detected',
      'We could not submit your request. Please try again.',
      [ 'status' => 400 ]
    );
  }

  $request_type = isset( $params['request_type'] ) ? sanitize_key( wp_unslash( $params['request_type'] ) ) : '';
  if ( ! in_array( $request_type, [ 'logo', 'merchandise' ], true ) ) {
    $errors['request_type'] = 'Choose whether you need logo design or merchandise support.';
  }

  $submission = [
    'request_type'      => $request_type,
    'logo_mode'         => isset( $params['logo_mode'] ) ? sanitize_key( wp_unslash( $params['logo_mode'] ) ) : '',
    'logo_ideas'        => isset( $params['logo_ideas'] ) ? sanitize_textarea_field( wp_unslash( $params['logo_ideas'] ) ) : '',
    'logo_overview'     => isset( $params['logo_overview'] ) ? sanitize_textarea_field( wp_unslash( $params['logo_overview'] ) ) : '',
    'merch_service'     => isset( $params['merch_service'] ) ? sanitize_key( wp_unslash( $params['merch_service'] ) ) : '',
    'merch_items'       => [],
    'custom_merch_text' => isset( $params['custom_merch_text'] ) ? sanitize_text_field( wp_unslash( $params['custom_merch_text'] ) ) : '',
    'quantities'        => [],
    'name'              => isset( $params['name'] ) ? sanitize_text_field( wp_unslash( $params['name'] ) ) : '',
    'email'             => isset( $params['email'] ) ? sanitize_email( wp_unslash( $params['email'] ) ) : '',
    'brand_name'        => isset( $params['brand_name'] ) ? sanitize_text_field( wp_unslash( $params['brand_name'] ) ) : '',
    'timeline'          => isset( $params['timeline'] ) ? sanitize_text_field( wp_unslash( $params['timeline'] ) ) : '',
    'notes'             => isset( $params['notes'] ) ? sanitize_textarea_field( wp_unslash( $params['notes'] ) ) : '',
    'estimate'          => [],
  ];

  $allowed_merch = dna_b2b_merch_options();
  $raw_merch_items = dna_b2b_parse_list_field( $params['merch_items'] ?? [] );
  $submission['merch_items'] = array_values( array_filter( array_unique( array_map( 'sanitize_key', $raw_merch_items ) ), function ( $slug ) use ( $allowed_merch ) {
    return isset( $allowed_merch[ $slug ] );
  } ) );

  $raw_quantities = dna_b2b_parse_map_field( $params['quantities'] ?? [] );
  foreach ( $raw_quantities as $slug => $quantity ) {
    $slug = sanitize_key( $slug );
    if ( ! isset( $allowed_merch[ $slug ] ) ) {
      continue;
    }

    $submission['quantities'][ $slug ] = absint( $quantity );
  }

  $raw_estimate = dna_b2b_parse_map_field( $params['estimate'] ?? [] );
  $submission['estimate'] = dna_b2b_normalize_estimate( $raw_estimate );

  $file_errors = dna_b2b_validate_files( $files );
  if ( ! empty( $file_errors['uploads'] ) ) {
    $errors['uploads'] = $file_errors['uploads'];
  }

  if ( $request_type === 'logo' ) {
    if ( ! in_array( $submission['logo_mode'], [ 'has_sketch', 'from_scratch' ], true ) ) {
      $errors['logo_mode'] = 'Choose how you would like to begin the logo project.';
    }

    if ( $submission['logo_mode'] === 'has_sketch' ) {
      if ( empty( $files ) ) {
        $errors['logo_uploads'] = 'Upload at least one sketch or reference file.';
      }

      if ( $submission['logo_ideas'] === '' ) {
        $errors['logo_ideas'] = 'Tell us the overall idea for your logo.';
      }
    }

    if ( $submission['logo_mode'] === 'from_scratch' && $submission['logo_overview'] === '' ) {
      $errors['logo_overview'] = 'Describe your business and the direction you want the logo to carry.';
    }

    $submission['merch_service'] = '';
    $submission['merch_items'] = [];
    $submission['custom_merch_text'] = '';
    $submission['quantities'] = [];
  }

  if ( $request_type === 'merchandise' ) {
    if ( ! in_array( $submission['merch_service'], [ 'production_only', 'design_and_production', 'design_only' ], true ) ) {
      $errors['merch_service'] = 'Choose the level of merchandise support you need.';
    }

    if ( empty( $submission['merch_items'] ) ) {
      $errors['merch_items'] = 'Select at least one merchandise item.';
    }

    if ( in_array( 'other', $submission['merch_items'], true ) && $submission['custom_merch_text'] === '' ) {
      $errors['custom_merch_text'] = 'Name your custom merchandise item.';
    }

    if ( dna_b2b_requires_quantities( $submission['merch_service'] ) ) {
      foreach ( $submission['merch_items'] as $slug ) {
        if ( empty( $submission['quantities'][ $slug ] ) ) {
          $errors['merch_quantities'] = 'Enter a quantity for each selected merchandise item.';
          break;
        }
      }
    } else {
      $submission['quantities'] = [];
    }

    if ( $submission['merch_service'] === 'production_only' && empty( $files ) ) {
      $errors['merch_uploads'] = 'Upload your design files so we can review them for production.';
    }

    $submission['logo_mode'] = '';
    $submission['logo_ideas'] = '';
    $submission['logo_overview'] = '';
  }

  if ( $submission['name'] === '' ) {
    $errors['name'] = 'Enter your name.';
  }

  if ( $submission['email'] === '' || ! is_email( $submission['email'] ) ) {
    $errors['email'] = 'Enter a valid email address.';
  }

  if ( $submission['brand_name'] === '' ) {
    $errors['brand_name'] = 'Enter your brand or business name.';
  }

  if ( $submission['timeline'] === '' ) {
    $errors['timeline'] = 'Tell us your preferred timeline.';
  }

  if ( ! empty( $errors ) ) {
    return new WP_Error(
      'dna_b2b_validation_failed',
      'Please review the highlighted fields and try again.',
      [
        'status' => 400,
        'fields' => $errors,
      ]
    );
  }

  return [
    'submission' => $submission,
    'files'      => $files,
  ];
}

function dna_b2b_selected_merch_lines( $submission ) {
  $options = dna_b2b_merch_options();
  $lines = [];

  foreach ( $submission['merch_items'] as $slug ) {
    $label = $options[ $slug ] ?? $slug;

    if ( $slug === 'other' && ! empty( $submission['custom_merch_text'] ) ) {
      $label = $submission['custom_merch_text'];
    }

    if ( ! empty( $submission['quantities'][ $slug ] ) ) {
      $label .= ' x ' . (int) $submission['quantities'][ $slug ];
    }

    $lines[] = $label;
  }

  return $lines;
}

function dna_b2b_build_summary_text( $submission, $attachment_ids = [] ) {
  $lines = [];
  $lines[] = 'Request type: ' . dna_b2b_request_type_label( $submission['request_type'] );

  if ( $submission['request_type'] === 'logo' ) {
    $lines[] = 'Logo approach: ' . dna_b2b_logo_mode_label( $submission['logo_mode'] );

    if ( ! empty( $submission['logo_ideas'] ) ) {
      $lines[] = '';
      $lines[] = 'Logo ideas:';
      $lines[] = $submission['logo_ideas'];
    }

    if ( ! empty( $submission['logo_overview'] ) ) {
      $lines[] = '';
      $lines[] = 'Business overview:';
      $lines[] = $submission['logo_overview'];
    }
  }

  if ( $submission['request_type'] === 'merchandise' ) {
    $lines[] = 'Merchandise service: ' . dna_b2b_merch_service_label( $submission['merch_service'] );

    if ( ! empty( $submission['merch_items'] ) ) {
      $lines[] = 'Merchandise items: ' . implode( ', ', dna_b2b_selected_merch_lines( $submission ) );
    }
  }

  if ( ! empty( $submission['estimate'] ) && is_array( $submission['estimate'] ) ) {
    $estimate = $submission['estimate'];
    $design_package = ! empty( $estimate['design_package'] ) && is_array( $estimate['design_package'] ) ? $estimate['design_package'] : [];
    $promo = ! empty( $estimate['promo'] ) && is_array( $estimate['promo'] ) ? $estimate['promo'] : [];
    $design_label = ! empty( $design_package['label'] )
      ? (string) $design_package['label']
      : dna_b2b_estimate_design_label( $estimate['design_option'] ?? '' );

    $lines[] = '';
    $lines[] = 'Rough estimate:';
    $lines[] = 'Design package: ' . $design_label;
    if ( ! empty( $design_package['details'] ) ) {
      $lines[] = 'Design details: ' . (string) $design_package['details'];
    }
    $design_hourly_rate = dna_b2b_decimal_value( $design_package['hourly_rate'] ?? ( $estimate['design_hourly_rate'] ?? 0 ) );
    $design_effective_hours = dna_b2b_decimal_value( $design_package['effective_hours'] ?? ( $estimate['design_effective_hours'] ?? 0 ), 1 );
    if ( $design_hourly_rate > 0 ) {
      $lines[] = 'Design hourly rate: ' . dna_b2b_money( $design_hourly_rate ) . ' / hr';
    }
    if ( $design_effective_hours > 0 ) {
      $lines[] = 'Estimated effective design hours: ' . number_format_i18n( $design_effective_hours, 1 ) . ' hrs';
    }
    if ( isset( $design_package['days'] ) && (int) $design_package['days'] > 0 ) {
      $lines[] = 'Design package days: ' . (int) $design_package['days'] . ' days';
    }
    if ( ! empty( $design_package['source'] ) ) {
      $lines[] = 'Design package source: ' . (string) $design_package['source'];
    }

    if ( ! empty( $estimate['items'] ) && is_array( $estimate['items'] ) ) {
      $lines[] = 'Items:';
      foreach ( $estimate['items'] as $item ) {
        if ( ! is_array( $item ) ) {
          continue;
        }
        $line = '- ' . ( $item['label'] ?? $item['id'] ?? 'Item' )
          . ' | qty ' . (int) ( $item['qty'] ?? 0 )
          . ' | unit ' . dna_b2b_money( $item['unit_cost'] ?? 0 )
          . ' | weight ' . number_format_i18n( (float) ( $item['unit_weight'] ?? 0 ), 3 ) . ' kg';
        if ( empty( $item['auto_priced'] ) ) {
          $line .= ' | manual quote';
        } else {
          $line .= ' | production ' . dna_b2b_money( $item['line_production'] ?? 0 );
        }
        $lines[] = $line;
      }
    }

    $shipping_label = '';
    if ( ! empty( $estimate['shipping']['label'] ) ) {
      $shipping_label = $estimate['shipping']['label'];
    } elseif ( ! empty( $estimate['shipping_mode'] ) ) {
      $shipping_label = dna_b2b_estimate_shipping_label( $estimate['shipping_mode'] );
    }
    if ( $shipping_label !== '' ) {
      $shipping_days = (int) ( $estimate['shipping']['days'] ?? ( $estimate['shipping_days'] ?? 0 ) );
      $shipping_rate = $estimate['shipping']['rate'] ?? ( $estimate['shipping_rate'] ?? 0 );
      $lines[] = 'Shipping: ' . $shipping_label . ' | rate ' . dna_b2b_money( $shipping_rate ) . ' / kg | lead ' . $shipping_days . ' days';
    }

    $lines[] = 'Production time: ' . (int) ( $estimate['production_days'] ?? 0 ) . ' days';
    if ( isset( $estimate['design_hourly_rate'] ) && (float) $estimate['design_hourly_rate'] > 0 ) {
      $lines[] = 'Design hourly rate used: ' . dna_b2b_money( $estimate['design_hourly_rate'] ) . ' / hr';
    }
    if ( isset( $estimate['design_effective_hours'] ) && (float) $estimate['design_effective_hours'] > 0 ) {
      $lines[] = 'Effective design hours used: ' . number_format_i18n( (float) $estimate['design_effective_hours'], 1 ) . ' hrs';
    }
    $lines[] = 'Design: ' . dna_b2b_money( $estimate['design_cost'] ?? 0 );
    $lines[] = 'Production: ' . dna_b2b_money( $estimate['production'] ?? 0 );
    $lines[] = 'Profit (25%): ' . dna_b2b_money( $estimate['margin'] ?? 0 );
    if ( ! empty( $promo['code'] ) ) {
      $promo_value = isset( $promo['value'] ) ? (float) $promo['value'] : 0;
      $promo_type = (string) ( $promo['type'] ?? '' );
      $promo_value_label = $promo_type === 'percent'
        ? number_format_i18n( $promo_value, 2 ) . '%'
        : dna_b2b_money( $promo_value );
      $lines[] = 'Promo code: ' . (string) $promo['code'] . ' (' . $promo_type . ' ' . $promo_value_label . ')';
    }
    $lines[] = 'Discount: ' . dna_b2b_money( $estimate['discount'] ?? ( $promo['discount_amount'] ?? 0 ) );
    $lines[] = 'Logistics & tax: ' . dna_b2b_money( $estimate['logistics'] ?? 0 );
    $lines[] = 'Total: ' . dna_b2b_money( $estimate['total'] ?? 0 );

    if ( ! empty( $estimate['total_days'] ) ) {
      $lines[] = 'Total lead time: ' . (int) $estimate['total_days'] . ' days';
    }
  }

  $lines[] = '';
  $lines[] = 'Brand/business: ' . $submission['brand_name'];
  $lines[] = 'Contact name: ' . $submission['name'];
  $lines[] = 'Email: ' . $submission['email'];
  $lines[] = 'Timeline: ' . $submission['timeline'];

  if ( ! empty( $submission['notes'] ) ) {
    $lines[] = '';
    $lines[] = 'Final notes:';
    $lines[] = $submission['notes'];
  }

  $lines[] = '';
  $lines[] = 'Files attached: ' . count( $attachment_ids );

  return implode( "\n", $lines );
}

function dna_b2b_store_request_meta( $post_id, $submission, $attachment_ids ) {
  update_post_meta( $post_id, 'dna_b2b_submission', $submission );
  update_post_meta( $post_id, 'dna_b2b_request_type', $submission['request_type'] );
  update_post_meta( $post_id, 'dna_b2b_email', $submission['email'] );
  update_post_meta( $post_id, 'dna_b2b_brand_name', $submission['brand_name'] );
  update_post_meta( $post_id, 'dna_b2b_estimate', $submission['estimate'] ?? [] );
  update_post_meta( $post_id, 'dna_b2b_attachment_ids', array_map( 'absint', $attachment_ids ) );
}

function dna_b2b_upload_single_file( $file, $post_id ) {
  require_once ABSPATH . 'wp-admin/includes/file.php';
  require_once ABSPATH . 'wp-admin/includes/image.php';
  require_once ABSPATH . 'wp-admin/includes/media.php';

  $uploaded = wp_handle_upload( $file, [
    'test_form' => false,
    'mimes'     => dna_b2b_allowed_upload_mimes(),
  ] );

  if ( isset( $uploaded['error'] ) ) {
    return new WP_Error(
      'dna_b2b_upload_failed',
      $uploaded['error'],
      [ 'status' => 400 ]
    );
  }

  $attachment = [
    'post_mime_type' => $uploaded['type'],
    'post_title'     => sanitize_file_name( wp_basename( $uploaded['file'] ) ),
    'post_content'   => '',
    'post_status'    => 'inherit',
    'post_parent'    => $post_id,
  ];

  $attachment_id = wp_insert_attachment( $attachment, $uploaded['file'], $post_id, true );
  if ( is_wp_error( $attachment_id ) ) {
    return $attachment_id;
  }

  $metadata = wp_generate_attachment_metadata( $attachment_id, $uploaded['file'] );
  if ( ! empty( $metadata ) ) {
    wp_update_attachment_metadata( $attachment_id, $metadata );
  }

  return (int) $attachment_id;
}

function dna_b2b_delete_attachments( $attachment_ids ) {
  foreach ( $attachment_ids as $attachment_id ) {
    wp_delete_attachment( (int) $attachment_id, true );
  }
}

function dna_b2b_handle_uploads( $files, $post_id ) {
  $attachment_ids = [];

  foreach ( $files as $file ) {
    $attachment_id = dna_b2b_upload_single_file( $file, $post_id );
    if ( is_wp_error( $attachment_id ) ) {
      dna_b2b_delete_attachments( $attachment_ids );
      return $attachment_id;
    }

    $attachment_ids[] = (int) $attachment_id;
  }

  return $attachment_ids;
}

function dna_b2b_send_notification_email( $post_id, $submission, $attachment_ids ) {
  $subject_parts = [];
  if ( ! empty( $submission['brand_name'] ) ) {
    $subject_parts[] = $submission['brand_name'];
  }
  $subject_parts[] = dna_b2b_request_type_label( $submission['request_type'] );

  $subject = 'B2B Request: ' . implode( ' - ', $subject_parts );
  $message = dna_b2b_build_summary_text( $submission, $attachment_ids );
  $message .= "\n\nAdmin link: " . admin_url( 'post.php?post=' . (int) $post_id . '&action=edit' );

  return wp_mail( 'hello@designnaesthetics.com', $subject, $message );
}

function dna_b2b_create_request_post( $submission ) {
  $title_parts = [];
  if ( ! empty( $submission['brand_name'] ) ) {
    $title_parts[] = $submission['brand_name'];
  }
  $title_parts[] = dna_b2b_request_type_label( $submission['request_type'] );
  $title_parts[] = gmdate( 'Y-m-d H:i' );

  return wp_insert_post( [
    'post_type'    => 'dna_b2b_request',
    'post_status'  => 'publish',
    'post_title'   => implode( ' — ', $title_parts ),
    'post_content' => dna_b2b_build_summary_text( $submission, [] ),
  ], true );
}

add_action( 'init', function () {
  register_post_type( 'dna_b2b_request', [
    'labels' => [
      'name'               => __( 'B2B Requests', 'dna' ),
      'singular_name'      => __( 'B2B Request', 'dna' ),
      'menu_name'          => __( 'B2B Requests', 'dna' ),
      'add_new_item'       => __( 'Add B2B Request', 'dna' ),
      'edit_item'          => __( 'View B2B Request', 'dna' ),
      'new_item'           => __( 'New B2B Request', 'dna' ),
      'view_item'          => __( 'View B2B Request', 'dna' ),
      'search_items'       => __( 'Search B2B Requests', 'dna' ),
      'not_found'          => __( 'No B2B requests found.', 'dna' ),
      'not_found_in_trash' => __( 'No B2B requests found in Trash.', 'dna' ),
    ],
    'public'              => false,
    'show_ui'             => true,
    'show_in_menu'        => true,
    'show_in_rest'        => false,
    'exclude_from_search' => true,
    'publicly_queryable'  => false,
    'menu_icon'           => 'dashicons-portfolio',
    'supports'            => [ 'title', 'editor' ],
  ] );
} );

add_filter( 'manage_dna_b2b_request_posts_columns', function ( $columns ) {
  return [
    'cb'           => $columns['cb'] ?? '',
    'title'        => __( 'Lead', 'dna' ),
    'request_type' => __( 'Request', 'dna' ),
    'contact'      => __( 'Contact', 'dna' ),
    'date'         => $columns['date'] ?? __( 'Date', 'dna' ),
  ];
} );

add_action( 'manage_dna_b2b_request_posts_custom_column', function ( $column, $post_id ) {
  if ( $column === 'request_type' ) {
    $value = get_post_meta( $post_id, 'dna_b2b_request_type', true );
    echo esc_html( dna_b2b_request_type_label( $value ) );
  }

  if ( $column === 'contact' ) {
    $email = get_post_meta( $post_id, 'dna_b2b_email', true );
    $brand = get_post_meta( $post_id, 'dna_b2b_brand_name', true );

    if ( $brand ) {
      echo '<strong>' . esc_html( $brand ) . '</strong><br>';
    }

    echo esc_html( $email );
  }
}, 10, 2 );

add_action( 'add_meta_boxes', function () {
  add_meta_box(
    'dna-b2b-request-details',
    __( 'Request Details', 'dna' ),
    'dna_b2b_render_request_meta_box',
    'dna_b2b_request',
    'normal',
    'high'
  );
} );

function dna_b2b_render_request_meta_box( $post ) {
  $submission = get_post_meta( $post->ID, 'dna_b2b_submission', true );
  $attachment_ids = get_post_meta( $post->ID, 'dna_b2b_attachment_ids', true );
  $estimate = get_post_meta( $post->ID, 'dna_b2b_estimate', true );

  if ( ! is_array( $submission ) ) {
    echo '<p>No request details are available for this entry.</p>';
    return;
  }

  if ( ! is_array( $attachment_ids ) ) {
    $attachment_ids = [];
  }
  if ( ! is_array( $estimate ) ) {
    $estimate = [];
  }

  echo '<table class="widefat striped" style="border-collapse:collapse;">';
  echo '<tbody>';
  echo '<tr><td style="width:220px;"><strong>Request type</strong></td><td>' . esc_html( dna_b2b_request_type_label( $submission['request_type'] ?? '' ) ) . '</td></tr>';

  if ( ( $submission['request_type'] ?? '' ) === 'logo' ) {
    echo '<tr><td><strong>Logo approach</strong></td><td>' . esc_html( dna_b2b_logo_mode_label( $submission['logo_mode'] ?? '' ) ) . '</td></tr>';
    if ( ! empty( $submission['logo_ideas'] ) ) {
      echo '<tr><td><strong>Logo ideas</strong></td><td>' . nl2br( esc_html( $submission['logo_ideas'] ) ) . '</td></tr>';
    }
    if ( ! empty( $submission['logo_overview'] ) ) {
      echo '<tr><td><strong>Business overview</strong></td><td>' . nl2br( esc_html( $submission['logo_overview'] ) ) . '</td></tr>';
    }
  }

  if ( ( $submission['request_type'] ?? '' ) === 'merchandise' ) {
    echo '<tr><td><strong>Merch service</strong></td><td>' . esc_html( dna_b2b_merch_service_label( $submission['merch_service'] ?? '' ) ) . '</td></tr>';
    echo '<tr><td><strong>Merch items</strong></td><td>' . esc_html( implode( ', ', dna_b2b_selected_merch_lines( $submission ) ) ) . '</td></tr>';
  }

  if ( ! empty( $estimate ) ) {
    $design_package = ! empty( $estimate['design_package'] ) && is_array( $estimate['design_package'] ) ? $estimate['design_package'] : [];
    $promo = ! empty( $estimate['promo'] ) && is_array( $estimate['promo'] ) ? $estimate['promo'] : [];
    $shipping_mode = dna_b2b_estimate_shipping_label( $estimate['shipping_mode'] ?? '' );
    if ( ! empty( $estimate['shipping']['label'] ) ) {
      $shipping_mode = (string) $estimate['shipping']['label'];
    }
    $shipping_rate = $estimate['shipping']['rate'] ?? ( $estimate['shipping_rate'] ?? 0 );
    $shipping_days = $estimate['shipping']['days'] ?? ( $estimate['shipping_days'] ?? 0 );
    $design_option = ! empty( $design_package['label'] )
      ? (string) $design_package['label']
      : dna_b2b_estimate_design_label( $estimate['design_option'] ?? '' );
    echo '<tr><td><strong>Estimate design package</strong></td><td>' . esc_html( $design_option ) . '</td></tr>';
    if ( ! empty( $design_package['details'] ) ) {
      echo '<tr><td><strong>Estimate design details</strong></td><td>' . nl2br( esc_html( (string) $design_package['details'] ) ) . '</td></tr>';
    }
    $design_hourly_rate = dna_b2b_decimal_value( $design_package['hourly_rate'] ?? ( $estimate['design_hourly_rate'] ?? ( $design_package['cost'] ?? 0 ) ) );
    $design_effective_hours = dna_b2b_decimal_value( $design_package['effective_hours'] ?? ( $estimate['design_effective_hours'] ?? 0 ), 1 );
    if ( $design_hourly_rate > 0 ) {
      echo '<tr><td><strong>Estimate design hourly rate</strong></td><td>' . esc_html( dna_b2b_money( $design_hourly_rate ) ) . ' / hr</td></tr>';
    }
    if ( $design_effective_hours > 0 ) {
      echo '<tr><td><strong>Estimate effective design hours</strong></td><td>' . esc_html( number_format_i18n( $design_effective_hours, 1 ) ) . ' hrs</td></tr>';
    }
    if ( isset( $design_package['days'] ) && (int) $design_package['days'] > 0 ) {
      echo '<tr><td><strong>Estimate design lead days</strong></td><td>' . esc_html( (string) (int) $design_package['days'] ) . ' days</td></tr>';
    }
    if ( ! empty( $design_package['source'] ) ) {
      echo '<tr><td><strong>Estimate design package source</strong></td><td>' . esc_html( (string) $design_package['source'] ) . '</td></tr>';
    }

    if ( ! empty( $estimate['items'] ) && is_array( $estimate['items'] ) ) {
      $item_lines = [];
      foreach ( $estimate['items'] as $item ) {
        if ( ! is_array( $item ) ) {
          continue;
        }
        $line = esc_html( ( $item['label'] ?? $item['id'] ?? 'Item' ) )
          . ' · Qty ' . esc_html( (string) ( $item['qty'] ?? 0 ) )
          . ' · Unit ' . esc_html( dna_b2b_money( $item['unit_cost'] ?? 0 ) )
          . ' · Weight ' . esc_html( number_format_i18n( (float) ( $item['unit_weight'] ?? 0 ), 3 ) ) . ' kg';
        if ( empty( $item['auto_priced'] ) ) {
          $line .= ' · Manual quote';
        } else {
          $line .= ' · Production ' . esc_html( dna_b2b_money( $item['line_production'] ?? 0 ) );
        }
        $item_lines[] = $line;
      }
      if ( ! empty( $item_lines ) ) {
        echo '<tr><td><strong>Estimate items</strong></td><td>' . implode( '<br>', $item_lines ) . '</td></tr>';
      }
    }

    echo '<tr><td><strong>Estimate production days</strong></td><td>' . esc_html( (string) ( $estimate['production_days'] ?? 0 ) ) . ' days</td></tr>';
    echo '<tr><td><strong>Estimate shipping</strong></td><td>' . esc_html( $shipping_mode ) . '</td></tr>';
    echo '<tr><td><strong>Estimate shipping rate</strong></td><td>' . esc_html( dna_b2b_money( $shipping_rate ) ) . ' / kg</td></tr>';
    echo '<tr><td><strong>Estimate shipping days</strong></td><td>' . esc_html( (string) $shipping_days ) . ' days</td></tr>';
    if ( ! empty( $promo['code'] ) ) {
      $promo_type = (string) ( $promo['type'] ?? '' );
      $promo_value = isset( $promo['value'] ) ? (float) $promo['value'] : 0;
      $promo_value_label = $promo_type === 'percent'
        ? number_format_i18n( $promo_value, 2 ) . '%'
        : dna_b2b_money( $promo_value );
      echo '<tr><td><strong>Estimate promo</strong></td><td>' . esc_html( (string) $promo['code'] . ' (' . $promo_type . ' ' . $promo_value_label . ')' ) . '</td></tr>';
    }
    echo '<tr><td><strong>Estimate discount</strong></td><td>' . esc_html( dna_b2b_money( $estimate['discount'] ?? ( $promo['discount_amount'] ?? 0 ) ) ) . '</td></tr>';
    echo '<tr><td><strong>Estimate total</strong></td><td>' . esc_html( dna_b2b_money( $estimate['total'] ?? 0 ) ) . '</td></tr>';
    echo '<tr><td><strong>Estimate breakdown</strong></td><td>'
      . 'Design ' . esc_html( dna_b2b_money( $estimate['design_cost'] ?? 0 ) ) . ' · '
      . 'Production ' . esc_html( dna_b2b_money( $estimate['production'] ?? 0 ) ) . ' · '
      . 'Profit ' . esc_html( dna_b2b_money( $estimate['margin'] ?? 0 ) ) . ' · '
      . 'Discount ' . esc_html( dna_b2b_money( $estimate['discount'] ?? ( $promo['discount_amount'] ?? 0 ) ) ) . ' · '
      . 'Logistics & tax ' . esc_html( dna_b2b_money( $estimate['logistics'] ?? 0 ) )
      . '</td></tr>';
    if ( ! empty( $estimate['total_days'] ) ) {
      echo '<tr><td><strong>Estimate lead time</strong></td><td>' . esc_html( (string) $estimate['total_days'] ) . ' days</td></tr>';
    }
  }

  echo '<tr><td><strong>Brand / business</strong></td><td>' . esc_html( $submission['brand_name'] ?? '' ) . '</td></tr>';
  echo '<tr><td><strong>Contact name</strong></td><td>' . esc_html( $submission['name'] ?? '' ) . '</td></tr>';
  echo '<tr><td><strong>Email</strong></td><td><a href="mailto:' . esc_attr( $submission['email'] ?? '' ) . '">' . esc_html( $submission['email'] ?? '' ) . '</a></td></tr>';
  echo '<tr><td><strong>Timeline</strong></td><td>' . esc_html( $submission['timeline'] ?? '' ) . '</td></tr>';
  echo '<tr><td><strong>Notes</strong></td><td>' . nl2br( esc_html( $submission['notes'] ?? '' ) ) . '</td></tr>';
  echo '<tr><td><strong>Email sent</strong></td><td>' . ( get_post_meta( $post->ID, 'dna_b2b_email_sent', true ) === '1' ? 'Yes' : 'No' ) . '</td></tr>';
  echo '<tr><td><strong>Files</strong></td><td>';

  if ( empty( $attachment_ids ) ) {
    echo 'No files attached.';
  } else {
    foreach ( $attachment_ids as $attachment_id ) {
      $url = wp_get_attachment_url( $attachment_id );
      if ( ! $url ) {
        continue;
      }

      echo '<div><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( get_the_title( $attachment_id ) ?: basename( $url ) ) . '</a></div>';
    }
  }

  echo '</td></tr>';
  echo '</tbody>';
  echo '</table>';
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'dna/v1', '/b2b-request', [
    'methods'             => WP_REST_Server::CREATABLE,
    'callback'            => 'dna_b2b_handle_rest_request',
    'permission_callback' => '__return_true',
  ] );
} );

function dna_b2b_handle_rest_request( WP_REST_Request $request ) {
  $validated = dna_b2b_validate_submission_request( $request );
  if ( is_wp_error( $validated ) ) {
    return $validated;
  }

  $submission = $validated['submission'];
  $files = $validated['files'];

  $post_id = dna_b2b_create_request_post( $submission );
  if ( is_wp_error( $post_id ) ) {
    return new WP_Error(
      'dna_b2b_create_failed',
      'We could not save your request. Please try again.',
      [ 'status' => 500 ]
    );
  }

  $attachment_ids = dna_b2b_handle_uploads( $files, $post_id );
  if ( is_wp_error( $attachment_ids ) ) {
    wp_delete_post( (int) $post_id, true );
    return $attachment_ids;
  }

  dna_b2b_store_request_meta( $post_id, $submission, $attachment_ids );
  wp_update_post( [
    'ID'           => $post_id,
    'post_content' => dna_b2b_build_summary_text( $submission, $attachment_ids ),
  ] );

  $email_sent = dna_b2b_send_notification_email( $post_id, $submission, $attachment_ids );
  update_post_meta( $post_id, 'dna_b2b_email_sent', $email_sent ? '1' : '0' );

  return new WP_REST_Response( [
    'id'      => (int) $post_id,
    'message' => 'Request submitted successfully.',
  ], 201 );
}

/**
 * WooCommerce admin products list: hide GTIN/UPC/EAN/ISBN column so SEO columns have room.
 */
add_filter( 'manage_edit-product_columns', function ( $columns ) {
  if ( ! is_array( $columns ) || empty( $columns ) ) {
    return $columns;
  }

  foreach ( $columns as $key => $label ) {
    $key_text = strtolower( (string) $key );
    $label_text = strtolower( wp_strip_all_tags( (string) $label ) );

    $key_match = strpos( $key_text, 'gtin' ) !== false
      || strpos( $key_text, 'upc' ) !== false
      || strpos( $key_text, 'ean' ) !== false
      || strpos( $key_text, 'isbn' ) !== false;

    $label_match = strpos( $label_text, 'gtin' ) !== false
      || strpos( $label_text, 'upc' ) !== false
      || strpos( $label_text, 'ean' ) !== false
      || strpos( $label_text, 'isbn' ) !== false;

    if ( $key_match || $label_match ) {
      unset( $columns[ $key ] );
    }
  }

  return $columns;
}, 1000 );

/**
 * Fallback hide for plugins that inject GTIN column outside the normal column filter.
 */
add_action( 'admin_head-edit.php', function () {
  $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
  if ( ! $screen || $screen->id !== 'edit-product' ) {
    return;
  }
  ?>
  <style id="dna-hide-gtin-column-fallback">
    .post-type-product .wp-list-table th[class*="gtin"],
    .post-type-product .wp-list-table th[class*="upc"],
    .post-type-product .wp-list-table th[class*="ean"],
    .post-type-product .wp-list-table th[class*="isbn"],
    .post-type-product .wp-list-table td[class*="gtin"],
    .post-type-product .wp-list-table td[class*="upc"],
    .post-type-product .wp-list-table td[class*="ean"],
    .post-type-product .wp-list-table td[class*="isbn"]{
      display: none !important;
    }

    .post-type-product .wp-list-table th.column-rank_math_seo_details,
    .post-type-product .wp-list-table td.column-rank_math_seo_details{
      width: 240px !important;
      min-width: 240px !important;
    }

    .post-type-product .wp-list-table td.column-rank_math_seo_details{
      white-space: normal !important;
      word-break: normal !important;
      overflow-wrap: break-word !important;
      line-height: 1.35;
    }
  </style>
  <script>
    (function () {
      var table = document.querySelector('.post-type-product .wp-list-table');
      if (!table) return;

      var headers = table.querySelectorAll('thead th');
      headers.forEach(function (th, index) {
        var text = (th.textContent || '').toLowerCase();
        var compact = text.replace(/\s+/g, ' ').trim();

        if (compact.indexOf('seo details') !== -1) {
          th.style.width = '240px';
          th.style.minWidth = '240px';
          th.style.whiteSpace = 'normal';

          var seoRows = table.querySelectorAll('tbody tr');
          seoRows.forEach(function (row) {
            var seoCell = row.children[index];
            if (!seoCell) return;
            seoCell.style.width = '240px';
            seoCell.style.minWidth = '240px';
            seoCell.style.whiteSpace = 'normal';
            seoCell.style.wordBreak = 'normal';
            seoCell.style.overflowWrap = 'break-word';
            seoCell.style.lineHeight = '1.35';
          });
          return;
        }

        if (
          text.indexOf('gtin') === -1 &&
          text.indexOf('upc') === -1 &&
          text.indexOf('ean') === -1 &&
          text.indexOf('isbn') === -1
        ) {
          return;
        }
        th.style.display = 'none';
        var rows = table.querySelectorAll('tbody tr');
        rows.forEach(function (row) {
          var cell = row.children[index];
          if (cell) cell.style.display = 'none';
        });
      });
    })();
  </script>
  <?php
} );
