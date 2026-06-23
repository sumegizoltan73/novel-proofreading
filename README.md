# novel-proofreading
Novel proofreading and linker - WordPress Plugin

## WordPress version
The plugin was tested on WordPress 7.0.0, but it also works on earlier and future versions because it does not rely on any specific WordPress function or procedure.

## Translations for the README
The README text is also available in Hungarian here: [README.md](/translations/hu-HU/README.md)

## The story
I was thinking about self-publishing my upcoming books, but I realized that without proofreading, the book wouldn't be professional. I started taking notes, but my trilogy ended up being 800 pages long, and I got bogged down in my notes. Even though I'd read my own book through many times, I couldn't keep up with the many different plots, and there were missing endings, and there were places where the story wasn't quite right. I decided to create a tracking program, and this is what I did. The program tracks the main plots, with references to the manuscript (page numbers, major events, and similar data), and then generates statistics about which plot lasted from when to when according to the story, whether there was a return to the storyline, and whether it was concluded in the story. The motivation is thus a need for proofreading, which will serve as a kind of pre-proofreading or a program suitable for inviting a proofreader, but the motivation itself has become the support for these.

## Frontend list views
Content can be inserted into WordPress pages or posts using a shortcode. The page is preferably not a public page, but a page that can only be viewed by administrators or user groups of the Group plugin.
The shortcode:
```text
  [novel_proofreading view="storyline_chains" book_id="1" details="closed"]
  ```

## The development team
I (Zoltan Peter Sumegi) am the development team, with artificial intelligence also involved in the development process.

## Internationalization
The plugin translated to three languages, Hungarian, German and English. The Calendar control is also multi language.

## Releases
Version v1.7.0 is recommended for release and can be downloaded under the Releases section.

## Screenshots
There are some screenshots on the following external page (the blog of the initiator of the initiative): [programozo.info.hu blog](https://www.programozo.info.hu/regeny-lektoralas-es-lancolo-wordpress-plugin/) .

## License
The program is licensed under the MIT License and may be freely used, distributed, integrated, and modified. The license text is included with the code in the LICENCE file.

## Github repository
[sumegizoltan73 - Novel proofreading and linker](https://github.com/sumegizoltan73/novel-proofreading)

## Third-party code
The plugin uses third-party solutions, especially the following:
- [FullCalendar](https://fullcalendar.io)
- [SweetAllert2](https://sweetalert2.github.io)
- [Date Range Picker](https://www.daterangepicker.com)