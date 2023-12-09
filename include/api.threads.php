<?php

include_once INCLUDE_DIR.'class.api.php';
include_once INCLUDE_DIR.'class.thread.php';

class ThreadApiController extends ApiController {

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
            if (count($filter) == 0)
                $tasks = Thread::objects()->values()->all();
            else
                $tasks = Thread::objects()->filter($filter)->values()->all();
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

    function get($format, $id) {

        if(!($key=$this->requireApiKey()))
            return $this->exerr(401, __('API key not authorized'));

        $thread = Thread::objects()->filter(array('id__exact' => $id ))->values()->one();

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

        Http::response(200,  $encoder->encode($thread), $contentType);
        exit;
    }

   function getEntries($format, $id) {
       if(!($key=$this->requireApiKey()))
           return $this->exerr(401, __('API key not authorized'));

       $entries = ThreadEntry::objects()->filter(array('thread_id__exact' => $id ))->values()->all();

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

       Http::response(200,  $encoder->encode($entries), $contentType);
       exit;
   }

}

?>
