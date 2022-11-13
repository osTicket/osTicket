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
require_once INCLUDE_DIR . 'class.variable.php';
require_once INCLUDE_DIR . 'class.search.php';
require_once INCLUDE_DIR . 'class.organization.php';

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

    static function getIdByEmail($email) {
        $row = UserEmailModel::objects()
            ->filter(array('address'=>$email))
            ->values_flat('user_id')
            ->first();

        return $row ? $row[0] : 0;
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
                'reverse' => 'Ticket.user',
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
            'entries' => array(
                'constraint' => array(
                    'id' => 'DynamicFormEntry.object_id',
                    "'U'" => 'DynamicFormEntry.object_type',
                ),
                'list' => true,
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

    public function setFlag($flag, $val) {
        if ($val)
            $this->status |= $flag;
        else
            $this->status &= ~$flag;
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
implements TemplateVariable, Searchable {

    var $_email;
    var $_entries;
    var $_forms;
    var $_queue;



    static function fromVars($vars, $create=true, $update=false) {
        // Try and lookup by email address
        $user = static::lookupByEmail($vars['email']);
        if (!$user
                // can create user?
                && $create
                // Make sure at least email is valid
                && Validator::is_email($vars['email'])) {
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
            $type = array('type' => 'created');
            Signal::send('object.created', $user, $type);
            Signal::send('user.created', $user);
        } elseif ($update && $user) {
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
            return !isset($thisstaff) || $f->isRequiredForStaff() || $f->isVisibleToStaff();
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

        if (!isset($this->_email))
            $this->_email = new EmailAddress(sprintf('"%s" <%s>',
                    addcslashes($this->getName(), '"'),
                    $this->default_email->address));

        return $this->_email;
    }

    function getAvatar($size=null) {
        global $cfg;
        $source = $cfg->getClientAvatarSource();
        $avatar = $source->getAvatar($this);
        if (isset($size))
            $avatar->setSize($size);
        return $avatar;
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

        return Format::json_encode($info);
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

    static function getSearchableFields() {
        $base = array();
        $uform = UserForm::getUserForm();
        $base = array();
        foreach ($uform->getFields() as $F) {
            $fname = $F->get('name') ?: ('field_'.$F->get('id'));
            # XXX: email in the model corresponds to `emails__address` ORM path
            if ($fname == 'email')
                $fname = 'emails__address';
            if (!$F->hasData() || $F->isPresentationOnly())
                continue;
            if (!$F->isStorable())
                $base[$fname] = $F;
            else
                $base["cdata__{$fname}"] = $F;
        }
        return $base;
    }

    static function supportsCustomData() {
        return true;
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
            $vars += $entry->getFilterData();

            // Add in special `name` and `email` fields
            if ($entry->getDynamicForm()->get('type') != 'U')
                continue;

            foreach (array('name', 'email') as $name) {
                if ($f = $entry->getField($name))
                    $vars['field.'.$f->get('id')] =
                        $name == 'name' ? $this->getName() : $this->getEmail();
            }
        }

        return $vars;
    }

    function getForms($data=null, $cb=null) {

        if (!isset($this->_forms)) {
            $this->_forms = array();
            $cb = $cb ?: function ($f) use($data) { return ($data); };
            foreach ($this->getDynamicData() as $entry) {
                $entry->addMissingFields();
                if(($form = $entry->getDynamicForm())
                        && $form->get('type') == 'U' ) {

                    foreach ($entry->getFields() as $f) {
                        if ($f->get('name') == 'name' && !$cb($f))
                            $f->value = $this->getFullName();
                        elseif ($f->get('name') == 'email' && !$cb($f))
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
                        print_r(Format::htmlchars($data), true)));
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

    static function importFromPost($stream, $extra=array()) {
        if (!is_array($stream))
            $stream = sprintf('name, email%s %s',PHP_EOL, $stream);

        return User::importCsv($stream, $extra);
    }

    function updateInfo($vars, &$errors, $staff=false) {
        $isEditable = function ($f) use($staff) {
            return ($staff ? $f->isEditableToStaff() :
                    $f->isEditableToUsers());
        };
        $valid = true;
        $forms = $this->getForms($vars, $isEditable);
        foreach ($forms as $entry) {
            $entry->setSource($vars);
            if ($staff && !$entry->isValidForStaff(true))
                $valid = false;
            elseif (!$staff && !$entry->isValidForClient(true))
                $valid = false;
            elseif ($entry->getDynamicForm()->get('type') == 'U'
                    && ($f=$entry->getField('email'))
                    && $isEditable($f)
                    && $f->getClean()
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
            $fields = $entry->getFields();
            foreach ($fields as $field) {
                $changes = $field->getChanges();
                if ((is_array($changes) && $changes[0]) || $changes && !is_array($changes)) {
                    $type = array('type' => 'edited', 'key' => $field->getLabel());
                    Signal::send('object.edited', $this, $type);
                }
            }

            if ($entry->getDynamicForm()->get('type') == 'U') {
                //  Name field
                if (($name = $entry->getField('name')) && $isEditable($name) ) {
                    $name = $name->getClean();
                    if (is_array($name))
                        $name = implode(', ', $name);
                    if ($this->name != $name) {
                        $type = array('type' => 'edited', 'key' => 'Name');
                        Signal::send('object.edited', $this, $type);
                    }
                    $this->name = $name;
                }

                // Email address field
                if (($email = $entry->getField('email'))
                        && $isEditable($email)) {
                    if ($this->default_email->address != $email->getClean()) {
                        $type = array('type' => 'edited', 'key' => 'Email');
                        Signal::send('object.edited', $this, $type);
                    }
                    $this->default_email->address = $email->getClean();
                    $this->default_email->save();
                }
            }

            // DynamicFormEntry::saveAnswers returns the number of answers updated
            if ($entry->saveAnswers($isEditable)) {
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

        $type = array('type' => 'deleted');
        Signal::send('object.deleted', $this, $type);

        // Delete user
        return parent::delete();
    }

    function deleteAllTickets() {
        $status_id = TicketStatus::lookup(array('state' => 'deleted'));
        foreach($this->tickets as $ticket) {
            if (!$T = Ticket::lookup($ticket->getId()))
                continue;
            if (!$T->setStatus($status_id))
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

    static function getLink($id) {
        global $thisstaff;

        if (!$id || !$thisstaff)
            return false;

        return ROOT_PATH . sprintf('scp/users.php?id=%s', $id);
    }

    function getTicketsQueue($collabs=true) {
        global $thisstaff;

        if (!$this->_queue) {
            $email = $this->getDefaultEmailAddress();
            $filter = [
                ['user__id', 'equal', $this->getId()],
            ];
            if ($collabs)
                $filter = [
                    ['user__emails__address', 'equal', $email],
                    ['thread__collaborators__user__emails__address', 'equal',  $email],
                ];
            $this->_queue = new AdhocSearch(array(
                'id' => 'adhoc,uid'.$this->getId(),
                'root' => 'T',
                'staff_id' => $thisstaff->getId(),
                'title' => $this->getName()
            ));
            $this->_queue->config = $filter;
        }

        return $this->_queue;
    }
}

class EmailAddress
implements TemplateVariable {
    var $email;
    var $address;
    protected $_info;

    function __construct($address) {
        $this->_info = self::parse($address);
        $this->email = sprintf('%s@%s',
                $this->getMailbox(),
                $this->getDomain());

        if ($this->getName())
            $this->address = sprintf('"%s" <%s>',
                    $this->getName(),
                    $this->email);
    }

    function __toString() {
        return (string) $this->email;
    }

    function getVar($what) {

        if (!$this->_info)
            return '';

        switch ($what) {
        case 'host':
        case 'domain':
            return $this->_info->host;
        case 'personal':
            return trim($this->_info->personal, '"');
        case 'mailbox':
            return $this->_info->mailbox;
        }
    }

    function getAddress() {
        return $this->address ?: $this->email;
    }

    function getHost() {
        return $this->getVar('host');
    }

    function getDomain() {
        return $this->getHost();
    }

    function getName() {
        return $this->getVar('personal');
    }

    function getMailbox() {
        return $this->getVar('mailbox');
    }

    // Parse and email adddress (RFC822) into it's parts.
    // @address - one address is expected
    static function parse($address) {
        require_once PEAR_DIR . 'PEAR.php';
        if (($parts = Mail_Parse::parseAddressList($address))
                && !PEAR::isError($parts))
            return current($parts);
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

    function getFirstInitial() {
        if ($this->parts['first'])
            return mb_substr($this->parts['first'],0,1).'.';
        return '';
    }

    function getMiddleInitial() {
        if ($this->parts['middle'])
            return mb_substr($this->parts['middle'],0,1).'.';
        return '';
    }

    function getLastInitial() {
        if ($this->parts['last'])
            return mb_substr($this->parts['last'],0,1).'.';
        return '';
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
            $this->getMiddleInitial(),
            $this->parts['last'],
        );
        return implode(' ', array_filter($parts));
    }

    function getComplete() {
        $parts = array(
            $this->parts['salutation'],
            $this->parts['first'],
            $this->getMiddleInitial(),
            $this->parts['last'],
            $this->parts['suffix']
        );
        return implode(' ', array_filter($parts));
    }

    function getLastFirst() {
        $name = $this->parts['last'].', '.$this->parts['first'];
        $name = trim($name, ', ');
        if ($this->parts['suffix'])
            $name .= ', '.$this->parts['suffix'];
        return $name;
    }

    function getShort() {
        return $this->parts['first'].' '.$this->getLastInitial();
    }

    function getShortFormal() {
        return $this->getFirstInitial().' '.$this->parts['last'];
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

    function getNameFormats($user, $type) {
      $nameFormats = array();

      foreach (PersonsName::allFormats() as $format => $func) {
          $nameFormats[$type . '.name.' . $format] = $user->getName()->$func[1]();
      }

      return $nameFormats;
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

    function statusChanged($flag, $var) {
        if (($this->hasStatus($flag) && !$var) ||
            (!$this->hasStatus($flag) && $var))
                return true;
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

    function isActive() {
        return (!$this->isLocked() && $this->isConfirmed());
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

    function getUserName() {
        return $this->getUser()->getName();
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
        return $this->sendUnlockEmail('pwreset-client') === true;
    }

    function sendConfirmEmail() {
        return $this->sendUnlockEmail('registration-client') === true;
    }

    function setPassword($new) {
        $this->set('passwd', Passwd::hash($new));
        // Clean sessions
        Signal::send('auth.clean', $this->getUser());
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
     * Updates may be done by Staff or by the User if registration
     * options are set to Public
     */
    function update($vars, &$errors) {
        // TODO: Make sure the username is unique

        // Timezone selection is not required. System default is a valid
        // fallback

        // Changing password?
        if ($vars['passwd1'] || $vars['passwd2']) {
            if (!$vars['passwd1'])
                $errors['passwd1'] = __('New password is required');
            else {
                try {
                    self::checkPassword($vars['passwd1']);
                } catch (BadPassword $ex) {
                    $errors['passwd1'] =  $ex->getMessage();
                }
            }
        }

        // Make sure the username is not an email.
        if ($vars['username'] && Validator::is_email($vars['username']))
            $errors['username'] =
                __('Users can always sign in with their email address');

        if ($errors) return false;

        //flags
        $pwreset = $this->statusChanged(UserAccountStatus::REQUIRE_PASSWD_RESET, $vars['pwreset-flag']);
        $locked = $this->statusChanged(UserAccountStatus::LOCKED, $vars['locked-flag']);
        $forbidPwChange = $this->statusChanged(UserAccountStatus::FORBID_PASSWD_RESET, $vars['forbid-pwchange-flag']);

        $info = $this->getInfo();
        foreach ($vars as $key => $value) {
            if (($key != 'id' && $info[$key] && $info[$key] != $value) || ($pwreset && $key == 'pwreset-flag' ||
                    $locked && $key == 'locked-flag' || $forbidPwChange && $key == 'forbid-pwchange-flag')) {
                $type = array('type' => 'edited', 'key' => $key);
                Signal::send('object.edited', $this, $type);
            }
        }

        $this->set('timezone', $vars['timezone']);
        $this->set('username', Format::sanitize($vars['username']));

        if ($vars['passwd1']) {
            $this->setPassword($vars['passwd1']);
            $this->setStatus(UserAccountStatus::CONFIRMED);
            $type = array('type' => 'edited', 'key' => 'password');
            Signal::send('object.edited', $this, $type);
        }

        // Set flags
        foreach (array(
                'pwreset-flag' => UserAccountStatus::REQUIRE_PASSWD_RESET,
                'locked-flag' => UserAccountStatus::LOCKED,
                'forbid-pwchange-flag' => UserAccountStatus::FORBID_PASSWD_RESET
        ) as $ck=>$flag) {
            if ($vars[$ck])
                $this->setStatus($flag);
            else {
                if (($pwreset && $ck == 'pwreset-flag') || ($locked && $ck == 'locked-flag') ||
                    ($forbidPwChange && $ck == 'forbid-pwchange-flag')) {
                        $type = array('type' => 'edited', 'key' => $ck);
                        Signal::send('object.edited', $this, $type);
                }
                $this->clearStatus($flag);
            }
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
        if (Validator::is_email($username))
            $user = static::lookup(array('user__emails__address' => $username));
        elseif (Validator::is_userid($username))
            $user = static::lookup(array('username' => $username));

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
            elseif ($vars['passwd1'] && strcmp($vars['passwd1'], $vars['passwd2']))
                $errors['passwd2'] = 'Passwords do not match';
            else {
                try {
                    self::checkPassword($vars['passwd1']);
                } catch (BadPassword $ex) {
                    $errors['passwd1'] =  $ex->getMessage();
                }
            }
        }

        if ($errors) return false;

        $account = new UserAccount(array(
            'user_id' => $user->getId(),
            'timezone' => $vars['timezone'],
            'backend' => $vars['backend'],
        ));

        if ($vars['username'] && strcasecmp($vars['username'], $user->getEmail()))
            $account->set('username', Format::sanitize($vars['username']));

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

    static function checkPassword($new, $current=null) {
        osTicketClientAuthentication::checkPassword($new, $current);
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
class UserList extends MailingList {

   function add($user) {
        if (!$user instanceof ITicketUser)
            throw new InvalidArgumentException('User expected');

        return parent::add($user);
    }
}

?>
