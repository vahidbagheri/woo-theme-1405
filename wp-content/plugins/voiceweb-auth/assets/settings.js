(function () {
  'use strict';
  var selector = document.getElementById('vwa-sms-provider');
  if (!selector) return;
  function showProvider() {
    document.querySelectorAll('[data-vwa-sms-provider]').forEach(function (section) {
      // Hidden fields remain enabled so switching providers preserves their settings.
      section.hidden = section.dataset.vwaSmsProvider !== selector.value;
    });
  }
  selector.addEventListener('change', showProvider);
  showProvider();
})();
