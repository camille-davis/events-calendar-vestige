# Events Calendar (Extension for Vestige Core Wordpress plugin)

Front and back end Wordpress plugin development.

Displays a calendar of events (custom post type from Vestige theme) for a given month and year.
* Featuring: PHP OOP, jQuery, CSS transitions, WAI-ARIA, semantic HTML
* No-js fallback required by [City of San Francisco Web Accessibility Guidelines](https://sfgov.org/web-accessibility-standards-and-guidelines)
* Could be adapted to other custom post types by modifying the `fetch_events` method in `includes/class-calendar.php`

## Usage

``` [ecv_calendar month="January" year="2024"] ```

Or:
``` [ecv_calendar month="01" year="2024"] ```

Or default to current month:
``` [ecv_calendar] ```

## Examples

"Live" version: https://web.archive.org/web/20220503055636/https://missionculturalcenter.org/#calendar-subscribe

## Screenshots

![Calendar showing April event dates](https://github.com/camille-davis/events-calendar-vestige/assets/54077815/d2075545-6063-4abe-a0f8-ad44d0f886f8)

Fallback if JS is disabled:

![Events displayed as list](https://github.com/camille-davis/events-calendar-vestige/assets/54077815/2ef7ba3d-73c5-4b02-9c91-6907ed0894df)

