# Referencia API

Všetky verejné triedy a metódy balíka. Všetky triedy sú `final`; menný priestor `Omnipay\Eplatby`. Metódy zdedené z Omnipay sú uvedené tam, kde sú pre túto bránu dôležité; ostatné sa správajú ako v [omnipay/common](https://github.com/thephpleague/omnipay-common).

## `Gateway` {#gateway}

`final class Gateway extends Omnipay\Common\AbstractGateway`

| Metóda | Vráti | Popis |
|---|---|---|
| `__construct(?ClientInterface $httpClient = null, ?Request $httpRequest = null)` | | Konštruktor z Omnipay. `$httpRequest` (Symfony) je požiadavka, z ktorej `completePurchase()` číta návrat; `null` znamená `Request::createFromGlobals()`. Pri `$httpClient` rovnom `null` sa vyhľadá PSR-18 klient. |
| `getName()` | `string` | `'VUB ePlatby Gateway'` |
| `getShortName()` | `string` | `'Eplatby'`, názov pre `Omnipay::create('Eplatby')` |
| `getDefaultParameters()` | `array<string, string>` | `['mid' => '', 'sharedSecret' => '', 'testHost' => '']` |
| `initialize(array $parameters = [])` | `$this` | Vráti parametre na predvolené hodnoty a potom nastaví zadané (Omnipay) |
| `getParameters()` | `array` | Všetky parametre (Omnipay) |
| `getMid()` / `setMid(?string $value)` | `?string` / `static` | Identifikátor obchodníka (MID) |
| `getSharedSecret()` / `setSharedSecret(?string $value)` | `?string` / `static` | Zdieľaný kľúč pre HMAC-SHA256 |
| `getTestMode()` / `setTestMode($value)` | `?bool` / `static` | Odosielať na simulátor (Omnipay) |
| `getTestHost()` / `setTestHost(?string $value)` | `?string` / `static` | Host simulátora pre testovací režim |
| `purchase(array $parameters = [])` | `Message\PurchaseRequest` | Nová platba s parametrami brány prepísanými hodnotami z `$parameters` |
| `completePurchase(array $parameters = [])` | `Message\CompletePurchaseRequest` | Overí návrat z banky, s parametrami brány prepísanými hodnotami z `$parameters` |
| `supportsPurchase()`, `supportsCompletePurchase()` | `bool` | `true` (Omnipay); ostatné `supports*()` vracajú `false` |

Gettery vracajú `null` pre parameter, ktorý nie je reťazec ani celé číslo (celé číslo sa vráti ako reťazec).

## `Message\PurchaseRequest` {#message-purchaserequest}

`final class PurchaseRequest extends Omnipay\Common\Message\AbstractRequest`

| Člen | Vráti | Popis |
|---|---|---|
| `getData()` | `array<string, ?string>` | `MID`, `AMT`, `VS`, `CS`, `RURL`, `SS`, `REM`, `RSMS`. Vyhodí `InvalidRequestException`, keď je `sharedSecret`, `mid`, `vs`, `rurl` alebo `amount` `null`, `sharedSecret` je prázdny alebo len z bielych znakov, `ss` je nastavený a nemá 1 – 10 číslic alebo `vs` nemá presne 10 číslic, alebo je suma neplatná. |
| `send()` | `PurchaseResponse` | `sendData(getData())` |
| `sendData(mixed $data)` | `PurchaseResponse` | Pridá `SIGN` = HMAC-SHA256 z `MID + AMT + VS + SS + CS + RURL`. Vyhodí `InvalidRequestException('Purchase data must be an array')`. |
| `generateSignature(string $data)` | `string` | HMAC-SHA256 z `$data` so zdieľaným kľúčom (`sharedSecret`), hexadecimálne veľkými písmenami; vyhodí `InvalidRequestException('The sharedSecret parameter is required')`, keď je kľúč `null`, prázdny alebo len z bielych znakov |
| `getEndpoint()` | `string` | `https://ib.vub.sk/e-platbyeuro.aspx`; v testovacom režime `<testHost alebo https://platby.tomaj.sk>/payment/eplatby-hmac` |
| `getMid()` / `setMid(?string)` | `?string` / `static` | `MID` |
| `getSharedSecret()` / `setSharedSecret(?string)` | `?string` / `static` | Kľúč pre HMAC |
| `getTestHost()` / `setTestHost(?string)` | `?string` / `static` | Host simulátora |
| `getVs()` / `setVs(?string)` | `?string` / `static` | `VS`, variabilný symbol |
| `getSs()` / `setSs(?string)` | `?string` / `static` | `SS`, špecifický symbol |
| `getCs()` / `setCs(?string)` | `?string` / `static` | `CS`, konštantný symbol |
| `getRurl()` / `setRurl(?string)` | `?string` / `static` | `RURL`, návratová URL |
| `getRem()` / `setRem(?string)` | `?string` / `static` | `REM`, e-mail pre notifikáciu |
| `getRsms()` / `setRsms(?string)` | `?string` / `static` | `RSMS`, telefónne číslo pre notifikáciu |
| `getAmount()` / `setAmount($value)` | `?string` / `static` | `AMT`, naformátovaná na dve desatinné miesta (Omnipay) |
| `getTestMode()` / `setTestMode($value)` | `?bool` / `static` | (Omnipay) |

## `Message\PurchaseResponse` {#message-purchaseresponse}

`final class PurchaseResponse extends Omnipay\Common\Message\AbstractResponse implements RedirectResponseInterface`

| Metóda | Vráti | Popis |
|---|---|---|
| `isSuccessful()` | `bool` | Vždy `false` |
| `isRedirect()` | `bool` | Vždy `true` |
| `getRedirectUrl()` | `string` | `getEndpoint()` požiadavky. Vyhodí `Exception\LogicException('PurchaseResponse requires a PurchaseRequest')`, keď bola odpoveď vytvorená pre inú požiadavku. |
| `getRedirectMethod()` | `string` | `'POST'` |
| `getRedirectData()` | `array` | Podpísané polia formulára |
| `getRedirectResponse()` | `Symfony\Component\HttpFoundation\Response` | Automaticky odosielaná HTML stránka z Omnipay |
| `redirect()` | `void` | Pošle `getRedirectResponse()` (Omnipay) |
| `getData()` | `mixed` | Tie isté polia ako `getRedirectData()` |
| `getRequest()` | `RequestInterface` | `PurchaseRequest` |

## `Message\CompletePurchaseRequest` {#message-completepurchaserequest}

`final class CompletePurchaseRequest extends Omnipay\Common\Message\AbstractRequest`

| Člen | Vráti | Popis |
|---|---|---|
| `getData()` | `array{RES: string, VS: string, SS: string}` | Prečíta `VS`, `SS`, `RES`, `SIGN` z query stringu a spustí [kontroly](../guide/complete-purchase#the-checks). Vyhodí `InvalidRequestException`. Chýbajúce parametre alebo parametre, ktoré nie sú reťazce, sa čítajú ako `''`. |
| `send()` | `CompletePurchaseResponse` | `sendData(getData())` |
| `sendData(mixed $data)` | `CompletePurchaseResponse` | Obalí `$data` |
| `getSharedSecret()` / `setSharedSecret(?string)` | `?string` / `static` | Kľúč pre HMAC |
| `getVs()` / `setVs(?string)` | `?string` / `static` | Očakávaný variabilný symbol (povinný) |
| `getSs()` / `setSs(?string)` | `?string` / `static` | Očakávaný špecifický symbol; `null` znamená žiadny |

## `Message\CompletePurchaseResponse` {#message-completepurchaseresponse}

`final class CompletePurchaseResponse extends Omnipay\Common\Message\AbstractResponse`

| Člen | Vráti | Popis |
|---|---|---|
| `const string SUCCESS = 'OK'` | | `RES` zaplateného návratu |
| `isSuccessful()` | `bool` | `getRes() === 'OK'` |
| `getRes()` | `?string` | `'OK'`, `'FAIL'` alebo `'TOUT'` |
| `getVs()` | `?string` | Vrátený (overený) variabilný symbol |
| `getSs()` | `?string` | Vrátený (overený) špecifický symbol, `''`, keď žiadny nie je |
| `getData()` | `mixed` | `['RES' => ..., 'VS' => ..., 'SS' => ...]` |
| `isRedirect()`, `isCancelled()` | `bool` | `false` (Omnipay) |
| `getMessage()`, `getCode()`, `getTransactionReference()` | `null` | (Omnipay) |

Gettery vrátia `null`, keď dáta pod daným kľúčom nemajú reťazec (to sa stane, len ak si odpoveď zostavíte sami).

## `Sign\HmacSign` {#sign-hmacsign}

`final readonly class HmacSign`

| Metóda | Vráti | Popis |
|---|---|---|
| `sign(string $input, #[\SensitiveParameter] string $secret)` | `string` | `strtoupper(hash_hmac('sha256', $input, $secret))` |

## Výnimky {#exceptions}

| Trieda | Rozširuje / implementuje | Vyhodí sa, keď |
|---|---|---|
| `Exception\EplatbyException` | rozhranie, rozširuje `Omnipay\Common\Exception\OmnipayException`, `\Throwable` | implementuje ho každá výnimka, ktorú balík definuje |
| `Exception\LogicException` | `\LogicException`, implementuje `EplatbyException` | `PurchaseResponse::getRedirectUrl()` bez `PurchaseRequest` |
| `Omnipay\Common\Exception\InvalidRequestException` | Omnipay, implementuje `OmnipayException` | chýbajúce parametre, neplatná suma, neplatný podpis, nezhoda symbolov |

Všetky správy nájdete v [Spracovaní chýb](../guide/errors).
