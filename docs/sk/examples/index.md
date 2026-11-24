# Príklady

Každý príklad je malý Composer projekt v [`examples/`](https://github.com/tomaj/omnipay-vub/tree/master/examples) s kódom, ktorý vidíte na jeho stránke, a so súborom `expected-output.txt`. CI každý z nich nainštaluje proti kódu pull requestu a zlyhá, keď sa výstup zmení, takže to, čo tu čítate, knižnica naozaj robí. Bežia bez siete: stranu banky hrá pár riadkov PHP, ktoré podpisujú cez `hash_hmac()` (a príkazový riadok `openssl`), nezávisle od knižnice.

| Príklad | Čo ukazuje | Spustenie |
|---|---|---|
| [Platobný formulár](./purchase-form) | `purchase()`, každé pole podpísaného formulára, podpis prepočítaný cez `openssl`, automaticky odosielaná stránka | `php run.php` |
| [Vlastné platobné tlačidlo](./custom-form) | vykreslenie formulára vo vašej šablóne z `getRedirectUrl()` / `getRedirectData()`, `testHost`, `rsms` | `php run.php` |
| [Návratová URL](./return-url) | `completePurchase()` v controlleri: zaplatené, zrušené, vypršaný čas, upravený návrat, návrat inej objednávky, bez parametrov, obnovenie stránky | `php run.php` |
| [Špecifický symbol](./specific-symbol) | platba so `ss`, variabilné symboly pevnej dĺžky, kontroly symbolov | `php run.php` |
| [Celý priebeh s presmerovaním](./full-flow) | e-shop a falošná banka na `php -S`, skript ako prehliadač: platba, zrušenie, upravená suma, podvrhnutý návrat | `bash e2e.sh` |

## Spustenie príkladu {#run-one}

```bash
git clone https://github.com/tomaj/omnipay-vub.git
cd omnipay-vub/examples/return-url
composer install
php run.php            # or: bash e2e.sh in examples/full-flow
```

`examples/ci/run.sh <name>` robí to isté čo CI: skopíruje príklad do dočasného adresára, nainštaluje ho proti vášmu checkoutu (Composer path repository) a porovná výstup s `expected-output.txt`.

Príklady používajú zdieľaný kľúč simulátora platieb (64 × `1`) a vymyslené údaje obchodníka. Vo vašom e-shope načítajte MID a kľúč z premenných prostredia.
