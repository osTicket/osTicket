<?php

require_once INCLUDE_DIR . 'class.plugin.php';
include_once INCLUDE_DIR . 'class.sla.php' ;

class SlaAddOnPluginConfig extends PluginConfig {
 
     // Provide compatibility function for versions of osTicket prior to
    // translation support (v1.9.4)
    function translate() {
        if (!method_exists('Plugin', 'translate')) {
            return array(
                function ($x) {
                    return $x;
                },
                function ($x, $y, $n) {
                    return $n != 1 ? $y : $x;
                }
            );
        }
        return Plugin::translate('teams');
    }

     function pre_save(&$config, &$errors) {
       /* if ($config['slack-regex-subject-ignore'] && false === @preg_match("/{$config['slack-regex-subject-ignore']}/i", null)) {
            $errors['err'] = 'Your regex was invalid, try something like "spam", it will become: "/spam/i" when we use it.';
            return FALSE;
        }*/
		global $msg;
        if (!$errors)
            $msg = __('Configuration updated successfully');
        return true;
        
    } 

    function getOptions() {
        list ($__, $_N) = self::translate();
        $sla = new SLA();
		$choices = SLA::getSLAs();
        return array(
            'sla-addon'                      => new SectionBreakField(array(
                'label' => $__('SLA AddOn To improve or customize SLA'),
                'hint'  => $__('Readme first')
            )),
			
			 'sla-plans'          => new ChoiceField(array(
                'label'         => $__('Select SLA PLAN'),
				'choices' => $choices, 
                'configuration' => array(				                        
                    'size'   => 100,
                    'length' => 500
                ),
            )), 
            'first-response-time'          => new TextboxField(array(
                'label'         => $__('First Response Time (- in hours)  '),
				 'validator' => 'number',
                'configuration' => array(
                    'size'   => 10,
                    'length' => 2
                ),
            )),
			 'temporary-solution-time'          => new TextboxField(array(
                'label'         => $__('Temporary Solution Time  (- in hours)  '),
                 'validator' => 'number',
				'configuration' => array(
                    'size'   => 10,
                    'length' => 2
                ),
            )),
			'final-solution-time'          => new TextboxField(array(
                'label'         => $__('Final Solution Time (- in hours) '),                
                 'validator' => 'number',
				'configuration' => array(
                    'size'   => 10,
                    'length' => 2
                ),
            )),
            /*'teams-regex-subject-ignore' => new TextboxField([
                'label'         => $__('Ignore when subject equals regex'),
                'hint'          => $__('Auto delimited, always case-insensitive'),
                'configuration' => [
                    'size'   => 30,
                    'length' => 200
                ],
            ])*/
            
        );
    }

}
