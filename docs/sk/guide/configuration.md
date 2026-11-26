# Konfigurácia

Parametre nastavíte na bráne, kde sa stanú predvolenými hodnotami každej požiadavky, alebo ich odovzdáte v poli `purchase([...])` / `completePurchase([...])`, kde pre danú požiadavku prepíšu hodnoty z brány. Každý parameter má na požiadavke aj typovaný setter (`setVs()`, `setRurl()`, ...).

```php
use Omnipay\Eplatby\Gateway;

$gateway = new Gateway();
$gateway->setMid('12345678');
$gateway->setSharedSecret(getenv('VUB_SHARED_SECRET') ?: str_repeat('1', 64));
$gateway->setTestMode(false);

// the same with initialize(), for example from a configuration array
$gateway->initialize([
    'mid' => '12345678',
    'sharedSecret' => getenv('VUB_SHARED_SECRET') ?: str_repeat('1', 64),
    'testMode' => false,
]);

var_dump($gateway->getMid(), $gateway->getTestMode());
```

<!-- snippet: output -->
```text
string(8) "12345678"
bool(false)
```

::: tip initialize() resetuje
Ako v každej Omnipay bráne, `initialize()` najprv vráti všetky parametre na predvolené hodnoty a až potom použije pole.
:::

## Parametre brány {#gateway-parameters}

| Parameter | Setter / getter | Typ | Predvolená hodnota | Popis |
|---|---|---|---|---|
| `mid` | `setMid()` / `getMid()` | `?string` | `''` | Identifikátor obchodníka (Merchant ID), ktorý pridelí VÚB. Posiela sa ako `MID` a je podpísaný. |
| `sharedSecret` | `setSharedSecret()` / `getSharedSecret()` | `?string` | `''` | Zdieľaný kľúč pre HMAC-SHA256 od VÚB. Podpisuje požiadavku a overuje návrat. Nikam sa neposiela. Povinný: `null`, `''` (predvolená hodnota) alebo len biele znaky vyhodia v `purchase()` aj `completePurchase()` `InvalidRequestException('The sharedSecret parameter is required')`. |
| `testMode` | `setTestMode()` / `getTestMode()` | `bool` | nenastavené (vypnuté) | `true` odošle formulár na simulátor platieb namiesto VÚB. Pozrite [Testovací režim](./test-mode). |
| `testHost` | `setTestHost()` / `getTestHost()` | `?string` | `''` (znamená `https://platby.tomaj.sk`) | Schéma a host simulátora v testovacom režime, bez lomky na konci, napríklad `http://127.0.0.1:8080`. |

::: warning Konfiguráciu skontrolujte pri štarte
Prázdny `sharedSecret` alebo `sharedSecret` len z bielych znakov (napríklad z chýbajúcej premennej prostredia) brána od verzie 4.0 odmietne: každé `purchase()` aj `completePurchase()` vyhodí výnimku. Predvolená hodnota `mid` je stále prázdny reťazec, ktorý kontrola povinných parametrov v Omnipay prijme. Skontrolujte oboje pri štarte aplikácie, napríklad cez `getenv('VUB_SHARED_SECRET') ?: throw new RuntimeException(...)`, aby pokazené nasadenie zlyhalo hneď, a nie až pri prvej platbe.
:::

## Parametre `purchase()` {#purchase-parameters}

| Parameter | Posiela sa ako | Podpísaný | Povinný | Popis |
|---|---|---|---|---|
| `amount` | `AMT` | áno | áno | Suma v eurách. Omnipay ju formátuje na dve desatinné miesta: `'10'`, `10` aj `'10.00'` sa pošlú ako `10.00`; viac ako dve desatinné miesta vyhodia `InvalidRequestException('Amount precision is too high for currency.')`, záporná suma `'A negative amount is not allowed.'`. |
| `vs` | `VS` | áno | áno | Variabilný symbol: identifikuje objednávku, len číslice, najviac 10 (kontroluje sa); **presne 10**, ak je nastavený `ss` (doplňte nulami). Použite hodnotu jedinečnú pre každú objednávku. |
| `ss` | `SS` | áno | nie | Špecifický symbol, len číslice, najviac 10 (kontroluje sa). Vyžaduje 10-miestny `vs`. Ak ho pošlete, tú istú hodnotu odovzdajte aj do `completePurchase()`. |
| `cs` | `CS` | áno | nie | Konštantný symbol, len číslice, najviac 4 (kontroluje sa; napríklad `0308`). |
| `rurl` | `RURL` | áno | áno | Návratová URL: kam banka pošle zákazníka späť s výsledkom. Absolútna `https://` URL (kontroluje sa); `http://` sa prijme len v testovacom režime. |
| `rem` | `REM` | nie | nie | E-mailová adresa, na ktorú banka pošle notifikáciu o platbe. |
| `rsms` | `RSMS` | nie | nie | Číslo mobilného telefónu pre SMS notifikáciu od banky. |
| `mid`, `sharedSecret`, `testMode`, `testHost` | | | | Zdedené z brány, pre jednotlivú požiadavku ich môžete prepísať. |

`purchase()` vyhodí `InvalidRequestException('The vs parameter is required')` (alebo pre `rurl`, `amount`, `mid`, `sharedSecret`), keď je niektorý z nich `null`, a pri prázdnom `sharedSecret` tiež. Chybný formát vyhodí výnimku skôr, než sa čokoľvek podpíše: `The vs parameter must be up to 10 digits`, `The cs parameter must be up to 4 digits`, `The rurl parameter must be an absolute https:// URL` (v testovacom režime `... https:// or http:// URL`) a s nastaveným `ss` `The ss parameter must be up to 10 digits` alebo `The vs parameter must be exactly 10 digits when ss is set`. Konštantný alebo špecifický symbol zložený len z núl (`0`, `0000`) VÚB v produkcii odmietne; simulátor ho prijme, takže parameter radšej vynechajte, než aby ste posielali nuly.

Mena nie je parameter: e-platby platia v eurách. Všeobecné parametre požiadavky v Omnipay (`currency`, `description`, `transactionId`, `returnUrl`, ...) brána prijme, ale banke ich neposiela; na návratovú URL použite `rurl`.

## Parametre `completePurchase()` {#completepurchase-parameters}

| Parameter | Typ | Povinný | Popis |
|---|---|---|---|
| `vs` | `?string` | áno | Variabilný symbol objednávky, ktorú dokončujete. Vrátený `VS` musí byť presne táto hodnota (`'0123'` nie je `'123'`). Presne 10 číslic, ak je nastavený `ss`. |
| `ss` | `?string` | ak ste ho poslali | Špecifický symbol, ktorý ste poslali v `purchase()`. Vrátený `SS` musí byť presne táto hodnota; `null` znamená, že žiadny `SS` nečakáte. |
| `sharedSecret` | `?string` | áno | Zvyčajne zdedený z brány. Prázdny alebo len z bielych znakov vyhodí výnimku. |

Vrátené `VS`, `SS`, `RES` a `SIGN` sa čítajú z query stringu HTTP požiadavky, s ktorou bola brána vytvorená.

## HTTP požiadavka vo frameworkoch {#the-http-request-in-frameworks}

Omnipay dá bráne Symfony `Request`, predvolene `Request::createFromGlobals()`, čo je správne pre čisté PHP. Vo frameworku alebo na dlho bežiacom serveri (RoadRunner, FrankenPHP v režime worker, Swoole) vytvárajte bránu pre každú požiadavku s aktuálnou požiadavkou:

```php
use Omnipay\Eplatby\Gateway;
use Symfony\Component\HttpFoundation\Request;

$request = Request::create('https://shop.example/return', 'GET', ['VS' => '2024000123', 'RES' => 'OK', 'SIGN' => '...']);

$gateway = new Gateway(null, $request);                 // null: Omnipay discovers the HTTP client
// or: Omnipay::create('Eplatby', null, $request);

echo $gateway->getShortName() . "\n";
```

<!-- snippet: output -->
```text
Eplatby
```

`Illuminate\Http\Request` z Laravelu dedí od Symfony požiadavky, takže ho môžete odovzdať priamo; PSR-7 požiadavku skonvertujte cez `symfony/psr-http-message-bridge`.
