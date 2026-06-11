=== Binimuse Geʽez Calendar ===
Contributors: binimuse
Tags: ethiopian calendar, geez calendar, amharic, date converter, calendar
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display and convert Ethiopian (Geʽez) calendar dates in WordPress — with a Gutenberg block, date picker, shortcode, and developer API.

== Description ==

Binimuse Geʽez Calendar brings accurate **Ethiopian calendar** support to WordPress.

Convert and display dates between the Ethiopian (Geʽez) and Gregorian calendars anywhere on your site — in the block editor, with a shortcode, or from your own code. Output supports Amharic and English month names and both Geʽez (፩፪፫) and Arabic numerals.

The Ethiopian calendar has 13 months — twelve months of 30 days plus Pagumē — and runs roughly 7–8 years behind the Gregorian calendar. This plugin handles all of that correctly, including the Ethiopian leap-year cycle.

**Features**

* **Ethiopian Date block** — a native Gutenberg block with a built-in Ethiopian date picker (13 months, leap-aware).
* **`[ethio_date]` shortcode** — for the classic editor, widgets, and theme files.
* **Site-wide defaults** — set your preferred format, language, and numeral system once.
* **REST API** — an endpoint for converting dates programmatically.
* **Developer API** — PHP helper functions for converting Ethiopian and Gregorian dates in your own themes and plugins.
* **Translation-ready** — ships with an Amharic translation.

Conversion uses the established Beyene-Kudlek algorithm via Julian Day Numbers, validated against known reference dates, with correct handling of Pagumē (the 13th month) and the Ethiopian leap year.

== Installation ==

1. Upload the plugin through Plugins → Add New, or install it from the WordPress.org directory.
2. Activate the plugin through the Plugins menu.
3. Set your defaults under Settings → Binimuse Geʽez Calendar.
4. Add the **Ethiopian Date** block in the editor, or use the `[ethio_date]` shortcode.

== Frequently Asked Questions ==

= How do I display an Ethiopian date in a post? =

Insert the **Ethiopian Date** block and pick a date, or use the shortcode, e.g. `[ethio_date language="both"]`.

= What shortcode attributes are supported? =

`date` (defaults to now), `language` (am / en / both), and `numerals` (geez / arabic). Anything you leave out falls back to your saved defaults.

= Does it handle the Ethiopian leap year correctly? =

Yes. The Ethiopian leap year falls the year before the Gregorian leap year, when Pagumē has 6 days instead of 5. This is covered by the plugin's test suite against known reference dates.

= Can I convert dates from my own code? =

Yes — use the provided PHP helper functions, or call the REST endpoint.

= Is it available in Amharic? =

Yes, an Amharic translation ships with the plugin, and it is fully translation-ready for other languages.

== Screenshots ==

1. The Ethiopian Date block with its date picker in the editor.
2. A converted Ethiopian date rendered on the front end.
3. The settings screen.

== Changelog ==

= 1.0.0 =
* Initial release: Ethiopian Date block + date picker, `[ethio_date]` shortcode, settings page, REST API, developer API, Amharic translation.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
