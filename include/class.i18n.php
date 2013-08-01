<?php
/*********************************************************************
    class.i18n.php

    Internationalization and localization helpers for osTicket

    Peter Rotich <peter@osticket.com>
    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once INCLUDE_DIR.'class.error.php';
require_once INCLUDE_DIR.'class.yaml.php';
require_once INCLUDE_DIR.'class.config.php';

class Internationalization {

    // Languages in order of decreasing priority. Always use en_US as a
    // fallback
    var $langs = array('en_US');

    function Internationalization($language=false) {
        if ($language)
            array_unshift($this->langs, $language);
    }

    function getTemplate($path) {
        return new DataTemplate($path, $this->langs);
    }

    /**
     * Loads data from the I18N_DIR for the target language into the
     * database. This is intended to be done at the time of installation;
     * however, care should be taken in this process to ensure that the
     * process could be repeated if an administrator wanted to change the
     * system language and reload the data.
     */
    function loadDefaultData() {
        $models = array(
            'email_template_group.yaml' => 'EmailTemplateGroup', # notrans
            'department.yaml' =>    'Dept', # notrans
            'sla.yaml' =>           'SLA', # notrans
            // Note that department and sla are required for help_topic
            'help_topic.yaml' =>    'Topic', # notrans
            'filter.yaml' =>        'Filter', # notrans
            'team.yaml' =>          'Team', # notrans
            // Note that group requires department
            'group.yaml' =>         'Group', # notrans
        );

        $errors = array();
        foreach ($models as $yaml=>$m)
            if ($objects = $this->getTemplate($yaml)->getData())
                foreach ($objects as $o)
                    // Model::create($o)
                    call_user_func_array(
                        array($m, 'create'), array($o, &$errors));

        // Priorities
        $priorities = $this->getTemplate('priority.yaml')->getData();
        foreach ($priorities as $name=>$info) {
            $sql = 'INSERT INTO '.PRIORITY_TABLE
                .' SET priority='.db_input($name)
                .', priority_desc='.db_input($info['priority_desc'])
                .', priority_color='.db_input($info['priority_color'])
                .', priority_urgency='.db_input($info['priority_urgency']);
            db_query($sql);
        }

        // Configuration
        if (($tpl = $this->getTemplate('config.yaml'))
                && ($data = $tpl->getData())) {
            foreach ($data as $section=>$items) {
                $_config = new Config($section);
                foreach ($items as $key=>$value)
                    $_config->set($key, $value);
            }
        }

        // Pages
        $_config = new OsticketConfig();
        foreach (array('landing','thank-you','offline') as $type) {
            $tpl = $this->getTemplate("templates/page/{$type}.yaml");
            if (!($page = $tpl->getData()))
                continue;
            $sql = 'INSERT INTO '.PAGE_TABLE.' SET type='.db_input($type)
                .', name='.db_input($page['name'])
                .', body='.db_input($page['body'])
                .', lang='.db_input($tpl->getLang())
                .', notes='.db_input($page['notes'])
                .', created=NOW(), updated=NOW(), isactive=1';
            if (db_query($sql) && ($id = db_insert_id()))
                $_config->set("{$type}_page_id", $id);
        }

        // Canned response examples
        if (($tpl = $this->getTemplate('templates/premade.yaml'))
                && ($canned = $tpl->getData())) {
            foreach ($canned as $c) {
                $sql = 'INSERT INTO '.CANNED_TABLE
                    .' SET title='.db_input($c['title'])
                    .', lang='.db_input($tpl->getLang())
                    .', response='.db_input($c['response'])
                    .', notes='.db_input($c['notes'])
                    .', created=NOW(), updated=NOW(), isenabled=1';
                if (db_query($sql) && ($id = db_insert_id())
                        && isset($c['attachments'])) {
                    foreach ($c['attachments'] as $att) {
                        if ($fileId = AttachmentFile::save($att))
                            $sql ='INSERT INTO '.CANNED_ATTACHMENT_TABLE
                                 .' SET canned_id='.db_input($id)
                                 .', file_id='.db_input($fileId);
                            db_query($sql);
                    }
                }
            }
        }

        // Email templates
        // TODO: Lookup tpl_id
        $tpl = EmailTemplateGroup::lookup(1);
        foreach ($tpl->all_names as $name=>$info) {
            if (($tp = $this->getTemplate("templates/email/$name.yaml"))
                    && ($t = $tp->getData())) {
                $t['tpl_id'] = $tpl->getId();
                $t['code_name'] = $name;
                EmailTemplate::create($t, $errors);
            }
        }
    }
}

class DataTemplate {
    // Base folder for default data and templates
    var $base = I18N_DIR;

    var $filepath;
    var $data;

    /**
     * Searches for the files matching the template in the order of the
     * received languages. Once matched, the language is captured so that
     * template itself does not have to keep track of the language for which
     * it is defined.
     */
    function DataTemplate($path, $langs=array('en_US')) {
        foreach ($langs as $l) {
            if (file_exists("{$this->base}/$l/$path")) {
                $this->lang = $l;
                $this->filepath = realpath("{$this->base}/$l/$path");
                break;
            }
        }
    }

    function getData() {
        if (!isset($this->data) && $this->filepath)
            $this->data = YamlDataParser::load($this->filepath);
            // TODO: If there was a parsing error, attempt to try the next
            //       language in the list of requested languages
        return $this->data;
    }

    function getLang() {
        return $this->lang;
    }
}

?>
