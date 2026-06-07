=== EthioCal — Ethiopian Calendar ===
Contributors: binimuse
Tags: ethiopian calendar, geez, amharic, calendar, date converter
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add the Ethiopian (Geʽez) calendar to WordPress: a Gutenberg block, an Ethiopian date picker, a shortcode, and a developer API.

== Description ==

EthioCal brings accurate Ethiopian (Geʽez) calendar support to WordPress, built the modern way.

Convert and display dates between the Ethiopian and Gregorian calendars anywhere on your site — in the block editor, via shortcode, or from your own code. Output supports Amharic and English month names and both Geʽez (፩፪፫) and Arabic numerals.

**Features**

* **Ethiopian Date block** — a native Gutenberg block with a built-in Ethiopian date picker (13 months, leap-aware).
* **`[ethio_date]` shortcode** — for the classic editor, widgets, and theme files.
* **Site-wide defaults** — set your preferred format, language, and numeral system once.
* **REST API** — `GET /wp-json/ethio-cal/v1/convert` for conversions.
* **Developer API** — PHP helper functions for converting in your own themes and plugins.
* **Translation-ready** — ships with an Amharic translation.

Conversion uses the well-established Beyene-Kudlek algorithm via Julian Day Numbers, validated against known reference dates, with correct handling of the Ethiopian leap-year cycle and the 13th month, Pagumē.

== Installation ==

1. Upload the `ethio-cal` folder to `/wp-content/plugins/`, or install through Plugins → Add New.
2. Activate the plugin through the Plugins menu.
3. Set your defaults under Settings → EthioCal.
4. Add the **Ethiopian Date** block in the editor, or use the `[ethio_date]` shortcode.

== Frequently Asked Questions ==

= How do I display an Ethiopian date in a post? =

Insert the **Ethiopian Date** block and pick a date, or use the shortcode, e.g. `[ethio_date language="both"]`.

= What shortcode attributes are supported? =

`date` (defaults to now), `language` (am / en / both), and `numerals` (geez / arabic). Anything you leave out falls back to your saved defaults.

= Does it handle the leap year correctly? =

Yes. The Ethiopian leap year falls the year before the Gregorian leap year, when Pagumē has 6 days instead of 5. This is covered by the plugin's test suite against reference dates.

= Can I convert dates from my own code? =

Yes — use the provided PHP helper functions, or call the REST endpoint at `/wp-json/ethio-cal/v1/convert`.

= Is it available in Amharic? =

Yes, an Amharic translation ships with the plugin, and it is fully translation-ready for other languages.

== Screenshots ==

1. The Ethiopian Date block with its date picker in the editor.
2. A converted date rendered on the front end.
3. The EthioCal settings screen.

== Changelog ==

= 1.0.0 =
* Initial release: Ethiopian Date block + date picker, `[ethio_date]` shortcode, settings page, REST API, developer API, Amharic translation.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
