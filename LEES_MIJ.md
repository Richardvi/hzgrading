# local_hzgrading — installatie na deze fix

## Wat is er veranderd
- **classes/external/set_override.php** (nieuw) — webservice die de override opslaat.
- **classes/observer.php** (nieuw) — overschrijft na het opslaan van het rubric-cijfer
  alsnog met de override.
- **db/services.php** (nieuw) — registreert de webservice.
- **db/events.php** (nieuw) — registreert de observer op `\mod_assign\event\submission_graded`.
- **db/install.xml** (nieuw) — schema voor de tabel `local_hzgrading_override`.
- **db/upgrade.php** (nieuw) — zorgt dat de tabel ook op een bestaande installatie
  wordt aangemaakt.
- **classes/hook_callbacks.php** — geeft nu ook `cmid` mee aan de JS-config.
- **amd/src/hzgrading.js** — stuurt overrides nu direct via AJAX naar de eigen
  webservice, i.p.v. hidden form fields te vullen die Moodle toch negeert.
- **lib.php** — legacy `before_footer`-functie verwijderd (overbodig naast de hook,
  zie toelichting in het bestand zelf).
- **db/hooks.php** — ongewijzigd, klopte al.

## Niet meegeleverd
Ik heb geen `version.php`, `templates/rubric_grading_form.mustache`, taalbestanden of
een `styles.css` van je gekregen, dus die zitten niet in deze set. Die hoef je
niet aan te passen, met één uitzondering:

### version.php: verhoog het versienummer
Zet in je bestaande `local/hzgrading/version.php` het versienummer minimaal naar:

```php
$plugin->version = 2026081300;
```

Dit is **verplicht**, anders:
- wordt `db/upgrade.php` niet uitgevoerd en bestaat de tabel niet;
- wordt de nieuwe webservice (`db/services.php`) niet geregistreerd;
- wordt de nieuwe observer (`db/events.php`) niet opgepikt.

## Installatiestappen
1. Kopieer alle bestanden hieronder naar de bijbehorende paden in je Moodle-installatie.
2. Verhoog `version.php` zoals hierboven.
3. Build de AMD-module (nodig omdat Moodle de geminificeerde build uit `amd/build/`
   laadt, niet rechtstreeks `amd/src/`):
   ```bash
   cd /pad/naar/moodle
   npx grunt amd --root=local/hzgrading
   ```
   (of `npx grunt` voor de hele site als je geen specifieke grunt-config hebt).
4. Ga naar **Beheer op siteniveau → Meldingen** om de upgrade te laten draaien.
5. Zet **Debugging** aan op *Developer* tijdelijk, en controleer of er geen
   PHP-warnings/errors verschijnen op de beoordelingspagina.

## Hoe te testen
1. Open een assignment met rubric-beoordeling, ga naar de beoordelingsinterface
   van een student (single grading view).
2. Vink **ND** of **NB** aan → open de Developer Tools → Network-tab, en
   controleer dat er een AJAX-call naar `local_hzgrading_set_override` verschijnt
   met status 200.
3. Klik op **Wijzigingen opslaan**.
4. Controleer in de database:
   ```sql
   SELECT * FROM mdl_local_hzgrading_override WHERE assignid = <id> AND userid = <id>;
   SELECT * FROM mdl_assign_grades WHERE assignment = <id> AND userid = <id>;
   ```
   Het `grade`-veld in `assign_grades` moet nu `0.10000` of `0.20000` zijn
   (of je handmatige waarde), niet het rubric-berekende cijfer.
5. Controleer ook de Cijferlijst van de cursus: het cijfer moet daar hetzelfde zijn.

## Bekende aandachtspunten / dingen om zelf te verifiëren
- Ik ga ervan uit dat `assign_grade_form` een hidden veld `userid` bevat in de
  DOM (nodig om `getCurrentUserId()` in de JS te laten werken). Dit is in alle
  Moodle-versies die ik heb kunnen nakijken het geval, maar controleer dit met
  de browser devtools op jullie exacte 5.2-build, vooral na het wisselen van
  student zonder volledige page reload.
- De observer gaat ervan uit dat `\mod_assign\event\submission_graded` altijd
  vuurt bij het opslaan van een cijfer via de grading-interface — dat is de
  standaard flow, maar niet bij bv. bulk/quick-grading vanuit de
  inzendingentabel (die gebruikt een net iets ander pad). Als jullie ND/NB/manual
  ook via quick grading willen ondersteunen, moet de observer-aanpak worden
  uitgebreid; laat het weten dan werk ik dat ook uit.
