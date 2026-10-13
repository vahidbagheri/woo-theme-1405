<?php
/** Address endpoint view, including native billing/shipping edit URLs. */
defined( 'ABSPATH' ) || exit;
get_template_part( 'template-parts/woocommerce/address-book', null, array( 'checkout' => false ) );
