<?php
/*********************************************************************
    class.filter.php

    Ticket Filter

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

require_once INCLUDE_DIR . 'class.filter_action.php';

class Filter {

    var $id;
    var $ht;

    static $match_types = array(
        /* @trans */ 'User Information' => array(
            array('name'      =>    /* @trans */ 'Name',
                'email'     =>      /* @trans */ 'Email',
            ),
            900
        ),
        /* @trans */ 'Email Meta-Data' => array(
            array('reply-to'  =>    /* @trans */ 'Reply-To Email',
                'reply-to-name' =>  /* @trans */ 'Reply-To Name',
                'addressee' =>      /* @trans */ 'Addressee (To and Cc)',
            ),
            200
        ),
    );

    function __construct($id) {
        $this->id=0;
        $this->load($id);
    }

    function load($id=0) {

        if(!$id && !($id=$this->getId()))
            return false;

        $sql='SELECT filter.*,count(rule.id) as rule_count '
            .' FROM '.FILTER_TABLE.' filter '
            .' LEFT JOIN '.FILTER_RULE_TABLE.' rule ON(rule.filter_id=filter.id) '
            .' WHERE filter.id='.db_input($id)
            .' GROUP BY filter.id';

        if(!($res=db_query($sql)) || !db_num_rows($res))
            return false;

        $this->ht=db_fetch_array($res);
        $this->id=$this->ht['id'];

        return true;
    }

    function reload() {
        return $this->load($this->getId());
    }

    function getId() {
        return $this->id;
    }

    function getTarget() {
        return $this->ht['target'];
    }

    function getName() {
        return $this->ht['name'];
    }

    function getNotes() {
        return $this->ht['notes'];
    }

    function getInfo() {
        return  $this->ht;
    }

    function getNumRules() {
        return $this->ht['rule_count'];
    }

    function getExecOrder() {
        return $this->ht['execorder'];
    }

    function getEmailId() {
        return $this->ht['email_id'];
    }

    function isActive() {
        return ($this->ht['isactive']);
    }

    function isSystemBanlist() {
        return !strcasecmp($this->getName(),'SYSTEM BAN LIST');
    }

    function getDeptId() {
        return $this->ht['dept_id'];
    }

    function getStatusId() {
        return $this->ht['status_id'];
    }

    function getPriorityId() {
        return $this->ht['priority_id'];
    }

    function getSLAId() {
        return $this->ht['sla_id'];
    }

    function getStaffId() {
        return $this->ht['staff_id'];
    }

    function getTeamId() {
        return $this->ht['team_id'];
    }

    function getCannedResponse() {
        return $this->ht['canned_response_id'];
    }

    function getHelpTopic() {
        return $this->ht['topic_id'];
    }

    function stopOnMatch() {
        return ($this->ht['stop_onmatch']);
    }

    function matchAllRules() {
        return ($this->ht['match_all_rules']);
    }

    function rejectOnMatch() {
        return ($this->ht['reject_ticket']);
    }

    function useReplyToEmail() {
        return ($this->ht['use_replyto_email']);
    }

    function disableAlerts() {
        return ($this->ht['disable_autoresponder']);
    }

    function sendAlerts() {
        return (!$this->disableAlerts());
    }

    function getRules() {
        if (!$this->ht['rules']) {
            $rules=array();
            //We're getting the rules...live because it gets cleared on update.
            $sql='SELECT * FROM '.FILTER_RULE_TABLE.' WHERE filter_id='.db_input($this->getId());
            if(($res=db_query($sql)) && db_num_rows($res)) {
                while($row=db_fetch_array($res))
                    $rules[]=array('w'=>$row['what'],'h'=>$row['how'],'v'=>$row['val']);
            }
            $this->ht['rules'] = $rules;
        }
        return $this->ht['rules'];
    }

    function addRule($what, $how, $val,$extra=array()) {
        $errors = array();

        $rule= array_merge($extra,array('what'=>$what, 'how'=>$how, 'val'=>$val));
        $rule['filter_id']=$this->getId();

        return FilterRule::create($rule,$errors);
    }

    function removeRule($what, $how, $val) {

        $sql='DELETE FROM '.FILTER_RULE_TABLE
            .' WHERE filter_id='.db_input($this->getId())
            .' AND what='.db_input($what)
            .' AND how='.db_input($how)
            .' AND val='.db_input($val);

        return (db_query($sql) && db_affected_rows());
    }

    function getRule($id) {
        return $this->getRuleById($id);
    }

    function getRuleById($id) {
        return FilterRule::lookup($id,$this->getId());
    }

    function containsRule($what, $how, $val) {
        $val = trim($val);
        if (isset($this->ht['rules'])) {
            $match = array("w"=>$what, "h"=>$how, "v"=>$val);
            foreach ($this->ht['rules'] as $rule) {
                if ($match == $rule)
                    return true;
            }
            return false;

        } else {
            # Fetch from database
            return 0 != db_count(
                "SELECT COUNT(*) FROM ".FILTER_RULE_TABLE
               ." WHERE filter_id=".db_input($this->id)
               ." AND what=".db_input($what)." AND how=".db_input($how)
               ." AND val=".db_input($val)
            );
        }
    }
    /**
     * Simple true/false if the rules defined for this filter match the
     * incoming email
     *
     * $info is an ARRAY, which has valid keys
     *   email - FROM email address of the ticket owner
     *   name - name of ticket owner
     *   subject - subject line of the ticket
     *   body - body content of the message (no attachments, please)
     *   reply-to - reply-to email address
     *   reply-to-name - name of sender to reply-to
     *   headers - array of email headers
     *   emailId - osTicket system email id
     */
    function matches($what) {

        if(!$what || !is_array($what)) return false;

        $how = array(
            # how => array(function, null or === this, null or !== this)
            'equal'     => array('strcasecmp', 0),
            'not_equal' => array('strcasecmp', null, 0),
            'contains'  => array('stripos', null, false),
            'dn_contain'=> array('stripos', false),
            'starts'    => array('stripos', 0),
            'ends'      => array('iendsWith', true),
            'match'     => array('pregMatchB', 1),
            'not_match' => array('pregMatchB', null, 0),
        );

        $match = false;
        # Respect configured filter email-id
        if ($this->getEmailId()
                && !strcasecmp($this->getTarget(), 'Email')
                && $this->getEmailId() != $what['emailId'])
            return false;

        foreach ($this->getRules() as $rule) {
            if (!isset($how[$rule['h']])) continue;
            list($func, $pos, $neg) = $how[$rule['h']];

            $result = call_user_func($func, $what[$rule['w']], $rule['v']);
            if (($pos === null && $result !== $neg) or ($result === $pos)) {
                # Match.
                $match = true;
                if (!$this->matchAllRules()) break;
            } else {
                # No match. Continue?
                if ($this->matchAllRules()) {
                    $match = false;
                    break;
                }
            }
        }

        return $match;
    }

    function getActions() {
        return FilterAction::objects()->filter(array(
            'filter_id'=>$this->getId()
        ));
    }
    /**
     * If the matches() method returns TRUE, send the initial ticket to this
     * method to apply the filter actions defined
     */
    function apply(&$ticket, $vars) {
        foreach ($this->getActions() as $a) {
            $a->setFilter($this);
            $a->apply($ticket, $vars);
        }
    }

    function getVars() {
        return $this->vars;
    }

    static function getSupportedMatches() {
        foreach (static::$match_types as $k=>&$v) {
            if (is_callable($v[0]))
                $v[0] = $v[0]();
        }
        unset($v);
        uasort(static::$match_types, function($a, $b) { return $a[1] - $b[1]; });
        return array_map(function($a) { return $a[0]; }, static::$match_types);
    }

    static function addSupportedMatches($group, $callable, $order=10) {
        static::$match_types[$group] = array($callable, $order);
    }

    static function getSupportedMatchFields() {
        $keys = array();
        foreach (static::getSupportedMatches() as $group=>$matches) {
            foreach ($matches as $key=>$label) {
                $keys[] = $key;
            }
        }
        return $keys;
    }

    /* static */ function getSupportedMatchTypes() {
        return array(
            'equal'=>       __('Equal'),
            'not_equal'=>   __('Not Equal'),
            'contains'=>    __('Contains'),
            'dn_contain'=>  __('Does Not Contain'),
            'starts'=>      __('Starts With'),
            'ends'=>        __('Ends With'),
            'match'=>       __('Matches Regex'),
            'not_match'=>   __('Does Not Match Regex'),
        );
    }

    function update($vars,&$errors) {

        if(!Filter::save($this->getId(),$vars,$errors))
            return false;

        $this->reload();

        return true;
    }

    function delete() {

        $id=$this->getId();
        $sql='DELETE FROM '.FILTER_TABLE.' WHERE id='.db_input($id).' LIMIT 1';
        if(db_query($sql) && ($num=db_affected_rows())) {
            db_query('DELETE FROM '.FILTER_RULE_TABLE.' WHERE filter_id='.db_input($id));
        }

        return $num;
    }

    /** static functions **/
    function getTargets() {
        return array(
                'Any' => __('Any'),
                'Web' => __('Web Forms'),
                'API' => __('API Calls'),
                'Email' => __('Emails'));
    }

    function create($vars,&$errors) {
        return Filter::save(0,$vars,$errors);
    }

    function getIdByName($name) {

        $sql='SELECT id FROM '.FILTER_TABLE.' WHERE name='.db_input($name);
        if(($res=db_query($sql)) && db_num_rows($res))
            list($id)=db_fetch_row($res);

        return $id;
    }

    function lookup($id) {

        if ($id && !is_numeric($id))
            $id = self::getIdByName($id);

        return ($id && is_numeric($id) && ($f= new Filter($id)) && $f->getId()==$id)?$f:null;
    }

    function validate_rules($vars,&$errors) {
        return self::save_rules(0,$vars,$errors);
    }

    function save_rules($id,$vars,&$errors) {

        $matches = array_keys(self::getSupportedMatchFields());
        $types = array_keys(self::getSupportedMatchTypes());

        $rules=array();
        foreach ($vars['rules'] as $i=>$rule) {
            if($rule["w"] || $rule["h"]) {
                // Check for REGEX compile errors
                if (in_array($rule["h"], array('match','not_match'))) {
                    $wrapped = "/".$rule["v"]."/iu";
                    if (false === @preg_match($rule["v"], ' ')
                            && (false !== @preg_match($wrapped, ' ')))
                        $rule["v"] = $wrapped;
                }

                if(!$rule["w"] || !in_array($rule["w"],$matches))
                    $errors["rule_$i"]=__('Invalid match selection');
                elseif(!$rule["h"] || !in_array($rule["h"],$types))
                    $errors["rule_$i"]=__('Invalid match type selection');
                elseif(!$rule["v"])
                    $errors["rule_$i"]=__('Value required');
                elseif($rule["w"]=='email'
                        && $rule["h"]=='equal'
                        && !Validator::is_email($rule["v"]))
                    $errors["rule_$i"]=__('Valid email required for the match type');
                elseif (in_array($rule["h"], array('match','not_match'))
                        && (false === @preg_match($rule["v"], ' ')))
                    $errors["rule_$i"] = sprintf(__('Regex compile error: (#%s)'),
                        preg_last_error());


                else //for everything-else...we assume it's valid.
                    $rules[]=array('what'=>$rule["w"],
                        'how'=>$rule["h"],'val'=>trim($rule["v"]));
            }elseif($rule["v"]) {
                $errors["rule_$i"]=__('Incomplete selection');
            }
        }

        if(!$rules && is_array($vars["rules"]))
            # XXX: Validation bypass
            $rules = $vars["rules"];
        elseif(!$rules && !$errors)
            $errors['rules']=__('You must set at least one rule.');

        if($errors) return false;

        if(!$id) return true; //When ID is 0 then assume it was just validation...

        //Clear existing rules...we're doing mass replace on each save!!
        db_query('DELETE FROM '.FILTER_RULE_TABLE.' WHERE filter_id='.db_input($id));
        $num=0;
        foreach($rules as $rule) {
            $rule['filter_id']=$id;
            if(FilterRule::create($rule, $errors))
                $num++;
        }

        return $num;
    }

    function save($id,$vars,&$errors) {

        if(!$vars['execorder'])
            $errors['execorder'] = __('Order required');
        elseif(!is_numeric($vars['execorder']))
            $errors['execorder'] = __('Must be numeric value');

        if(!$vars['name'])
            $errors['name'] = __('Name required');
        elseif(($sid=self::getIdByName($vars['name'])) && $sid!=$id)
            $errors['name'] = __('Name already in use');

        if(!$errors && !self::validate_rules($vars,$errors) && !$errors['rules'])
            $errors['rules'] = __('Unable to validate rules as entered');

        $targets = self::getTargets();
        if(!$vars['target'])
            $errors['target'] = __('Target required');
        else if(!is_numeric($vars['target']) && !$targets[$vars['target']])
            $errors['target'] = __('Unknown or invalid target');

        if($errors) return false;

        $emailId = 0;
        if(is_numeric($vars['target'])) {
            $emailId = $vars['target'];
            $vars['target'] = 'Email';
        }

        $sql=' updated=NOW() '
            .',isactive='.db_input($vars['isactive'])
            .',target='.db_input($vars['target'])
            .',name='.db_input($vars['name'])
            .',execorder='.db_input($vars['execorder'])
            .',email_id='.db_input($emailId)
            .',match_all_rules='.db_input($vars['match_all_rules'])
            .',stop_onmatch='.db_input(isset($vars['stop_onmatch'])?1:0)
            .',notes='.db_input(Format::sanitize($vars['notes']));

        if($id) {
            $sql='UPDATE '.FILTER_TABLE.' SET '.$sql.' WHERE id='.db_input($id);
            if(!db_query($sql))
                $errors['err']=sprintf(__('Unable to update %s.'), __('this ticket filter'))
                   .' '.__('Internal error occurred');
        }else{
            $sql='INSERT INTO '.FILTER_TABLE.' SET '.$sql.',created=NOW() ';
            if(!db_query($sql) || !($id=db_insert_id()))
                $errors['err']=sprintf(__('Unable to add %s.'), __('this ticket filter'))
                   .' '.__('Internal error occurred');
        }

        if($errors || !$id) return false;

        //Success with update/create...save the rules. We can't recover from any errors at this point.
        # Don't care about errors stashed in $xerrors
        $xerrors = array();
        self::save_rules($id,$vars,$xerrors);
        self::save_actions($id, $vars, $errors);

        return count($errors) == 0;
    }

    function save_actions($id, $vars, &$errors) {
        if (!is_array(@$vars['actions']))
            return;

        foreach ($vars['actions'] as $sort=>$v) {
            if (is_array($v)) {
                $info = $v['type'];
                $sort = $v['sort'] ?: $sort;
                $action = 'N';
            } else {
                $action = $v[0];
                $info = substr($v, 1);
            }

            switch ($action) {
            case 'N': # new filter action
                $I = new FilterAction(array(
                    'type'=>$info,
                    'filter_id'=>$id,
                    'sort' => (int) $sort,
                ));
                $I->setConfiguration($errors, $vars);
                $I->save();
                break;
            case 'I': # exiting filter action
                if ($I = FilterAction::lookup($info)) {
                    $I->setConfiguration($errors, $vars);
                    $I->sort = (int) $sort;
                    $I->save();
                }
                break;
            case 'D': # deleted filter action
                if ($I = FilterAction::lookup($info))
                    $I->delete();
                break;
            }
        }
    }
}

class FilterRule {

    var $id;
    var $ht;

    var $filter;

    function __construct($id,$filterId=0) {
        $this->id=0;
        $this->load($id,$filterId);
    }

    function load($id,$filterId=0) {

        $sql='SELECT rule.* FROM '.FILTER_RULE_TABLE.' rule '
            .' WHERE rule.id='.db_input($id);
        if($filterId)
            $sql.=' AND rule.filter_id='.db_input($filterId);

        if(!($res=db_query($sql)) || !db_num_rows($res))
            return false;



        $this->ht=db_fetch_array($res);
        $this->id=$this->ht['id'];

        $this->filter=null;

        return true;
    }

    function reload() {
        return $this->load($this->getId());
    }

    function getId() {
        return $this->id;
    }

    function isActive() {
        return ($this->ht['isactive']);
    }

    function getHashtable() {
        return $this->ht;
    }

    function getInfo() {
        return $this->getHashtable();
    }

    function getFilterId() {
        return $this->ht['filter_id'];
    }

    function getFilter() {

        if(!$this->filter && $this->getFilterId())
            $this->filter = Filter::lookup($this->getFilterId());

        return $this->filter;
    }

    function update($vars,&$errors) {
        if(!$this->save($this->getId(),$vars,$errors))
            return false;

        $this->reload();
        return true;
    }

    function delete() {

        $sql='DELETE FROM '.FILTER_RULE_TABLE.' WHERE id='.db_input($this->getId()).' AND filter_id='.db_input($this->getFilterId());

        return (db_query($sql) && db_affected_rows());
    }

    /* static */ function create($vars,&$errors) {
        return self::save(0,$vars,$errors);
    }

    /* static private */ function save($id,$vars,&$errors) {
        if(!$vars['filter_id'])
            $errors['err']=__('Parent filter ID required');


        if($errors) return false;

        $sql=' updated=NOW() '.
             ',what='.db_input($vars['what']).
             ',how='.db_input($vars['how']).
             ',val='.db_input($vars['val']).
             ',isactive='.db_input(isset($vars['isactive'])?$vars['isactive']:1);


        if(isset($vars['notes']))
            $sql.=',notes='.db_input($vars['notes']);

        if($id) {
            $sql='UPDATE '.FILTER_RULE_TABLE.' SET '.$sql.' WHERE id='.db_input($id).' AND filter_id='.db_input($vars['filter_id']);
            if(db_query($sql))
                return true;

        } else {
            $sql='INSERT INTO '.FILTER_RULE_TABLE.' SET created=NOW(), filter_id='.db_input($vars['filter_id']).', '.$sql;
            if(db_query($sql) && ($id=db_insert_id()))
                return $id;
        }

        return false;
    }

    /* static */ function lookup($id,$filterId=0) {
        return ($id && is_numeric($id) && ($r= new FilterRule($id,$filterId)) && $r->getId()==$id)?$r:null;
    }

}

/**
 * Applies rules defined in the admin control panel > Settings tab > "Ticket Filters". Each
 * filter can have up to 25 rules (*currently). This will attempt to match
 * the incoming tickets against the defined rules, and, if the email matches,
 * the ticket will be modified as described in the filter actions.
 */
class TicketFilter {

    var $target;
    var $vars;

    /**
     * Construct a list of filters to handle a new ticket
     * taking into account the source/origin of the ticket.
     *
     * $vars is an ARRAY, which has valid keys
     *  *email - email address of user
     *   name - name of user
     *   subject - subject of the ticket
     *   emailId - id of osTicket's system email (for emailed tickets)
     *  ---------------
     *  @see Filter::matches() for a complete list of supported keys
     */
    function __construct($origin, $vars=array()) {

        //Normalize the target based on ticket's origin.
        $this->target = self::origin2target($origin);

        //Extract the vars we care about (fields we filter by!).
        $this->vars = array('body'=>$vars['message']);
        $interest = Filter::getSupportedMatchFields();
        // emailId is always significant to the filter process
        $interest[] = 'emailId';
        foreach ($vars as $k=>$v) {
            if (in_array($k, $interest))
                $this->vars[$k] = trim($v);
        }
        if (isset($vars['recipients']) && $vars['recipients']) {
            foreach ($vars['recipients'] as $r) {
                $this->vars['addressee'][] = $r['name'];
                $this->vars['addressee'][] = $r['email'];
            }
            $this->vars['addressee'] = implode(' ', $this->vars['addressee']);
        }

         //Init filters.
        $this->build();
    }

    function build() {

        //Clear any memoized filters
        $this->filters = array();
        $this->short_list = null;

        //Query DB for "possibly" matching filters.
        $res = $this->getAllActive();
        if($res) {
            while (list($id) = db_fetch_row($res))
                $this->filters[] = new Filter($id);
        }

        return $this->filters;
    }

    function getTarget() {
        return $this->target;
    }

    /**
     * Fetches the short list of filters that match the ticket vars received in the
     * constructor. This function is memoized so subsequent calls will
     * return immediately.
     */
    function getMatchingFilterList() {

        if (!isset($this->short_list)) {
            $this->short_list = array();
            foreach ($this->filters as $filter)
                if ($filter->matches($this->vars))
                    $this->short_list[] = $filter;
        }

        return $this->short_list;
    }
    /**
     * Determine if any filters match the received email, and if so, apply
     * actions defined in those filters to the ticket-to-be-created.
     *
     * Throws:
     * RejectedException if the email should not be acceptable. If the email
     * should be rejected, the first filter that matches and has reject
     * ticket set is returned.
     */
    function apply(&$ticket) {
        foreach ($this->getMatchingFilterList() as $filter) {
            $filter->apply($ticket, $this->vars);
            if ($filter->stopOnMatch()) break;
        }
    }

    function getAllActive() {

        $sql='SELECT id FROM '.FILTER_TABLE
            .' WHERE isactive=1 '
            .'  AND target IN ("Any", '.db_input($this->getTarget()).') ';

        #Take into account email ID.
        if($this->vars['emailId'])
            $sql.=' AND (email_id=0 OR email_id='.db_input($this->vars['emailId']).')';

        $sql.=' ORDER BY execorder';

        return db_query($sql);
    }

    /**
     * Simple true/false if the headers of the email indicate that the email
     * is an automatic response.
     *
     * Thanks to http://wiki.exim.org/EximAutoReply
     * X-Auto-Response-Supress is outlined here,
     *    http://msdn.microsoft.com/en-us/library/ee219609(v=exchg.80).aspx
     */
    /* static */
    function isAutoReply($headers) {

        if($headers && !is_array($headers))
            $headers = Mail_Parse::splitHeaders($headers);

        $auto_headers = array(
            'Auto-Submitted'    => array('AUTO-REPLIED', 'AUTO-GENERATED'),
            'Precedence'        => array('AUTO_REPLY', 'BULK', 'JUNK', 'LIST'),
            'X-Precedence'      => array('AUTO_REPLY', 'BULK', 'JUNK', 'LIST'),
            'X-Autoreply'       => 'YES',
            'X-Auto-Response-Suppress' => array('ALL', 'DR', 'RN', 'NRN', 'OOF', 'AutoReply'),
            'X-Autoresponse'    => '*',
            'X-AutoReply-From'  => '*',
            'X-Autorespond'     => '*',
            'X-Mail-Autoreply'  => '*',
            'X-Autogenerated'   => 'REPLY',
            'X-AMAZON-MAIL-RELAY-TYPE' => 'NOTIFICATION',
        );

        foreach ($auto_headers as $header=>$find) {
            if(!isset($headers[$header])) continue;

            $value = strtoupper($headers[$header]);
            # Search text must be found at the beginning of the header
            # value. This is especially import for something like the
            # subject line, where something like an autoreponse may
            # appear somewhere else in the value.

            if (is_array($find)) {
                foreach ($find as $f)
                    if (strpos($value, $f) === 0)
                        return true;
            } elseif ($find === '*') {
                return true;
            } elseif (strpos($value, $find) === 0) {
                return true;
            }
        }

        return false;
    }

    static function isBounce($headers) {

        if($headers && !is_array($headers))
            $headers = Mail_Parse::splitHeaders($headers);

        $bounce_headers = array(
            'From'  => array('stripos',
                        array('MAILER-DAEMON', '<>', 'postmaster@'), null, false),
            'Subject'   => array('stripos',
                array('DELIVERY FAILURE', 'DELIVERY STATUS',
                    'UNDELIVERABLE:', 'Undelivered Mail Returned'), 0),
            'Return-Path'   => array('strcmp', array('<>'), 0),
            'Content-Type'  => array('stripos', array('report-type=delivery-status'), null, false),
            'X-Failed-Recipients' => array('strpos', array('@'), null, false)
        );

        foreach ($bounce_headers as $header => $find) {
            if(!isset($headers[$header])) continue;

            @list($func, $searches, $pos, $neg) = $find;

            if(!($value = $headers[$header]) || !is_array($searches))
                continue;

            foreach ($searches as $f) {
                $result = call_user_func($func, $value, $f);
                if (($pos === null && $result !== $neg) or ($result === $pos))
                    return true;
            }
        }

        return false;
    }

    /**
     * Normalize ticket source to supported filter target
     *
     */
    function origin2target($origin) {
        $sources=array('web' => 'Web', 'email' => 'Email', 'phone' => 'Web', 'staff' => 'Web', 'api' => 'API');

        return $sources[strtolower($origin)];
    }
}

class RejectedException extends Exception {
    var $filter;
    var $vars;

    function __construct(Filter $filter, $vars) {
        parent::__construct('Ticket rejected by a filter');
        $this->filter = $filter;
        $this->vars = $vars;
    }

    function getRejectingFilter() {
        return $this->filter;
    }

    function get($what) {
        return $this->vars[$what];
    }
}

class FilterDataChanged extends Exception {
    var $data;

    function __construct($data) {
         parent::__construct('Ticket filter data changed');
         $this->data = $data;
    }

    function getData() {
        return $this->data;
    }
}

/**
 * Function: endsWith
 *
 * Returns TRUE if the haystack ends with needle and FALSE otherwise.
 * Thanks, http://stackoverflow.com/a/834355
 */
function iendsWith($haystack, $needle)
{
    $length = mb_strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (strcasecmp(mb_substr($haystack, -$length), $needle) === 0);
}

function pregMatchB($subject, $pattern) {
    return preg_match($pattern, $subject);
}
?>
