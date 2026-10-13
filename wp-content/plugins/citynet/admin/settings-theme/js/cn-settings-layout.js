/* Native fields stay intact; this optional script only enhances navigation. */
(function ($) {
  'use strict';

  function initialize() {
    var $wrap = $('.toplevel_page_citynet-settings .wrap.acf-settings-wrap');
    var $box = $wrap.find('#acf-group_5ae45c1793d11');
    var $fields = $box.children('.acf-fields');
    var $tabList = $fields.children('.acf-tab-wrap').first().children('.acf-tab-group');
    if (!$tabList.length || $wrap.hasClass('cn-layout-ready') || !window.cnSettingsLayout) {
      return;
    }

    var categories = window.cnSettingsLayout.categories;
    var links = {};
    var categoryByKey = {};
    var lastTab = {};
    var selectedCategory = 'all';
    var displayedCategory;
    var $navigation = $('<nav class="cn-settings-categories" aria-label="دسته‌بندی تنظیمات"></nav>');
    var $section = $('<div class="cn-settings-section-header"><p class="cn-settings-eyebrow"></p><h2></h2><p class="cn-settings-description"></p></div>');

    Object.keys(categories).forEach(function (id) {
      categories[id].tabs.forEach(function (key) {
        categoryByKey[key] = id;
      });
    });
    $tabList.children('li').each(function () {
      var $item = $(this);
      var $link = $item.children('a');
      var key = $link.attr('data-key');
      if (!key) { return; }
      // All includes uncategorized and future native sections, in their original order.
      var category = categoryByKey[key] || 'all';
      categoryByKey[key] = category;
      links[key] = $link;
      $item.attr('data-cn-category', category);
      $link.attr('title', $link.text());
    });

    Object.keys(categories).forEach(function (id) {
      var count = Object.keys(links).filter(function (key) { return id === 'all' || categoryByKey[key] === id; }).length;
      if (!count) { return; }
      var $button = $('<button type="button" class="cn-settings-category" aria-pressed="false"></button>');
      $button.attr('data-category', id);
      $button.append($('<span aria-hidden="true" class="dashicons"></span>').addClass('dashicons-' + categories[id].icon));
      $button.append($('<span></span>').text(categories[id].label));
      $navigation.append($button);
    });

    $box.before($navigation);
    $fields.children('.acf-tab-wrap').first().after($section);
    $tabList.attr('aria-label', 'بخش‌های تنظیمات');
    $wrap.children('h1').after($('<p class="cn-settings-intro"></p>').text('تنظیمات آژانس و سرویس‌ها را از بخش‌های زیر مدیریت کنید.'));
    $wrap.children('h1').prepend('<span class="cn-settings-brand" aria-hidden="true"><span class="dashicons dashicons-admin-generic"></span></span>');

    var $save = $wrap.find('#major-publishing-actions');
    if ($save.length) {
      $save.prepend('<div class="cn-settings-save-copy"><strong>ذخیره تنظیمات</strong><span>تغییرات همه بخش‌ها با این دکمه ذخیره می‌شوند.</span></div>');
    }
    $wrap.addClass('cn-layout-ready');

    function showCategory(id) {
      selectedCategory = id;
      $tabList.children('li').each(function () {
        $(this).toggleClass('cn-tab-outside-category', id !== 'all' && $(this).attr('data-cn-category') !== id);
      });
      $navigation.find('button').each(function () {
        var active = $(this).attr('data-category') === id;
        $(this).toggleClass('is-active', active).attr('aria-pressed', String(active));
      });
      if (displayedCategory !== id) {
        displayedCategory = id;
        // Let ACF size the sidebar for the complete list, including long labels.
        if (window.acf) { window.acf.doAction('refresh'); }
      }
    }

    function synchronize() {
      var $active = $tabList.children('li.active').children('a');
      var key = $active.attr('data-key');
      if (!links[key]) { return; }
      var id = selectedCategory === 'all' ? 'all' : categoryByKey[key];
      showCategory(id);
      lastTab[categoryByKey[key]] = key;
      $section.find('.cn-settings-eyebrow').text(categories[id].label);
      $section.find('h2').text($active.text());
      $section.find('.cn-settings-description').text(categories[id].description);
      $tabList.children('li').children('a').removeAttr('aria-current');
      $active.attr('aria-current', 'page');
    }

    function selectCategory(id) {
      // Returning to All keeps the currently open tab and every field in place.
      var preferred = id === 'all' ? $tabList.children('li.active').children('a').attr('data-key') : lastTab[id];
      var keys = categories[id].tabs.concat(Object.keys(links));
      if (preferred) { keys.unshift(preferred); }
      var key = keys.find(function (candidate) {
        return (id === 'all' || categoryByKey[candidate] === id) && links[candidate] && !links[candidate].parent().hasClass('acf-hidden');
      });
      if (!key) { return; }
      showCategory(id);
      // Native ACF toggles its own fields, conditional logic and validation.
      links[key].trigger('click');
      synchronize();
      if (window.acf) { window.acf.doAction('refresh'); }
    }

    $navigation.on('click', 'button', function () {
      selectCategory($(this).attr('data-category'));
    });
    $navigation.on('keydown', 'button', function (event) {
      var $buttons = $navigation.find('button');
      var index = $buttons.index(this);
      if (event.key === 'ArrowLeft') { index++; }
      else if (event.key === 'ArrowRight') { index--; }
      else if (event.key === 'Home') { index = 0; }
      else if (event.key === 'End') { index = $buttons.length - 1; }
      else { return; }
      event.preventDefault();
      $buttons.eq((index + $buttons.length) % $buttons.length).trigger('focus').trigger('click');
    });
    $tabList.on('click', 'a', synchronize);
    $tabList.on('keydown', 'a', function (event) {
      var $links = $tabList.children('li:not(.cn-tab-outside-category):not(.acf-hidden)').children('a');
      var index = $links.index(this);
      if (event.key === 'ArrowDown') { index++; }
      else if (event.key === 'ArrowUp') { index--; }
      else if (event.key === 'Home') { index = 0; }
      else if (event.key === 'End') { index = $links.length - 1; }
      else { return; }
      event.preventDefault();
      $links.eq((index + $links.length) % $links.length).trigger('focus').trigger('click');
    });

    // ACF may open another tab when a hidden field fails validation. Reflect
    // that change in the category navigation without interfering with focus.
    if (window.MutationObserver) {
      var observer = new MutationObserver(synchronize);
      observer.observe($tabList[0], { subtree: true, attributes: true, attributeFilter: ['class'] });
    }
    synchronize();
    if (window.acf) { window.acf.doAction('refresh'); }
  }

  if (window.acf) {
    window.acf.addAction('ready', initialize, 100);
    window.acf.addAction('append', initialize, 100);
  }
  $(initialize);
})(jQuery);
