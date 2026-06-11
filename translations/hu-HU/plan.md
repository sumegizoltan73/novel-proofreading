# Implementációs tervek

## Első módosítás - történetszál és esemény (sztori) láncolást kellett megvalósítani. (Implementált)
Az implemetációt és a végrehajtási terv megfogalmazását ChatGPT végezte.

# Storyline és Event Láncolás Végrehajtási Terv (Implementált)

## Összefoglaló

Az első verzió egy könyvön belüli, storyline-alapú, kézzel sorrendezhető eseményláncot vezet be. A meglévő `novel_proofreading_storylines`, `novel_proofreading_events` és `novel_proofreading_common_mapping` táblákra épül, minimális adatbázis-bővítéssel.

A cél: látható legyen, hogy egy történetszál milyen eseményekből áll, milyen sorrendben halad, hol kezdődik/végződik a kéziratban, van-e visszatérés, és van-e lezáró esemény.

## Adatmodell és Backend

- Emelni kell a `NOVEL_PROOFREADING_DB_VERSION` értékét.
- A `novel_proofreading_events` táblát bővíteni kell:
  - `sequence_no INT NOT NULL DEFAULT 0`
  - `chain_role VARCHAR(32) NOT NULL DEFAULT 'STEP'`
- A `chain_role` támogatott értékei:
  - `OPENING`: szálindító esemény
  - `STEP`: normál láncszem
  - `RETURN`: visszatérés egy korábbi vagy szünetelt szálhoz
  - `CLOSING`: lezáró esemény
- A meglévő `storyline_id` marad az elsődleges storyline-event kapcsolat.
- A meglévő `storylines.main_event` mező marad, de a UI-ban a `chain_role = OPENING` eseménnyel összhangban kell kezelni.
- A kézirat-referenciákhoz nem új event mezők készülnek: a meglévő `novel_proofreading_common_mapping` táblát kell használni `type = 'EVENT'` vagy `type = 'STORYLINE'`, `page`, `chapter`, `event_id`, `storyline_id` mezőkkel.
- Backend validáció:
  - event csak azonos `book_id`-hoz tartozó storyline-hoz kapcsolható;
  - `sequence_no` nem lehet negatív;
  - egy storyline-on belül több `RETURN` lehet;
  - `CLOSING` több is lehet, de a statisztika lezártnak tekinti a szálat, ha legalább egy van;
  - storyline nélküli event továbbra is megengedett, de nem kerül láncolt nézetbe.

## Admin UI

- A hiányzó Storyline CRUD-ot pótolni kell az adminban:
  - storyline létrehozás;
  - szerkesztés;
  - törlés;
  - `book_id`, `storyline_name`, `main_event`, `description` mezőkkel.
- Az Events szekciót bővíteni kell:
  - `sequence_no` mező;
  - `chain_role` select;
  - storyline választó csak az adott könyv storyline-jait mutassa, vagy mentéskor hibázzon eltérő könyv esetén.
- Új admin szekció: `Storyline Chains`.
  - Könyv választó.
  - Storyline-onként csoportosított event lista.
  - Sorrend: `sequence_no ASC`, majd `id ASC`.
  - Minden eventnél látszik: sorrend, szerep, event név, leírás, kapcsolt page/chapter referenciák.
  - Gyors mentés mezők: `sequence_no`, `chain_role`, `storyline_id`.
- Alap statisztika storyline-onként:
  - események száma;
  - van-e `OPENING`;
  - van-e `CLOSING`;
  - van-e `RETURN`;
  - első és utolsó ismert page/chapter a `common_mapping` alapján;
  - figyelmeztetés, ha nincs lezárás vagy nincs esemény.

## Implementációs Helyek

- `includes/db.php`
  - DB verzió emelés.
  - `events` tábla bővítése.
  - opcionálisan új type seed értékek nem szükségesek, mert a szerepek event mezőben élnek.
- `includes/admin.php`
  - storyline CRUD függvények hozzáadása.
  - event sanitize/add/update bővítése.
  - könyv-storyline validáció hozzáadása.
  - `Storyline Chains` admin nézet és statisztika lekérdezések.
- `assets/js/admin.js`
  - könyv alapján storyline select szűrés, ha egyszerűen megoldható meglévő inline adatokból.
- `assets/css/admin.css`
  - láncolt nézethez tömör, WordPress-admin kompatibilis táblázat/státusz jelölések.

## Tesztelési Terv

- Aktiválás/frissítés után az új event mezők létrejönnek meglévő adatok elvesztése nélkül.
- Storyline létrehozás, szerkesztés, törlés működik.
- Event mentéskor:
  - érvényes storyline esetén ment;
  - más könyvhöz tartozó storyline esetén hibát ad;
  - üres storyline esetén nem láncolt eventként ment.
- Láncolt nézet:
  - storyline-onként csoportosít;
  - `sequence_no` szerint rendez;
  - jelzi a lezárt és lezáratlan szálakat;
  - jelzi a visszatérést `RETURN` alapján.
- Common mapping referencia:
  - eventhez vagy storyline-hoz kapcsolt `page`/`chapter` megjelenik a láncolt nézetben.
- Regresszió:
  - meglévő Books, Series, Persons, Locations, Datetimes, Professions, Events admin műveletek továbbra is működnek.

## Feltételezések

- Első verzióban sorrendezett lista készül, nem gráf-alapú előző/következő kapcsolatrendszer.
- Az elemzés első körben alap ellenőrzéseket ad, nem teljes dashboardot.
- A kéziratbeli oldal/fejezet referencia forrása a meglévő `novel_proofreading_common_mapping`, nem új event oszlop.
- A meglévő egyoldalas admin felület megtartható, külön React/Vue vagy új admin app nem szükséges.
