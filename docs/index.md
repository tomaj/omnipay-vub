---
layout: home

hero:
  name: Omnipay VÚB
  text: VÚB e-platby for Omnipay
  tagline: Signed payment requests to VÚB banka internet banking, verified returns, and a test mode against a payment simulator.
  image:
    src: /logo.svg
    alt: Omnipay VÚB
  actions:
    - theme: brand
      text: Get started
      link: /guide/getting-started
    - theme: alt
      text: Operations
      link: /guide/operations
    - theme: alt
      text: GitHub
      link: https://github.com/tomaj/omnipay-vub

features:
  - title: Omnipay gateway
    details: The Eplatby gateway plugs VÚB e-platby into the Omnipay API you already use for other payment providers.
  - title: HMAC-SHA256 signatures
    details: Payment requests are signed and the bank's return is verified with a constant-time comparison, with test vectors checked against the openssl CLI.
  - title: Returns bound to the order
    details: completePurchase() rejects a correctly signed return that belongs to another order (different variable or specific symbol).
  - title: Test mode
    details: Send payments to the hosted payment simulator or your own instance before you go live.
---
