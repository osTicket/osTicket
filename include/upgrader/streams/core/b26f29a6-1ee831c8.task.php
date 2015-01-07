<?php

class IntlMigrator extends MigrationTask {
    var $description = "Date format migration from date() to ICU";

    static $dateToIntl = array(
        'd' => 'dd',
        'D' => 'EEE',
        'j' => 'd',
        'l' => 'EEEE',
        'N' => 'e',
        'w' => 'c',
        'z' => 'D',

        'W' => 'w',

        'F' => 'MMMM',
        'm' => 'MM',
        'M' => 'MMM',
        'n' => 'M',

        'o' => 'Y',
        'Y' => 'y',
        'y' => 'yy',

        'A' => 'a',
        'g' => 'h',
        'G' => 'H',
        'h' => 'hh',
        'H' => 'HH',
        'i' => 'mm',
        's' => 'ss',
        'u' => 'SSSSSS',

        'e' => 'VV',
        'O' => 'ZZZ',
        'P' => 'ZZZZZ',
        'T' => 'z',

        'c' => "yyyy-MM-dd'T'HH:mm:ssXXXXX",
        'r' => 'EEE, d MMM yyyy HH:mm:ss XXXXX',
    );

    function run($max_time) {
        global $cfg;

        // Detect rough install date — rationale: the schema_signature is
        // touched by the database migrater; however the updated timestamp
        // associated with it is not touched.
        $install_date = $cfg->lastModified('schema_signature');
        $touched = false;

        // Upgrade date formats
        foreach (
            array('datetime_format', 'daydatetime_format', 'time_format', 'date_format')
            as $key
        ) {
            $new_format = '';
            $format = $cfg->get($key);
            foreach (str_split($format) as $char) {
                $new_format .= @self::$dateToIntl[$char] ?: $char;
            }
            $cfg->set($key, $new_format);

            // Consider the last-updated time of the key to see if the
            // format has been modified since installation
            $touched |= $cfg->lastModified($key) != $install_date;
        }

        // Add in new custom date format flag
        $cfg->set('date_formats', $touched ? 'custom' : '' );
    }
}

return 'IntlMigrator';
