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

class CsvImporter {
    var $stream;

    function __construct($stream) {
        // File upload
        if (is_array($stream) && !$stream['error']) {
            // Properly detect Macintosh style line endings
            ini_set('auto_detect_line_endings', true);
            $this->stream = fopen($stream['tmp_name'], 'r');
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
            throw new ImportError(__('Unable to parse submitted csv: ').print_r($stream, true));
        }
    }

    function importCsv($forms=array(), $defaults=array()) {
        $named_fields = $all_fields = array();
        foreach ($forms as $F)
            foreach ($F->getFields() as $field)
                $all_fields[] = $field;

        $has_header = true;
        foreach ($all_fields as $f)
            if ($f->get('name'))
                $named_fields[] = $f;

        // Read the first row and see if it is a header or not
        if (!($data = fgetcsv($this->stream, 1000, ",")))
            throw new ImportError(__('Whoops. Perhaps you meant to send some CSV records'));

        $headers = array();
        foreach ($data as $h) {
            $h = trim($h);
            $found = false;
            foreach ($all_fields as $f) {
                if (in_array(mb_strtolower($h), array(
                        mb_strtolower($f->get('name')), mb_strtolower($f->get('label'))))) {
                    $found = true;
                    if (!$f->get('name'))
                        throw new ImportError(sprintf(__(
                            '%s: Field must have `variable` set to be imported'), $h));
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
                    throw new ImportError(sprintf(__('%s: Unable to map header to a user field'), $h));
                }
            }
        }

        if (!$has_header)
            fseek($this->stream, 0);

        $objects = $fields = $keys = array();
        foreach ($forms as $F) {
            foreach ($headers as $h => $label) {
                if (!($f = $F->getField($h)))
                    continue;

                $name = $keys[] = $f->get('name');
                $fields[$name] = $f->getImpl();
            }
        }

        // Add default fields (org_id, etc).
        foreach ($defaults as $key => $val) {
            // Don't apply defaults which are also being imported
            if (isset($header[$key]))
                unset($defaults[$key]);
            $keys[] = $key;
        }

        while (($data = fgetcsv($this->stream, 4096, ",")) !== false) {
            if (count($data) == 1 && $data[0] == null)
                // Skip empty rows
                continue;
            elseif (count($data) != count($headers))
                throw new ImportError(sprintf(__('Bad data. Expected: %s'), implode(', ', $headers)));
            // Validate according to field configuration
            $i = 0;
            foreach ($headers as $h => $label) {
                $f = $fields[$h];
                $T = $f->parse($data[$i]);
                if ($f->validateEntry($T) && $f->errors())
                    throw new ImportError(sprintf(__(
                        /* 1 will be a field label, and 2 will be error messages */
                        '%1$s: Invalid data: %2$s'),
                        $label, implode(', ', $f->errors())));
                // Convert to database format
                $data[$h] = $data[$i++] = $f->to_database($T);
            }
            // Add default fields
            foreach ($defaults as $key => $val)
                $data[$key] = $data[$i++] = $val;

            $objects[] = $data;
        }

        return $objects;
    }
}
