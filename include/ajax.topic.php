<?php
  /******************************************************************
ajax.topics.php

Ajax interface for help topic information

Kholby Lawson <osticket@kholby.com>
Copyright (c) 2015 Bright House Networks, LLC

  ******************************************************************/

if(!defined('INCLUDE_DIR')) die('!');

class TopicAjaxAPI extends AjaxController {
  
  function help_topics($dept) {
    $topics = Topic::getHelpTopics(false, false, true, $dept);
    return json_encode($topics);
  }

  function getAssignment($topic) {
    $ht = Topic::lookup($topic);

    if($ht->getTeamId() != 0) {
      return "t".$ht->getTeamId();
    }
    else if($ht->getStaffId() != 0) {
      return "s".$ht->getStaffId();
    }
    else {
      return false;
    }
  }
}

?>