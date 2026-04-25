<?php namespace ProcessWire;

/**
 * FieldtypeTimezone - Simple and reliable timezone fieldtype for ProcessWire
 *
 * @version 1.0.1
 * @author Maxim Alex
 */
class FieldtypeTimezone extends Fieldtype {

    public static function getModuleInfo() {
        return [
            'title'    => 'Timezone',
            'version'  => 101,
            'summary'  => 'Fieldtype for storing timezone values with dynamic UTC offsets.',
            'author'   => 'Maxim Alex',
            'href'     => 'https://github.com/mxmsmnv/FieldtypeTimezone',
            'installs' => ['InputfieldTimezone'],
            'icon'     => 'clock-o',
            'requires' => 'ProcessWire>=3.0.0, PHP>=8.1.0',
        ];
    }

    /**
     * Get database schema for the field
     */
    public function getDatabaseSchema(Field $field) {
        $schema = parent::getDatabaseSchema($field);
        $schema['data'] = 'varchar(255) NOT NULL DEFAULT ""';
        $schema['keys']['data'] = 'KEY data (data)';
        return $schema;
    }

    /**
     * Get blank value for the field
     */
    public function getBlankValue(Page $page, Field $field) {
        return '';
    }

    /**
     * Sanitize value before storage
     *
     * Single source of truth for validation — InputfieldTimezone delegates
     * to this via ___processInput() without calling $this->error() itself,
     * so the user never sees duplicate error messages.
     */
    public function sanitizeValue(Page $page, Field $field, $value) {
        if (empty($value)) return '';

        $value = $this->wire('sanitizer')->text($value);

        if (!in_array($value, \DateTimeZone::listIdentifiers())) {
            $this->error(sprintf(
                $this->_('Invalid timezone identifier: %s'),
                $value
            ));
            return '';
        }

        return $value;
    }

    /**
     * Format value for output
     */
    public function ___formatValue(Page $page, Field $field, $value) {
        return $value;
    }

    /**
     * Get inputfield for this fieldtype
     */
    public function getInputfield(Page $page, Field $field) {
        return $this->wire('modules')->get('InputfieldTimezone');
    }

    /**
     * Get compatible fieldtypes
     *
     * FieldtypeTextarea inherits FieldtypeText, so a single isinstance
     * check covers both.
     */
    public function getCompatibleFieldtypes(Field $field) {
        $compatible = $this->wire(new WireArray());
        foreach ($this->wire('fieldtypes') as $fieldtype) {
            if ($fieldtype instanceof FieldtypeText) {
                $compatible->add($fieldtype);
            }
        }
        return $compatible;
    }

    /**
     * Get timezone information
     *
     * @param string $timezone Valid PHP timezone identifier
     * @return array|null Array with keys: identifier, offset (seconds), offset_formatted, name, abbreviation
     */
    public function getTimezoneInfo(string $timezone): ?array {
        if (!in_array($timezone, \DateTimeZone::listIdentifiers())) return null;

        try {
            $tz  = new \DateTimeZone($timezone);
            $now = new \DateTime('now', $tz);

            $offset = $tz->getOffset($now);

            return [
                'identifier'       => $timezone,
                'offset'           => $offset,
                'offset_formatted' => self::formatUtcOffset($offset),
                'name'             => $timezone,
                'abbreviation'     => $now->format('T'),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Format a raw UTC offset (seconds) into a human-readable string.
     *
     * Shared static helper — InputfieldTimezone calls this directly to avoid
     * duplicating the logic.
     *
     * @param int $offset Offset in seconds (may be negative)
     * @return string e.g. "UTC+5:30", "UTC-3", "UTC+0"
     */
    public static function formatUtcOffset(int $offset): string {
        $sign    = $offset >= 0 ? '+' : '-';
        $abs     = abs($offset);
        $hours   = intdiv($abs, 3600);
        $minutes = ($abs % 3600) / 60;

        return 'UTC' . $sign . $hours . ($minutes > 0 ? ':' . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) : '');
    }
}
