<?php
/*********************************************************************
    class.import.php

    Utilities for importing objects and data (usually via CSV)

    Peter Rotich <peter@osticket.com>
    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2015 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class ImportError extends Exception {}
class ImportDataError extends ImportError {}

class CsvImporter {
    // Bytes sequence of common BOM
    const BOMs = array(
                 'UTF8' => "\xEF\xBB\xBF",
                 'UTF16_BE' => "\xFE\xFF",
                 'UTF16_LE' => "\xFF\xFE",
                 'UTF32_BE' => "\x00\x00\xFE\xFF",
                 'UTF32_LE' => "\xFF\xFE\x00\x00");
    var $stream;

    function __construct($stream) {
        // File upload
        if (is_array($stream) && !$stream['error']) {
            $this->stream = fopen($stream['tmp_name'], 'r');
            // Skip Byte Order Mark (BOM) if present
            if (!self::isBOM(fgets($this->stream, 4)))
                rewind($this->stream);
        }
        // Open file
        elseif (is_resource($stream)) {
            $this->stream = $stream;
        }
        // Text from standard-in
        elseif (is_string($stream)) {
            $this->stream = fopen('php://temp', 'w+');
            fwrite($this->stream, $stream);
            rewind($this->stream);
        }
        else {
            throw new ImportError(__('Unable to parse submitted csv: ').print_r(Format::htmlchars($stream), true));
        }
    }

    function __destruct() {
        fclose($this->stream);
    }

    function importCsv($all_fields=array(), $defaults=array()) {
        $named_fields = array();
        $has_header = true;
        foreach ($all_fields as $f)
            if ($f->get('name'))
                $named_fields[$f->get('name')] = $f;

        // Read the first row and see if it is a header or not
        if (!($data = fgetcsv($this->stream, 1000, ",")))
            throw new ImportError(__('Whoops. Perhaps you meant to send some CSV records'));

        $headers = array();
        foreach (Format::htmlchars($data) as $h) {
            $h = trim($h);
            $found = false;
            foreach ($all_fields as $f) {
                if (in_array(mb_strtolower($h), array(
                        mb_strtolower($f->get('name')), mb_strtolower($f->get('label'))))) {
                    $found = true;
                    if (!$f->get('name'))
                        throw new ImportError(sprintf(__(
                            '%s: Field must have `variable` set to be imported'), Format::htmlchars($h)));
                    $headers[$f->get('name')] = $f->get('label');
                    break;
                }
            }
            if (!$found) {
                $has_header = false;
                if (count($data) == count($named_fields)) {
                    // Number of fields in the user form matches the number
                    // of fields in the data. Assume things line up
                    $headers = array();
                    foreach ($named_fields as $f)
                        $headers[$f->get('name')] = $f->get('label');
                    break;
                }
                else {
                    throw new ImportError(sprintf(
                                __('%s: Unable to map header to the object field'), Format::htmlchars($h)));
                }
            }
        }

        if (!$has_header)
            fseek($this->stream, 0);

        $objects = $fields = array();
        foreach ($headers as $h => $label) {
            if (!isset($named_fields[$h]))
                continue;

            $f = $named_fields[$h];
            $name = $f->get('name');
            $fields[$name] = $f;
        }

        // Add default fields (org_id, etc).
        foreach ($defaults as $key => $val) {
            // Don't apply defaults which are also being imported
            if (isset($header[$key]))
                unset($defaults[$key]);
        }

        // Avoid reading the entire CSV before yielding the first record.
        // Use an iterator. This will also allow row-level errors to be
        // continuable such that the rows with errors can be handled and the
        // iterator can continue with the next row.
        return new CsvImportIterator($this->stream, $headers, $fields, $defaults);
    }

    // Check if a string matches a BOM
    static function isBOM($str) {
        return in_array($str, self::BOMs);
    }
}

class CsvImportIterator
implements Iterator {
    var $stream;
    var $start = 0;
    var $headers;
    var $fields;
    var $defaults;

    var $current = true;
    var $row = 0;

    function __construct($stream, $headers, $fields, $defaults) {
        $this->stream = $stream;
        $this->start = ftell($stream);
        $this->headers = $headers;
        $this->fields = $fields;
        $this->defaults = $defaults;
    }

    // Iterator interface -------------------------------------
    function rewind() {
        @fseek($this->stream, $this->start);
        if (ftell($this->stream) != $this->start)
            throw new RuntimeException('Stream cannot be rewound');
        $this->row = 0;
        $this->next();
    }
    function valid() {
        return $this->current;
    }
    function current() {
        return $this->current;
    }
    function key() {
        return $this->row;
    }

    function next() {
        do {
            if (($csv = fgetcsv($this->stream, 4096, ",")) === false) {
                // Read error
                $this->current = false;
                break;
            }

            if (count($csv) == 1 && $csv[0] == null)
                // Skip empty rows
                continue;
            elseif (count($csv) != count($this->headers))
                throw new ImportDataError(sprintf(__('Bad data. Expected: %s'),
                    implode(', ', $this->headers)));

            // Validate according to field configuration
            $i = 0;
            $this->current = $this->defaults;
            foreach ($this->headers as $h => $label) {
                $f = $this->fields[$h];
                $f->_errors = array();
                $T = $f->parse(trim($csv[$i]));
                if ($f->validateEntry($T) && $f->errors())
                    throw new ImportDataError(sprintf(__(
                        /* 1 will be a field label, and 2 will be error messages */
                        '%1$s: Invalid data: %2$s'),
                        $label, implode(', ', $f->errors())));
                // Convert to database format
                $this->current[$h] = $f->to_database($T);
                $i++;
            }
        }
        // Use the do-loop only for the empty line skipping
        while (false);
        $this->row++;
    }
}
