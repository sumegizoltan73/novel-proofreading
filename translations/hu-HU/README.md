# novel-proofreading
Regény lektorálás és láncoló - WordPress Plugin

## WordPress verzió
A plugin WordPress 7.0.0 alatt tesztelt, azonban működik korábbi vagy jövőbeli verziók alatt is, mert nem használ verzió specifikus megoldásokat.

## Fordítások a README szövegéhez
A README szövege elérhető Angol nyelven is itt: [README.md](/README.md)

## A történet
Saját magam általi kiadáson gondolkoztam a készülő könyveim esetén, de szembesültem azzal, hogy lektorálás nélkül nem lesz professzionális a könyv. Elkezdtem jegyzeteket készteni, de a trilógiám 800 oldalasra sikerült, és belebonyolódtam a jegyzeteimbe. Hiába is olvastam már sokadszor végig a saját könyvem, nem birok megbírkózni a nagyon sokféle cselekménnyel, és maradtak ki lezárások, és van ahol nem kerek így a történet. Elhatároztam, hogy egy nyilvántartó programot készítek, és ez lesz az. A program a főbb cselekményeket nyilvántartja, a kéziratra hivatkozással (oldalszámok, főbb események, és hasonló adatokkal), és majd statisztikákat készít arról, hogy mely cselekméynek mikortól mikorig tartanak a történet szerint, van-e visszatérés a történetszálhoz, és az lezárásra került-e a történetben. A motiváció így egy lektorálás igénye, ami amolyan előlektorálásként, vagy lektor meghívására alkalmas programként szolgál majd, de ezek támogatása lett maga a motiváció.

## A Frontend listás nézetei
A WordPress oldalakon, vagy bejegyzésekben shortcode segítségével beilleszthető a tartalom. Az oldal célszerűen nem nyílvános oldal, hanem csak az adminisztrátorok, vagy a Group plugin felhasználócsoportjai által megtekinthető oldal.
A shortcode:
```text
  [novel_proofreading view="storyline_chains" book_id="1" details="closed"]
  ```

## A fejlesztő csapat
Én (Sümegi Zoltán Péter) vagyok az egyetlen fejlesztő, Mesterséges Intelligenciát is használva a fejlesztésekben.

## Többnyelvűsítés
A plugin három nyelvre lett fordítva, Magyar, Angol és Német nyelvekre. A Calendar vezérlő szintén több nyelvű.

## Kiadások
A v1.7.0 verzió kiadásra ajánlott, letölthető a Releases rész alatt.

## Képernyőképek
A következő külső oldalon (a kezdeményezés indítójának blogjában) van néhány képernyőkép: [programozo.info.hu blog](https://www.programozo.info.hu/regeny-lektoralas-es-lancolo-wordpress-plugin/) .

## Liszensz
A program MIT Liszensz alatt liszenszelt, szabadon felhazsnálható, és fejelszthető. A liszensz szövegét tartalmazza a kód repozitorija.

## Github repository
[sumegizoltan73 - Novel proofreading and linker](https://github.com/sumegizoltan73/novel-proofreading)

## Harmadik fél kódjai
A plugin használ harmadik féltől származó komponenseket, különösen a következőket:
- [FullCalendar](https://fullcalendar.io)
- [SweetAllert2](https://sweetalert2.github.io)
- [Date Range Picker](https://www.daterangepicker.com)