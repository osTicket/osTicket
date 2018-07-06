<?php
/*********************************************************************
    class.user.php

    External end-user identification for osTicket

    Peter Rotich <peter@osticket.com>
    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once INCLUDE_DIR . 'class.orm.php';
require_once INCLUDE_DIR . 'class.util.php';
require_once INCLUDE_DIR . 'class.organization.php';
require_once INCLUDE_DIR . 'class.variable.php';

class UserEmailModel extends VerySimpleModel {
    static $meta = array(
        'table' => USER_EMAIL_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'user' => array(
                'constraint' => array('user_id' => 'UserModel.id')
            )
        )
    );

    function __toString() {
        return (string) $this->address;
    }
}

class UserModel extends VerySimpleModel {
    static $meta = array(
        'table' => USER_TABLE,
        'pk' => array('id'),
        'select_related' => array('default_email', 'org', 'account'),
        'joins' => array(
            'emails' => array(
                'reverse' => 'UserEmailModel.user',
            ),
            'tickets' => array(
                'null' => true,
                'reverse' => 'TicketModel.user',
            ),
            'account' => array(
                'list' => false,
                'null' => true,
                'reverse' => 'ClientAccount.user',
            ),
            'org' => array(
                'null' => true,
                'constraint' => array('org_id' => 'Organization.id')
            ),
            'default_email' => array(
                'null' => true,
                'constraint' => array('default_email_id' => 'UserEmailModel.id')
            ),
            'cdata' => array(
                'constraint' => array('id' => 'UserCdata.user_id'),
                'null' => true,
            ),
            'cdata_entry' => array(
                'constraint' => array(
                    'id' => 'DynamicFormEntry.object_id',
                    "'U'" => 'DynamicFormEntry.object_type',
                ),
                'null' => true,
            ),
        )
    );

    const PRIMARY_ORG_CONTACT   = 0x0001;

    const PERM_CREATE =     'user.create';
    const PERM_EDIT =       'user.edit';
    const PERM_DELETE =     'user.delete';
    const PERM_MANAGE =     'user.manage';
    const PERM_DIRECTORY =  'user.dir';

    static protected $perms = array(
        self::PERM_CREATE => array(
            'title' => /* @trans */ 'Create',
            'desc' => /* @trans */ 'Ability to add new users',
            'primary' => true,
        ),
        self::PERM_EDIT => array(
            'title' => /* @trans */ 'Edit',
            'desc' => /* @trans */ 'Ability to manage user information',
            'primary' => true,
        ),
        self::PERM_DELETE => array(
            'title' => /* @trans */ 'Delete',
            'desc' => /* @trans */ 'Ability to delete users',
            'primary' => true,
        ),
        self::PERM_MANAGE => array(
            'title' => /* @trans */ 'Manage Account',
            'desc' => /* @trans */ 'Ability to manage active user accounts',
            'primary' => true,
        ),
        self::PERM_DIRECTORY => array(
            'title' => /* @trans */ 'User Directory',
            'desc' => /* @trans */ 'Ability to access the user directory',
            'primary' => true,
        ),
    );

    function getId() {
        return $this->id;
    }

    function getDefaultEmailAddress() {
        return $this->getDefaultEmail()->address;
    }

    function getDefaultEmail() {
        return $this->default_email;
    }

    function hasAccount() {
        return !is_null($this->account);
    }
    function getAccount() {
        return $this->account;
    }

    function getOrgId() {
         return $this->get('org_id');
    }

    function getOrganization() {
        return $this->org;
    }

    function setOrganization($org, $save=true) {

        $this->set('org', $org);

        if ($save)
            $this->save();

        return true;
    }

    protected function hasStatus($flag) {
        return $this->get('status') & $flag !== 0;
    }

    protected function clearStatus($flag) {
        return $this->set('status', $this->get('status') & ~$flag);
    }

    protected function setStatus($flag) {
        return $this->set('status', $this->get('status') | $flag);
    }

    function isPrimaryContact() {
        return $this->hasStatus(User::PRIMARY_ORG_CONTACT);
    }

    function setPrimaryContact($flag) {
        if ($flag)
            $this->setStatus(User::PRIMARY_ORG_CONTACT);
        else
            $this->clearStatus(User::PRIMARY_ORG_CONTACT);
    }

    static function getPermissions() {
        return self::$perms;
    }
}
include_once INCLUDE_DIR.'class.role.php';
RolePermission::register(/* @trans */ 'Users', UserModel::getPermissions());

class UserCdata extends VerySimpleModel {
    static $meta = array(
        'table' => USER_CDATA_TABLE,
        'pk' => array('user_id'),
        'joins' => array(
            'user' => array(
                'constraint' => array('user_id' => 'UserModel.id'),
            ),
        ),
    );
}

class User extends UserModel
implements TemplateVariable {

    var $_entries;
    var $_forms;

    static function fromVars($vars, $create=true, $update=false) {
        // Try and lookup by email address
        $user = static::lookupByEmail($vars['email']);
        if (!$user && $create) {
            $name = $vars['name'];
            if (is_array($name))
                $name = implode(', ', $name);
            elseif (!$name)
                list($name) = explode('@', $vars['email'], 2);

            $user = new User(array(
                'name' => Format::htmldecode(Format::sanitize($name, false)),
                'created' => new SqlFunction('NOW'),
                'updated' => new SqlFunction('NOW'),
                //XXX: Do plain create once the cause
                // of the detached emails is fixed.
                'default_email' => UserEmail::ensure($vars['email'])
            ));
            // Is there an organization registered for this domain
            list($mailbox, $domain) = explode('@', $vars['email'], 2);
            if (isset($vars['org_id']))
                $user->set('org_id', $vars['org_id']);
            elseif ($org = Organization::forDomain($domain))
                $user->setOrganization($org, false);

            try {
                $user->save(true);
                $user->emails->add($user->default_email);
                // Attach initial custom fields
                $user->addDynamicData($vars);
            }
            catch (OrmException $e) {
                return null;
            }
            Signal::send('user.created', $user);
        }
        elseif ($update) {
            $errors = array();
            $user->updateInfo($vars, $errors, true);
        }

        return $user;
    }

    static function fromForm($form, $create=true) {
        global $thisstaff;

        if(!$form) return null;

        //Validate the form
        $valid = true;
        $filter = function($f) use ($thisstaff) {
            return !isset($thisstaff) || $f->isRequiredForStaff();
        };
        if (!$form->isValid($filter))
            $valid  = false;

        //Make sure the email is not in-use
        if (($field=$form->getField('email'))
                && $field->getClean()
                && User::lookup(array('emails__address'=>$field->getClean()))) {
            $field->addError(__('Email is assigned to another user'));
            $valid = false;
        }

        return $valid ? self::fromVars($form->getClean(), $create) : null;
    }

    function getEmail() {
        return new EmailAddress($this->default_email->address);
    }

    function getAvatar() {
        global $cfg;
        $source = $cfg->getClientAvatarSource();
        return $source->getAvatar($this);
    }

    function getFullName() {
        return $this->name;
    }

    function getPhoneNumber() {
        foreach ($this->getDynamicData() as $e)
            if ($a = $e->getAnswer('phone'))
                return $a;
    }

    function getName() {
        if (!$this->name)
            list($name) = explode('@', $this->getDefaultEmailAddress(), 2);
        else
            $name = $this->name;
        return new UsersName($name);
    }

    function getUpdateDate() {
        return $this->updated;
    }

    function getCreateDate() {
        return $this->created;
    }

    function getTimezone() {
        global $cfg;

        if (($acct = $this->getAccount()) && ($tz = $acct->getTimezone())) {
            return $tz;
        }
        return $cfg->getDefaultTimezone();
    }

    function addForm($form, $sort=1, $data=null) {
        $entry = $form->instanciate($sort, $data);
        $entry->set('object_type', 'U');
        $entry->set('object_id', $this->getId());
        $entry->save();
        return $entry;
    }

    function getLanguage($flags=false) {
        if ($acct = $this->getAccount())
            return $acct->getLanguage($flags);
    }

    function to_json() {

        $info = array(
                'id'  => $this->getId(),
                'name' => Format::htmlchars($this->getName()),
                'email' => (string) $this->getEmail(),
                'phone' => (string) $this->getPhoneNumber());

        return JsonDataEncoder::encode($info);
    }

    function __toString() {
        return $this->asVar();
    }

    function asVar() {
        return (string) $this->getName();
    }

    function getVar($tag) {
        $tag = mb_strtolower($tag);
        foreach ($this->getDynamicData() as $e)
            if ($a = $e->getAnswer($tag))
                return $a;
    }

    static function getVarScope() {
        $base = array(
            'email' => array(
                'class' => 'EmailAddress', 'desc' => __('Default email address')
            ),
            'name' => array(
                'class' => 'PersonsName', 'desc' => 'User name, default format'
            ),
            'organization' => array('class' => 'Organization', 'desc' => __('Organization')),
        );
        $extra = VariableReplacer::compileFormScope(UserForm::getInstance());
        return $base + $extra;
    }

    function addDynamicData($data) {
        return $this->addForm(UserForm::objects()->one(), 1, $data);
    }

    function getDynamicData($create=true) {
        if (!isset($this->_entries)) {
            $this->_entries = DynamicFormEntry::forObject($this->id, 'U')->all();
            if (!$this->_entries && $create) {
                $g = UserForm::getNewInstance();
                $g->setClientId($this->id);
                $g->save();
                $this->_entries[] = $g;
            }
        }

        return $this->_entries ?: array();
    }

    function getFilterData() {
        $vars = array();
        foreach ($this->getDynamicData() as $entry) {
            if ($entry->getDynamicForm()->get('type') != 'U')
                continue;
            $vars += $entry->getFilterData();
            // Add in special `name` and `email` fields
            foreach (array('name', 'email') as $name) {
                if ($f = $entry->getField($name))
                    $vars['field.'.$f->get('id')] =
                        $name == 'name' ? $this->getName() : $this->getEmail();
            }
        }
        return $vars;
    }

    function getForms($data=null) {

        if (!isset($this->_forms)) {
            $this->_forms = array();
            foreach ($this->getDynamicData() as $entry) {
                $entry->addMissingFields();
                if(!$data
                        && ($form = $entry->getDynamicForm())
                        && $form->get('type') == 'U' ) {
                    foreach ($entry->getFields() as $f) {
                        if ($f->get('name') == 'name')
                            $f->value = $this->getFullName();
                        elseif ($f->get('name') == 'email')
                            $f->value = $this->getEmail();
                    }
                }

                $this->_forms[] = $entry;
            }
        }

        return $this->_forms;
    }

    function getAccountStatus() {

        if (!($account=$this->getAccount()))
            return __('Guest');

        return (string) $account->getStatus();
    }

    function canSeeOrgTickets() {
        return $this->org && (
                $this->org->shareWithEverybody()
            || ($this->isPrimaryContact() && $this->org->shareWithPrimaryContacts()));
    }

    function register($vars, &$errors) {

        // user already registered?
        if ($this->getAccount())
            return true;

        return UserAccount::register($this, $vars, $errors);
    }

    static function importCsv($stream, $defaults=array()) {
        require_once INCLUDE_DIR . 'class.import.php';

        $importer = new CsvImporter($stream);
        $imported = 0;
        try {
            db_autocommit(false);
            $records = $importer->importCsv(UserForm::getUserForm()->getFields(), $defaults);
            foreach ($records as $data) {
                if (!Validator::is_email($data['email']) || empty($data['name']))
                    throw new ImportError('Both `name` and `email` fields are required');
                if (!($user = static::fromVars($data, true, true)))
                    throw new ImportError(sprintf(__('Unable to import user: %s'),
                        print_r($data, true)));
                $imported++;
            }
            db_autocommit(true);
        }
        catch (Exception $ex) {
            db_rollback();
            return $ex->getMessage();
        }
        return $imported;
    }

    function importFromPost($stream, $extra=array()) {
        return User::importCsv($stream, $extra);
    }

    function updateInfo($vars, &$errors, $staff=false) {

        $valid = true;
        $forms = $this->getForms($vars);
        foreach ($forms as $entry) {
            $entry->setSource($vars);
            if ($staff && !$entry->isValidForStaff())
                $valid = false;
            elseif (!$staff && !$entry->isValidForClient())
                $valid = false;
            elseif ($entry->getDynamicForm()->get('type') == 'U'
                    && ($f=$entry->getField('email'))
                    &&  $f->getClean()
                    && ($u=User::lookup(array('emails__address'=>$f->getClean())))
                    && $u->id != $this->getId()) {
                $valid = false;
                $f->addError(__('Email is assigned to another user'));
            }

            if (!$valid)
                $errors = array_merge($errors, $entry->errors());
        }


        if (!$valid)
            return false;

        // Save the entries
        foreach ($forms as $entry) {
            if ($entry->getDynamicForm()->get('type') == 'U') {
                //  Name field
                if (($name = $entry->getField('name'))) {
                    $name = $name->getClean();
                    if (is_array($name))
                        $name = implode(', ', $name);
                    $this->name = $name;
                }

                // Email address field
                if (($email = $entry->getField('email'))) {
                    $this->default_email->address = $email->getClean();
                    $this->default_email->save();
                }
            }

            // DynamicFormEntry::save returns the number of answers updated
            if ($entry->save()) {
                $this->updated = SqlFunction::NOW();
            }
        }

        return $this->save();
    }


    function save($refetch=false) {
        // Drop commas and reorganize the name without them
        $parts = array_map('trim', explode(',', $this->name));
        switch (count($parts)) {
            case 2:
                // Assume last, first --or-- last suff., first
                $this->name = $parts[1].' '.$parts[0];
                // XXX: Consider last, first suff.
                break;
            case 3:
                // Assume last, first, suffix, write 'first last suffix'
                $this->name = $parts[1].' '.$parts[0].' '.$parts[2];
                break;
        }

        // Handle email addresses -- use the box name
        if (Validator::is_email($this->name)) {
            list($box, $domain) = explode('@', $this->name, 2);
            if (strpos($box, '.') !== false)
                $this->name = str_replace('.', ' ', $box);
            else
                $this->name = $box;
            $this->name = mb_convert_case($this->name, MB_CASE_TITLE);
        }

        if (count($this->dirty)) //XXX: doesn't work??
            $this->set('updated', new SqlFunction('NOW'));
        return parent::save($refetch);
    }

    function delete() {

        // Refuse to delete a user with tickets
        if ($this->tickets->count())
            return false;

        // Delete account record (if any)
        if ($this->getAccount())
            $this->getAccount()->delete();

        // Delete emails.
        $this->emails->expunge();

        // Drop dynamic data
        foreach ($this->getDynamicData() as $entry) {
            $entry->delete();
        }

        // Delete user
        return parent::delete();
    }

    function deleteAllTickets() {
        $deleted = TicketStatus::lookup(array('state' => 'deleted'));
        foreach($this->tickets as $ticket) {
            if (!$T = Ticket::lookup($ticket->getId()))
                continue;
            if (!$T->setStatus($deleted))
                return false;
        }
        $this->tickets->reset();
        return true;
    }

    static function lookupByEmail($email) {
        return static::lookup(array('emails__address'=>$email));
    }

    static function getNameById($id) {
        if ($user = static::lookup($id))
            return $user->getName();
    }
}

class EmailAddress
implements TemplateVariable {
    var $address;

    function __construct($address) {
        $this->address = $address;
    }

    function __toString() {
        return (string) $this->address;
    }

    function getVar($what) {
        require_once PEAR_DIR . 'Mail/RFC822.php';
        require_once PEAR_DIR . 'PEAR.php';
        if (!($mails = Mail_RFC822::parseAddressList($this->address)) || PEAR::isError($mails))
            return '';

        if (count($mails) > 1)
            return '';

        $info = $mails[0];
        switch ($what) {
        case 'domain':
            return $info->host;
        case 'personal':
            return trim($info->personal, '"');
        case 'mailbox':
            return $info->mailbox;
        }
    }

    static function getVarScope() {
        return array(
            'domain' => __('Domain'),
            'mailbox' => __('Mailbox'),
            'personal' => __('Personal name'),
        );
    }
}

class PersonsName
implements TemplateVariable {
    var $format;
    var $parts;
    var $name;

    static $formats = array(
        'first' => array(     /*@trans*/ "First", 'getFirst'),
        'last' => array(      /*@trans*/ "Last", 'getLast'),
        'full' => array(      /*@trans*/ "First Last", 'getFull'),
        'legal' => array(     /*@trans*/ "First M. Last", 'getLegal'),
        'lastfirst' => array( /*@trans*/ "Last, First", 'getLastFirst'),
        'formal' => array(    /*@trans*/ "Mr. Last", 'getFormal'),
        'short' => array(     /*@trans*/ "First L.", 'getShort'),
        'shortformal' => array(/*@trans*/ "F. Last", 'getShortFormal'),
        'complete' => array(  /*@trans*/ "Mr. First M. Last Sr.", 'getComplete'),
        'original' => array(  /*@trans*/ '-- As Entered --', 'getOriginal'),
    );

    function __construct($name, $format=null) {
        global $cfg;

        if ($format && isset(static::$formats[$format]))
            $this->format = $format;
        else
            $this->format = 'original';

        if (!is_array($name)) {
            $this->parts = static::splitName($name);
            $this->name = $name;
        }
        else {
            $this->parts = $name;
            $this->name = implode(' ', $name);
        }
    }

    function getFirst() {
        return $this->parts['first'];
    }

    function getLast() {
        return $this->parts['last'];
    }

    function getMiddle() {
        return $this->parts['middle'];
    }

    function getMiddleInitial() {
        return mb_substr($this->parts['middle'],0,1).'.';
    }

    function getFormal() {
        return trim($this->parts['salutation'].' '.$this->parts['last']);
    }

    function getFull() {
        return trim($this->parts['first'].' '.$this->parts['last']);
    }

    function getLegal() {
        $parts = array(
            $this->parts['first'],
            mb_substr($this->parts['middle'],0,1),
            $this->parts['last'],
        );
        if ($parts[1]) $parts[1] .= '.';
        return implode(' ', array_filter($parts));
    }

    function getComplete() {
        $parts = array(
            $this->parts['salutation'],
            $this->parts['first'],
            mb_substr($this->parts['middle'],0,1),
            $this->parts['last'],
            $this->parts['suffix']
        );
        if ($parts[2]) $parts[2] .= '.';
        return implode(' ', array_filter($parts));
    }

    function getLastFirst() {
        $name = $this->parts['last'].', '.$this->parts['first'];
        if ($this->parts['suffix'])
            $name .= ', '.$this->parts['suffix'];
        return $name;
    }

    function getShort() {
        return $this->parts['first'].' '.mb_substr($this->parts['last'],0,1).'.';
    }

    function getShortFormal() {
        return mb_substr($this->parts['first'],0,1).'. '.$this->parts['last'];
    }

    function getOriginal() {
        return $this->name;
    }

    function getInitials() {
        $names = array($this->parts['first']);
        $names = array_merge($names, explode(' ', $this->parts['middle']));
        $names[] = $this->parts['last'];
        $initials = '';
        foreach (array_filter($names) as $n)
            $initials .= mb_substr($n,0,1);
        return mb_convert_case($initials, MB_CASE_UPPER);
    }

    function getName() {
        return $this;
    }

    function asVar() {
        return $this->__toString();
    }

    static function getVarScope() {
        $formats = array();
        foreach (static::$formats as $name=>$info) {
            if (in_array($name, array('original', 'complete')))
                continue;
            $formats[$name] = $info[0];
        }
        return $formats;
    }

    function __toString() {

        @list(, $func) = static::$formats[$this->format];
        if (!$func) $func = 'getFull';

        return (string) call_user_func(array($this, $func));
    }

    static function allFormats() {
        return static::$formats;
    }

    /**
     * Thanks, http://stackoverflow.com/a/14420217
     */
    static function splitName($name) {
        $results = array();

        $r = explode(' ', $name);
        $size = count($r);

        //check if name is bad format (ex: J.Everybody), and fix them
        if($size==1 && mb_strpos($r[0], '.') !== false)
        {
            $r = explode('.', $name);
            $size = count($r);
        }

        //check first for period, assume salutation if so
        if (mb_strpos($r[0], '.') === false)
        {
            $results['salutation'] = '';
            $results['first'] = $r[0];
        }
        else
        {
            $results['salutation'] = $r[0];
            $results['first'] = $r[1];
        }

        //check last for period, assume suffix if so
        if (mb_strpos($r[$size - 1], '.') === false)
        {
            $results['suffix'] = '';
        }
        else
        {
            $results['suffix'] = $r[$size - 1];
        }

        //combine remains into last
        $start = ($results['salutation']) ? 2 : 1;
        $end = ($results['suffix']) ? $size - 2 : $size - 1;

        $middle = array();
        for ($i = $start; $i <= $end; $i++)
        {
            $middle[] = $r[$i];
        }
        if (count($middle) > 1) {
            $results['last'] = array_pop($middle);
            $results['middle'] = implode(' ', $middle);
        }
        else {
            $results['last'] = $middle[0];
            $results['middle'] = '';
        }

        return $results;
    }

}

class AgentsName extends PersonsName {
    function __construct($name, $format=null) {
        global $cfg;

        if (!$format && $cfg)
            $format = $cfg->getAgentNameFormat();

        parent::__construct($name, $format);
    }
}

class UsersName extends PersonsName {
    function __construct($name, $format=null) {
        global $cfg;
        if (!$format && $cfg)
            $format = $cfg->getClientNameFormat();

        parent::__construct($name, $format);
    }
}


class UserEmail extends UserEmailModel {
    static function ensure($address) {
        $email = static::lookup(array('address'=>$address));
        if (!$email) {
            $email = new static(array('address'=>$address));
            $email->save();
        }
        return $email;
    }
}


class UserAccount extends VerySimpleModel {
    static $meta = array(
        'table' => USER_ACCOUNT_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'user' => array(
                'null' => false,
                'constraint' => array('user_id' => 'User.id')
            ),
        ),
    );

    const LANG_MAILOUTS = 1;            // Language preference for mailouts

    var $_status;
    var $_extra;

    function getStatus() {
        if (!isset($this->_status))
            $this->_status = new UserAccountStatus($this->get('status'));
        return $this->_status;
    }

    protected function hasStatus($flag) {
        return $this->getStatus()->check($flag);
    }

    protected function clearStatus($flag) {
        return $this->set('status', $this->get('status') & ~$flag);
    }

    protected function setStatus($flag) {
        return $this->set('status', $this->get('status') | $flag);
    }

    function confirm() {
        $this->setStatus(UserAccountStatus::CONFIRMED);
        return $this->save();
    }

    function isConfirmed() {
        return $this->getStatus()->isConfirmed();
    }

    function lock() {
        $this->setStatus(UserAccountStatus::LOCKED);
        return $this->save();
    }

    function unlock() {
        $this->clearStatus(UserAccountStatus::LOCKED);
        return $this->save();
    }

    function isLocked() {
        return $this->getStatus()->isLocked();
    }

    function forcePasswdReset() {
        $this->setStatus(UserAccountStatus::REQUIRE_PASSWD_RESET);
        return $this->save();
    }

    function isPasswdResetForced() {
        return $this->hasStatus(UserAccountStatus::REQUIRE_PASSWD_RESET);
    }

    function isPasswdResetEnabled() {
        return !$this->hasStatus(UserAccountStatus::FORBID_PASSWD_RESET);
    }

    function getInfo() {
        return $this->ht;
    }

    function getId() {
        return $this->get('id');
    }

    function getUserId() {
        return $this->get('user_id');
    }

    function getUser() {
        return $this->user;
    }

    function getExtraAttr($attr=false, $default=null) {
        if (!isset($this->_extra))
            $this->_extra = JsonDataParser::decode($this->get('extra', ''));

        return $attr ? (@$this->_extra[$attr] ?: $default) : $this->_extra;
    }

    function setExtraAttr($attr, $value) {
        $this->getExtraAttr();
        $this->_extra[$attr] = $value;
    }

    /**
     * Function: getLanguage
     *
     * Returns the language preference for the user or false if no
     * preference is defined. False indicates the browser indicated
     * preference should be used. For requests apart from browser requests,
     * the last language preference of the browser is set in the
     * 'browser_lang' extra attribute upon logins. Send the LANG_MAILOUTS
     * flag to also consider this saved value. Such is useful when sending
     * the user a message (such as an email), and the user's browser
     * preference is not available in the HTTP request.
     *
     * Parameters:
     * $flags - (int) Send UserAccount::LANG_MAILOUTS if the user's
     *      last-known browser preference should be considered. Normally
     *      only the user's saved language preference is considered.
     *
     * Returns:
     * Current or last-known language preference or false if no language
     * preference is currently set or known.
     */
    function getLanguage($flags=false) {
        $lang = $this->get('lang', false);
        if (!$lang && ($flags & UserAccount::LANG_MAILOUTS))
            $lang = $this->getExtraAttr('browser_lang', false);

        return $lang;
    }

    function getTimezone() {
        return $this->timezone;
    }

    function save($refetch=false) {
        // Serialize the extra column on demand
        if (isset($this->_extra)) {
            $this->extra = JsonDataEncoder::encode($this->_extra);
        }
        return parent::save($refetch);
    }

    function hasPassword() {
        return (bool) $this->get('passwd');
    }

    function sendResetEmail() {
        return static::sendUnlockEmail('pwreset-client') === true;
    }

    function sendConfirmEmail() {
        return static::sendUnlockEmail('registration-client') === true;
    }

    function setPassword($new) {
        $this->set('passwd', Passwd::hash($new));
    }

    protected function sendUnlockEmail($template) {
        global $ost, $cfg;

        $token = Misc::randCode(48); // 290-bits

        $email = $cfg->getDefaultEmail();
        $content = Page::lookupByType($template);

        if (!$email ||  !$content)
            return new BaseError(sprintf(_S('%s: Unable to retrieve template'),
                $template));

        $vars = array(
            'url' => $ost->getConfig()->getBaseUrl(),
            'token' => $token,
            'user' => $this->getUser(),
            'recipient' => $this->getUser(),
            'link' => sprintf(
                "%s/pwreset.php?token=%s",
                $ost->getConfig()->getBaseUrl(),
                $token),
        );
        $vars['reset_link'] = &$vars['link'];

        $info = array('email' => $email, 'vars' => &$vars, 'log'=>true);
        Signal::send('auth.pwreset.email', $this->getUser(), $info);

        $lang = $this->getLanguage(UserAccount::LANG_MAILOUTS);
        $msg = $ost->replaceTemplateVariables(array(
            'subj' => $content->getLocalName($lang),
            'body' => $content->getLocalBody($lang),
        ), $vars);

        $_config = new Config('pwreset');
        $_config->set($vars['token'], 'c'.$this->getUser()->getId());

        $email->send($this->getUser()->getEmail(),
            Format::striptags($msg['subj']), $msg['body']);

        return true;
    }

    function __toString() {
        return (string) $this->getStatus();
    }

    /*
     * This assumes the staff is doing the update
     */
    function update($vars, &$errors) {
        global $thisstaff;


        if (!$thisstaff) {
            $errors['err'] = __('Access denied');
            return false;
        }

        // TODO: Make sure the username is unique

        // Timezone selection is not required. System default is a valid
        // fallback

        // Changing password?
        if ($vars['passwd1'] || $vars['passwd2']) {
            if (!$vars['passwd1'])
                $errors['passwd1'] = __('New password is required');
            elseif ($vars['passwd1'] && strlen($vars['passwd1'])<6)
                $errors['passwd1'] = __('Must be at least 6 characters');
            elseif ($vars['passwd1'] && strcmp($vars['passwd1'], $vars['passwd2']))
                $errors['passwd2'] = __('Passwords do not match');
        }

        // Make sure the username is not an email.
        if ($vars['username'] && Validator::is_email($vars['username']))
            $errors['username'] =
                __('Users can always sign in with their email address');

        if ($errors) return false;

        $this->set('timezone', $vars['timezone']);
        $this->set('username', $vars['username']);

        if ($vars['passwd1']) {
            $this->setPassword($vars['passwd1']);
            $this->setStatus(UserAccountStatus::CONFIRMED);
        }

        // Set flags
        foreach (array(
                'pwreset-flag' => UserAccountStatus::REQUIRE_PASSWD_RESET,
                'locked-flag' => UserAccountStatus::LOCKED,
                'forbid-pwchange-flag' => UserAccountStatus::FORBID_PASSWD_RESET
        ) as $ck=>$flag) {
            if ($vars[$ck])
                $this->setStatus($flag);
            else
                $this->clearStatus($flag);
        }

        return $this->save(true);
    }

    static function createForUser($user, $defaults=false) {
        $acct = new static(array('user_id'=>$user->getId()));
        if ($defaults && is_array($defaults)) {
            foreach ($defaults as $k => $v)
                $acct->set($k, $v);
        }
        return $acct;
    }

    static function lookupByUsername($username) {
        if (strpos($username, '@') !== false)
            $user = static::lookup(array('user__emails__address'=>$username));
        else
            $user = static::lookup(array('username'=>$username));

        return $user;
    }

    static function register($user, $vars, &$errors) {

        if (!$user || !$vars)
            return false;

        //Require temp password.
        if ((!$vars['backend'] || $vars['backend'] != 'client')
                && !isset($vars['sendemail'])) {
            if (!$vars['passwd1'])
                $errors['passwd1'] = 'Temporary password required';
            elseif ($vars['passwd1'] && strlen($vars['passwd1'])<6)
                $errors['passwd1'] = 'Must be at least 6 characters';
            elseif ($vars['passwd1'] && strcmp($vars['passwd1'], $vars['passwd2']))
                $errors['passwd2'] = 'Passwords do not match';
        }

        if ($errors) return false;

        $account = new UserAccount(array(
            'user_id' => $user->getId(),
            'timezone' => $vars['timezone'],
            'backend' => $vars['backend'],
        ));

        if ($vars['username'] && strcasecmp($vars['username'], $user->getEmail()))
            $account->set('username', $vars['username']);

        if ($vars['passwd1'] && !$vars['sendemail']) {
            $account->set('passwd', Passwd::hash($vars['passwd1']));
            $account->setStatus(UserAccountStatus::CONFIRMED);
            if ($vars['pwreset-flag'])
                $account->setStatus(UserAccountStatus::REQUIRE_PASSWD_RESET);
            if ($vars['forbid-pwreset-flag'])
                $account->setStatus(UserAccountStatus::FORBID_PASSWD_RESET);
        }
        elseif ($vars['backend'] && $vars['backend'] != 'client') {
            // Auto confirm remote accounts
            $account->setStatus(UserAccountStatus::CONFIRMED);
        }

        $account->save(true);

        if (!$account->isConfirmed() && $vars['sendemail'])
            $account->sendConfirmEmail();

        return $account;
    }

}

class UserAccountStatus {

    var $flag;

    const CONFIRMED             = 0x0001;
    const LOCKED                = 0x0002;
    const REQUIRE_PASSWD_RESET  = 0x0004;
    const FORBID_PASSWD_RESET   = 0x0008;

    function __construct($flag) {
        $this->flag = $flag;
    }

    function check($flag) {
        return 0 !== ($this->flag & $flag);
    }

    function isLocked() {
        return $this->check(self::LOCKED);
    }

    function isConfirmed() {
        return $this->check(self::CONFIRMED);
    }

    function __toString() {

        if ($this->isLocked())
            return __('Locked (Administrative)');

        if (!$this->isConfirmed())
            return __('Locked (Pending Activation)');

        // ... Other flags here (password reset, etc).

        return __('Active (Registered)');
    }
}


/*
 *  Generic user list.
 */
class UserList extends ListObject
implements TemplateVariable {

    function __toString() {
        return $this->getNames();
    }

    function getNames() {
        $list = array();
        foreach($this->storage as $user) {
            if (is_object($user))
                $list [] = $user->getName();
        }
        return $list ? implode(', ', $list) : '';
    }

    function getFull() {
        $list = array();
        foreach($this->storage as $user) {
            if (is_object($user))
                $list[] = sprintf("%s <%s>", $user->getName(), $user->getEmail());
        }

        return $list ? implode(', ', $list) : '';
    }

    function getEmails() {
        $list = array();
        foreach($this->storage as $user) {
            if (is_object($user))
                $list[] = $user->getEmail();
        }
        return $list ? implode(', ', $list) : '';
    }

    static function getVarScope() {
        return array(
            'names' => __('List of names'),
            'emails' => __('List of email addresses'),
            'full' => __('List of names and email addresses'),
        );
    }
}
?>
