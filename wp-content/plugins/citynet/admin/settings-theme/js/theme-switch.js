(function ($) {
  'use strict';

  $(function () {
    var config = window.cnSettingsTheme;
    var $wrap = $('.toplevel_page_citynet-settings .wrap.acf-settings-wrap');
    if (!config || !$wrap.length || $wrap.find('.cn-settings-theme-switch').length) { return; }

    var $controls = $('<div class="cn-settings-theme-switch"></div>');
    var $button = $('<button type="button" class="button"><span class="dashicons dashicons-admin-appearance" aria-hidden="true"></span><span class="cn-theme-switch-label"></span></button>');
    var $status = $('<span class="cn-theme-switch-status" role="status" aria-live="polite"></span>');
    $button.attr('aria-pressed', String(config.enabled));
    $button.find('.cn-theme-switch-label').text(config.enabled ? config.disableLabel : config.enableLabel);
    $controls.append($button, $status);
    // Outside the ACF form, with no field name or submit behavior.
    $wrap.children('h1').before($controls);

    $button.on('click', function () {
      if (window.acf && window.acf.unload && window.acf.unload.changed) {
        $status.text(config.unsavedMessage);
        return;
      }
      var preference = config.enabled ? 'legacy' : 'modern';
      var cookie = config.cookieName + '=' + preference;
      document.cookie = cookie + '; Path=' + (config.cookiePath || '/') + '; Max-Age=31536000; SameSite=Lax' + (window.location.protocol === 'https:' ? '; Secure' : '');
      var saved = document.cookie.split(';').some(function (part) { return part.trim() === cookie; });
      if (!saved) {
        $status.text(config.cookieError);
        return;
      }
      $button.prop('disabled', true);
      window.location.reload();
    });
  });
})(jQuery);
