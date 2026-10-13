(function () {
  'use strict';
  var root = document.querySelector('[data-cn-account-menu]');
  if (!root) return;
  var trigger = root.querySelector('[data-cn-account-toggle]');
  var panel = root.querySelector('.cn-account-menu__panel');
  var desktop = window.matchMedia('(min-width: 1200px)');
  function close(restoreFocus) {
    panel.hidden = true;
    trigger.setAttribute('aria-expanded', 'false');
    if (restoreFocus) trigger.focus();
  }
  function open(focusLast) {
    if (!desktop.matches) return;
    panel.hidden = false;
    trigger.setAttribute('aria-expanded', 'true');
    if (focusLast !== undefined) {
      var links = panel.querySelectorAll('a[href]');
      if (links.length) links[focusLast ? links.length - 1 : 0].focus();
    }
  }
  trigger.addEventListener('click', function () { if (panel.hidden) open(); else close(false); });
  trigger.addEventListener('keydown', function (event) {
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') { event.preventDefault(); open(event.key === 'ArrowUp'); }
  });
  panel.addEventListener('keydown', function (event) {
    if (!['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;
    event.preventDefault();
    var links = Array.from(panel.querySelectorAll('a[href]'));
    var index = links.indexOf(document.activeElement);
    if (event.key === 'Home') index = 0;
    else if (event.key === 'End') index = links.length - 1;
    else index = (index + (event.key === 'ArrowDown' ? 1 : -1) + links.length) % links.length;
    if (links[index]) links[index].focus();
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !panel.hidden) { event.preventDefault(); close(true); }
  });
  document.addEventListener('click', function (event) { if (!root.contains(event.target)) close(false); });
  root.addEventListener('focusout', function () {
    window.setTimeout(function () { if (!root.contains(document.activeElement)) close(false); }, 0);
  });
  desktop.addEventListener('change', function () { close(false); });
  window.addEventListener('pageshow', function () { close(false); });
})();
