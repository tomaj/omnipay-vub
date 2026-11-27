# Security Policy

## Supported Versions

Security fixes are provided for the lines below.

| Version | Supported |
| ------- | --------- |
| 4.x (`master`, not released yet) | Yes: bug fixes and security fixes |
| 3.x     | Yes until 4.0.0 is released; then security fixes only, for six months |
| < 3.0   | No |

3.x is the current stable line until 4.0.0 is released. After the 4.0.0 release, 3.x gets fixes for security vulnerabilities only, for six months; there is no 3.x maintenance branch, so a fix is released from one created from the `3.1.0` tag. Versions older than 3.0 do not receive security updates.

## Reporting a Vulnerability

Do not open a public GitHub issue for a security vulnerability.

The preferred way to report a vulnerability is [GitHub private vulnerability reporting](https://github.com/tomaj/omnipay-vub/security/advisories/new).

If you cannot use private vulnerability reporting, email the details to **tomasmajer@gmail.com**.

Please include:

- A description of the vulnerability
- Steps to reproduce, or a proof of concept
- The omnipay-vub version
- The potential impact
- A suggested fix, if you have one

### Response

You will receive an acknowledgement within 14 days.

### Coordinated disclosure

We follow coordinated disclosure. Please give us time to prepare a fix before discussing the issue in public.

When a fix is ready, we publish a [GitHub Security Advisory](https://github.com/tomaj/omnipay-vub/security/advisories) and record the security fix in the [CHANGELOG](CHANGELOG.md).

## Security considerations

omnipay-vub signs payment requests to VÚB e-platby and verifies the bank's return request:

- Keep the `sharedSecret` (the HMAC key from VÚB) in the environment or a secret store. Do not commit it, log it or send it to the browser. Since 4.0 an empty or whitespace-only secret is rejected instead of being used as an HMAC key.
- Since 4.0 the secret parameters (`setSharedSecret()`, `HmacSign::sign()`) are marked `#[\SensitiveParameter]`, so stack traces show `Object(SensitiveParameterValue)` instead of the key. A secret passed in the `initialize()`/`purchase([...])` array is not covered: set it with `setSharedSecret()` and use `zend.exception_ignore_args=On` in production.
- Always confirm a payment with `completePurchase()` on the return URL, and pass the variable symbol (`vs`) of the order you expect. Never mark an order paid from the query parameters alone.
- The bank's return signature covers only the variable symbol, the specific symbol and the result, **not the amount**. Load the order by its variable symbol and use the amount stored on your side; never take an amount from the request.
- Use a variable symbol that is unique per order, so one successful return cannot be replayed for another order. With a specific symbol (`ss`), the variable symbol must have exactly 10 digits (the gateway enforces it since 4.0), because VÚB signs both symbols without a separator.
- Test mode sends payments to a simulator (`https://platby.tomaj.sk` by default, or your `testHost`). Never enable test mode in production.
