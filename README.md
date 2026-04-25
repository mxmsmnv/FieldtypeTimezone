# Timezone Fieldtype and Inputfield for ProcessWire

**Module Name**: FieldtypeTimezone / InputfieldTimezone  
**Author**: Maxim Alex  
**Version**: 1.0.1  
**License**: Mozilla Public License 2.0  
**Icon**: clock-o

## Overview

Simple and reliable module for ProcessWire that provides timezone functionality. The module automatically calculates current UTC offsets taking into account Daylight Saving Time (DST).

### Key features

- **Dynamic UTC offsets** — automatic calculation of current offsets with DST support
- **Simplicity** — no complex settings, works out of the box
- **Performance** — intelligent caching (1 hour) with lazy rendering
- **Reliability** — strict validation at all levels, single source of truth
- **Complete coverage** — all major world timezones including Russia, Central Asia, Baltic states
- **ProcessWire 3.x** — full compatibility

## Display format

Timezones are displayed in the format:

```
Country → City (UTC+X)
```

Examples:

```
United States → New York City (UTC-5)
Japan → Tokyo (UTC+9)
United Kingdom → London (UTC+0)
Australia → Sydney, New South Wales (UTC+11)
```

UTC offsets are calculated dynamically, so for timezones with DST the values change automatically:

- `America/New_York`: UTC-5 in winter, UTC-4 in summer
- `Europe/London`: UTC+0 in winter, UTC+1 in summer

## Requirements

- ProcessWire 3.0.0 or higher
- PHP 8.1 or higher
- InputfieldTimezone module (installs automatically)

## Installation

### Option 1: Via Git

```bash
cd /path/to/processwire/site/modules/
git clone https://github.com/mxmsmnv/FieldtypeTimezone.git
```

### Option 2: Manual installation

1. Download the ZIP archive
2. Extract to `/site/modules/FieldtypeTimezone/`
3. Ensure the structure is correct:

```
/site/modules/FieldtypeTimezone/
├── FieldtypeTimezone.module.php
├── InputfieldTimezone.module.php
└── README.md
```

### Module activation

1. Log in to your ProcessWire admin panel
2. Go to **Modules** → **Site**
3. Find **FieldtypeTimezone** and click **Install**
4. InputfieldTimezone will be installed automatically

## Usage

### Creating a field

1. Go to **Setup** → **Fields** → **Add New**
2. Enter field name (e.g., `user_timezone`)
3. Select type **Timezone**
4. Save the field
5. Add the field to desired templates

No additional configuration required.

### Working with the field in templates

#### Basic usage

```php
// Get timezone value
$timezone = $page->user_timezone;
echo $timezone; // Outputs: America/New_York

// Check for empty value
if ($page->user_timezone) {
    echo "Timezone: " . $page->user_timezone;
}
```

#### Working with DateTime

```php
if ($page->user_timezone) {
    $tz = new \DateTimeZone($page->user_timezone);
    $datetime = new \DateTime('now', $tz);

    echo "Current time in user's timezone: " . $datetime->format('F j, Y \a\t g:i A T');
    // Outputs: Current time in user's timezone: December 31, 2025 at 5:13 PM EST
}
```

#### Time conversion

```php
function convertToUserTimezone(string $utcTime, string $userTimezone): string {
    $utc = new \DateTimeZone('UTC');
    $tz  = new \DateTimeZone($userTimezone);

    $datetime = new \DateTime($utcTime, $utc);
    $datetime->setTimezone($tz);

    return $datetime->format('F j, Y \a\t g:i A T');
}

$utcTime   = '2025-12-31 18:30:00';
$localTime = convertToUserTimezone($utcTime, $page->user_timezone);
echo $localTime; // December 31, 2025 at 1:30 PM EST (for America/New_York)
```

#### Getting timezone information

```php
$fieldtype = $fields->get('user_timezone')->type;
$info      = $fieldtype->getTimezoneInfo($page->user_timezone);

if ($info) {
    echo "Identifier: "   . $info['identifier']       . "<br>";
    echo "Offset: "       . $info['offset_formatted'] . "<br>";
    echo "Abbreviation: " . $info['abbreviation']     . "<br>";
}
```

`getTimezoneInfo()` returns an array with the following keys:

| Key | Type | Description |
|---|---|---|
| `identifier` | string | PHP timezone identifier, e.g. `America/New_York` |
| `offset` | int | Raw offset in seconds (negative west of UTC) |
| `offset_formatted` | string | Human-readable offset, e.g. `UTC-5` |
| `name` | string | Same as `identifier` |
| `abbreviation` | string | Short abbreviation, e.g. `EST` |

#### Programmatic value setting

```php
$page->of(false);
$page->user_timezone = 'Europe/London';
$page->save('user_timezone');
$page->of(true);
```

#### Displaying user's current time

```php
if ($user->timezone) {
    $tz  = new \DateTimeZone($user->timezone);
    $now = new \DateTime('now', $tz);

    echo "<div class='user-time'>";
    echo "<strong>Your local time:</strong> ";
    echo $now->format('l, F j, Y - g:i A T');
    echo "</div>";
}
```

### Auto-detect timezone (with JavaScript)

```php
<script>
document.addEventListener('DOMContentLoaded', function() {
    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    const field    = document.getElementById('timezone_field');

    if (field && timezone) {
        field.value = timezone;
    }
});
</script>
```

## Security

### Data validation

- Automatic sanitization of all input data via `wire('sanitizer')->text()`
- Validation of timezone identifiers against `DateTimeZone::listIdentifiers()`
- Single validation point in `FieldtypeTimezone::sanitizeValue()` — no duplicate error messages
- Protection against SQL injection via parameterized queries
- Database field indexing for fast lookup

### Error handling

```php
try {
    $tz = new \DateTimeZone($page->user_timezone);
} catch (Exception $e) {
    // Fallback to UTC
    $tz = new \DateTimeZone('UTC');
}
```

## Performance

### Caching

The module caches the timezone list for 1 hour. This keeps DST transitions accurate while still avoiding the list rebuild on every request:

```php
// Manually clear cache if needed
$cache = wire('cache');
$cache->deleteFor('InputfieldTimezone_*');
```

### Lazy rendering

The timezone select options are only populated when the inputfield is actually rendered inside a form — not on every page load. This avoids unnecessary overhead when the field is not visible.

### Database optimization

The field is automatically indexed for fast lookup:

```sql
KEY data (data)
```

## Changelog

### Version 1.0.1

**Fixed:**

- `version` in `getModuleInfo()` changed from string `'1.0.0'` to integer `101` — string versions break ProcessWire's modules directory registration and version comparison
- `InputfieldTimezone::init()` no longer calls `populateTimezones()` eagerly; options are now populated lazily in `___render()` to avoid running the select-building loop on every page load
- Removed duplicate `formatOffset()` method from `InputfieldTimezone`; both classes now use the shared static `FieldtypeTimezone::formatUtcOffset()` helper
- `getCompatibleFieldtypes()` now uses a single `instanceof FieldtypeText` check, which correctly covers `FieldtypeTextarea` and all other subclasses
- Cache TTL reduced from 24 hours to 1 hour to limit stale DST offsets after clock changes
- `InputfieldTimezone::___processInput()` no longer calls `$this->error()` on invalid values — validation and error messaging are now handled exclusively in `FieldtypeTimezone::sanitizeValue()`, preventing duplicate error messages

**Changed:**

- `$timezoneMapping` expanded: added `Europe/Kyiv` (replaces deprecated `Europe/Kiev`), `Asia/Almaty`, `Asia/Novosibirsk`, `Asia/Vladivostok`, `Asia/Yakutsk`, `Asia/Krasnoyarsk`, `Asia/Omsk`, `Asia/Magadan`, `Asia/Kamchatka`, `Atlantic/Reykjavik`, `Africa/Tunis`, `Africa/Abidjan`, `Africa/Addis_Ababa`, `Europe/Minsk`, `Europe/Riga`, `Europe/Vilnius`, `Europe/Tallinn`, `Asia/Qatar`
- Removed static UTC offset comments from `$timezoneMapping` sections — offsets are always dynamic, so static comments were misleading
- `formatOffset()` renamed to `formatUtcOffset()` and made `public static` on `FieldtypeTimezone` for shared use
- Module version numbers updated to `101` in both classes

### Version 1.0.0 (2025-10-03)

Initial stable release.

- Dynamic UTC offset calculation with DST support
- Intelligent caching
- Database field indexing
- `getTimezoneInfo()` API method
- Extended data validation

## Support and contribution

- GitHub Issues: [Report Bug](https://github.com/mxmsmnv/FieldtypeTimezone/issues)
- ProcessWire Forum: [Support Thread](https://processwire.com/talk/)

## License

This module is distributed under the **Mozilla Public License 2.0**.
