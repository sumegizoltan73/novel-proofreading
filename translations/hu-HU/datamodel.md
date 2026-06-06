# Vázlat az adatmodellhez

## Történetszál
- név
- rövid összefoglaló
- fontosabb események ...
- Fő esemény
- Főbb szereplők ...
- Kiemelt fő szereplők ...
- Időpontok számított értékekkel ...
- Oldalszamok es fejezetek ...
- Elnagyolt jelzővel
- Javasolt módosítás a történetszálhoz (lektori javaslat átfogalmazásra, kiegészítésre, lezárásra)
- javaslattevő (userid)
- rögzítette (userid)
- modósította (userid)

## Esemény
- Név
- rövid összefoglaló 
- Időpontok ...
- Helyszín ...
- Kihatás szereplőre ...
- Kihatás másik eseményre vagy történetszálra ...
- Lezárás van-e benne, kire, mire? ...
- Oldalszámok és fejezetek ...
- Elnagyolt jelzővel 
- Javasolt módosítás a eseményhez (lektori javaslat átfogalmazásra, kiegészítésre, lezárásra)
- javaslattevő (userid)
- rögzítette (userid)
- modósította (userid)

## Személy 
- név (nem kötelező, nélküle csak nem mérvadó alias)
- Mérvadó-e?
- rögzítette (userid)
- modósította (userid)
- Alias ...
    - Előfordulásai ...
    - Sejtett előfordulásai ...
    - Született , 
    - Életkor vagy év adatok az előfordulásnál
    - Titulus vagy rang vagy foglalkozás 
    - Hely ...
    - Elnagyolt jelzővel 

## Helyszín
- név
- Alias ...
- hely rövid leírása
- rögzítette (userid)
- modósította (userid)
- Mérvadó-e?
- Párhuzamos világ-e?
    - Előfordulásai (időbeli, történetbeli) ...
    - Elnagyolt lejzővel

## Időpontok
- név
- Dátum vagy intervallum, kor
- típus (születési adat, esemény ideje, életút adat)
- Előfordulásai (történetbeli) ...
- Elnagyolt jelzővel
- rögzítette (userid)
- modósította (userid)

## Visszásságok
- név
- visszásság rövid leírása
- jelentette (userid)
- modósította (userid)
- megoldott-e?
- megoldás ideje
- megoldás típusa (átírat, kiegészítés, törlés)
- hely ...
- időpont ...
- személy ...
- esemény ...
- történetszál ...

## Lektori javaslat
- név
- javaslat rövid leírása
- rögzítette (userid)
- modósította (userid)
- megoldott-e?
- megoldás ideje
- megoldás típusa (átírat, kiegészítés, törlés)
- hely ...
- időpont ...
- személy ...
- esemény ...
- történetszál ...

## Módosítási egyezség (Lektor és szerző közös megegyezésén alapuló)
- név
- javaslat rövid leírása
- rögzítette (userid)
- modósította (userid)
- megoldott-e?
- megoldás ideje
- megoldás típusa (átírat, kiegészítés, törlés)
- hely ...
- időpont ...
- személy ...
- esemény ...
- történetszál ...

### Magyarázat a jelölésekhez
- saját tulajdonság
    - kapcsoló tábla, mapping tábla tulajdonságai
... többszörös értékek, jellemzően külön tábla, vagy mapping tábla többszörös rekordjai

# Adatmodell

## novel_proofreading_books (IMPLEMENTED)
- id
- title
- subtitle
- author
- year
- status
- created_at
- updated_at

## novel_proofreading_types
- id
- name
- category  (COMMON_TYPE, SOLVED_TYPE, DATETIME_TYPE, PRESENCE_TYPE, PERSON_SUBTYPE, AREA_TYPE)
- created_at
- created_by
VALUES of COMMON_TYPE : (STORYLINE, EVENT, PERSON, LOCATION, TIME, MISTAKE, SUGGESTION, AGREEMENT)
VALUES of SOLVED_TYPE : (REWRITTED, ADDITION, DELETED)
VALUES of DATETIME_TYPE : (BIRTHDATE, EVENTDATE, AGE, LIFEPATH, SERVICETIME)
VALUES of PRESENCE_TYPE : (LOCATION, TIME, PERSON, EVENT, STORYLINE)
VALUES of PERSON_SUBTYPE : (ACTOR, HIGHLIGHTED, 2ND_ACTOR, EVIL, HERO)
VALUES of AREA_TYPE : (SPACE, GALAXYS, EARTH, COUNTRY, CITY)

## novel_proofreading_common_mapping
- id
- book_id
- type (STORYLINE, EVENT, PERSON, LOCATION, TIME, MISTAKE, SUGGESTION, AGREEMENT)
- person_related_subtype (ACTOR, HIGHLIGHTED, 2ND_ACTOR, EVIL, HERO)
- description VARCHAR(2048)
- page
- chapter
- storyline_id NULL
- event_id NULL
- person_id NULL
- location_id NULL
- time_id NULL
- created_at
- created_by
- updated_at
- updated_by
- suggested_at
- suggested_by
- to_be_solved (Y, N) DEFAULT 'N'
- is_solved (Y, N)
- solved_at
- solved_type (REWRITTED, ADDITION, DELETED)

* In the Storyline the HERO type PERSON is sometimes EVIL or 2ND_ACTOR.

## novel_proofreading_datetimes
- id
- book_id
- name
- time_description (date, range, age, named time) TEXT
- description
- time_type (BIRTHDATE, EVENTDATE, AGE, LIFEPATH, SERVICETIME)
- is_inaccurate (Y, N) DEFAULT 'N'
- created_at
- created_by
- updated_at
- updated_by

## novel_proofreading_professions
- id
- book_id
- person_id
- profession_name
- description
- is_inaccurate (Y, N) DEFAULT 'N'
- created_at
- created_by
- updated_at
- updated_by

## novel_proofreading_persons
- id
- book_id
- name
- alias
- description
- is_inaccurate (Y, N) DEFAULT 'N'
- created_at
- created_by
- updated_at
- updated_by

## novel_proofreading_locations
- id
- book_id
- name
- alias
- area    (SPACE, GALAXYS, EARTH, COUNTRY, CITY)
- region
- description
- is_in_alternative_universe (Y, N) DEFAULT 'N'
- is_inaccurate (Y, N) DEFAULT 'N'
- created_at
- created_by
- updated_at
- updated_by

## novel_proofreading_presence_mapping
- id
- book_id
- common_mapping_id
- description
- is_suspected (Y, N) DEFAULT 'N'
- is_inaccurate (Y, N) DEFAULT 'N'
- created_at
- created_by
- updated_at
- updated_by
