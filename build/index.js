(function() {
    "use strict";

    var t = window.wp.element,
        e = window.wp.htmlEntities,
        a = window.wp.i18n,
        n = window.wc.wcBlocksRegistry,
        i = window.wc.wcSettings;

    const l = () => {
        const t = i.getSetting("beepxtra_data", null);
        if (!t) throw new Error("Beepxtra initialization data is not available");
        return t;
    };

    const r = () => e.decodeEntities(l()?.description || "");

    n.registerPaymentMethod({
        name: "beepxtra",
        label: t.createElement(() => t.createElement("img", {
            src: l()?.logo_url,
            alt: l()?.title
        }), null),
        ariaLabel: a.__("Beepxtra payment method", "woocommerce-gateway-beepxtra"),
        canMakePayment: () => true,
        content: t.createElement(r, null),
        edit: t.createElement(r, null),
        supports: {
            features: l()?.supports || []
        }
    });
})();
