(function () {
  'use strict';
  // Label numeric quantity controls without changing WooCommerce's own cart behavior.
  document.querySelectorAll('.quantity .qty').forEach(function (input) {
    if (!input.getAttribute('aria-label')) input.setAttribute('aria-label', 'تعداد محصول');
    input.setAttribute('inputmode', 'numeric');
  });
})();
