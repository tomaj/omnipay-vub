# Spracovanie chýb

Brána hlási problémy výnimkami; platba, ktorá neprešla, nie je výnimka, ale odpoveď s `isSuccessful() === false`.

## Čo môže byť vyhodené {#what-can-be-thrown}

| Výnimka | Vyhodí ju | Kedy |
|---|---|---|
| `Omnipay\Common\Exception\InvalidRequestException` | `purchase()->send()`, `getData()` | povinný parameter je `null` (`The vs parameter is required`, `... rurl ...`, `... amount ...`, `... mid ...`, `... sharedSecret ...`; zdieľaný kľúč aj vtedy, keď je prázdny), `ss` bez 10-miestneho `vs` (`The vs parameter must be exactly 10 digits when ss is set`, `The ss parameter must be up to 10 digits`), neplatná suma (`Amount precision is too high for currency.`, `A negative amount is not allowed.`), `sendData()` zavolané s niečím iným ako pole (`Purchase data must be an array`) |
| `Omnipay\Common\Exception\InvalidRequestException` | `completePurchase()->send()`, `getData()` | `The sharedSecret parameter is required`, `The vs parameter is required`, dve správy o formáte symbolov, `incorect signature`, `Variable symbol mismatch`, `Specific symbol mismatch` ([kontroly](./complete-purchase#the-checks)) |
| `Omnipay\Eplatby\Exception\LogicException` | `PurchaseResponse::getRedirectUrl()` | odpoveď bola vytvorená pre inú požiadavku ako `PurchaseRequest` (chyba v programe) |
| `Omnipay\Common\Exception\RuntimeException` | Omnipay, `initialize()` a settery | požiadavka sa zmenila po `send()` (`Request cannot be modified after it has been sent!`) |

Každá výnimka, ktorú balík definuje, implementuje `Omnipay\Eplatby\Exception\EplatbyException`, ktoré rozširuje `OmnipayException` z Omnipay. `InvalidRequestException` aj `RuntimeException` z Omnipay implementujú `OmnipayException` tiež, takže jeden `catch` zachytí všetko, čo brána vyhodí:

```php
use Omnipay\Common\Exception\OmnipayException;

try {
    $response = $gateway->completePurchase(['vs' => '2024000999'])->send();
} catch (OmnipayException $e) {
    echo get_class($e) . ': ' . $e->getMessage() . "\n";
}
```

<!-- snippet: output -->
```text
Omnipay\Common\Exception\InvalidRequestException: Variable symbol mismatch
```

## Na návratovej URL {#on-the-return-url}

Každá `InvalidRequestException` z `completePurchase()` znamená pre objednávku to isté: **táto požiadavka nepreukazuje platbu tejto objednávky**. Objednávku nemeňte, odpovedzte chybovou stránkou (400 je v poriadku) a správu zalogujte spolu s query stringom; pomôže to odlíšiť chybnú konfiguráciu od útoku:

| Správa | Obvyklá príčina |
|---|---|
| `incorect signature` pri každom návrate | nesprávny `sharedSecret` (kľúč simulátora v produkcii alebo naopak), alebo router, ktorý query string mení alebo ho dekóduje dvakrát |
| `incorect signature` občas | niekto upravuje URL alebo otvára návratovú URL bez parametrov |
| `Variable symbol mismatch` | návrat inej objednávky, alebo sa objednávka načítaná zo session líši od tej, ktorú zákazník zaplatil v inej karte |
| `Specific symbol mismatch` | `ss` poslaný v `purchase()`, ale neodovzdaný do `completePurchase()`, alebo naopak |
| `The vs parameter is required` | chyba vo vašej návratovej URL: nenačítala sa žiadna objednávka |

## V pokladni {#in-the-checkout}

Výnimky z `purchase()` sú chyby konfigurácie alebo programu: chýbajúca návratová URL, suma alebo variabilný symbol, suma s tromi desatinnými miestami. Nechajte ich dôjsť k vášmu error handleru, alebo objednávku overte skôr, než zavoláte bránu.
