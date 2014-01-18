<?php

class Option {

    var $default = false;

    function Option() {
        call_user_func_array(array($this, "__construct"), func_get_args());
    }

    function __construct($options=false) {
        list($this->short, $this->long) = array_slice($options, 0, 2);
        $this->help = (isset($options['help'])) ? $options['help'] : "";
        $this->action = (isset($options['action'])) ? $options['action']
            : "store";
        $this->dest = (isset($options['dest'])) ? $options['dest']
            : substr($this->long, 2);
        $this->type = (isset($options['type'])) ? $options['type']
            : 'string';
        $this->const = (isset($options['const'])) ? $options['const']
            : null;
        $this->default = (isset($options['default'])) ? $options['default']
            : null;
        $this->metavar = (isset($options['metavar'])) ? $options['metavar']
            : 'var';
        $this->nargs = (isset($options['nargs'])) ? $options['nargs']
            : 1;
    }

    function hasArg() {
        return $this->action != 'store_true'
            && $this->action != 'store_false';
    }

    function handleValue(&$destination, $args) {
        $nargs = 0;
        $value = ($this->hasArg()) ? array_shift($args) : null;
        if ($value[0] == '-')
            $value = null;
        elseif ($value)
            $nargs = 1;
        if ($this->type == 'int')
            $value = (int)$value;
        switch ($this->action) {
            case 'store_true':
                $value = true;
                break;
            case 'store_false':
                $value = false;
                break;
            case 'store_const':
                $value = $this->const;
                break;
            case 'append':
                if (!isset($destination[$this->dest]))
                    $destination[$this->dest] = array($value);
                else {
                    $T = &$destination[$this->dest];
                    $T[] = $value;
                    $value = $T;
                }
                break;
            case 'store':
            default:
                break;
        }
        $destination[$this->dest] = $value;
        return $nargs;
    }

    function toString() {
        $short = explode(':', $this->short);
        $long = explode(':', $this->long);
        if ($this->nargs === '?')
            $switches = sprintf('    %s [%3$s], %s[=%3$s]', $short[0],
                $long[0], $this->metavar);
        elseif ($this->hasArg())
            $switches = sprintf('    %s %3$s, %s=%3$s', $short[0], $long[0],
                $this->metavar);
        else
            $switches = sprintf("    %s, %s", $short[0], $long[0]);
        $help = preg_replace('/\s+/', ' ', $this->help);
        if (strlen($switches) > 23)
            $help = "\n" . str_repeat(" ", 24) . $help;
        else
            $switches = str_pad($switches, 24);
        $help = wordwrap($help, 54, "\n" . str_repeat(" ", 24));
        return $switches . $help;
    }
}

class OutputStream {
    var $stream;

    function OutputStream() {
        call_user_func_array(array($this, '__construct'), func_get_args());
    }
    function __construct($stream) {
        $this->stream = fopen($stream, 'w');
    }

    function write($what) {
        fwrite($this->stream, $what);
    }
}

class Module {

    var $options = array();
    var $arguments = array();
    var $prologue = "";
    var $epilog = "";
    var $usage = '$script [options] $args [arguments]';
    var $autohelp = true;
    var $module_name;

    var $stdout;
    var $stderr;

    var $_options;
    var $_args;

    function Module() {
        call_user_func_array(array($this, '__construct'), func_get_args());
    }

    function __construct() {
        $this->options['help'] = array("-h","--help",
            'action'=>'store_true',
            'help'=>"Display this help message");
        foreach ($this->options as &$opt)
            $opt = new Option($opt);
        $this->stdout = new OutputStream('php://output');
        $this->stderr = new OutputStream('php://stderr');
    }

    function showHelp() {
        if ($this->prologue)
            echo $this->prologue . "\n\n";

        global $argv;
        $manager = @$argv[0];

        echo "Usage:\n";
        echo "    " . str_replace(
                array('$script', '$args'),
                array($manager ." ". $this->module_name, implode(' ', array_keys($this->arguments))),
            $this->usage) . "\n";

        ksort($this->options);
        if ($this->options) {
            echo "\nOptions:\n";
            foreach ($this->options as $name=>$opt)
                echo $opt->toString() . "\n";
        }

        if ($this->arguments) {
            echo "\nArguments:\n";
            foreach ($this->arguments as $name=>$help)
                $extra = '';
                if (is_array($help)) {
                    if (isset($help['options']) && is_array($help['options'])) {
                        foreach($help['options'] as $op=>$desc)
                            $extra .= wordwrap(
                                "\n        $op - $desc", 76, "\n            ");
                    }
                    $help = $help['help'];
                }
                echo $name . "\n    " . wordwrap(
                    preg_replace('/\s+/', ' ', $help), 76, "\n    ")
                        .$extra."\n";
        }

        if ($this->epilog) {
            echo "\n\n";
            $epilog = preg_replace('/\s+/', ' ', $this->epilog);
            echo wordwrap($epilog, 76, "\n");
        }

        echo "\n";
    }

    function getOption($name, $default=false) {
        $this->parseOptions();
        if (isset($this->_options[$name]))
            return $this->_options[$name];
        elseif (isset($this->options[$name]) && $this->options[$name]->default)
            return $this->options[$name]->default;
        else
            return $default;
    }

    function getArgument($name, $default=false) {
        $this->parseOptions();
        if (isset($this->_args[$name]))
            return $this->_args[$name];
        return $default;
    }

    function parseOptions() {
        if (is_array($this->_options))
            return;

        global $argv;
        list($this->_options, $this->_args) =
            $this->parseArgs(array_slice($argv, 1));

        foreach (array_keys($this->arguments) as $idx=>$name)
            if (!isset($this->_args[$idx]))
                $this->optionError($name . " is a required argument");
            elseif (is_array($this->arguments[$name])
                    && isset($this->arguments[$name]['options'])
                    && !isset($this->arguments[$name]['options'][$this->_args[$idx]]))
                $this->optionError($name . " does not support such a value");
            else
                $this->_args[$name] = &$this->_args[$idx];

        foreach ($this->options as $name=>$opt)
            if (!isset($this->_options[$name]))
                $this->_options[$name] = $opt->default;

        if ($this->autohelp && $this->getOption('help')) {
            $this->showHelp();
            die();
        }
    }

    function optionError($error) {
        echo "Error: " . $error . "\n\n";
        $this->showHelp();
        die();
    }

    function _run($module_name) {
        $this->module_name = $module_name;
        $this->parseOptions();
        return $this->run($this->_args, $this->_options);
    }

    /* abstract */
    function run($args, $options) {
    }

    function fail($message) {
        $this->stderr->write($message . "\n");
        die();
    }

    /* static */
    function register($action, $class) {
        global $registered_modules;
        $registered_modules[$action] = new $class();
    }

    /* static */ function getInstance($action) {
        global $registered_modules;
        return $registered_modules[$action];
    }

    function parseArgs($argv) {
        $options = $args = array();
        $argv = array_slice($argv, 0);
        while ($arg = array_shift($argv)) {
            if (strpos($arg, '=') !== false) {
                list($arg, $value) = explode('=', $arg, 2);
                array_unshift($argv, $value);
            }
            $found = false;
            foreach ($this->options as $opt) {
                if ($opt->short == $arg || $opt->long == $arg) {
                    if ($opt->handleValue($options, $argv))
                        array_shift($argv);
                    $found = true;
                }
            }
            if (!$found && $arg[0] != '-')
                $args[] = $arg;
        }
        return array($options, $args);
    }
}

$registered_modules = array();

?>
