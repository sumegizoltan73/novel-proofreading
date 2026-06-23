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

A meglévő adatmodell erre alkalmas: a `novel_proofreading_common_mapping` már tartalmazza a `type`, `page`, `chapter`, `storyline_id`, `event_id`, `person_id`, `location_id`, `time_id` mezőket a [datamodel.md](translations/hu-HU/datamodel.md:141) és [db.php](includes/db.php:174) alapján.

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
[includes/db.php](includes/db.php): DB verzió 0.9, új tábla: novel_proofreading_person_alias_mapping.
[includes/admin.php](includes/admin.php): új mapping CRUD logika, validáció, action routing, és a 3.2 alatti 3.3 admin UI.
[datamodel.md](translations/hu-HU/datamodel.md): új mapping tábla dokumentálva.
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

## Nyolcadik módosítás - (job) megejelnítése az info oszlopban a személyek listánál (Implementált)
A 3.1 List of Persons szakasz táblázatában a Name és Alias oszlopban egy (job) feliratú badge is jelenjen meg, ha van a személyhez, vagy a mapping-al hozzárendelt alias-hoz profession rekord (novel_proofreading_professions tábla). A (job) feliratra kattintva hasonló információt jelenítsünk meg, mint az alias (i) badge-je esetén, csak a foglalkozásokat felsorolva a hozzá tartozó leírással együtt.

## Kilencedik módosítás - Ereklyék (Implementált)
A 11. Relics (azaz ereklyék) szakasz is szükséges új beviteli mezők szakaszaként, illetve a Relics listaként. Ehhez adatbázis tábla is szükséges (novel_proofreading_relics), és bővíteni szükséges a novel_proofreading_common_mapping táblát is relics_id oszloppal, és ilyen lenyílóval kombinált beviteli mezővel is el kell látni a 10.2 Kézirathivatkozás hozzáadása szakaszt. Kell egy új rekord a novel_proofreading_types táblába beszúrással, ez COMMON_TYPE category-val a RELIC érték. A szöveges adatok angolul mehetnek, fordítást készítek hozzá majd.

## Tizedik módosítás - Badge alapú címkék hozzáadási lehetősége szükséges az 10.1 Kézirathivatkozások listája résznél (Implementált)
Badge alapú címkék hozzáadási lehetősége szükséges az 10.1 Kézirathivatkozások listája résznél, a táblázat sorainál. Minden sornál egy következő sorban csak badge alapú címke hozzáadása szükséges egy + gombbal. Ez a sor egy "Labels:" (Címkék) szöveggel bevezetve sorolja fel a hozzáadott címkéket, amelyek lehetnek bármilyen többszavas szöveg is. A címkék egy új táblába kerülnek, amely tábla a novel_proofreading_labels és többféle másik táblához kapcsolódhat. Így a datamodel.md fájlban lévő adatszerkezet szükséges hozzá. A címkék hozzáadásához ajax hívás lenne ideális, alkalmanként bekért szöveggel. A címkéhez tároljuk a referenced_id adatot, ami a hozzáadás helye szerinti adat, így a kéziratok listájánál a "LABEL_REF_TYPE" kategóriájú rekord a novel_proofreading_types táblából, amelyek ennél a listánál "CROSSREFERENCE". Fel is kell venni az új rekordokat a novel_proofreading_types táblába "LABEL_REF_TYPE" kategóriával és a következő értékekkel: (STORYLINE, EVENT, PERSON, LOCATION, TIME, PROFESSION, STORYLINE_CHAIN, CROSSREFERENCE).
A badge hozzáadási lehetőség egyenlőre csak a Kézirathivatkozások listájánál kell. A badge színe legyen a "novel-proofreading-badge is-info" css osztályhoz hasonló új szín, talán szürke, fehér betűkkel.

## Tizenegyedik módosítás - Scroll tábla szükséges a listáknál (Implementált)
Átalakítás szükséges a listáknál, scroll-table viselkedés mintát kell beállítani a "widefat striped" css osztállyal ellátott table vezérlőelemeknél. Egy wrapper div-ben kell elhelyezni a table tag-et, ami div magassága 300px lesz, és overflow-y tulajdonsága auto. A táblázat head elemét position:sticky és top:0px és background-color:white stílusokkal kell ellátni. Célszerűen stíluslappal lesznek beállítva az új értékek.

## Tizenkettedik módosítás - Bootstrap-szerű tab működés (Implementált)
Codex / ChatGPT:
Megvalósítottam a fő szakaszokat Bootstrap-szerű tab működéssel.
Változott:
[includes/admin.php (line 4171)](includes/admin.php:4171): az eddigi h2 + Show / Hide szerkezet helyett egy h2.nav-tab-wrapper tab navigáció van.
A fő wrapper div-ek tabpanel panelek lettek, stabil id-val, aria-labelledby kapcsolattal és Bootstrap-mintájú tab-pane show active osztályokkal.
A gombokon van data-bs-toggle="tab" és data-bs-target, de nem húztam be Bootstrap függőséget.
[assets/js/admin.js (line 27)](assets/js/admin.js:27): saját tabváltó logika kezeli az aktív tabot, a panelek rejtését, hash-t, sessionStorage-t és billentyűzetes navigációt.
[assets/css/admin.css (line 13)](assets/css/admin.css:13): bekerült a tab sor és tab panelek admin stílusa.
Kivettem a régi show_hide() függvényt és a Show / Hide ... gombokat.

## Tizenharmadik módosítás - Címke csoportokkal szűrés (Implementált)
Szükség van egy címkecsoportokkal való szűrési lehetőségre, amikor az összetartozó címkék alapján történik úgy szűrés, hogy minden címkéhez tartozó sor megjelenjen a szűrés után, ami az adott csoportban összetartozó címke.
Az összetarrtozás alapja a fejezet vagy az oldalszám például a kézirathivatkozásoknál, külön switch alapú programozással meghatározva a címkéhez tartozó kategória (referenced_type_id) alapján.
Így a címkék kijelzése egy-egy cimke csoporttal történik, egy címke több csoportban is szerepelhet, és a csoport egészére lehetséges kattintással történik a szűrés, vagy a szűrés kikapcsolása. Kattintás után így a csopoirt közös halványszürke hátterével lehet jelezni, hogy bekapcsolat a szűrés.
Ezt a funkciót egyenlőre csak a Kézirathivatkozások listájánál kell bevezetni.

Codex / ChatGPT:
Elkészült a címkecsoportos szűrés a Kézirathivatkozások listájánál.
Módosítottam:
[includes/admin.php (line 145)](includes/admin.php:145): bekerült a címkecsoport-képzés switch alapú helperrel. A CROSSREFERENCE címketípusnál most fejezet és oldalszám alapján képez csoportokat.
[includes/admin.php (line 5768)](includes/admin.php:5768): a kézirathivatkozás sorok és címkesorok megkapják a szűréshez szükséges data-label-ids adatot.
[includes/admin.php (line 5876)](includes/admin.php:5876): a címkék csoportosítva jelennek meg, egy csoport gombként kattintható.
[assets/js/admin.js (line 341)](assets/js/admin.js:341): kattintásra a csoport szűr, újra kattintásra kikapcsolja a szűrést.
[assets/css/admin.css (line 157)](assets/css/admin.css:157): az aktív címkecsoport halványszürke hátteret kap.
A szűrés úgy működik, hogy egy csoport címkéire kattintva minden olyan kézirathivatkozás sor megjelenik, amelynek van bármelyik címkéje a kiválasztott csoportból. A fő sor és a hozzá tartozó címkesor együtt rejtőzik/jelenik meg.

## Tizennegyedik módosítás - Címke csoportokkal szűrés II. (Implementált)
Rosszul fejeztem ki magam az előbb, nem a sorok között kellene csoportokba foglalni a címkéket, bár érdekes működést hozott, de inkább ott elegendő ha csak a címkék szerepelnek. A csoportokhoz használt jelölések, vagy logika maradjon, mert jó az ilyen elvű összeválogatás, így a data-label-ids és a data-label-group-key a button-on maradjon meg, de más funkció kell hozzá, csak egy popup ablakot kell megjelenítsen. A popup ablakban legyen felsorolva a data-label-ids labelje, csoportosítva, és a data-label-broup-key -ből a title, alatta felsorolva a címkéket. A címke group alapú szűrés lehetősége a táblázat felett szükséges, külön sávban, a meglévő könyv és típus szűrés alatt. Ott pedig fel kell sorolni minden címkét, de csoportokba rendezve, attól függően, hogy fejezet alapján tartozik-e hozzá más címke, vagy az oldalszám alapján tartozik-e hozzá más címke.

Codex / ChatGPT:
Átállítottam a működést a pontosítás szerint.
Most így működik:
A kézirathivatkozás sorok alatt újra csak egyszerű címke badge-ek látszanak.
A soroknál megmaradtak a csoportadatok kis (i) popup gombokon: data-label-group-key, data-label-group-title, data-label-ids.
Az (i) gomb már nem szűr, csak popupot nyit, benne a csoport címe és a hozzá tartozó címkék listája.
A tényleges címkecsoport alapú szűrés átkerült a Kézirathivatkozások táblázat fölé, a könyv/típus szűrő alá.
A felső szűrősáv fejezet és oldalszám alapú csoportokra bontva listázza a címkéket; ezekre kattintva szűr, újra kattintva kikapcsolja.

## Tizenötödik módosítás - Címke csoportokkal szűrés III. (Implementált)
Ezt a szűrést akartam, csak a szűrésnél lévő címkecsoportokat össze kellene voni, nem kell kiírni a fejezet és oldalszám adatokat, de egy csoportba azok a címkék tartozzanak, amelyek az oldalszám alapján összetartoznak, és pluszba még hozzá azok a címkék is, amelyek ezen oldalszámokon keresztül más címkéket is tartalmaznak. A csoport így leszűr olyan címkéket is, amelyek csak köztes kapcsolat révén tartoznak össze. Konkrétan a következő lesz az első címkecsoport:
1985, Házassági évfordulók (Mert: label = "1985" és fejezet = "1985-ben" ->  label = "Házassági évfordulók" is és label = "1985" is, és fejezet = "Titanic" -> ... lesz még ide talán label = "időutazás", ha a "Titanic" fejezetet feldolgozom, és rakok ott ki ilyen címkét.)
A címkék láncolata is érdekes összefoglalva, ha van valamilyen kapcsolat a címkékkel jelzett soroknál.

Codex / ChatGPT:
Átalakítottam a címkecsoport-szűrést láncolt, összevont csoportokra.
Most a felső szűrősávban már nem külön “fejezet” és “oldal” csoportok jelennek meg, hanem összefüggő címkeláncok. A logika lényege:
azonos fejezeten vagy oldalon együtt szereplő címkék összekapcsolódnak,
ha egy címke másik fejezeten/oldalon további címkékkel is kapcsolatban van, azok is ugyanabba a csoportba kerülnek,
a szűrőgomb címe a csoport címkéiből áll, például: 1985, Házassági évfordulók,
a szűrés továbbra is data-label-ids alapján működik, így a köztes kapcsolaton keresztül tartozó sorok is megjelennek.
A soroknál lévő (i) popup gombok megmaradtak, és a popupban most a címkék mellett a kapcsolati pontok is láthatók, például mely fejezet/oldal kapcsolta össze a címkéket.

## Tizenhatodik módosítás - Címke csoportokkal szűrés IV. (Implementált - nem lett jó, visszavonásra kerül)
Használd ezt a lekérdezést a címkecsoportok előállításához: 
SELECT
	l.label,
    m1.id as mapping_id,
    m1.page,
    m1.chapter
FROM wp_novel_proofreading_labels l
INNER JOIN wp_novel_proofreading_types t
	ON t.id = l.referenced_type_id
JOIN wp_novel_proofreading_common_mapping m1
	ON m1.id = l.referenced_id
    OR EXISTS (
    	SELECT m2.id
        FROM wp_novel_proofreading_common_mapping m2
        WHERE m2.page = m1.page OR m2.chapter = m1.chapter
    )
WHERE 
		t.name = 'CROSSREFERENCE'
GROUP BY m1.id, m1.page, m1.chapter, l.label
ORDER BY CONVERT(m1.page, UNSIGNED), m1.chapter, REPLACE(l.label, ' ', '');

PHP segítségével használd a soron következő csoport megállapításához a sorbarendezésnél előálló eltéréseket a mapping_id, majd a page, majd a chapter esetén úgy, hogy ha a soron kövekező rekordnál nem lenne azonos a chapter sem már, de az oldalszám még marad azonos, akkor az még változatlanul a korábbi csoport.
Jobb megoldást nem tudtam kitalálni, a LEAD() OVER függvény lenne jobb, de ez a logika nem valósítható meg vele.
Az admin.php-ban a lista szűréséhez ezt a csoportosítást nem kell használni, csak egy újabb sávban ki kell csoportosítva írni, egyszerű szövegként, a csoprotokat keretezve valahogy, button-al is jó közrefogni, de nem kell rá eseménykezelő.
A címkecsoportokon belül csak egyszer kell kiírni minden címkét, így a label_id vagy mapping_id nem lesz használva, helyette a szöveges adattal lesz majd szűrés.

## Tizenhetedik módosítás - Címke csoportokkal szűrés V. (Implementált - végleges jó változat)
A Related label groups feliratú szűrő csoportok lesznek jók, csak deduplikáció szükséges, nem kellenek fejlécszövegek, és egy csoportban csak egyszer kell szerepeltetni egy címkét. A csoportok jók, csak ha olyan csoport adódik, ahol ugyanazok a deduplikált elemek, mint egy másik korábbi csoportban, akkor az a csoport nem szükséges hogy megjelenjen másodszor is.
A szűrés ezek után csak a címke szöveg alapján kell úgy, hogy bármelyik címke ha tartozik a rekordhoz a csoportból, akkor megjelenhet. 

Codex / ChatGPT:
Átállítottam a Related label groups szűrést a kért működésre.
A változás lényege:
Nincs külön fejlécszöveg a csoportsávban.
Egy csoporton belül minden címke csak egyszer jelenik meg, címkeszöveg alapján deduplikálva.
Ha két csoport deduplikált címkeszöveg-készlete azonos, csak az első jelenik meg.
A szűrés már nem label_id, hanem címkeszöveg alapján történik: ha a rekord bármely címkéje szerepel a kiválasztott csoportban, a rekord megjelenik.
A korábbi SQL-es “Sequential label groups” extra sávot és helper-t eltávolítottam.

## Tizennyolcadik módosítás - Címkék megjelenítése a 10.1 Kézirathivatkozások listájában (Implementált - címkék is a végleges jó változattal)
Vissza kell alakítani a 10.1 Kézirathivatkozások listájában a címkék megjelenítését, egyszerű módozatra, csak olyan címke jelenjen meg, ami a Kézirathivatkozáshoz hozzá lett adva, és csak egyszer. Így nem szükséges a kattinthatóság sem, a popup megjelenítés sem kell itt. 

## Tizenkilencedik módosítás - 10.3 Cselekményszál hivatkozás (Implementált)
10.3 Cselekményszál hivatkozás címmel be kell vezetni egy cselekményszál láncolást, ahol cselekményszálhoz lehet hivatkozni másik cselekményszálat. Ez a könyv kiválasztót, cselekményszál kiválasztót, és egy harmadik "Kapcsolódó cselekményszál" elnvezéssel ellátott cselekményszál kiválasztót tartalmaz majd csak. Listás nézet nem kell itt hozzá. A cselekményszál-láncok résznél az Események:... kezdetű közös információs sávok alatt külön sávban bal oldali margóval kell a közvetlen leágazásokat egymás alá felsorolni. A felsorolás kattintható legyen, és ezen a szakaszon navigáljon oda a csatolt Cselekményszálhoz, egyszerű oldalon belüli <a> tagekkel, ahol id-val ellátott tag-et kap a h3 tag, és a kattintható hivatkozás felsorolás meg a href-es értékét.

Codex / ChatGPT:
Elkészült a 10.3 Cselekményszál hivatkozás funkció.
A lényeg:
Új DB tábla: novel_proofreading_storyline_links, DB verzió 1.3-ra emelve: [includes/db.php (line 4)](includes/db.php:4)
Új mentési action: add_storyline_link, validációval, duplikáció- és önhivatkozás-ellenőrzéssel: [includes/admin.php (line 3476)](includes/admin.php:3476)
Új 10.3 form a kézirathivatkozások tabon: könyv, cselekményszál, kapcsolódó cselekményszál: [includes/admin.php (line 6421)](includes/admin.php:6421)
A Cselekményszál-láncok nézetben a h3 kapott belső anchor célpontot, alatta pedig megjelennek a közvetlen leágazások kattintható linkként: [includes/admin.php (line 5988)](includes/admin.php:5988)
Magyar fordítás és .mo frissítés is bekerült az új feliratokra.

## Huszadik módosítás - (i) a Cselekményszál-láncok résznél (Implementált)
A Cselekményszál-láncok résznél a szűrés gomb mellett jobra egy nyomógombszerű (i) szükséges, ami kattintás után megjeleníti a cselekményszálak leírását, de a cselekményszál szakaszoknál az elnevezés alatt külön sávban. A kattintható kapcsolódó cselekményszál alatt is a hozzátartozó leírás külön legyen megjelenítve, hasonló behúzással, mint az elnevezés. Az (i) gomb alapértelmezetten kikapcsolt, tehát nincs alapértelemzetten megjelenítve az extra leírás.

Codex / ChatGPT:
Implemented the storyline-description toggle in the Storyline Chains section.
Changed:
Added an i button next to Filter, default off.
Clicking it shows/hides storyline descriptions.
Main storyline descriptions render under the storyline title in a separate bar.
Related storyline descriptions render under each related link with the same branch indentation.
Related storyline query now loads description.
Added CSS/JS and updated PO/MO translations for the tooltip.

## Huszonegyedik módosítás - Útvonal a cselekményszál hivatkozásoknál, előző cselekményszálak felsorolásával (Implementált)
Útvonalkénti szerepet kapnak a cselekményszál hivatkozások az előző cselekményszálak felsorolásával a Cselekményszál-láncok tab-on.
Az előző, és a következő cselekményszál hivatkozások között külön sorban egy "|-> Ez a cselekményszál" szöveget kell megjeleníteni, így jelölve azt is, ha nincsenek előző cselekményszálak, és újonnan indított cseelkményszálról van szó. Az előző hivatkozások behúzása legyen a mostani behúzás, az "|-> Ez a cselekményszál" szöveg 100 px-el beljebb legyen, majd még 100 pixellel beljebb a mostani következő hivatkozások linkje.

Codex / ChatGPT:
Elkészítettem a Cselekményszál-láncok útvonalas megjelenítését.
Módosítások:
[includes/admin.php (line 3773)](includes/admin.php:3773): bekerült a bejövő, “előző” cselekményszál-hivatkozások gyűjtése.
[includes/admin.php (line 6059)](includes/admin.php:6059): a nézet most sorrendben mutatja az előző hivatkozásokat, a |-> Ez a cselekményszál sort, majd a következő hivatkozásokat.
[assets/css/admin.css (line 81)](assets/css/admin.css:81): beállítottam a behúzásokat: előző 24px, aktuális +100px, következő még +100px.
Frissítettem a .po, .mo és .pot fordításokat az új szöveghez.

## Huszonkettedik módosítás - Személy jellemvonás (Implementált)
Egy új adatrögzítési lehetőség szükséges a 10.4 Személy jellemvonása résszel.
Ez a rész a 10.3 Cselekményszál hivatkozás rész alatt kap helyet, mert a mapping táblába rögzít adatokat.
Egy mapping rögzítési lehetőség lesz ez, fix PERSON típussal.
A beviteli lehetőségek a következők: könyv, Hivatkozott elem (személy, helyszín, idő, és új jellemvonás választó), és fejezet, oldal, leírás. Vagyis a cselekményszál, és esemény, valamint az ereklye megadása itt nem lesz lehetséges. 
A jellemvonás választó az a mapping tábla person_related_subtype mezőjébe kerül be, és a PERSON_SUBTYPE kategóriájú típusokat lehet kiválasztani. A mezőbe a szöveges érték kerüljön be.


Codex / ChatGPT:
Elkészült a 10.4 Személy jellemvonása rögzítés.
Módosítások:
Új 10.4 admin űrlap a 10.3 alatt: könyv, személy, helyszín, idő, jellemvonás, fejezet, oldal, leírás: [includes/admin.php (line 6713)](includes/admin.php:6713)
Mentés a novel_proofreading_common_mapping táblába type = PERSON értékkel, a jellemvonás a person_related_subtype mezőbe kerül: [includes/admin.php (line 4364)](includes/admin.php:4364)
A jellemvonás csak PERSON_SUBTYPE kategóriából választható.
Storyline, event és relic mező nincs az új formban, és a mentés is null értékkel zárja ki őket.
A 10.1 listában a PERSON típusú hivatkozásnál megjelenik a jellemvonás, illetve a mentett hely/idő kontextus is: [includes/admin.php (line 3960)](includes/admin.php:3960)
Frissítettem a .po, .pot és .mo fordítási fájlokat.

## Huszonharmadik módosítás - Frontend shortcode segítségével listás nézet a Cselekményszál-láncok részére (Implementált)
Frontend shortcode segítségével listás nézet szükséges a Cselekményszál-láncok részére.
Kérnék javaslatot és tervet ehhez, mert ennek a mintájára lenne más listás nézet is, például Helyszínek és Személyek és időpontok kombinált listás nézet, meg más hasonló listás nézet még.
Jelenleg csak a Cselekményszál-láncok listás nézet szükséges, de egy "Részletek" kapcsoló segítségével megjelenítésre kerülne az adott cselekményszál személyei, helyszínei, időpontjai, ereklyéi külön sávban mindegyik az útvonal sáv alatt. Nem szükséges egyéb adat ehhez, további részletezés csak a többi jövőbeli listánál lenne. A felsorolás badge kontolként lenne ideális, valamilyen halvány kiemeléssel, vagy szürke, vagy sárga kiemeléssel, de egészen fakó színűvel, és a betűszín lenne változó típusonként, így például a személyek felsorolásánál sötét kék betűszín lehetne, a helyszínek felsorolásánál világosabb kék lehetene.

# Frontend Cselekményszál-Lánc Lista Shortcode

## Summary
A meglévő `[novel_proofreading]` shortcode kapjon `view` alapú működést, első új nézetként:

```text
[novel_proofreading view="storyline_chains" book_id="123" details="closed"]
```

A meglévő alapértelmezett shortcode-viselkedés marad változatlan. A Cselekményszál-lánc frontend nézet olvasói lista lesz, admin szerkesztési mezők nélkül, a későbbi Helyszínek / Személyek / Időpontok listanézetekhez újrahasználható renderelési mintával.

## Key Changes
- `includes/shortcode.php` kapjon dispatch logikát:
  - ha nincs `view`, marad a jelenlegi naptár renderelés;
  - `view="storyline_chains"` esetén új frontend lánclista renderelő fut;
  - támogatott attribútumok: `book_id`, `details`;
  - `book_id=0` vagy hiányzó `book_id` minden könyvet listáz.
- A láncadatokat a meglévő `novel_proofreading_get_storyline_chains($book_id)` alapján kell használni, de a frontend renderelés ne jelenítsen meg admin táblázatot, mentést, szűrő űrlapot vagy szerkeszthető mezőt.
- Készüljön külön helper a kapcsolt részletekhez:
  - személyek, helyszínek, időpontok, ereklyék lekérése `novel_proofreading_common_mapping` alapján;
  - egy cselekményszálhoz tartozzon minden olyan rekord, amely közvetlenül a `storyline_id`-ra mutat, vagy a cselekményszál valamelyik eseményére (`event_id`) mutat;
  - azonos entitásokat deduplikálni kell ID alapján;
  - megjelenítési sorrend: első előfordulás sorrendje, majd név.
- A frontend markup legyen jövőbiztos, például:
  - `.novel-proofreading-list`
  - `.novel-proofreading-list-item`
  - `.novel-proofreading-storyline-route`
  - `.novel-proofreading-detail-bands`
  - `.novel-proofreading-detail-band`
  - `.novel-proofreading-badge is-person|is-location|is-time|is-relic`
- A „Részletek” kapcsoló alapból zárt legyen (`details="closed"`), és egy globális kapcsolóval mutassa/elrejtse minden cselekményszál részletsávját.
- A részletsávok az útvonal sáv alatt jelenjenek meg, külön sorban:
  - `Személyek: [badge] [badge]`
  - `Helyszínek: [badge] [badge]`
  - `Időpontok: [badge] [badge]`
  - `Ereklyék: [badge] [badge]`
- Badge stílus:
  - nagyon halvány, közös háttér: világosszürke vagy fakó sárgás árnyalat;
  - típusonként eltérő betűszín;
  - személy: sötétkék;
  - helyszín: világosabb kék;
  - időpont: tompa lila vagy szürkéskék;
  - ereklye: tompa barna vagy okker.

## Public Interface
- Elsődleges shortcode:
  ```text
  [novel_proofreading view="storyline_chains"]
  ```
- Könyvre szűrt használat:
  ```text
  [novel_proofreading view="storyline_chains" book_id="123"]
  ```
- Részletek alapból nyitva, ha később szükséges:
  ```text
  [novel_proofreading view="storyline_chains" details="open"]
  ```
- A későbbi listanézetek ugyanebbe a mintába illeszkedjenek:
  ```text
  [novel_proofreading view="locations"]
  [novel_proofreading view="person_time_locations"]
  ```

## Test Plan
- Ellenőrizni kell, hogy a régi `[novel_proofreading]` továbbra is a jelenlegi naptárnézetet adja.
- Ellenőrizni kell, hogy `[novel_proofreading view="storyline_chains"]` minden könyv cselekményszál-láncát listázza.
- Ellenőrizni kell, hogy `book_id` attribútummal csak az adott könyv láncai jelennek meg.
- Ellenőrizni kell a „Részletek” kapcsolót:
  - alapból rejtett;
  - kattintásra megjelennek a személy/hely/idő/ereklye badge sávok;
  - újabb kattintásra eltűnnek.
- Ellenőrizni kell az üres állapotokat:
  - nincs lánc;
  - van lánc, de nincs kapcsolt személy/hely/idő/ereklye;
  - duplikált mapping rekordok esetén egy badge csak egyszer jelenik meg.
- Ellenőrizni kell frontend oldalon, hogy nem töltődnek be feleslegesen admin szerkesztő UI-elemek.

## Assumptions
- A frontend könyvszűrés nem kap külön űrlapot; csak shortcode attribútummal történik.
- A kapcsolt részletek a cselekményszál közvetlen mappingjeiből és a lánc eseményeinek mappingjeiből állnak össze.
- A jelenlegi admin láncnézet működése és adatsorrendje marad változatlan.


Codex / ChatGPT:
Implemented.
Main changes:
Added view dispatch to [includes/shortcode.php (line 10)](includes/shortcode.php:10), preserving the old default calendar shortcode.
Added [novel_proofreading view="storyline_chains" book_id="123" details="closed"].
Added frontend-only storyline chain rendering with global Details toggle, route links, stats badges, and detail badge bands for persons, locations, datetimes, and relics.
Added detail aggregation/deduping from direct storyline mappings and linked event mappings.
Added frontend styles in [assets/css/style.css (line 1)](assets/css/style.css:1).
Updated POT/PO/MO translations for Details, Hide details, and Persons.
Verification:
php -l passed for changed/relevant PHP files.
git diff --check passed.
Recompiled hu_HU, en_US, and de_DE .mo files.
Note: translations/hu-HU/plan.md was already modified in the worktree and I did not touch it. I did not run a live WordPress/browser runtime test in this environment.