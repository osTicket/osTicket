<?php

include_once INCLUDE_DIR.'class.api.php';
include_once INCLUDE_DIR.'class.task.php';

class TaskApiController extends ApiController {


    function report($format) {

        if(!($key=$this->requireApiKey()))
            return $this->exerr(401, __('API key not authorized'));

        # Parse request body
        $tasks = $this->reportTasks(null);
        Http::response(200,  JsonDataEncoder::encode($tasks), 'application/json');
        exit;
    }

    function reportTasks($data) {
        return Task::objects()->filter(array('flags__exact' => Task::ISOPEN))->all();
        //return Task::objects()->filter(array('id__exact' => 3))->all();
        //return Task::getOpenTasks();
    }

}

?>
