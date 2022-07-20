<?php
require_once INCLUDE_DIR.'class.migrater.php';
require_once INCLUDE_DIR.'class.email.php';

class EmailSettingMigrater extends MigrationTask {
    var $description = "Migrating Settingsinto Email Accounts";
    var $status ='We getting modern over-hea!';

    function run($max_time) {
        $sql='SELECT * FROM '.EMAIL_TABLE;
        if(($res=db_query($sql)) && db_num_rows($res)) {
            while($row = db_fetch_array($res)) {
                if ($row['mail_host'])
                    $this->addMailBoxAccount($row);
                if ($row['smtp_host'])
                    $this->addSmtpAccount($row);
            }
        }
    }

    private function addMailBoxAccount($row) {
        $fields = [
            'email_id' => 'email_id',
            'mail_active' => 'active',
            'mail_host' => 'host',
            'mail_port' => 'port',
            'mail_protocol' => 'protocol',
            'mail_folder' => 'folder',
            'mail_fetchfreq' => 'fetchfreq',
            'mail_fetchmax' => 'fetchmax',
            'mail_archivefolder' => 'archivefolder',
            'mail_lasterror' => 'last_error',
            'mail_lastfetch' => 'last_activity',
            'mail_errors' => 'num_errors',
            'userid' => 'username',
            'userpass' => 'passwd',
        ];

        $info = ['auth_bk' => 'basic'];
        foreach ($fields as $k => $v)
            $info[$v] = $row[$k];

        if ($row['mail_delete'])
            $info['postfetch'] = 'delete';
        elseif ($row['mail_archivefolder'])
            $info['postfetch'] = 'archive';
        else
             $info['postfetch'] = 'nothing';

        $mailbox = MailBoxAccount::create($info);
        return $mailbox->save();
    }

    private function addSmtpAccount($row) {
        $fields = [
            'email_id' => 'email_id',
            'smtp_active' => 'active',
            'smtp_host' => 'host',
            'smtp_port' => 'port',
            'smtp_spoofing' => 'allow_spoofing',
        ];

        $info = ['auth_bk' => 'basic', 'protocol' => 'SMTP'];
        if (!$row['smtp_auth'])
            $info['auth_bk'] = 'none';
        elseif (!$row['smtp_auth_creds'])
            $info['auth_bk'] = 'mailbox';
        else {
            $fields += [
                'smtp_userid' => 'username',
                'smtp_userpass' => 'passwd',
            ];
        }
        foreach ($fields as $k => $v)
            $info[$v] = $row[$k];

        $smtp = SmtpAccount::create($info);
        return $smtp->save();
    }
}
return 'EmailSettingMigrater';
?>
