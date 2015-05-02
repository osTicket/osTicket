<?php
/*********************************************************************
    class.variable.php

    Variable replacer

    Used to parse, resolve and replace variables.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class VariableReplacer {

    var $start_delim;
    var $end_delim;

    var $objects = array();
    var $variables = array();
    var $extras = array();

    var $errors;

    function VariableReplacer($start_delim='%{', $end_delim='}') {

        $this->start_delim = $start_delim;
        $this->end_delim = $end_delim;
    }

    function setError($error) {
        $this->errors[] = $error;
    }

    function getErrors() {
        return $this->errors;
    }

    function getObj($tag) {
        return @$this->objects[$tag];
    }

    function assign($var, $val='') {

        if($val && is_object($val)) {
            $this->objects[$var] = $val;
        } elseif($var && is_array($var)) {
            foreach($var as $k => $v)
                $this->assign($k, $v);
        } elseif($var) {
            $this->variables[$var] = $val;
        }
    }

    function getVar($obj, $var) {

        if(!$obj) return "";

        if (!$var) {
            if (method_exists($obj, 'asVar'))
                return call_user_func(array($obj, 'asVar'), $this);
            elseif (method_exists($obj, '__toString'))
                return (string) $obj;
        }

        list($v, $part) = explode('.', $var, 2);
        if ($v && is_callable(array($obj, 'get'.ucfirst($v)))) {
            $rv = call_user_func(array($obj, 'get'.ucfirst($v)), $this);
            if(!$rv || !is_object($rv))
                return $rv;

            return $this->getVar($rv, $part);
        }

        if (is_array($obj) && isset($obj[$v]))
            return $obj[$v];

        if (!$var || !method_exists($obj, 'getVar'))
            return "";

        list($tag, $remainder) = explode('.', $var, 2);
        if(($rv = call_user_func(array($obj, 'getVar'), $tag, $this))===false)
            return "";

        if(!is_object($rv))
            return $rv;

        return $this->getVar($rv, $remainder);
    }

    function replaceVars($input) {

        // Preserve existing extras
        if ($input instanceof TextWithExtras)
            $this->extras = $input->extras;

        if($input && is_array($input))
            return array_map(array($this, 'replaceVars'), $input);

        if(!($vars=$this->_parse($input)))
            return $input;

        $text = str_replace(array_keys($vars), array_values($vars), $input);
        if ($this->extras) {
            return new TextWithExtras($text, $this->extras);
        }
        return $text;
    }

    function _resolveVar($var) {

        //Variable already memoized?
        if($var && @isset($this->variables[$var]))
            return $this->variables[$var];

        $parts = explode('.', $var, 2);
        try {
            if ($parts && ($obj=$this->getObj($parts[0])))
                return $this->getVar($obj, $parts[1]);
        }
        catch (OOBContent $content) {
            $type = $content->getType();
            $existing = @$this->extras[$type] ?: array();
            $this->extras[$type] = array_merge($existing, $content->getContent());
            return '';
        }

        if ($parts[0] && @isset($this->variables[$parts[0]])) { //root override
            if (is_array($this->variables[$parts[0]])
                    && isset($this->variables[$parts[0]][$parts[1]]))
                return $this->variables[$parts[0]][$parts[1]];

            return $this->variables[$parts[0]];
        }

        //Unknown object or variable - leavig it alone.
        $this->setError(sprintf(__('Unknown object for "%s" tag'), $var));
        return false;
    }

    function _parse($text) {

        $input = $text;
        $result = array();
        if(!preg_match_all('/'.$this->start_delim.'([A-Za-z_][\w._]+)'.$this->end_delim.'/',
                $input, $result))
            return null;

        $vars = array();
        foreach($result[0] as $k => $v) {
            if(isset($vars[$v])) continue;
            $val=$this->_resolveVar($result[1][$k]);
            if($val!==false)
                $vars[$v] = $val;
        }

        return $vars;
    }

    static function compileScope($scope, $recurse=5, $exclude=false) {
        $items = array();
        foreach ($scope as $name => $info) {
            if ($exclude === $name)
                continue;
            if (isset($info['class']) && $recurse) {
                $items[$name] = $info['desc'];
                foreach (static::compileScope($info['class']::getVarScope(), $recurse-1,
                    @$info['exclude'] ?: $name)
                as $name2=>$desc) {
                    $items["{$name}.{$name2}"] = $desc;
                }
            }
            if (!is_array($info)) {
                $items[$name] = $info;
            }
        }
        return $items;
    }

    static function compileFormScope($form) {
        $items = array();
        foreach ($form->getFields() as $f) {
            if (!($name = $f->get('name')))
                continue;
            if (!$f->isStorable() || !$f->hasData())
                continue;

            $desc = $f->getLocal('label');
            if (($class = $f->asVarType()) && class_exists($class)) {
                $desc = array('desc' => $desc, 'class' => $class);
            }
            $items[$name] = $desc;
            foreach (VariableReplacer::compileFieldScope($f) as $name2=>$desc) {
                $items["$name.$name2"] = $desc;
            }
        }
        return $items;
    }

    static function compileFieldScope($field, $recurse=2, $exclude=false) {
        $items = array();
        if (!$field->hasSubFields())
            return $items;

        foreach ($field->getSubFields() as $f) {
            if (!($name = $f->get('name')))
                continue;
            if ($exclude === $name)
                continue;
            $items[$name] = $f->getLabel();
            if ($recurse) {
                foreach (static::compileFieldScope($f, $recurse-1, $name)
                as $name2=>$desc) {
                    if (($class = $f->asVarType()) && class_exists($class)) {
                        $desc = array('desc' => $desc, 'class' => $class);
                    }
                    $items["$name.$name2"] = $desc;
                }
            }
        }
        return $items;
    }

    static function getContextForRoot($root) {
        switch ($root) {
        case 'cannedresponse':
            $roots = array('ticket');
            break;

        default:
            if ($info = Page::getContext($root)) {
                $roots = $info;
                break;
            }

            // Get the context for an email template
            if ($tpl_info = EmailTemplateGroup::getTemplateDescription($root))
                $roots = $tpl_info['context'];
        }

        if (!$roots)
            return false;

        $contextTypes = array(
            'activity' => __('Type of recent activity'),
            'assignee' => array('class' => 'Staff', 'desc' => __('Assigned agent/team')),
            'assigner' => array('class' => 'Staff', 'desc' => __('Agent performing the assignment')),
            'comments' => __('Assign/transfer comments'),
            'link' => __('Access link'),
            'message' => array('class' => 'MessageThreadEntry', 'desc' => 'Message from the EndUser'),
            'note' => array('class' => 'NoteThreadEntry', 'desc' => __('Internal note')),
            'poster' => array('class' => 'User', 'desc' => 'EndUser or Agent originating the message'),
            // XXX: This could be EndUser -or- Staff object
            'recipient' => array('class' => 'TicketUser', 'desc' => 'Message recipient'),
            'response' => array('class' => 'ResponseThreadEntry', 'desc' => __('Outgoing response')),
            'signature' => 'Selected staff or department signature',
            'staff' => array('class' => 'Staff', 'desc' => 'Agent originating the activity'),
            'ticket' => array('class' => 'Ticket', 'desc' => 'The ticket'),
            'user' => array('class' => 'User', 'desc' => __('Message recipient')),
        );
        $context = array();
        foreach ($roots as $C=>$desc) {
            // $desc may be either the root or the description array
            if (is_array($desc))
                $context[$C] = $desc;
            else
                $context[$desc] = $contextTypes[$desc];
        }
        $global = osTicket::getVarScope();
        return self::compileScope($context + $global);
    }
}

class PlaceholderList
/* implements TemplateVariable */ {
    var $items;

    function __construct($items) {
        $this->items = $items;
    }

    function asVar() {
        $items = array();
        foreach ($this->items as $I) {
            if (method_exists($I, 'asVar')) {
                $items[] = $I->asVar();
            }
            else {
                $items[] = (string) $I;
            }
        }
        return implode(',', $items);
    }

    function getVar($tag) {
        $items = array();
        foreach ($this->items as $I) {
            if (is_object($I) && method_exists($I, 'get'.ucfirst($tag))) {
                $items[] = call_user_func(array($I, 'get'.ucfirst($tag)));
            }
            elseif (method_exists($I, 'getVar')) {
                $items[] = $I->getVar($tag);
            }
        }
        if (count($items) == 1) {
            return $items[0];
        }
        return new static($items);
    }
}

/**
 * Exception used in the variable replacement process to indicate non text
 * content (such as attachments)
 */
class OOBContent extends Exception {
    var $type;
    var $content;

    const FILES = 'files';

    function __construct($type, $content) {
        $this->type = $type;
        $this->content = $content;
    }

    function getType() { return $this->type; }
    function getContent() { return $this->content; }
}

class TextWithExtras {
    var $text = '';
    var $extras;

    function __construct($text, array $extras) {
        $this->setText($text);
        $this->extras = $extras;
    }

    function setText($text) {
        try {
            $this->text = (string) $text;
        }
        catch (Exception $e) {
            throw new InvalidArgumentException('String type is required', 0, $e);
        }
    }

    function __toString() {
        return $this->text;
    }

    function getFiles() {
        return $this->extras[OOBContent::FILES];
    }
}

interface TemplateVariable {
    // function asVar(); — not absolutely required
    // function getVar($name); — not absolutely required
    static function getVarScope();
}
?>
