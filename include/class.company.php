<?php
/*********************************************************************
    class.company.php

    Company information

    Peter Rotich <peter@osticket.com>
    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once(INCLUDE_DIR.'class.forms.php');
require_once(INCLUDE_DIR.'class.dynamic_forms.php');

class Company
implements TemplateVariable {
    var $form;
    var $entry;

    function getForm() {
        if (!isset($this->form)) {
            // Look for the entry first
            if ($this->form = DynamicFormEntry::lookup(array('object_type'=>'C'))) {
                return $this->form;
            }
            // Make sure the form is in the database
            elseif (!($this->form = DynamicForm::lookup(array('type'=>'C')))) {
                $this->__loadDefaultForm();
                return $this->getForm();
            }
            // Create an entry to be saved later
            $this->form = $this->form->instanciate();
            $this->form->object_type = 'C';
        }
        return $this->form;
    }

    function getVar($name) {
        if ($info = $this->getInfo()) {
            $name = mb_strtolower($name);
            if (isset($info[$name]))
                return $info[$name];
        }
    }

    function getInfo() {
        return $this->getForm()->getClean();
    }

    function getName() {
        return $this->getVar('name');
    }

    function asVar() {
        return $this->getName();
    }

    static function getVarScope() {
        return VariableReplacer::compileFormScope(
            DynamicForm::lookup(array('type'=>'C'))
        );
    }

    function __toString() {
        try {
            if ($name = $this->getForm()->getAnswer('name'))
                return $name->display();
        } catch (Exception $e) {}
        return '';
    }

    /**
     * Auto-installer. Necessary for 1.8 users between the RC1 release and
     * the stable release who don't have the form in their database because
     * it wan't in the yaml file for installation or upgrade.
     */
    function __loadDefaultForm() {
        require_once(INCLUDE_DIR.'class.i18n.php');

        $i18n = new Internationalization();
        $tpl = $i18n->getTemplate('form.yaml');
        foreach ($tpl->getData() as $f) {
            if ($f['type'] == 'C') {
                $form = DynamicForm::create($f);
                $form->save();
                break;
            }
        }
    }
}
?>
