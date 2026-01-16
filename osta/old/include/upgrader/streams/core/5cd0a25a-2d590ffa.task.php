<?php

class InstructionsPorter extends MigrationTask {
    var $description = "Converting custom form instructions to HTML";

    function run($max_time) {
        foreach (DynamicForm::objects() as $F) {
            $F->instructions = Format::htmlchars($F->get('instructions'));
            $F->save();
        }
        foreach (DynamicFormField::objects() as $F){
            $F->hint = Format::htmlchars($F->get('hint'));
            $F->save();
        }
    }
}

return 'InstructionsPorter';
