<?php
/*********************************************************************
    class.filter.php

    Variable replacer 
    
    Used to resolve and replace variables.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class VariableReplacer {

    var $start_delim;
    var $end_delim;

    var $objects;
    var $variables;

    var $errors;

    function VariableReplacer($start_delim='%{', $end_delim='}') {

        $this->start_delim = $start_delim;
        $this->end_delim = $end_delim;

        $this->objects = array();
        $this->variables = array();
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

    function assign($tag, $val) {
        
        if($val && is_object($val))
            $this->objects[$tag] = $val;
        else
            $this->variables[$tag] = $val;
    }

    function getVar($obj, $var) {

        if(!$var && is_callable(array($obj, 'asVar')))
            return call_user_func(array($obj, 'asVar'));

        if($var && is_callable(array($this, 'get'.ucfirst($var))))
            return call_user_func(array($this, 'get'.ucfirst($var)));

        if(!$var || !is_callable(array($obj, 'getVar')))
            return null;

        $parts = explode('.', $var);
        if(($rv = call_user_func(array($obj, 'getVar'), $parts[0]))===false)
            return null;

        if(!is_object($rv))
            return $rv;
            
        list(, $part) = explode('.', $var, 2);
        
        return $this->getVar($rv, $part);
    }

    function replaceVars($text) {

        if(!($vars=$this->_parse($text)))
            return $text;

        return preg_replace($this->_delimit(array_keys($vars)), array_values($vars), $text);
    }

    function _resolveVar($var) {

        //Variable already memoized?
        if($var && @$this->variables[$var])
            return $this->variables[$var];

        $parts = explode('.', $var, 2);
        if(!$parts || !($obj=$this->getObj($parts[0]))) {
            $this->setError('Unknown obj for "'.$var.'" tag ');
            return null;
        }

        return $this->getVar($obj, $parts[1]);
    }

    function _parse($text) {

        $input = $text;
        if(!preg_match_all('/'.$this->start_delim.'([A-Za-z\._]+)'.$this->end_delim.'/', $input, $result))
            return null;

        $vars = array();
        foreach($result[0] as $k => $v) {
            if(!@$vars[$v] && ($val=$this->_resolveVar($result[1][$k])))
                $vars[$v] = $val;
        }

        return $vars;
    }

    //Helper function - will be replaced by a lambda function (PHP 5.3+)
    function _delimit($val, $d='/') {

        if($val && is_array($val))
            return array_map(array($this, '_delimit'), $val);

        return $d.$val.$d;
    }
}
?>
