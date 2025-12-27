<?php
/**
 * Backward-compat shim.
 *
 * Older DNA versions used a Montessori-specific WooCommerce taxonomy template.
 * v12.4 removes ALL slug hard-coding; delegate to the unified taxonomy template.
 */

defined('ABSPATH') || exit;

require get_stylesheet_directory() . '/taxonomy-product_cat.php';
