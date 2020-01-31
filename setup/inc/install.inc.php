<?php
if(!defined('SETUPINC')) die('Kwaheri!');
$info=($_POST && $errors)?Format::htmlchars($_POST):array('prefix'=>'ost_','dbhost'=>'localhost','lang_id'=>'en_US');
?>
<div id="main" class="step2">
    <h1><?php echo __('osTicket Basic Installation'); ?></h1>
        <p><?php echo __('Please fill out the information below to continue your osTicket installation. All fields are required.');?></p>
            <font class="error"><strong><?php echo $errors['err']; ?></strong></font>
            <form action="install.php" method="post" id="install">
                <input type="hidden" name="s" value="install">
                <h4 class="head system"><?php echo __('System Settings');?></h4>
                <span class="subhead"><?php echo __('The URL of your helpdesk, its name, and the default system email address');?></span>
                <div class="row">
                    <label><?php echo __('Helpdesk URL');?>:</label>
                    <span class="ltr"><strong><?php echo URL; ?></strong></span>
                </div>
                <div class="row">
                    <label for="name"><?php echo __('Helpdesk Name');?>:</label>
                    <input type="text" id="name" name="name" size="45" value="<?php echo $info['name']; ?>">
                    <a class="tip" href="#helpdesk_name" aria-label="What's this?"><i class="icon-question-sign help-tip"></i></a>
                    <font class="error"><?php echo $errors['name']; ?></font>
                </div>
                <div class="row">
                    <label for="email"><?php echo __('Default Email');?>:</label>
                    <input type="text" id="email" name="email" size="45" value="<?php echo $info['email']; ?>">
                    <a class="tip" href="#system_email" aria-label="What's this?"><i class="icon-question-sign help-tip"></i></a>
                    <font class="error"><?php echo $errors['email']; ?></font>
                </div>
                <div class="row">
                    <label for="lang_id"><?php echo __('Primary Language');?>:</label>
<?php $langs = Internationalization::availableLanguages(); ?>
                <select id="lang_id" name="lang_id">
<?php foreach($langs as $l) {
    $selected = ($info['lang_id'] == $l['code']) ? 'selected="selected"' : ''; ?>
                    <option value="<?php echo $l['code']; ?>" <?php echo $selected;
                        ?>><?php echo Internationalization::getLanguageDescription($l['code']); ?></option>
<?php } ?>
                </select>
                <a class="tip" href="#default_lang" aria-label="What's this?"><i class="icon-question-sign help-tip"></i></a>
                <font class="error">&nbsp;<?php echo $errors['lang_id']; ?></font>
                </div>

                <h4 class="head admin"><?php echo __('Admin User');?></h4>
                <span class="subhead"><?php echo __('Your primary administrator account - you can add more users later.');?></span>
                <div class="row">
                    <label for="fname"><?php echo __('First Name');?>:</label>
                    <input type="text" id="fname" name="fname" size="45" value="<?php echo $info['fname']; ?>">
                    <a class="tip" href="#first_name" aria-label="What's this?"><i class="icon-question-sign help-tip"></i></a>
                    <font class="error"><?php echo $errors['fname']; ?></font>
                </div>
                <div class="row">
                    <label for="lname"><?php echo __('Last Name');?>:</label>
                    <input type="text" id="lname" name="lname" size="45" value="<?php echo $info['lname']; ?>">
                    <a class="tip" href="#last_name" aria-label="What's this?"><i class="icon-question-sign help-tip"></i></a>
                    <font class="error"><?php echo $errors['lname']; ?></font>
                </div>
                <div class="row">
                    <label for="admin_email"><?php echo __('Email Address');?>:</label>
                    <input type="text" id="admin_email" name="admin_email" size="45" value="<?php echo $info['admin_email']; ?>">
                    <a class="tip" href="#email" aria-label="What's this?"><i class="icon-question-sign help-tip"></i></a>
                    <font class="error"><?php echo $errors['admin_email']; ?></font>
                </div>
                <div class="row">
                    <label for="username"><?php echo __('Username');?>:</label>
                    <input type="text" id="username" name="username" size="45" value="<?php echo $info['username']; ?>" autocomplete="off">
                    <a class="tip" href="#username" aria-label="What's this?"><i class="icon-question-sign help-tip"></i></a>
                    <font class="error"><?php echo $errors['username']; ?></font>
                </div>
                <div class="row">
                    <label for="passwd"><?php echo __('Password');?>:</label>
                    <input type="password" id="passwd" name="passwd" size="45" value="<?php echo $info['passwd']; ?>" autocomplete="off">
                    <a class="tip" href="#password" aria-label="What's this?"><i class="icon-question-sign help-tip"></i></a>
                    <font class="error"><?php echo $errors['passwd']; ?></font>
                </div>
                <div class="row">
                    <label for="passwd2"><?php echo __('Retype Password');?>:</label>
                    <input type="password" id="passwd2" name="passwd2" size="45" value="<?php echo $info['passwd2']; ?>">
                    <a class="tip" href="#password2" aria-label="What's this?"><i class="icon-question-sign help-tip"></i></a>
                    <font class="error"><?php echo $errors['passwd2']; ?></font>
                </div>

                <h4 class="head database"><?php echo __('Database Settings');?></h4>
                <span class="subhead"><?php echo __('Database connection information');?> <font class="error"><?php echo $errors['db']; ?></font></span>
                <div class="row">
                    <label for="prefix"><?php echo __('MySQL Table Prefix');?>:</label>
                    <input type="text" id="prefix" name="prefix" size="45" value="<?php echo $info['prefix']; ?>">
                    <a class="tip" href="#db_prefix" aria-label="What's this?"><i class="icon-question-sign help-tip"></i></a>
                    <font class="error"><?php echo $errors['prefix']; ?></font>
                </div>
                <div class="row">
                    <label for="dbhost"><?php echo __('MySQL Hostname');?>:</label>
                    <input type="text" id="dbhost" name="dbhost" size="45" value="<?php echo $info['dbhost']; ?>">
                    <a class="tip" href="#db_host" aria-label="What's this?"><i class="icon-question-sign help-tip"></i></a>
                    <font class="error"><?php echo $errors['dbhost']; ?></font>
                </div>
                <div class="row">
                    <label for="dbname"><?php echo __('MySQL Database');?>:</label>
                    <input type="text" id="dbname" name="dbname" size="45" value="<?php echo $info['dbname']; ?>">
                    <a class="tip" href="#db_schema" aria-label="What's this?"><i class="icon-question-sign help-tip"></i></a>
                    <font class="error"><?php echo $errors['dbname']; ?></font>
                </div>
                <div class="row">
                    <label for="dbuser"><?php echo __('MySQL Username');?>:</label>
                    <input type="text" id="dbuser" name="dbuser" size="45" value="<?php echo $info['dbuser']; ?>">
                    <a class="tip" href="#db_user"aria-label="What's this?"><i class="icon-question-sign help-tip"></i></a>
                    <font class="error"><?php echo $errors['dbuser']; ?></font>
                </div>
                <div class="row">
                    <label for="dbpass"><?php echo __('MySQL Password');?>:</label>
                    <input type="password" id="dbpass" name="dbpass" size="45" value="<?php echo $info['dbpass']; ?>">
                    <a class="tip" href="#db_password" aria-label="What's this?"><i class="icon-question-sign help-tip"></i></a>
                    <font class="error"><?php echo $errors['dbpass']; ?></font>
                </div>
                <br>
                <div id="bar">
                    <input class="btn" type="submit" value="<?php echo __('Install Now');?>">
                </div>

                <input type="hidden" name="timezone" id="timezone"/>
            </form>
    </div>
    <div>
        <p><strong><?php echo __('Need Help?');?></strong> <?php echo __('We provide <u>professional installation services</u> and commercial support.');?> <a target="_blank" href="https://osticket.com/support"><?php echo __('Learn More!');?></a></p>
    </div>
    <div id="overlay"></div>
    <div id="loading">
        <h4><?php echo __('Doing stuff!');?></h4>
        <?php echo __('Please wait... while we install your new support ticket system!');?>
    </div>
