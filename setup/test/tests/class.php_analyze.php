<?php
require_once('class.test.php');

class SourceAnalyzer extends Test {
    static $super_globals = array(
        '$_SERVER'=>1, '$_FILES'=>1, '$_SESSION'=>1, '$_GET'=>1,
        '$_POST'=>1, '$_REQUEST'=>1, '$_ENV'=>1, '$_COOKIE'=>1);

    var $bugs = array();
    var $globals = array();
    var $file = '';

    function __construct($source) {
        $this->tokens = token_get_all(file_get_contents($source));
        $this->file = $source;
    }

    function parseFile() {
        $this->checkVariableUsage(
            array('line'=>array(0, $this->file), 'name'=>'(main)'),
            array(),
            1);
    }

    function traverseClass($line) {
        $class = array('line'=>$line);
        $token = false;
        $blocks = 0;
        $func_options = array('allow_this'=>true);
        while (list($i,$token) = each($this->tokens)) {
            switch ($token[0]) {
            case '{':
                $blocks++;
                break;
            case '}':
                if (--$blocks == 0)
                    return;
                break;
            case T_STRING:
                if (!isset($class['name']))
                    $class['name'] = $token[1];
                break;
            case T_FUNCTION:
                $this->traverseFunction(
                    array($token[2], $line[1]),
                    $func_options);
                // Continue to reset $func_options
            case ';':
                // Reset function options
                $func_options = array('allow_this'=>true);
                break;
            case T_STATIC:
                $func_options['allow_this'] = false;
                break;
            case T_VAR:
                // var $variable
                // used inside classes to define instance variables
                while (list(,$token) = each($this->tokens)) {
                    if (is_array($token) && $token[0] == T_VARIABLE)
                        // TODO: Add this to some class context in the
                        // future to support indefined access to $this->blah
                        break;
                }
                break;
            }
        }
    }

    function traverseFunction($line=0, $options=array()) {
        // Scan for function name
        $function = array('line'=>$line, 'name'=>'(inline)');
        $token = false;
        $scope = array();
        while ($token != "{") {
            list(,$token) = each($this->tokens);
            switch ($token[0]) {
            case T_WHITESPACE:
                continue;
            case T_STRING:
                $function['name'] = $token[1];
                break;
            case T_VARIABLE:
                $scope[$token[1]] = 1;
                break;
            case ';':
                // Abstract function -- no body will follow
                return;
            }
        }
        // Start inside a block -- we've already consumed the {
        $this->checkVariableUsage($function, $scope, 1, $options);
    }

    function checkVariableUsage($function, $scope=array(), $blocks=0,
            $options=array()) {
        // Merge in defaults to the options array
        $options = array_merge(array(
            'allow_this' => false,
            ), $options);
        // Unpack function[line][file] if set
        if (is_array($function['line']))
            $function['file'] = $function['line'][1];
        while (list($i,$token) = each($this->tokens)) {
            // Check variable usage and for nested blocks
            switch ($token[0]) {
            case '{':
                $blocks++;
                break;
            case '}':
                if (--$blocks == 0)
                    return $scope;
                break;
            case T_VARIABLE:
                // Look-ahead for assignment
                $assignment = false;
                while ($next = @$this->tokens[++$i])
                    if (!is_array($next) || $next[0] != T_WHITESPACE)
                        break;
                switch ($next[0]) {
                case '=':
                    // For assignment, check if the variable is explictly
                    // assigned to NULL. If so, treat the assignment as an
                    // unset()
                    while ($next = @$this->tokens[++$i])
                        if (!is_array($next) || $next[0] != T_WHITESPACE)
                            break;
                    if (is_array($next) && strcasecmp('NULL', $next[1]) === 0) {
                        $scope[$token[1]] = 'null';
                        $assignment = true;
                        break;
                    }
                case T_AND_EQUAL:
                case T_CONCAT_EQUAL:
                case T_DIV_EQUAL:
                case T_MINUS_EQUAL:
                case T_MOD_EQUAL:
                case T_MUL_EQUAL:
                case T_OR_EQUAL:
                case T_PLUS_EQUAL:
                case T_SL_EQUAL:
                case T_SR_EQUAL:
                case T_XOR_EQUAL:
                    $assignment = true;
                    $scope[$token[1]] = 1;
                    break;
                }
                if ($assignment)
                    break;

                if (!isset($scope[$token[1]])) {
                    if ($token[1] == '$this' && $options['allow_this']) {
                        // Always valid in a non-static class method
                        // TODO: Determine if this function is defined in a class
                        break;
                    }
                    elseif (isset(static::$super_globals[$token[1]]))
                        // Super globals are always in scope
                        break;
                    elseif (!isset($function['name']) || $function['name'] == '(main)')
                        // Not in a function. Cowardly continue.
                        // TODO: Recurse into require() statements to
                        // determine if global variables are actually
                        // defined somewhere in the code base
                        break;
                    $this->bugs[] = array(
                        'type' => 'UNDEF_ACCESS',
                        'func' => $function['name'],
                        'line' => array($token[2], $function['file']),
                        'name' => $token[1],
                    );
                }
                elseif ($scope[$token[1]] == 'null') {
                    // See if the next token is accessing a property of the
                    // object
                    $c = current($this->tokens);
                    switch ($c[0]) {
                    case T_OBJECT_OPERATOR:
                    case T_PAAMAYIM_NEKUDOTAYIM:
                    case '[':
                        $this->bugs[] = array(
                            'type' => 'MAYBE_UNDEF_ACCESS',
                            'func' => $function['name'],
                            'line' => array($token[2], $function['file']),
                            'name' => $token[1],
                        );
                    }
                }
                break;
            case T_PAAMAYIM_NEKUDOTAYIM:
                // Handle static variables $instance::$static
                $current = current($this->tokens);
                if ($current[0] == T_VARIABLE)
                    next($this->tokens);
                break;
            case T_CLASS:
                // XXX: PHP does not allow nested classes
                $this->traverseClass(
                    array($token[2], $function['file']));
                break;
            case T_FUNCTION:
                // PHP does not automatically nest scopes. Variables
                // available inside the closure must be explictly defined.
                // Therefore, there is no need to pass the current scope.
                // However, $this is not allowed inside inline functions
                // unless declared in the use () parameters.
                $this->traverseFunction(
                    array($token[2], $function['file']),
                    array('allow_this'=>false));
                break;
            case T_STATIC:
                $c = current($this->tokens);
                // (nolint) static::func() or static::$var
                if ($c[0] == T_PAAMAYIM_NEKUDOTAYIM)
                    break;
            case T_GLOBAL:
                while (list(,$token) = each($this->tokens)) {
                    if ($token == ';')
                        break;
                    elseif (!is_array($token))
                        continue;
                    elseif ($token[0] == T_VARIABLE)
                        $scope[$token[1]] = 1;
                }
                break;
            case T_FOR:
                // for ($i=0;...)
                // Find first semi-colon, variables defined before it should
                // be added to the current scope
                while (list(,$token) = each($this->tokens)) {
                    if ($token == ';')
                        break;
                    elseif (!is_array($token))
                        continue;
                    elseif ($token[0] == T_VARIABLE)
                        $scope[$token[1]] = 1;
                }
                break;
            case T_FOREACH:
                // foreach ($blah as $a=>$b) -- add $a, $b to the local
                // scope
                $after_as = false;
                $parens = 0;
                // Scan for the variables defined for the scope of the
                // foreach block
                while (list(,$token) = each($this->tokens)) {
                    if ($token == '(')
                        $parens++;
                    elseif ($token == ')' && --$parens == 0)
                        break;
                    elseif (!is_array($token))
                        continue;
                    elseif ($token[0] == T_AS)
                        $after_as = true;
                    elseif ($after_as && $token[0] == T_VARIABLE)
                        // Technically, variables defined in a foreach()
                        // block are still accessible after the completion
                        // of the foreach block
                        $scope[$token[1]] = 1;
                }
                break;
            case T_LIST:
                // list($a, $b) = ...
                // Find all variables defined up to the closing parenthesis
                while (list(,$token) = each($this->tokens)) {
                    if ($token == ')')
                        break;
                    elseif (!is_array($token))
                        continue;
                    elseif ($token[0] == T_VARIABLE)
                        $scope[$token[1]] = 1;
                }
                break;
            case T_ISSET:
                // isset($var)
                // $var is allowed to be undefined and not be an error.
                // Consume tokens until close parentheses
                while (list(,$token) = each($this->tokens)) {
                    if ($token == ')')
                        break;
                }
                break;
            case T_UNSET:
                // unset($var)
                // Var will no longer be in scope
                while (list(,$token) = each($this->tokens)) {
                    if ($token == ')')
                        break;
                    elseif (is_array($token) && $token[0] == T_VARIABLE) {
                        // Check for unset($var[key]) -- don't unset anything
                        // Check for unset($this->blah)
                        $next = current($this->tokens);
                        switch ($next[0]) {
                        case '[':
                        case T_OBJECT_OPERATOR:
                            break;
                        default:
                            unset($scope[$token[1]]);
                        }
                        break;
                    }
                }
                break;
            case T_CATCH:
                // catch (Exception $var) {
                while (list(,$token) = each($this->tokens)) {
                    if ($token == '{')
                        break;
                    elseif ($token[0] == T_VARIABLE)
                        $variable = $token[1];
                }
                $scope[$variable] = 1;
                $scope = $this->checkVariableUsage($function, $scope, 1,
                    $options);
                // Variable is no longer in scope; however, other variables
                // defined in the catch {} block remain in scope.
                // (Technically, the variable is in scope, but we will
                // consider it bad coding practice to deal with an exception
                // outisde the catch block
                unset($scope[$variable]);
                break;
            case T_DOLLAR_OPEN_CURLY_BRACES:
            case T_CURLY_OPEN:
                // "{$a .. }"
                // This screws up block detection. We will see another close
                // brace somewhere along the way
                $blocks++;
                break;
            }
        }
    }
}
?>
