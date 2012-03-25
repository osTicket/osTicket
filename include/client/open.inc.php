<?php
if(!defined('OSTCLIENTINC')) die('Access Denied!');
$info=array();
if($thisclient && $thisclient->isValid()) {
    $info=array('name'=>$thisclient->getName(),
                'email'=>$thisclient->getEmail(),
                'phone'=>$thisclient->getPhone(),
                'phone_ext'=>$thisclient->getPhoneExt());
}

$info=($_POST && $errors)?Format::htmlchars($_POST):$info;
?>
<h1>Open a New Ticket</h1>
<p>Please fill in the form below to open a new ticket.</p>
<form id="ticketForm" method="post" action="open.php" enctype="multipart/form-data">
    <input type="hidden" name="a" value="open">
    <div>
        <label for="name" class="required">Full Name:</label>
        <?php
        if($thisclient && $thisclient->isValid()) {
            echo $thisclient->getName();
        } else { ?>
        <input id="name" type="text" name="name" size="30" value="<?php echo $info['name']; ?>">
        <font class="error">*&nbsp;<?php echo $errors['name']; ?></font>
        <?php
        } ?>
    </div>
    <div>
        <label for="email" class="required">Email Address:</label>
        <?php
        if($thisclient && $thisclient->isValid()) { 
            echo $thisclient->getEmail();
        } else { ?>
        <input id="email" type="text" name="email" size="30" value="<?php echo $info['email']; ?>">
        <font class="error">*&nbsp;<?php echo $errors['email']; ?></font>
        <?php
        } ?>
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
            if($topics=Topic::getPublicHelpTopics()) {
                foreach($topics as $id =>$name) {
                    echo sprintf('<option value="%d" %s>%s</option>',
                            $id, ($info['topicId']==$id)?'selected="selected"':'', $name);
                }
            } else { ?>
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
            || ($cfg->allowAttachmentsOnlogin() && ($thisclient && $thisclient->isValid()))) { ?>
     <div>
        <label for="attachments">Attachments:</label>
        <span id="uploads"></span>
        <input type="file" class="multifile" name="attachments[]" id="attachments" size="30" value="" />
        <font class="error">&nbsp;<?php echo $errors['attachments']; ?></font>
    </div>                                                                
    <?php } ?>
    <?php
    if($cfg->allowPriorityChange() && ($priorities=Priority::getPriorities())) { ?>
    <div>
        <label for="priority">Ticket Priority:</label>
        <select id="priority" name="priorityId">
            <?php
                if(!$info['priorityId'])
                    $info['priorityId'] = $cfg->getDefaultPriorityId(); //System default.
                foreach($priorities as $id =>$name) {
                    echo sprintf('<option value="%d" %s>%s</option>',
                                    $id, ($info['priorityId']==$id)?'selected="selected"':'', $name);
                        
                }
            ?>

                
                
        </select>
        
        <font class="error">&nbsp;<?php echo $errors['priorityId']; ?></font>
        
    </div>
    <?php
    }
    ?>
    <?php
    if($cfg && $cfg->enableCaptcha() && (!$thisclient || !$thisclient->isValid())) {
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
    <p style="padding-left:150px;">
        <input type="submit" value="Create Ticket">
        <input type="reset" value="Reset">
        <input type="button" value="Cancel" onClick='window.location.href="index.php"'>
    </p>
</form>
