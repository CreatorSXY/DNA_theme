<?php
/**
 * Backward-compat shim.
 *
 * Older DNA versions had a Montessori-specific taxonomy template.
 * v12.4 removes ALL slug hard-coding; this file now delegates to the unified
 * product category template.
 */

defined('ABSPATH') || exit;

require __DIR__ . '/taxonomy-product_cat.php';
