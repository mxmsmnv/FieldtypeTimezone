<?php namespace ProcessWire;

/**
 * FieldtypeTimezone - Simple and reliable timezone fieldtype for ProcessWire
 * 
 * @version 1.0.0
 * @author Maxim Alex
 */
class FieldtypeTimezone extends Fieldtype {

    public static function getModuleInfo() {
        return [
            'title' => 'Timezone',
            'version' => '1.0.0',
            'summary' => 'Fieldtype for storing timezone values with dynamic UTC offsets.',
            'author' => 'Maxim Alex',
            'href' => 'https://github.com/mxmsmnv/FieldtypeTimezone',
            'installs' => ['InputfieldTimezone'],
            'icon' => 'clock-o',
            'requires' => 'ProcessWire>=3.0.0, PHP>=8.1.0'
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
     */
    public function sanitizeValue(Page $page, Field $field, $value) {
        if (empty($value)) return '';
        
        $value = $this->wire('sanitizer')->text($value);
        
        // Validate timezone identifier
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
     */
    public function getCompatibleFieldtypes(Field $field) {
        $compatible = $this->wire(new WireArray());
        foreach ($this->wire('fieldtypes') as $fieldtype) {
            if ($fieldtype instanceof FieldtypeText || 
                $fieldtype instanceof FieldtypeTextarea) {
                $compatible->add($fieldtype);
            }
        }
        return $compatible;
    }

    /**
     * Get timezone information
     */
    public function getTimezoneInfo($timezone) {
        if (!in_array($timezone, \DateTimeZone::listIdentifiers())) {
            return null;
        }
        
        try {
            $tz = new \DateTimeZone($timezone);
            $now = new \DateTime('now', $tz);
            $offset = $tz->getOffset($now);
            
            return [
                'identifier' => $timezone,
                'offset' => $offset,
                'offset_formatted' => $this->formatOffset($offset),
                'name' => $timezone,
                'abbreviation' => $now->format('T')
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Format UTC offset
     */
    protected function formatOffset($offset) {
        $hours = floor($offset / 3600);
        $minutes = abs(($offset % 3600) / 60);
        
        return sprintf(
            'UTC%s%d%s',
            $offset >= 0 ? '+' : '',
            $hours,
            $minutes > 0 ? ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT) : ''
        );
    }
}