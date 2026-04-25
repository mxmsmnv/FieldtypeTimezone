<?php namespace ProcessWire;

/**
 * InputfieldTimezone - Simple timezone inputfield for ProcessWire
 *
 * @version 1.0.1
 * @author Maxim Alex
 */
class InputfieldTimezone extends InputfieldSelect {

    /**
     * Cache lifetime in seconds.
     *
     * Kept short (1 hour) because UTC offsets are DST-sensitive — a 24-hour
     * cache built before a DST transition would show stale offsets for up to
     * a day. Building the list takes ~5ms so a shorter TTL is fine.
     */
    const CACHE_EXPIRE = 3600;

    public static function getModuleInfo() {
        return [
            'title'    => 'Timezone Inputfield',
            'version'  => 101,
            'summary'  => 'Simple inputfield for selecting timezones with dynamic UTC offsets.',
            'author'   => 'Maxim Alex',
            'requires' => ['FieldtypeTimezone'],
            'icon'     => 'clock-o',
        ];
    }

    /**
     * Curated list of well-known timezone identifiers with country / city labels.
     *
     * Keys are valid PHP DateTimeZone identifiers. Values are [country, city].
     * Offsets are intentionally absent here — they are calculated dynamically
     * in buildTimezoneList() so DST transitions are always reflected correctly.
     */
    protected static array $timezoneMapping = [
        // UTC-12 to UTC-11
        'Pacific/Kwajalein'              => ['Marshall Islands', 'Kwajalein'],
        'Pacific/Midway'                 => ['United States', 'Midway Island'],
        'Pacific/Niue'                   => ['Niue', 'Alofi'],
        'Pacific/Pago_Pago'              => ['American Samoa', 'Pago Pago'],

        // UTC-10
        'Pacific/Honolulu'               => ['United States', 'Honolulu, Hawaii'],
        'Pacific/Rarotonga'              => ['Cook Islands', 'Rarotonga'],
        'Pacific/Tahiti'                 => ['French Polynesia', 'Papeete, Tahiti'],

        // UTC-9:30 to UTC-9
        'Pacific/Marquesas'              => ['French Polynesia', 'Marquesas Islands'],
        'America/Anchorage'              => ['United States', 'Anchorage, Alaska'],
        'America/Juneau'                 => ['United States', 'Juneau, Alaska'],
        'Pacific/Gambier'                => ['French Polynesia', 'Gambier Islands'],

        // UTC-8
        'America/Los_Angeles'            => ['United States', 'Los Angeles, California'],
        'America/Vancouver'              => ['Canada', 'Vancouver, British Columbia'],
        'America/Seattle'                => ['United States', 'Seattle, Washington'],
        'America/Tijuana'                => ['Mexico', 'Tijuana, Baja California'],

        // UTC-7
        'America/Denver'                 => ['United States', 'Denver, Colorado'],
        'America/Phoenix'                => ['United States', 'Phoenix, Arizona'],
        'America/Calgary'                => ['Canada', 'Calgary, Alberta'],
        'America/Chihuahua'              => ['Mexico', 'Chihuahua'],
        'America/Mazatlan'               => ['Mexico', 'Mazatlan'],

        // UTC-6
        'America/Chicago'                => ['United States', 'Chicago, Illinois'],
        'America/Mexico_City'            => ['Mexico', 'Mexico City'],
        'America/Winnipeg'               => ['Canada', 'Winnipeg, Manitoba'],
        'America/Guatemala'              => ['Guatemala', 'Guatemala City'],
        'America/Tegucigalpa'            => ['Honduras', 'Tegucigalpa'],
        'America/Costa_Rica'             => ['Costa Rica', 'San José'],
        'America/El_Salvador'            => ['El Salvador', 'San Salvador'],

        // UTC-5
        'America/New_York'               => ['United States', 'New York City'],
        'America/Toronto'                => ['Canada', 'Toronto, Ontario'],
        'America/Montreal'               => ['Canada', 'Montreal, Quebec'],
        'America/Lima'                   => ['Peru', 'Lima'],
        'America/Bogota'                 => ['Colombia', 'Bogotá'],
        'America/Panama'                 => ['Panama', 'Panama City'],
        'America/Havana'                 => ['Cuba', 'Havana'],
        'America/Jamaica'                => ['Jamaica', 'Kingston'],

        // UTC-4
        'America/Caracas'                => ['Venezuela', 'Caracas'],
        'America/La_Paz'                 => ['Bolivia', 'La Paz'],
        'America/Halifax'                => ['Canada', 'Halifax, Nova Scotia'],
        'America/Santo_Domingo'          => ['Dominican Republic', 'Santo Domingo'],
        'America/Puerto_Rico'            => ['Puerto Rico', 'San Juan'],
        'America/Asuncion'               => ['Paraguay', 'Asunción'],

        // UTC-3:30
        'America/St_Johns'               => ['Canada', "St. John's, Newfoundland"],

        // UTC-3
        'America/Sao_Paulo'              => ['Brazil', 'São Paulo'],
        'America/Argentina/Buenos_Aires' => ['Argentina', 'Buenos Aires'],
        'America/Montevideo'             => ['Uruguay', 'Montevideo'],
        'America/Santiago'               => ['Chile', 'Santiago'],

        // UTC+0
        'UTC'                            => ['UTC', 'Coordinated Universal Time'],
        'Atlantic/Reykjavik'             => ['Iceland', 'Reykjavik'],
        'Europe/London'                  => ['United Kingdom', 'London'],
        'Europe/Dublin'                  => ['Ireland', 'Dublin'],
        'Europe/Lisbon'                  => ['Portugal', 'Lisbon'],
        'Africa/Casablanca'              => ['Morocco', 'Casablanca'],
        'Africa/Accra'                   => ['Ghana', 'Accra'],
        'Africa/Abidjan'                 => ['Ivory Coast', 'Abidjan'],

        // UTC+1
        'Europe/Paris'                   => ['France', 'Paris'],
        'Europe/Berlin'                  => ['Germany', 'Berlin'],
        'Europe/Madrid'                  => ['Spain', 'Madrid'],
        'Europe/Rome'                    => ['Italy', 'Rome'],
        'Europe/Amsterdam'               => ['Netherlands', 'Amsterdam'],
        'Europe/Brussels'                => ['Belgium', 'Brussels'],
        'Europe/Vienna'                  => ['Austria', 'Vienna'],
        'Europe/Prague'                  => ['Czech Republic', 'Prague'],
        'Europe/Warsaw'                  => ['Poland', 'Warsaw'],
        'Europe/Stockholm'               => ['Sweden', 'Stockholm'],
        'Europe/Oslo'                    => ['Norway', 'Oslo'],
        'Europe/Copenhagen'              => ['Denmark', 'Copenhagen'],
        'Europe/Zurich'                  => ['Switzerland', 'Zurich'],
        'Europe/Budapest'                => ['Hungary', 'Budapest'],
        'Africa/Lagos'                   => ['Nigeria', 'Lagos'],
        'Africa/Tunis'                   => ['Tunisia', 'Tunis'],

        // UTC+2
        'Europe/Athens'                  => ['Greece', 'Athens'],
        'Europe/Helsinki'                => ['Finland', 'Helsinki'],
        'Europe/Kyiv'                    => ['Ukraine', 'Kyiv'],
        'Europe/Bucharest'               => ['Romania', 'Bucharest'],
        'Europe/Riga'                    => ['Latvia', 'Riga'],
        'Europe/Vilnius'                 => ['Lithuania', 'Vilnius'],
        'Europe/Tallinn'                 => ['Estonia', 'Tallinn'],
        'Africa/Cairo'                   => ['Egypt', 'Cairo'],
        'Africa/Johannesburg'            => ['South Africa', 'Johannesburg'],
        'Asia/Jerusalem'                 => ['Israel', 'Jerusalem'],
        'Asia/Beirut'                    => ['Lebanon', 'Beirut'],
        'Asia/Damascus'                  => ['Syria', 'Damascus'],
        'Asia/Amman'                     => ['Jordan', 'Amman'],

        // UTC+3
        'Europe/Moscow'                  => ['Russia', 'Moscow'],
        'Europe/Istanbul'                => ['Turkey', 'Istanbul'],
        'Europe/Minsk'                   => ['Belarus', 'Minsk'],
        'Africa/Nairobi'                 => ['Kenya', 'Nairobi'],
        'Africa/Addis_Ababa'             => ['Ethiopia', 'Addis Ababa'],
        'Asia/Riyadh'                    => ['Saudi Arabia', 'Riyadh'],
        'Asia/Kuwait'                    => ['Kuwait', 'Kuwait City'],
        'Asia/Baghdad'                   => ['Iraq', 'Baghdad'],
        'Asia/Qatar'                     => ['Qatar', 'Doha'],

        // UTC+3:30
        'Asia/Tehran'                    => ['Iran', 'Tehran'],

        // UTC+4
        'Asia/Dubai'                     => ['United Arab Emirates', 'Dubai'],
        'Asia/Muscat'                    => ['Oman', 'Muscat'],
        'Asia/Baku'                      => ['Azerbaijan', 'Baku'],
        'Asia/Yerevan'                   => ['Armenia', 'Yerevan'],
        'Asia/Tbilisi'                   => ['Georgia', 'Tbilisi'],

        // UTC+4:30
        'Asia/Kabul'                     => ['Afghanistan', 'Kabul'],

        // UTC+5
        'Asia/Karachi'                   => ['Pakistan', 'Karachi'],
        'Asia/Tashkent'                  => ['Uzbekistan', 'Tashkent'],
        'Asia/Almaty'                    => ['Kazakhstan', 'Almaty'],

        // UTC+5:30
        'Asia/Kolkata'                   => ['India', 'Mumbai / Kolkata'],
        'Asia/Colombo'                   => ['Sri Lanka', 'Colombo'],

        // UTC+5:45
        'Asia/Kathmandu'                 => ['Nepal', 'Kathmandu'],

        // UTC+6
        'Asia/Dhaka'                     => ['Bangladesh', 'Dhaka'],
        'Asia/Thimphu'                   => ['Bhutan', 'Thimphu'],
        'Asia/Omsk'                      => ['Russia', 'Omsk'],

        // UTC+6:30
        'Asia/Yangon'                    => ['Myanmar', 'Yangon'],

        // UTC+7
        'Asia/Bangkok'                   => ['Thailand', 'Bangkok'],
        'Asia/Jakarta'                   => ['Indonesia', 'Jakarta'],
        'Asia/Ho_Chi_Minh'               => ['Vietnam', 'Ho Chi Minh City'],
        'Asia/Novosibirsk'               => ['Russia', 'Novosibirsk'],

        // UTC+8
        'Asia/Shanghai'                  => ['China', 'Shanghai / Beijing'],
        'Asia/Hong_Kong'                 => ['Hong Kong', 'Hong Kong'],
        'Asia/Taipei'                    => ['Taiwan', 'Taipei'],
        'Asia/Singapore'                 => ['Singapore', 'Singapore'],
        'Asia/Kuala_Lumpur'              => ['Malaysia', 'Kuala Lumpur'],
        'Asia/Manila'                    => ['Philippines', 'Manila'],
        'Australia/Perth'                => ['Australia', 'Perth, Western Australia'],
        'Asia/Krasnoyarsk'               => ['Russia', 'Krasnoyarsk'],

        // UTC+9
        'Asia/Tokyo'                     => ['Japan', 'Tokyo'],
        'Asia/Seoul'                     => ['South Korea', 'Seoul'],
        'Asia/Yakutsk'                   => ['Russia', 'Yakutsk'],

        // UTC+9:30
        'Australia/Adelaide'             => ['Australia', 'Adelaide, South Australia'],
        'Australia/Darwin'               => ['Australia', 'Darwin, Northern Territory'],

        // UTC+10
        'Australia/Sydney'               => ['Australia', 'Sydney, New South Wales'],
        'Australia/Melbourne'            => ['Australia', 'Melbourne, Victoria'],
        'Australia/Brisbane'             => ['Australia', 'Brisbane, Queensland'],
        'Pacific/Guam'                   => ['Guam', 'Hagåtña'],
        'Asia/Vladivostok'               => ['Russia', 'Vladivostok'],

        // UTC+11
        'Pacific/Noumea'                 => ['New Caledonia', 'Nouméa'],
        'Asia/Magadan'                   => ['Russia', 'Magadan'],

        // UTC+12
        'Pacific/Auckland'               => ['New Zealand', 'Auckland'],
        'Pacific/Fiji'                   => ['Fiji', 'Suva'],
        'Asia/Kamchatka'                 => ['Russia', 'Petropavlovsk-Kamchatsky'],

        // UTC+13
        'Pacific/Tongatapu'              => ["Tonga", "Nuku'alofa"],
        'Pacific/Apia'                   => ['Samoa', 'Apia'],
    ];

    /**
     * Initialize the inputfield.
     *
     * Deliberately does NOT call populateTimezones() here.
     * Populating happens lazily in ___render() so the heavy select-building
     * code only runs when the field is actually rendered in a form, not on
     * every page load that happens to instantiate a Fieldtype.
     */
    public function init() {
        parent::init();
    }

    /**
     * Render the inputfield, populating options lazily.
     */
    public function ___render() {
        if (!count($this->getOptions())) {
            $this->populateTimezones();
        }
        return parent::___render();
    }

    /**
     * Populate timezone options from cache or rebuild.
     */
    protected function populateTimezones(): void {
        $cache    = $this->wire('cache');
        $cacheKey = 'InputfieldTimezone_options';

        $timezones = $cache->get($cacheKey);

        if ($timezones === null) {
            $timezones = $this->buildTimezoneList();
            $cache->save($cacheKey, $timezones, self::CACHE_EXPIRE);
        }

        foreach ($timezones as $identifier => $label) {
            $this->addOption($identifier, $label);
        }
    }

    /**
     * Build timezone list with dynamic UTC offsets.
     *
     * Offsets are calculated at build time — DST is reflected automatically.
     * The cache TTL (1 hour) ensures a transition is picked up promptly.
     *
     * @return array<string, string>  identifier => "Country → City (UTC+X)"
     */
    protected function buildTimezoneList(): array {
        $timezones = [];
        $now       = new \DateTime();

        foreach (self::$timezoneMapping as $identifier => $info) {
            try {
                $tz     = new \DateTimeZone($identifier);
                $offset = $tz->getOffset($now);
                $label  = $info[0] . ' → ' . $info[1] . ' (' . FieldtypeTimezone::formatUtcOffset($offset) . ')';

                $timezones[$identifier] = $label;
            } catch (\Exception $e) {
                continue;
            }
        }

        asort($timezones);

        return $timezones;
    }

    /**
     * Process input.
     *
     * Validation is delegated entirely to FieldtypeTimezone::sanitizeValue()
     * via the Fieldtype layer, so we only clear the value here on failure
     * without emitting a second error message.
     */
    public function ___processInput(WireInputData $input) {
        $result = parent::___processInput($input);

        $value = $this->getAttribute('value');

        if ($value && !in_array($value, \DateTimeZone::listIdentifiers())) {
            $this->setAttribute('value', '');
        }

        return $result;
    }
}
