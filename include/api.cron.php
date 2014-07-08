<?php

include_once INCLUDE_DIR.'class.cron.php';

class CronApiController extends ApiController {

    function execute() {

        if(!($key=$this->requireApiKey()) || !$key->canExecuteCron())
            return $this->exerr(401, __('API key not authorized'));

        $this->run();
    }

    /* private */
    function run() {
        global $ost;

        Cron::run();
       
        $ost->logDebug(__('Cron Job'),__('Cron job executed').' ['.$_SERVER['REMOTE_ADDR'].']');
        $this->response(200,'Completed');
    }
}

class LocalCronApiController extends CronApiController {

    function response($code, $resp) {

        if($code == 200) //Success - exit silently.
            exit(0);
        
        //On error echo the response (error)
        echo $resp;
        exit(1);
    }
        
    function call() {
        $cron = new LocalCronApiController();
        $cron->run();
    }
}
?>
