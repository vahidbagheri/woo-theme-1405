/*!
 * global.js — VoiceWeb site-wide script (handle "cn-global"), deferred, footer.
 * jQuery-based. Put behaviour that should run on every page here.
 *
 * A child theme fully replaces this file by shipping assets/js/global.js
 * (the resolver loads the child copy and this one never runs).
 */
(function ($) {
  'use strict';

  var menu = document.getElementById('cn-mobile-menu');
  var toggle = document.querySelector('.cn-header-toggle');
  var panel = (menu) ? menu.querySelector('.cn-mobile-menu__panel') : null;
  var closeButtons = (menu)
    ? menu.querySelectorAll('[data-cn-menu-close]')
    : [];
  var lastFocusedElement = null;

  var preferenceModal = document.getElementById('cn-preference-modal');
  var preferenceDialog = (preferenceModal)
    ? preferenceModal.querySelector('.cn-preference-modal__dialog')
    : null;
  var preferenceTriggers = document.querySelectorAll(
    '[data-cn-preference-open]'
  );
  var preferenceCloseButtons = (preferenceModal)
    ? preferenceModal.querySelectorAll('[data-cn-preference-close]')
    : [];
  var activePreferenceTrigger = null;

  function getFocusableElements(container) {
    if (!container) {
      return [];
    }

    return Array.prototype.slice
      .call(
        container.querySelectorAll(
          'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'
        )
      )
      .filter(function (element) {
        return element.offsetParent !== null;
      });
  }

  function openMenu() {
    if (!menu || !toggle || !panel) {
      return;
    }

    lastFocusedElement = document.activeElement;
    menu.classList.add('is-open');
    menu.setAttribute('aria-hidden', 'false');
    toggle.setAttribute('aria-expanded', 'true');
    document.body.classList.add('cn-menu-open');

    window.requestAnimationFrame(function () {
      var elements = getFocusableElements(panel);
      ((elements.length) ? elements[0] : panel).focus();
    });
  }

  function closeMenu() {
    if (!menu || !toggle) {
      return;
    }

    menu.classList.remove('is-open');
    menu.setAttribute('aria-hidden', 'true');
    toggle.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('cn-menu-open');

    if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
      lastFocusedElement.focus();
    }
  }

  function openPreferenceModal(event) {
    if (!preferenceModal || !preferenceDialog) {
      return;
    }

    activePreferenceTrigger = event.currentTarget;
    preferenceModal.classList.add('is-open');
    preferenceModal.setAttribute('aria-hidden', 'false');
    activePreferenceTrigger.setAttribute('aria-expanded', 'true');
    document.body.classList.add('cn-preference-open');

    window.requestAnimationFrame(function () {
      preferenceDialog.focus();
    });
  }

  function closePreferenceModal() {
    if (!preferenceModal || !preferenceModal.classList.contains('is-open')) {
      return;
    }

    preferenceModal.classList.remove('is-open');
    preferenceModal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('cn-preference-open');

    preferenceTriggers.forEach(function (trigger) {
      trigger.setAttribute('aria-expanded', 'false');
    });

    if (
      activePreferenceTrigger &&
      typeof activePreferenceTrigger.focus === 'function'
    ) {
      activePreferenceTrigger.focus();
    }
  }

  if (toggle) {
    toggle.addEventListener('click', openMenu);
  }

  Array.prototype.forEach.call(closeButtons, function (button) {
    button.addEventListener('click', closeMenu);
  });

  preferenceTriggers.forEach(function (trigger) {
    trigger.addEventListener('click', openPreferenceModal);
  });

  Array.prototype.forEach.call(preferenceCloseButtons, function (button) {
    button.addEventListener('click', closePreferenceModal);
  });

  document.addEventListener('keydown', function (event) {
    if (preferenceModal && preferenceModal.classList.contains('is-open')) {
      if (event.key === 'Escape') {
        event.preventDefault();
        closePreferenceModal();
        return;
      }

      if (event.key !== 'Tab') {
        return;
      }

      var modalElements = getFocusableElements(preferenceDialog);
      if (!modalElements.length) {
        event.preventDefault();
        preferenceDialog.focus();
        return;
      }

      var modalFirst = modalElements[0];
      var modalLast = modalElements[modalElements.length - 1];

      if (
        document.activeElement === preferenceDialog ||
        !preferenceDialog.contains(document.activeElement)
      ) {
        event.preventDefault();
        ((event.shiftKey) ? modalLast : modalFirst).focus();
        return;
      }

      if (event.shiftKey && document.activeElement === modalFirst) {
        event.preventDefault();
        modalLast.focus();
      } else if (!event.shiftKey && document.activeElement === modalLast) {
        event.preventDefault();
        modalFirst.focus();
      }
      return;
    }

    if (!menu || !panel || !menu.classList.contains('is-open')) {
      return;
    }

    if (event.key === 'Escape') {
      closeMenu();
      return;
    }

    if (event.key !== 'Tab') {
      return;
    }

    var elements = getFocusableElements(panel);
    if (!elements.length) {
      event.preventDefault();
      panel.focus();
      return;
    }

    var first = elements[0];
    var last = elements[elements.length - 1];

    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });
})(jQuery);
