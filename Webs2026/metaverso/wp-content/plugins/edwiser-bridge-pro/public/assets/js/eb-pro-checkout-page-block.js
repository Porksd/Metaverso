const { registerBlockType } = wp.blocks;
const { Button } = wp.components;
const { createElement: el } = wp.element;
const { doAction } = wp.hooks;

registerBlockType('edwiser-bridge-pro/eb-pro-checkout-page-block', {
    title: 'Edwiser Bridge Pro Checkout Page Block',
    icon: 'smiley',
    category: 'common',
    edit: () => {
        const onClickHandler = () => {
            doAction('eb_pro_checkout_page_block_hook');
        };

        return el(
            'div',
            null,
            'Edwiser Bridge Pro Checkout Page Block',
            el(
                Button,
                { onClick: onClickHandler },
                ' '
            )
        );
    },
    save: () => {
        return el(
            'div',
            { className: 'eb-pro-checkout-page-block' },
            'Edwiser Bridge Pro Checkout Page Block'
        );
    },
});
