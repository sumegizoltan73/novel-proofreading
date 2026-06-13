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

## Második módosítás - hivatkozási részletek (oldalszám, fejezet) (Implementált)

Szerintem jó irány az önálló 10. szakasz, de nem nevezném túl általánosan `Common mapping`-nek. UI-ban inkább:

**Kézirat-referenciák** vagy **Előfordulások**

Ez pontosabban leírja, mire való: azt rögzíti, hogy egy szereplő, helyszín, időpont, esemény, történetszál stb. hol fordul elő a kéziratban.

A meglévő adatmodell erre alkalmas: a `novel_proofreading_common_mapping` már tartalmazza a `type`, `page`, `chapter`, `storyline_id`, `event_id`, `person_id`, `location_id`, `time_id` mezőket a [datamodel.md](/Users/sumegizoltan/github/_uj/plugins/novel-proofreading/translations/hu-HU/datamodel.md:141) és [db.php](/Users/sumegizoltan/github/_uj/plugins/novel-proofreading/includes/db.php:174) alapján.

Én ezt a UI-t javasolnám:

1. `Book` választó
2. `Mire vonatkozik?` select  
   Forrás: `novel_proofreading_types`, `category = COMMON_TYPE`
3. Dinamikus második mező:
   - `STORYLINE` esetén storyline select
   - `EVENT` esetén event select
   - `PERSON` esetén person select
   - `LOCATION` esetén location select
   - `TIME` esetén datetime select
   - `MISTAKE`, `SUGGESTION`, `AGREEMENT` esetén inkább leírás + opcionális kapcsolt entitások
4. `Chapter` szövegmező
5. `Page` szövegmező
6. `Description` rövid megjegyzés
7. Mentés

A legfontosabb szabály: normál kézirat-előfordulásnál pontosan egy cél-entitás legyen kitöltve. Tehát ha `type = PERSON`, akkor `person_id` kötelező, a többi célmező üres. Ha `type = EVENT`, akkor `event_id` kötelező, stb. Ez tisztán tartja a táblát.

A `MISTAKE`, `SUGGESTION`, `AGREEMENT` kivételt képezhet. Ezek inkább "ügy" jellegű rekordok, ahol hasznos lehet több kapcsolat is: például egy visszásság érinthet egy személyt, egy eseményt és egy helyszínt is. Ezeknél a UI külön blokkot mutathatna: `Kapcsolódó személy`, `Kapcsolódó esemény`, `Kapcsolódó helyszín`, mind opcionálisan.

A meglévő szakaszokba nem tenném be ezeket a mezőket első körben. Az szétkenné ugyanazt a funkciót sok helyre. Inkább legyen egy egységes "előfordulás napló", és később az adott entitások saját szakaszainál csak olvasható összefoglalót lehet mutatni: például egy személynél "Előfordulások: 2. fejezet / 14. oldal, 5. fejezet / 83. oldal".

Plusz javaslat: a lista nézet legyen szűrhető `Book`, `Type`, `Chapter`, `Entity` szerint. Ez gyorsan nagyon sok rekordot fog tartalmazni, ezért a felvitel mellett a visszakeresés legalább olyan fontos lesz.

Adatmodell-módosítást most nem erőltetnék. Ha később a fejezeteket normalizálni akarod, akkor lehet külön `novel_proofreading_chapters` tábla, de első körben a meglévő `chapter VARCHAR(255)` és `page VARCHAR(64)` elég pragmatikus.

## Harmadik módosítás - Az események néha csak elbeszélések (Implementált)
Nem minden történet rész eseményre alapozott, van ami csak egyszerű elbeszélés, vagy párbeszédes elbeszélés. Így szeretném kiegészíteni az event rögzítését egy Narrative jelölőnégyzettel, ami default NULL vagy 'N', és ha bejelölt, akkor a hozzá tartozó is_narrative adatbázis mező legyen 'Y' értékű. Ehhez a szükséges adatbázis mező hozzáadást is kérem a novel_proofreading_events táblához.

## Negyedik módosítás - Javaslatok kijelzése egy "Has Suggestion" badge segítségével (Implementált)
A 9. Storyline Chains résznél az információs sávban kellene a "has opening" és "missing closing" információk mellé egy harmadik "has suggestion" információs felirat megjelenítése is, ha a történetszálhoz tartozik AGREEMENT vagy SUGGESTION categóriájú mapping. Ez az információs cimke javascript alapú ajax hívással rákattintva meg is kell, hogy jelenítse sweetalert2 popup panelben a description mezőjét a javaslatnak, külön cimkével kijelezve, hogy AGREEMENT vagy SUGGESTION.

Codex / ChatGPT:
Megcsináltam a 9. Storyline Chains részhez.
A változtatás:
Ha egy storyline-hoz van novel_proofreading_common_mapping rekord type IN ('AGREEMENT', 'SUGGESTION') és storyline_id alapján, akkor megjelenik egy kattintható Has suggestion badge.
Kattintásra AJAX hívás lekéri az adott storyline AGREEMENT / SUGGESTION rekordjait.
SweetAlert2 popupban megjelenik minden rekord description mezője, külön AGREEMENT vagy SUGGESTION címkével.
Az admin JS most explicit függ a sweetalert2 scriptre.

## Ötödik módosítás - megerősítés törlés gombra kattintáskor (Implementált)
Megerősítés szükséges a törlés gombra kattintáskor, ezért minden "button remove-item" class name tulajdonságú button type paramétere módosítandó "submit"-ről "button"-ra, és onclick="..." tulajdonság állítandó be a kliens oldali sweetalert2 pluginnal megjelenített megerősítő popup-hoz, ahol ha a mégsem gombra kattint, akkor nem törölhető a tétel. A popup ablekban a "Biztosan törölni szeretné?" üzenet jelenítendő meg.

## Hatodik módosítás - Személy nevek és Alias-ok mapping-ja (Implementált)
A 3.2 Add Person szakasz alatt létre kell hozni egy 3.3 Person name and alias mapping szakaszt. Itt a Nevekhez lehet hozzárendelni egyszerű select vezérlők segítségével az Alias-okat, és ehhez egy mapping tábla létrehozása is szükséges.

Codex / ChatGPT:
Megvalósítottam a 3.3 Person name and alias mapping szakaszt.
A változtatások:
[includes/db.php](/Users/sumegizoltan/github/_uj/plugins/novel-proofreading/includes/db.php): DB verzió 0.9, új tábla: novel_proofreading_person_alias_mapping.
[includes/admin.php](/Users/sumegizoltan/github/_uj/plugins/novel-proofreading/includes/admin.php): új mapping CRUD logika, validáció, action routing, és a 3.2 alatti 3.3 admin UI.
[datamodel.md](/Users/sumegizoltan/github/_uj/plugins/novel-proofreading/translations/hu-HU/datamodel.md): új mapping tábla dokumentálva.
A 3.3 szakaszban lehet:
meglévő person-alias kapcsolatok listázása,
kapcsolat szerkesztése könyv / név / alias selectekkel,
kapcsolat törlése a korábbi SweetAlert megerősítéssel,
új kapcsolat felvitele.
Validáció:
könyv kötelező,
név és alias kötelező,
a két személy nem lehet ugyanaz,
mindkét személynek a kiválasztott könyvhöz kell tartoznia,
DB szinten egyedi: book_id + person_id + alias_person_id.
Ellenőrzések:
php -l includes/db.php sikeres
php -l includes/admin.php sikeres
node --check assets/js/admin.js sikeres
célzott git diff --check sikeres.

## Hetedik módosítás - (i) megjelenítése a név és alias oszlop között ha van alias mapping (Implementált)
A 3.1 List of Persons szakasz táblázatában a Name és Alias oszlop között egy (i) fejlécű oszlop beszúrása szükséges, ahol a sorok között akkor jelenik meg az (i) kattintható szöveg, ha van a novel_proofreading_person_alias_mapping táblában hozzárendelt alias a névhez. A kattintható (i) szöveg egy badge formázású szöveg legyen, világos kék háttérszínnel, és sötét kék szövegszínnel. Ehhez is ajax lekérdezés kell, csak kattintáskor elkérve a tényleges alias-okat.

## nyolcadik módosítás - (job) megejelnítése az info oszlopban a személyek listánál (Implementált)
A 3.1 List of Persons szakasz táblázatában a Name és Alias oszlopban egy (job) feliratú badge is jelenjen meg, ha van a személyhez, vagy a mapping-al hozzárendelt alias-hoz profession rekord (novel_proofreading_professions tábla). A (job) feliratra kattintva hasonló információt jelenítsünk meg, mint az alias (i) badge-je esetén, csak a foglalkozásokat felsorolva a hozzá tartozó leírással együtt.