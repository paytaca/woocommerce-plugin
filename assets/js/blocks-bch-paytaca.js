(function () {
    const blocks = window.wc?.wcBlocksRegistry;
    const element = window.wp?.element;

    if (!blocks || !element || typeof blocks.registerPaymentMethod !== 'function') {
        console.error('WooCommerce Blocks or wp.element not loaded.');
        return;
    }

    const { createElement, Fragment } = element;

    console.log("Paytaca BCH block JS loaded");

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
        content: createElement('div', null, 'You will be redirected to the Paytaca Payment Hub to complete your BCH payment securely.'),
        edit: createElement('div', null, 'You will be redirected to the Paytaca Payment Hub to complete your BCH payment securely.'),
        canMakePayment: () => true,
        ariaLabel: 'Bitcoin Cash (BCH)',
        supports: {
            features: ['products']
        },
        paymentMethodData: () => ({
            payment_method: 'bch_paytaca'
        }),
        onPaymentProcessing: () => {
            console.log("Processing Paytaca BCH payment...");
        }
    });

})();
