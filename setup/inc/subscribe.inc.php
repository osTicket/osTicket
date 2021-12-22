<?php if(!defined('SETUPINC')) die('Kwaheri!');
$info=($_POST && $errors)?Format::htmlchars($_POST):$_SESSION['info'];
?>
    <div id="main">
        <h1><?php echo __('Basic Installation Completed');?></h1>
        <p><?php echo __('osTicket installation has been completed successfully.');?></p>
        <h3 style="color:#FF7700;"><?php echo __('Stay up to date');?>: </h3>
        <?php echo __("It's important to keep your osTicket installation up to date. Get announcements, security updates and alerts delivered directly to you!");?>
        <br><br>
        <form action="install.php" method="post">
            <input type="hidden" name="s" value="subscribe">
                <div class="row">
                    <label><?php echo __('Full Name');?>:</label>
                    <input type="text" name="name" size="30" value="<?php echo $info['name']; ?>">
                    <font color="red"><?php echo $errors['name']; ?></font>
                </div>
                <div class="row">
                    <label><?php echo __('Email Address');?>:</label>
                    <input type="text" name="email" size="30" value="<?php echo $info['email']; ?>">
                    <font color="red"><?php echo $errors['email']; ?></font>
                </div>
                <br>
                <div class="row">
                    <strong><?php echo __("I'd like to receive the following notifications");?>: <font color="red"><?php echo $errors['notify']; ?></font></strong>
                    <label style="width:500px">
                        <input style="position:relative; top:4px; margin-right:10px"
                            type="checkbox" name="news" value="1" <?php echo (!isset($info['news']) || $info['news'])?'checked="checked"':''; ?> >
                            <?php echo __('News &amp; Announcements');?></label>
                    <label style="width:500px">
                        <input style="position:relative; top:4px; margin-right:10px"
                            type="checkbox" name="alerts" value="1" <?php echo (!isset($info['alerts']) || $info['alerts'])?'checked="checked"':''; ?>>
                            <?php echo __('Security Alerts');?></label>
                </div>
                <div id="bar">
                    <input class="btn" type="submit" value="<?php echo __('Keep me Updated');?>">
                    <a class="unstyled" href="install.php?s=ns"><?php echo __('No thanks.');?></a>
                </div>
        </form>
    </div>
    <div id="sidebar">
            <h3><?php echo __('Thank you!');?></h3>
            <p>
                <?php echo __('Once again, thank you for choosing osTicket as your new customer support platform! ');?>
            </p>
            <p>
               <?php echo __('Launching a new customer support platform can be a daunting task. Let us get you started! We provide professional support services to help get osTicket up and running smoothly for your organization.');?> <a target="_blank" href="https://osticket.com/services/professional-support/"><?php echo __('Learn More!');?></a>
            </p>
    </div>
