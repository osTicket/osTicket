<?php

/*
 * Loads the initial data for dynamic forms into the system. This is
 * preferred over providing the data inside the SQL scripts
 */

class DynamicFormLoader extends MigrationTask {
    var $description = "Loading initial data for dynamic forms";

    function run($max_time) {
        $i18n = new Internationalization('en_US');
        $forms = $i18n->getTemplate('form.yaml')->getData();
        foreach ($forms as &$f) {
            // Only import forms which exist at this stage.
            if (!in_array($f['type'], array('U', 'T', 'C', 'O')))
                continue;

            if ($f['fields'])  {
                foreach($f['fields'] as &$field) {
                    $flags = $field['flags'];
                    // Edit mask
                    $field['edit_mask'] = $this->f2m($flags);
                    // private
                    if (!($flags & DynamicFormField::FLAG_CLIENT_VIEW))
                        $field['private'] = true;
                    // required
                    if (($flags & DynamicFormField::FLAG_CLIENT_REQUIRED)
                            || ($flags & DynamicFormField::FLAG_AGENT_REQUIRED))
                        $field['required'] = true;

                    unset($field['flags']);
                }
                unset($field);
            }

            DynamicForm::create($f);
        }
        unset($f);
    }

    function f2m($flags) {
        $masks = array(
                1 => DynamicFormField::FLAG_MASK_DELETE,
                2 => DynamicFormField::FLAG_MASK_NAME,
                4 => DynamicFormField::FLAG_MASK_VIEW,
                8 => DynamicFormField::FLAG_MASK_REQUIRE
               );

        $mask = 0;
        foreach ($masks as $k => $v)
            if (($flags & $v) != 0)
                $mask += $k;

        return $mask;
   }

}

return 'DynamicFormLoader';

?>
