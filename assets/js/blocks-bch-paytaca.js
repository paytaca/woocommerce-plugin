(function () {
    const blocks = window.wc?.wcBlocksRegistry;
    const element = window.wp?.element;
    const apiFetch = window.wp?.apiFetch;

    if (!blocks || !element || typeof blocks.registerPaymentMethod !== 'function') {
        console.error('❌ WooCommerce Blocks or wp.element not loaded.');
        return;
    }

    // ✅ Attach nonce header to all REST requests
    if (apiFetch && window.wcSettings?.nonce) {
        apiFetch.use(apiFetch.createNonceMiddleware(window.wcSettings.nonce));
        console.log("✅ Nonce middleware attached");
    } else {
        console.warn("⚠️ wp.apiFetch or nonce missing");
    }

    const { createElement, Fragment } = element;

    console.log("🚀 Paytaca BCH block JS loaded");

    const bchIconUrl = window.bchPaytacaIconUrl || '';
    const LabelWithIcon = createElement(Fragment, null,
        createElement('img', {
            src: bchIconUrl,
            alt: 'BCH',
            style: {
                width: '20px',
                height: '20px',
                verticalAlign: 'middle',
                marginRight: '8px',
            }
        }),
        'Bitcoin Cash (BCH)'
    );

    blocks.registerPaymentMethod({
        name: 'bch_paytaca',
        label: LabelWithIcon,
        content: createElement('div', null, 'You will be redirected to Paytaca to complete your BCH payment.'),
        edit: createElement('div', null, 'You will be redirected to Paytaca to complete your BCH payment.'),
        canMakePayment: () => true,
        ariaLabel: 'Bitcoin Cash (BCH)',
        supports: {
            features: ['products']
        }
    });
})();
