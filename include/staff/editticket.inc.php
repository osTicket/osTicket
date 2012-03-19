<?php
if(!defined('OSTSCPINC') || !is_object($ticket) || !is_object($thisstaff) || !$thisstaff->isStaff()) die('Access Denied');

if(!($thisstaff->canEditTickets() || ($thisstaff->isManager() && $ticket->getDeptId()==$thisstaff->getDeptId()))) die('Access Denied. Perm error.');

if($_POST && $errors){
    $info=Format::input($_POST);
}else{
    $info=array('email'=>$ticket->getEmail(),
                'name' =>$ticket->getName(),
                'phone'=>$ticket->getPhone(),
                'phone_ext'=>$ticket->getPhoneExt(),
                'pri'=>$ticket->getPriorityId(),
                'topicId'=>$ticket->getTopicId(),
                'topic'=>$ticket->getHelpTopic(),
                'subject' =>$ticket->getSubject(),
                'duedate' =>$ticket->getDueDate()?(Format::userdate('m/d/Y',Misc::db2gmtime($ticket->getDueDate()))):'',
                'time'=>$ticket->getDueDate()?(Format::userdate('G:i',Misc::db2gmtime($ticket->getDueDate()))):'',
                );
    /*Note: Please don't make me explain how dates work - it is torture. Trust me! */
}

?>
<div width="100%">
    <?php if($errors['err']) { ?>
        <p align="center" id="errormessage"><?php echo $errors['err']; ?></p>
    <?php }elseif($msg) { ?>
        <p align="center" class="infomessage"><?php echo $msg; ?></p>
    <?php }elseif($warn) { ?>
        <p class="warnmessage"><?php echo $warn; ?></p>
    <?php } ?>
</div>
<table width="100%" border="0" cellspacing=1 cellpadding=2>
  <form action="tickets.php?id=<?php echo $ticket->getId(); ?>" method="post">
    <input type='hidden' name='id' value='<?php echo $ticket->getId(); ?>'>
    <input type='hidden' name='a' value='update'>
    <tr><td align="left" colspan=2 class="msg">
        Update Ticket #<?php echo $ticket->getExtId(); ?>&nbsp;&nbsp;(<a href="tickets.php?id=<?php echo $ticket->getId(); ?>" style="color:black;">View Ticket</a>)<br></td></tr>
    <tr>
        <td align="left" nowrap width="120"><b>Email Address:</b></td>
        <td>
            <input type="text" id="email" name="email" size="25" value="<?php echo $info['email']; ?>">
            &nbsp;<font class="error"><b>*</b>&nbsp;<?php echo $errors['email']; ?></font>
        </td>
    </tr>
    <tr>
        <td align="left" ><b>Full Name:</b></td>
        <td>
            <input type="text" id="name" name="name" size="25" value="<?php echo $info['name']; ?>">
            &nbsp;<font class="error"><b>*</b>&nbsp;<?php echo $errors['name']; ?></font>
        </td>
    </tr>
    <tr>
        <td align="left"><b>Subject:</b></td>
        <td>
            <input type="text" name="subject" size="35" value="<?php echo $info['subject']; ?>">
            &nbsp;<font class="error">*&nbsp;<?php echo $errors['subject']; ?></font>
        </td>
    </tr>
    <tr>
        <td align="left">Telephone:</td>
        <td><input type="text" name="phone" size="25" value="<?php echo $info['phone']; ?>">
             &nbsp;Ext&nbsp;<input type="text" name="phone_ext" size="6" value="<?php echo $info['phone_ext']; ?>">
            &nbsp;<font class="error">&nbsp;<?php echo $errors['phone']; ?></font></td>
    </tr>
    <tr height=1px><td align="left" colspan=2 >&nbsp;</td></tr>
    <tr>
        <td align="left" valign="top">Due Date:</td>
        <td>
            <i>Time is based on your time zone (GM <?php echo $thisstaff->getTZoffset(); ?>)</i>&nbsp;<font class="error">&nbsp;<?php echo $errors['time']; ?></font><br>
            <input id="duedate" name="duedate" value="<?php echo Format::htmlchars($info['duedate']); ?>"
                onclick="event.cancelBubble=true;calendar(this);" autocomplete=OFF>
            <a href="#" onclick="event.cancelBubble=true;calendar(getObj('duedate')); return false;"><img src='images/cal.png'border=0 alt=""></a>
            &nbsp;&nbsp;
            <?php
             $min=$hr=null;
             if($info['time'])
                list($hr,$min)=explode(':',$info['time']);
                echo Misc::timeDropdown($hr,$min,'time');
            ?>
            &nbsp;<font class="error">&nbsp;<?php echo $errors['duedate']; ?></font>
        </td>
    </tr>
    <?php
      $sql='SELECT priority_id,priority_desc FROM '.TICKET_PRIORITY_TABLE.' ORDER BY priority_urgency DESC';
      if(($priorities=db_query($sql)) && db_num_rows($priorities)){ ?>
      <tr>
        <td align="left">Priority:</td>
        <td>
            <select name="pri">
              <?php
                while($row=db_fetch_array($priorities)){ ?>
                    <option value="<?php echo $row['priority_id']; ?>" <?php echo $info['pri']==$row['priority_id']?'selected':''; ?> ><?php echo $row['priority_desc']; ?></option>
              <?php } ?>
            </select>
        </td>
       </tr>
    <?php } ?>

    <?php
    $services= db_query('SELECT topic_id,topic,isactive FROM '.TOPIC_TABLE.' ORDER BY topic');
    if($services && db_num_rows($services)){ ?>
    <tr>
        <td align="left" valign="top">Help Topic:</td>
        <td>
            <select name="topicId">    
                <option value="0" selected >None</option>
                <?php if(!$info['topicId'] && $info['topic']){ //old helptopic ?>
                <option value="0" selected ><?php echo $info['topic']; ?> (deleted)</option>
                <?php
                }
                 while (list($topicId,$topic,$active) = db_fetch_row($services)){
                    $selected = ($info['topicId']==$topicId)?'selected':'';
                    $status=$active?'Active':'Inactive';
                    ?>
                    <option value="<?php echo $topicId; ?>"<?php echo $selected; ?>><?php echo $topic; ?>&nbsp;&nbsp;&nbsp;(<?php echo $status; ?>)</option>
                <?php
                 } ?>
            </select>
            &nbsp;(optional)<font class="error">&nbsp;<?php echo $errors['topicId']; ?></font>
        </td>
    </tr>
    <?php
    } ?>
    <tr>
        <td align="left" valign="top"><b>Internal Note:</b></td>
        <td>
            <i>Reasons for the edit.</i><font class="error"><b>*&nbsp;<?php echo $errors['note']; ?></b></font><br/>
            <textarea name="note" cols="45" rows="5" wrap="soft"><?php echo $info['note']; ?></textarea></td>
    </tr>
    <tr height=2px><td align="left" colspan=2 >&nbsp;</td></tr>
    <tr>
        <td></td>
        <td>
            <input class="button" type="submit" name="submit_x" value="Update Ticket">
            <input class="button" type="reset" value="Reset">
            <input class="button" type="button" name="cancel" value="Cancel" onClick='window.location.href="tickets.php?id=<?php echo $ticket->getId(); ?>"'>    
        </td>
    </tr>
  </form>
</table>

