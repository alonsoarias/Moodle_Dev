# PayU Colombia payment gateway

This is an experimental payment gateway for connecting Moodle to the PayU platform in Colombia. It is based on the structure of existing payment gateway plugins such as `paygw_payanyway` and `paygw_stripe`.

The plugin currently provides an in-site checkout that submits transactions directly to PayU's API.
Each payment method is rendered via a Mustache template located in `templates/` so the forms can
be customised through Moodle's standard theming system. It supports credit-card payments, PSE bank
transfers, Nequi, the Bancolombia button, Google Pay and cash-based options like Efecty, all inside Moodle
without redirecting through the PayU Web Checkout. Buyer and payer data such as document number,
phone and address are sent with each request as required by PayU. The code is based on `paygw_payanyway` and
`paygw_stripe`.

Further work is required to cover additional payment methods and advanced features such as
subscriptions or refunds.
