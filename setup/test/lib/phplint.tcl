#!/usr/bin/tclsh

# Copyright (C) 2007 Salvatore Sanfilippo <antirez at gmail dot com>
# This software is released under the GPL license version 2

proc scan file {
    set fd [open $file]
    set infunc 0
    set linenr 0
    set fnre {(^\s*)((public|private|protected|static)\s*)*function\s+([^(]+)\s*\((.*)\).*}
    while {[gets $fd line] != -1} {
        incr linenr
        if {[regexp $fnre $line - ind - - - fa]} {
            # If $infunc is true we miss the end of the last function
            # so we analyze it now.
            if {$infunc} {
                analyze $file $arglist $body
            }
            set body {}
            set arglist {}
            foreach arg [split $fa ,] {
                # remove default value
                regsub {=.*} $arg {} arg
                # remove optional type spec
                regsub {^.*\s+} [string trim $arg] {} arg
                set arg [string trim $arg " $&"]
                lappend arglist $arg
            }
            set infunc 1
        } elseif {$infunc && [regexp "^$ind\}" $line]} {
            set infunc 0
            analyze $file $arglist $body
        } elseif {$infunc} {
            lappend body $linenr [string trim $line]
        }
    }
}

proc analyze {file arglist body} {
    set initialized(this) 1
    set linton 1
    foreach arg $arglist {
        set initialized($arg) 1
    }
    # Superglobals
    set superglobals {
        "GLOBALS"
        "_SESSION"
        "_SESSION"
        "_GET"
        "_POST"
        "_REQUEST"
        "_ENV"
        "_SERVER"
        "_FILES"
        "php_errormsg"
    }
    foreach sg $superglobals {
        set initialized($sg) 1
    }
    # analyze body
    foreach {linenr line} $body {
        # Handle annotations
        if {[string first {nolint} [string tolower $line]] != -1} continue
        if {[string first {linton} [string tolower $line]] != -1} {
            if {$linton == 1} {
                puts "! Warning 'linton' annotation with lint already ON"
                continue
            }
            set linton 1
            puts ". $skipped lines skipped in $file from line $skipstart"
        }
        if {[string first {lintoff} [string tolower $line]] != -1} {
            if {$linton == 0} {
                puts "! Warning 'lintoff' annotation with lint already OFF"
                continue
            }
            set linton 0
            set skipped 0
            set skipstart [expr {$linenr+1}]
            continue
        }
        if {$linton == 0} {
            incr skipped
            continue
        }
        # Skip comments
        if {[string index $line 0] eq {#}} continue
        if {[string index $line 0] eq {/} && [string index $line 1] eq {/}} continue
        # PHP variable regexp
        set varre {\$[_A-Za-z]+[_A-Za-z0-9]*(\[[^\]]*\])*}
        # Check for globals
        set re {\s*(global|static)\s+((?:\$[^;,]+[ ,]*)+)(;|$)}
        if {[regexp $re $line -> - g]} {
            set g [split [string trim $g ";"] ,]
            foreach v $g {
                set v [string trim $v "$ "]
                set initialized($v) 1
            }
        }
        # Check for assignment via foreach ... as &$varname
        set re {}
        append re {foreach\s*\(.*\s+as\s+&?(} $varre {)\s*\)}
        set l [regexp -all -inline -nocase $re $line]
        foreach {- a -} $l {
            set initialized([string trim $a "$ "]) 1
        }
        # Check for assignment via foreach ... as $key => &$val
        set re {}
        append re {foreach\s*\(.*\s+as\s+(} $varre {)\s*=>\s*&?(} $varre {)\s*\)}
        set l [regexp -all -inline -nocase $re $line]
        foreach {- a1 - a2 -} $l {
            set initialized([string trim $a1 "$ "]) 1
            set initialized([string trim $a2 "$ "]) 1
        }
        # Check for assigments in the form list($a,$b,$c) = ...
        set re {list\s*\(([^=]*)\)\s*=}
        set l [regexp -all -inline $re $line]
        foreach {- vars} $l {
            foreach v [split $vars ,] {
                set v [string trim $v "$ "]
                set initialized($v) 1
            }
        }
        # Check for assigments via = operator
        set re $varre
        append re {\s*=}
        set l [regexp -all -inline $re $line]
        foreach {a -} $l {
            set a [string trim $a "=$ "]
            regsub -all {\[.*\]} $a {[]} a
            #puts "assigmnent of $a"
            set initialized($a) 1
            regsub -all {\[\]} $a {} a
            set initialized($a) 1
        }
        # Check for assignments via catch(Exception $e)
        set re {}
        append re {catch\s*\(.*\s+(} $varre {)}
        set l [regexp -all -inline -nocase $re $line]
        foreach {- a} $l {
            set initialized([string trim $a "$ "]) 1
        }
        # Check for assignments by reference
        #
        # funclist format is {type funcname spos epos} where spos is the
        # zero-based index of the first argument that can be considered
        # an assignment, while epos is the last.
        #
        # name is the function name to match, and type is what
        # to do with the args. "assignment" to consider them assigned
        # or "ingore" to ingore them for the current line.
        #
        # The "ignore" is used for isset() and other functions that can
        # deal with not initialized vars.
        unset -nocomplain -- ignore
        array set ignore {}
        set funclist {
            assignment scanf 2 100
            assignment preg_match 2 100
            assignment preg_match_all 2 100
            assignment ereg 2 100
            ignore isset 0 0
        }
        set cline $line
        regsub -all {'[^']+'} $cline {''} cline
        foreach {type name spos epos} $funclist {
            set re {}
            append re $name {\s*\(([^()]*)\)}
            foreach {- fargs} [regexp -all -inline $re $cline] {
                set argidx 0
                foreach a [split $fargs ,] {
                    set a [string trim $a ", $"]
                    regsub -all {\[.*\]} $a {} a
                    if {$argidx >= $spos && $argidx <= $epos} {
                        if {$type eq {assignment}} {
                            set initialized($a) 1
                        } elseif {$type eq {ignore}} {
                            set ignore($a) 1
                        }
                    }
                    incr argidx
                }
            }
        }

        # Check for var accesses
        set varsimplere {\$[_A-Za-z]+[_A-Za-z0-9]*}
        set l [regexp -all -inline $varsimplere $line]
        foreach a $l {
            set a [string trim $a "=$ "]
            regsub -all {\[.*\]} $a {} a
            #puts "access of $a"
            if {![info exists initialized($a)] &&
                ![info exists ignore($a)]} {
                puts "* In $file line $linenr: access to uninitialized var '$a'"
            }
        }
    }
}

proc main argv {
    foreach file $argv {
        scan $file
    }
}

main $argv
