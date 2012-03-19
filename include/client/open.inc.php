<?php
if(!defined('OSTCLIENTINC')) die('Access Denied'); //Say bye to our friend..

$info=($_POST && $errors)?Format::htmlchars($_POST):array();
?>

<h1>Open a New Ticket</h1>
<p>Please fill in the form below to open a new ticket.</p>
<form id="ticketForm" method="post" action="open.php" enctype="multipart/form-data">
    <div>
        <label for="name" class="required">Full Name:</label>
        <input id="name" type="text" name="name" size="30" value="<?php echo $info['name']; ?>">
        <font class="error">*&nbsp;<?php echo $errors['name']; ?></font>
    </div>
    <div>
        <label for="email" class="required">E-Mail Address:</label>
        <input id="email" type="text" name="email" size="30" value="<?php echo $info['email']; ?>">
        <font class="error">*&nbsp;<?php echo $errors['email']; ?></font>
    </div>
    <div>
        <label for="phone">Telephone:</label>
        <input id="phone" type="text" name="phone" size="17" value="<?php echo $info['phone']; ?>">
        <label for="ext" class="inline">Ext.:</label>
        <input id="ext" type="text" name="phone_ext" size="3" value="<?php echo $info['phone_ext']; ?>">
        <font class="error">&nbsp;<?php echo $errors['phone']; ?>&nbsp;&nbsp;<?php echo $errors['phone_ext']; ?></font>
    </div>
    <br>
    <div>
        <label for="topicId" class="required">Help Topic:</label>
        <select id="topicId" name="topicId">
            <option value="" selected="selected">&mdash; Select a Help Topics &mdash;</option>
            <?php
                $sql='SELECT topic_id,topic FROM '.TOPIC_TABLE.' WHERE isactive=1 ORDER BY topic';
                 if(($res=db_query($sql)) && db_num_rows($res)) {
                     while (list($topicId,$topic) = db_fetch_row($res)){
                        $selected = ($info['topicId']==$topicId)?'selected="selected"':''; ?>
                        <option value="<?php echo $topicId; ?>"<?php echo $selected; ?>><?php echo $topic; ?></option>
                        <?php
                     }
                 }else{ ?>
                    <option value="0" >General Inquiry</option>
                <?php } ?>
        </select>
        <font class="error">*&nbsp;<?php echo $errors['topicId']; ?></font>
    </div>
    <div>
        <label for="subject" class="required">Subject:</label>
        <input id="subject" type="text" name="subject" size="40" value="<?php echo $info['subject']; ?>">
        <font class="error">*&nbsp;<?php echo $errors['subject']; ?></font>
    </div>
    <div>
        <label for="msg" class="required">Message:</label>
        <span id="msg">
        <em>Please provide as much details as possible so we can best assist you.</em> <font class="error">*&nbsp;<?php echo $errors['message']; ?></font></span>
    </div>
    <div>
        <label for="message" class="required">&nbsp;</label>
        <textarea id="message" cols="60" rows="8" name="message"><?php echo $info['message']; ?></textarea>
    </div>
    <?php if(($cfg->allowOnlineAttachments() && !$cfg->allowAttachmentsOnlogin())
            || ($cfg->allowAttachmentsOnlogin() && ($thisuser && $thisuser->isValid()))) { ?>
     <div>
        <label for="attachment">Attachments:</label>
        <input id="attachment" type="file" name="attachment"><font class="error">&nbsp;<?php echo $errors['attachment']; ?></font>
    </div>                                                                
    <?php } ?>
    <?php
    if($cfg && $cfg->allowPriorityChange()) {
      $sql='SELECT priority_id,priority_desc FROM '.TICKET_PRIORITY_TABLE.' WHERE ispublic=1 ORDER BY priority_urgency DESC';
      if(($res=db_query($sql)) && db_num_rows($res)) {?>
      <div>
        <label for="priority">Ticket Priority:</label>
        <select id="priority" name="priorityId">
              <?php
                if(!$info['priorityId'])
                    $info['priorityId']=$cfg->getDefaultPriorityId(); //use system's default priority.
                while($row=db_fetch_array($res)){ 
                    $selected=$info['priorityId']==$row['priority_id']?'selected="selected"':'';
                    ?>
                    <option value="<?php echo $row['priority_id']; ?>" <?php echo $selected; ?> ><?php echo $row['priority_desc']; ?></option>
              <?php } ?>
        </select>
        <font class="error">&nbsp;<?php echo $errors['priorityId']; ?></font>
     </div>
    <?php
      }
    } ?>
    <?php
    if($cfg && $cfg->enableCaptcha() && (!$thisuser || !$thisuser->isValid())) {
        if($_POST && $errors && !$errors['captcha'])
            $errors['captcha']='Please re-enter the text again';
        ?>
    <br>
    <div class="captchaRow">
        <label for="captcha" class="required">CAPTCHA Text:</label>
        <span class="captcha"><img src="captcha.php" border="0" align="left"></span>
        <input id="captcha" type="text" name="captcha" size="6">
        <em>Enter the text shown on the image.</em>
        <font class="error">*&nbsp;<?php echo $errors['captcha']; ?></font>
    </div>
    <?php
    } ?>
    <br>
    <p>
        <input type="submit" value="Create Ticket">
        <input type="reset" value="Reset">
        <input type="button" value="Cancel" onClick='window.location.href="index.php"'>
    </p>
</form>
