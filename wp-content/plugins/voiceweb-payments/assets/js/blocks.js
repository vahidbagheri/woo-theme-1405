/* No build dependencies: native WooCommerce Blocks payment-method registration. */
(() => {
  'use strict';
  const { createElement: h } = window.wp.element;
  const { decodeEntities } = window.wp.htmlEntities;
  for (const name of ['voiceweb_bale', 'voiceweb_card']) {
    const settings = window.wc.wcSettings;
    const data = settings.getPaymentMethodData ? settings.getPaymentMethodData(name, null) : settings.getSetting(name + '_data', null);
    if (!data) continue;
    const title = decodeEntities(data.title || name);
    const content = h('p', { className: 'vwp-block-description' }, decodeEntities(data.description || ''));
    window.wc.wcBlocksRegistry.registerPaymentMethod({
      name, label: h('span', { className: 'vwp-block-label' }, title),
      content, edit: content, canMakePayment: () => true, ariaLabel: title,
      supports: { features: data.supports || ['products'] }
    });
  }
})();
