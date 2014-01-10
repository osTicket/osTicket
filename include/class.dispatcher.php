<?php
/*********************************************************************
    class.dispatcher.php

    Dispatcher that will read files with URL lists in them and attempt to
    match the URL requested to a function that should be invoked to handle
    the request.

    Jared Hancock
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

/**
 * URL resolver and dispatcher. It's meant to be quite lightweight, so the
 * functions aren't separated
 */
class Dispatcher {
    function Dispatcher($file=false) {
        $this->urls = array();
        $this->file = $file;
    }

    function resolve($url, $args=null) {
        if ($this->file) { $this->lazy_load(); }
        # Support HTTP method emulation with the _method GET argument
        if (isset($_GET['_method'])) {
            $_SERVER['REQUEST_METHOD'] = strtoupper($_GET['_method']);
            unset($_GET['_method']);
        }
        foreach ($this->urls as $matcher) {
            if ($matcher->matches($url)) {
                return $matcher->dispatch($url, $args);
            }
        }
        Http::response(400, "URL not supported");
    }
    /**
     * Returns the url for the given function and arguments (arguments
     * aren't declared, but will be handled
     */
    function reverse($func) { }
    /**
     * Add the url to the list of supported URLs
     */
    function append($url, $prefix=false) {
        if ($prefix) { $url->setPrefix($prefix); }
        array_push($this->urls, $url);
    }
    /**
     * Add the urls from another dispatcher onto this one
     */
    function extend($dispatcher) {
        foreach ($dispatcher->urls as $url) { $this->append($url); }
        /* allow inlining / chaining */ return $this;
    }

    /* static */ function include_urls($file, $absolute=false, $lazy=true) {
        if (!$absolute) {
            # Fetch the working path of the caller
            $bt = debug_backtrace();
            $file = dirname($bt[0]["file"]) . "/" . $file;
        }
        if ($lazy) return new Dispatcher($file);
        else return (include $file);
    }
    /**
     * The include_urls() method will create a new Dispatcher and set the
     * $this->file to where the file to be loaded is located. When this
     * dispatcher is first accessed, the file will be loaded.
     */
    function lazy_load() {
        $this->extend(include $this->file);
        $this->file=false;
    }
}

class UrlMatcher {
    function UrlMatcher($regex, $func, $args=false, $method=false) {
        # Add the slashes for the Perl syntax
        $this->regex = "@" . $regex . "@";
        $this->func = $func;
        $this->args = ($args) ? $args : array();
        $this->prefix = false;
        $this->method = $method;
    }

    function setPrefix($prefix) { $this->prefix = $prefix; }

    function matches($url) {
        if ($this->method && $_SERVER['REQUEST_METHOD'] != $this->method) {
            return false;
        }
        return preg_match($this->regex, $url, $this->matches) == 1;
    }

    function dispatch($url, $prev_args=null) {

        # Remove named values from the match array
        $f = array_filter(array_keys($this->matches), 'is_numeric');
        $this->matches = array_intersect_key($this->matches, array_flip($f));

        if (@get_class($this->func) == "Dispatcher") {
            # Trim the leading match off the $url and call the
            # sub-dispatcher. This will be the case for lines in the URL
            # file like
            # url("^/blah", Dispatcher::include_urls("blah/urls.conf.php"))
            # Also, pass arguments matched so far (if any) to the receiving
            # resolve() method by merging the $prev_args into $this->matches
            # (excluding $this->matches[0], which is the matched URL at this
            # level)
            return $this->func->resolve(
                substr($url, strlen($this->matches[0])),
                array_merge(($prev_args) ? $prev_args : array(),
                    array_slice($this->matches, 1)));
        }

        # Drop the first item of the matches array (which is the whole
        # matched url). Then merge in any initial arguments.
        unset($this->matches[0]);

        # Prepend received arguments (from a parent Dispatcher). This is
        # different from the static args, which are postpended
        if (is_array($prev_args))
            $args = array_merge($prev_args, $this->matches);
        else $args = $this->matches;
        # Add in static args specified in the constructor
        $args = array_merge($args, $this->args);
        # Apply the $prefix given
        list($class, $func) = $this->apply_prefix();
        if ($class) {
            # Create instance of the class, which is the first item,
            # then call the method which is the second item
            $func = array(new $class, $func);
        }

        if (!is_callable($func))
            Http::response(500, 'Dispatcher compile error. Function not callable');

        return call_user_func_array($func, $args);
    }
    /**
     * For the $prefix recieved by the constuctor, prepend it to the
     * received $class, if any, then make an import if necessary. Lastly,
     * return the appropriate $class, and $func that should be invoked to
     * dispatch the URL.
     */
    function apply_prefix() {
        if (is_array($this->func)) { list($class, $func) = $this->func; }
        else { $func = $this->func; $class = ""; }
        if (is_object($class))
            return array(false, $this->func);
        if ($this->prefix)
            $class = $this->prefix . $class;

        if (strpos($class, ":")) {
            list($file, $class) = explode(":", $class, 2);
            include $file;
        }
        return array($class, $func);
    }
}

function patterns($prefix) {
    $disp = new Dispatcher();
    for ($i=1, $k=func_num_args(); $i<$k; $i++) {
        # NOTE: that $prefix is added to each url rather than to the
        #       dispatcher as a whole so that urls can be copied from one
        #       dispatcher to another (via the ->extend() method) and
        #       completely maintain their integrity
        $disp->append(func_get_arg($i), $prefix);
    }
    return $disp;
}

function url($regex, $func, $args=false, $method=false) {
    return new UrlMatcher($regex, $func, $args, $method);
}

function url_post($regex, $func, $args=false) {
    return url($regex, $func, $args, "POST");
}

function url_get($regex, $func, $args=false) {
    return url($regex, $func, $args, "GET");
}

function url_delete($regex, $func, $args=false) {
    return url($regex, $func, $args, "DELETE");
}
?>
