<?php

include_once INCLUDE_DIR.'class.cron.php';

class CronApiController extends ApiController {
    function execute() {

        if (!($key=$this->requireApiKey()) || !$key->canExecuteCron())
            return $this->exerr(401, __('API key not authorized'));

        $this->run();
    }

    protected function run() {
        Cron::run();
        // TODO: Add elapsed time to the debug log
        $this->debug(__('Cron Job'),
                sprintf('%s [%s]', __('Cron job executed'), $this->getRemoteAddr()));
        $this->response(200,'Completed');
    }
}

class LocalCronApiController extends CronApiController {

    public function isCli() {
        return true;
    }

    protected function getRemoteAddr() {
        // Local Cron doesn't have IP Addr set
        return 'CLI';
    }

    public function response($code, $response) {

        if ($code == 200) //Success - exit silently.
            exit(0);

        echo $response;
        exit(1);
    }

    static function call() {
        $cron = new LocalCronApiController('cli');
        $cron->run();
    }
}
?>
