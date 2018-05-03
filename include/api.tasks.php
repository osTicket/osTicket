<?php

include_once INCLUDE_DIR.'class.api.php';
include_once INCLUDE_DIR.'class.task.php';

class TaskApiController extends ApiController {


    function report($format) {

        if(!($key=$this->requireApiKey()))
            return $this->exerr(401, __('API key not authorized'));


        # Parse request query string
        $filter = array();
        parse_str($_SERVER['QUERY_STRING'], $filter);
        foreach ($filter as $key => $value) {
            $filter[$key . "__exact"] = $value;
            unset($filter[$key]);
        }

        try {
            $tasks = $this->reportTasks($filter);
        } catch (InconsistentModelException $e) {
            Http::response(400, $e->getMessage());
            exit;
        }
        $encoder = null;
        $contentType = null;
        switch(strtolower($format)) {
            case 'json':
                $encoder = new JsonDataEncoder();
                $contentType = 'application/json';
                break;
            default:
                $this->exerr(415, __('Unsupported response format'));
        }

        Http::response(200,  $encoder->encode($tasks), $contentType);
        exit;
    }

    function reportTasks($filter) {
        if (count($filter) == 0)
            return TaskModel::objects()->values()->all();
        else
            return TaskModel::objects()->filter($filter)->values()->all();
    }

}

?>
