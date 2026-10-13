/*!
 * global.js (CHILD) — runs on every page (handle "hb-global"), deferred, footer.
 *
 * Because this file exists, it FULLY REPLACES the parent's assets/js/global.js
 * (the resolver loads the child copy and the parent's never runs).
 *
 * Two ways to fill it:
 *   • from scratch — just write your own code below, or
 *   • start from the parent's version and edit:
 *       cp ../voiceweb/assets/js/global.js assets/js/global.js
 */
(function () {
  'use strict';

  var roots = document.querySelectorAll(
    '.cn-site-header, #cn-search, #cn-mobile-menu, #cn-preference-modal, .cn-site-footer, #front-page'
  );

  if (!roots.length) {
    return;
  }

  var isPersian = (document.documentElement.lang || '')
    .toLowerCase()
    .indexOf('fa') === 0;
  var latinDigits = '0123456789';
  var persianDigits = '۰۱۲۳۴۵۶۷۸۹';
  var arabicDigits = '٠١٢٣٤٥٦٧٨٩';
  var skippedTags = /^(SCRIPT|STYLE|TEXTAREA|PRE|CODE)$/;

  function localizeDigits(value) {
    return value.replace(/[0-9۰-۹٠-٩]/g, function (digit) {
      var index = latinDigits.indexOf(digit);

      if (index < 0) {
        index = persianDigits.indexOf(digit);
      }
      if (index < 0) {
        index = arabicDigits.indexOf(digit);
      }

      return (isPersian) ? persianDigits.charAt(index) : latinDigits.charAt(index);
    });
  }

  function localizeTextNode(node) {
    var parent = node.parentElement;

    if (
      !parent ||
      skippedTags.test(parent.tagName) ||
      parent.closest('[data-cn-keep-digits]')
    ) {
      return;
    }

    var localized = localizeDigits(node.nodeValue);
    if (localized !== node.nodeValue) {
      node.nodeValue = localized;
    }
  }

  function localizeTree(root) {
    if (root.nodeType === Node.TEXT_NODE) {
      localizeTextNode(root);
      return;
    }

    if (root.nodeType !== Node.ELEMENT_NODE) {
      return;
    }

    var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    var node;
    while ((node = walker.nextNode())) {
      localizeTextNode(node);
    }
  }

  Array.prototype.forEach.call(roots, localizeTree);

  if (window.MutationObserver) {
    var observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        if (mutation.type === 'characterData') {
          localizeTextNode(mutation.target);
          return;
        }

        Array.prototype.forEach.call(mutation.addedNodes, localizeTree);
      });
    });

    Array.prototype.forEach.call(roots, function (root) {
      observer.observe(root, {
        childList: true,
        characterData: true,
        subtree: true
      });
    });
  }
}());
