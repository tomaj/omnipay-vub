---
layout: home
title: omnipay-vub - VÚB e-platby pre Omnipay
titleTemplate: false
description: 'Brána VÚB e-platby pre Omnipay a PHP 8.4+: platobné požiadavky podpísané HMAC-SHA256, návraty overené voči vašej objednávke, testovací režim a päť spustiteľných príkladov.'

hero:
  name: omnipay-vub
  text: VÚB e-platby pre Omnipay
  tagline: Pošlite zákazníka do internet bankingu VÚB banky s podpísanou platobnou požiadavkou a na návratovej URL overte odpoveď banky. Brána pre platobnú knižnicu Omnipay, PHP 8.4+.
  image:
    light: /illustrations/hero-sk-light.svg
    dark: /illustrations/hero-sk-dark.svg
    alt: E-shop pošle podpísanú platbu do VÚB e-platby a na návratovú URL dostane späť podpísaný výsledok
  actions:
    - theme: brand
      text: Začíname
      link: /sk/guide/getting-started
    - theme: alt
      text: Príklady
      link: /sk/examples/
    - theme: alt
      text: GitHub
      link: https://github.com/tomaj/omnipay-vub

features:
  - icon: '<svg class="ot-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="40" height="40" aria-hidden="true" focusable="false"><rect class="ot-soft" x="3" y="4" width="30" height="40" rx="6"/><path class="ot-ink" d="M10 14h16M10 21h16M10 28h9"/><circle class="ot-acc" cx="36" cy="33" r="9"/><path class="ot-mark" d="M32 33h8M37 29.5l3.5 3.5-3.5 3.5"/></svg>'
    title: Podpísaný platobný formulár
    details: purchase() zostaví POST formulár podpísaný HMAC-SHA256, ktorý zákazníka prenesie do internet bankingu VÚB.
    link: /sk/guide/purchase
    linkText: Začatie platby
  - icon: '<svg class="ot-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="40" height="40" aria-hidden="true" focusable="false"><path class="ot-soft" d="M24 3l17 6v13c0 11-7 19-17 23C14 41 7 33 7 22V9z"/><circle class="ot-acc" cx="24" cy="23" r="9"/><path class="ot-mark" d="M19.5 23.5l3.2 3 6-6.5"/></svg>'
    title: Overené návraty
    details: completePurchase() overí podpis banky v konštantnom čase a skontroluje, že návrat patrí k vašej objednávke, nie k ľubovoľnej inej.
    link: /sk/guide/complete-purchase
    linkText: Návratová URL
  - icon: '<svg class="ot-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="40" height="40" aria-hidden="true" focusable="false"><rect class="ot-soft" x="3" y="8" width="42" height="32" rx="7"/><circle class="ot-acc" cx="16" cy="24" r="6"/><path class="ot-ink" d="M22 24h18M35 24v5M40 24v4"/></svg>'
    title: Zdokumentované podpisy
    details: Presné podpisované reťazce s testovacími vektormi, ktoré si overíte cez príkazový riadok openssl.
    link: /sk/guide/signatures
    linkText: Podpisy
  - icon: '<svg class="ot-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="40" height="40" aria-hidden="true" focusable="false"><path class="ot-soft" d="M17 4h14v12l11 20c2 4 0 8-5 8H11c-5 0-7-4-5-8l11-20z"/><path class="ot-acc" d="M12 31h24l3.5 6.5c1 2 0 3.5-2.5 3.5h-26c-2.5 0-3.5-1.5-2.5-3.5z"/><path class="ot-ink" d="M15 4h18M20 4v13M28 4v13"/></svg>'
    title: Testovací režim
    details: Nasmerujte bránu na simulátor platieb alebo na vlastnú falošnú banku a otestujte celý priebeh bez skutočných peňazí.
    link: /sk/guide/test-mode
    linkText: Testovací režim
  - icon: '<svg class="ot-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="40" height="40" aria-hidden="true" focusable="false"><rect class="ot-soft" x="3" y="6" width="42" height="30" rx="6"/><path class="ot-ink" d="M10 15l6 5-6 5M20 26h9M17 42h14M24 36v6"/><circle class="ot-acc" cx="37" cy="16" r="4"/></svg>'
    title: Otestované príklady
    details: Päť spustiteľných receptov, od platobného formulára po e-shop a falošnú banku na localhoste. CI spúšťa každý z nich.
    link: /sk/examples/
    linkText: Príklady
  - icon: '<svg class="ot-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="40" height="40" aria-hidden="true" focusable="false"><rect class="ot-soft" x="3" y="3" width="19" height="19" rx="5"/><rect class="ot-soft" x="26" y="3" width="19" height="19" rx="5"/><rect class="ot-soft" x="3" y="26" width="19" height="19" rx="5"/><rect class="ot-acc" x="26" y="26" width="19" height="19" rx="5"/><path class="ot-mark" d="M31 35.5l3 3 6-6.5"/></svg>'
    title: Štandardné Omnipay
    details: Brána Eplatby dodržiava API Omnipay, takže sa zaradí vedľa ostatných platobných metód vášho e-shopu.
    link: /sk/api/reference
    linkText: Referencia API
---

## Ako to vyzerá

Pokladňa pošle zákazníka do banky:

```php
use Omnipay\Omnipay;

$gateway = Omnipay::create('Eplatby');
$gateway->setMid('12345678');
$gateway->setSharedSecret(getenv('VUB_SHARED_SECRET') ?: str_repeat('1', 64));

$response = $gateway->purchase([
    'amount' => '10.00',
    'vs' => '2024000123',                      // variable symbol: unique per order
    'rurl' => 'https://shop.example/return',   // the bank sends the customer back here
])->send();

$response->redirect(); // an auto-submitting POST form to VÚB e-platby
```

Návratová URL potvrdí platbu, a to pre objednávku, ktorú očakávate:

```php
$response = $gateway->completePurchase(['vs' => $order->vs])->send();

if ($response->isSuccessful()) {
    // paid: the bank does not sign the amount, use the amount stored with the order
    echo "Order {$response->getVs()} is paid\n";
}
```

## Kam ďalej

<div class="ot-cards">
  <a href="./guide/getting-started.html"><strong>Začíname</strong><span>Inštalácia, konfigurácia, prvá platba</span></a>
  <a href="./guide/payment-flow.html"><strong>Priebeh platby</strong><span>Čo sa deje medzi vaším e-shopom a VÚB</span></a>
  <a href="./examples/"><strong>Príklady</strong><span>Spustiteľné recepty aj s ich výstupom</span></a>
  <a href="./guide/testing.html"><strong>Testovanie integrácie</strong><span>Unit testy s podpísanými návratmi, falošná banka, simulátor</span></a>
  <a href="./api/reference.html"><strong>Referencia API</strong><span>Každá trieda, metóda a výnimka</span></a>
  <a href="./guide/security.html"><strong>Bezpečnosť</strong><span>Čo podpisy pokrývajú a čo nie</span></a>
</div>
