<?php

class CliServerModule extends Module {
    var $prologue = "Run a CLI server for osTicket";

    var $options = array(
        'port' => array('-p','--port',
            'default'=>'8000',
            'help'=>'Specify the listening port number. Default is 8000',
        ),
        'host' => array('-h','--host',
            'default'=>'localhost',
            'help'=>'Specify the bind address. Default is "localhost"',
        ),
    );

    function make_router() {
        $temp = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $router_path = $temp
            . substr(md5('osticket-router'.getcwd()), -12)
            . '.php';

        // Ensure that the router file is cleaned up on exit
        $cleanup = function() use ($router_path) {
            @unlink($router_path);
        };
        if (function_exists('pcntl_signal'))
            pcntl_signal(SIGINT, $cleanup);

        // This will very likely not fire
        register_shutdown_function($cleanup);

        $fp = fopen($router_path, 'wt');
        fwrite($fp, <<<EOF
<?php
\$full_path = \$_SERVER["DOCUMENT_ROOT"] . \$_SERVER["REQUEST_URI"];
# Ensure trailing slash on folders
if (is_dir(\$full_path)
    && rtrim(\$full_path, '/') == \$full_path
) {
    header("Location: " . \$_SERVER["REQUEST_URI"] . '/');
}
elseif (file_exists(\$_SERVER['SCRIPT_FILENAME'])) {
    return false;
}
// Support various dispatchers
elseif (\$offs = stripos(\$_SERVER["REQUEST_URI"], 'scp/apps/')) {
    \$_SERVER["PATH_INFO"] = substr(\$_SERVER["REQUEST_URI"], \$offs + 8);
    chdir('scp/');
    require "apps/dispatcher.php";
}
elseif (\$offs = stripos(\$_SERVER["REQUEST_URI"], 'pages/')) {
    \$_SERVER["PATH_INFO"] = substr(\$_SERVER["REQUEST_URI"], \$offs + 5);
    require "pages/index.php";
}
elseif (\$offs = stripos(\$_SERVER["REQUEST_URI"], 'api/')) {
    \$_SERVER["PATH_INFO"] = substr(\$_SERVER["REQUEST_URI"], \$offs + 3);
    require "api/http.php";
}
EOF
        );
        fclose($fp);

        return $router_path;
    }

    function run($args, $options) {
        $router = $this->make_router();
        $pipes = array();
        $php = proc_open(
            sprintf("php -S %s:%s -t %s %s", $options['host'], $options['port'],
                ROOT_DIR, $router),
            array(
                1 => array('pipe', 'w'),
                2 => array('pipe', 'w'),
            ), $pipes);

        stream_set_blocking($pipes[1], 0);
        stream_set_blocking($pipes[2], 0);

        while (true) {
            if (feof($pipes[1]) || feof($pipes[2])) {
                fclose($pipes[1]);
                fclose($pipes[2]);
                break;
            }
            if ($block = fgets($pipes[1], 1024))
                fwrite(STDOUT, $block);
            if ($block = fgets($pipes[2], 1024))
                fwrite(STDERR, $block);
            usleep(100);
        }
    }
}
Module::register('serve', 'CliServerModule');
