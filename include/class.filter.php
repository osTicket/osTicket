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

class Filter
extends VerySimpleModel {
    static $meta = array(
        'table' => FILTER_TABLE,
        'pk' => array('id'),
        'ordering' => array('execorder'),
        'joins' => array(
            'rules' => array(
                'reverse' => 'FilterRule.filter',
            ),
            'actions' => array(
                'reverse' => 'FilterAction.filter',
            ),
        ),
    );

    const FLAG_INACTIVE_HT = 0x0001;
    const FLAG_INACTIVE_DEPT  = 0x0002;

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

    function __construct($vars=array()) {
        parent::__construct($vars);
        $this->created = SqlFunction::NOW();
    }

    function getId() {
        return $this->id;
    }

    function getTarget() {
        return $this->target;
    }

    function getName() {
        return $this->name;
    }

    function getNotes() {
        return $this->notes;
    }

    function getInfo() {
        $ht = $this->ht;
        if (static::$meta['joins'])
            foreach (static::$meta['joins'] as $k => $v)
                unset($ht[$k]);
        return $ht;
    }

    function getNumRules() {
        return $this->rules->count();
    }

    function getExecOrder() {
        return $this->execorder;
    }

    function getEmailId() {
        return $this->email_id;
    }

    function isActive() {
        return ($this->isactive);
    }

    function isSystemBanlist() {
        return !strcasecmp($this->getName(),'SYSTEM BAN LIST');
    }

    function getDeptId() {
        return $this->dept_id;
    }

    function getStatusId() {
        return $this->status_id;
    }

    function getPriorityId() {
        return $this->priority_id;
    }

    function getSLAId() {
        return $this->sla_id;
    }

    function getStaffId() {
        return $this->staff_id;
    }

    function getTeamId() {
        return $this->team_id;
    }

    function getCannedResponse() {
        return $this->canned_response_id;
    }

    function getHelpTopic() {
        return $this->topic_id;
    }

    public function setFlag($flag, $val) {
        $vars = array();
        $errors = array();
        if ($val)
            $this->flags |= $flag;
        else
            $this->flags &= ~$flag;
        $vars['rules']= $this->getRules();
        $this->ht['pass'] = true;
        $this->update($this->ht, $errors);
    }

    function hasFlag($flag) {
        return 0 !== ($this->ht['flags'] & $flag);
    }

    function stopOnMatch() {
        return ($this->stop_onmatch);
    }

    function matchAllRules() {
        return ($this->match_all_rules);
    }

    function rejectOnMatch() {
        return ($this->reject_ticket);
    }

    function useReplyToEmail() {
        return ($this->use_replyto_email);
    }

    function disableAlerts() {
        return ($this->disable_autoresponder);
    }

    function sendAlerts() {
        return (!$this->disableAlerts());
    }

    function getRules() {
        $rules = [];
        foreach ($this->rules as $r)
            $rules[] = array('w'=>$r->what,'h'=>$r->how,'v'=>$r->val);

        return $rules;
    }

    function addRule($what, $how, $val,$extra=array()) {
        $rule = array_merge($extra,array('what'=>$what, 'how'=>$how, 'val'=>$val));
        $rule = new FilterRule($rule);
        $this->rules->add($rule);
        if ($rule->save())
            return true;
    }

    function removeRule($what, $how, $val) {
        return $this->rules->filter([
            'what' => $what,
            'how' => $how,
            'val' => $val,
        ])->delete();
    }

    function getRule($id) {
        return $this->getRuleById($id);
    }

    function getRuleById($id) {
        return FilterRule::lookup(array('id'=>$id, 'filter_id'=>$this->getId()));
    }

    function containsRule($what, $how, $val) {
        return $this->rules->filter([
            'what' => $what,
            'how' => $how,
            'val' => $val,
        ])->exists();
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
            'not_match' => array('pregMatchB', null, 1),
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
        return $this->actions;
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
        //validate filter actions before moving on
        if (!self::validate_actions($vars, $errors))
            return false;

        $vars['flags'] = $this->flags;

        if(!$vars['execorder'])
            $errors['execorder'] = __('Order required');
        elseif(!is_numeric($vars['execorder']))
            $errors['execorder'] = __('Must be numeric value');

        if(!$vars['name'])
            $errors['name'] = __('Name required');
        elseif(($filter=static::getByName($vars['name'])) && $filter->id!=$this->id)
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

        //Note: this will be set when validating filters
        if ($vars['email_id'])
            $emailId = $vars['email_id'];
        $this->isactive = $vars['isactive'];
        $this->flags = $vars['flags'];
        $this->target = $vars['target'];
        $this->name = $vars['name'];
        $this->execorder = $vars['execorder'];
        $this->email_id = $emailId;
        $this->match_all_rules = $vars['match_all_rules'];
        $this->stop_onmatch = $vars['stop_onmatch'];
        $this->notes = Format::sanitize($vars['notes']);

        if (!$this->save()) {
            if (!$this->__new__) {
                $errors['err']=sprintf(__('Unable to update %s.'), __('this ticket filter'))
                   .' '.__('Internal error occurred');
            }
            else {
                $errors['err']=sprintf(__('Unable to add %s.'), __('this ticket filter'))
                   .' '.__('Internal error occurred');
            }
            return false;
        }

        // Attempt to create/update the actions. Collect the errors
        $this->save_actions($this->getId(), $vars, $errors);
        if ($errors)
            return false;

        //Success with update/create...save the rules. We can't recover from any errors at this point.
        # Don't care about errors stashed in $xerrors
        $xerrors = array();
        if (!$this->save_rules($vars,$xerrors))
            return false;

        return true;
    }

    function delete() {
        try {
            parent::delete();
            $type = array('type' => 'deleted');
            Signal::send('object.deleted', $this, $type);
            $this->rules->expunge();
            $this->actions->expunge();
        }
        catch (OrmException $e) {
            return false;
        }
        return true;
    }

    /** static functions **/
    function getTargets() {
        return array(
                'Any' => __('Any'),
                'Web' => __('Web Forms'),
                'API' => __('API Calls'),
                'Email' => __('Emails'));
    }

    static function getByName($name) {
        return static::lookup(['name' => $name]);
    }

    function validate_rules($vars,&$errors) {
        $matches = array_keys(self::getSupportedMatchFields());
        $types = array_keys(self::getSupportedMatchTypes());
        $rules = array();
        foreach ($vars['rules'] as $i=>$rule) {
            if ($rule->ht) {
                $rule = $rule->ht;
                $rule["w"] = $rule["what"];
                $rule["h"] = $rule["how"];
                $rule["v"] = $rule["val"];
            }

            if (is_array($rule)) {
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
        }

        if(!$rules && !$errors)
            $errors['rules']=__('You must set at least one rule.');

        return $rules;
    }

    function save_rules($vars, &$errors) {
        $rules = $this->validate_rules($vars, $errors);

        if ($errors)
            return false;

        //Clear existing rules...we're doing mass replace on each save!!
        $this->rules->expunge();
        $num = 0;
        foreach ($rules as $rule) {
            $rule = new FilterRule($rule);
            $this->rules->add($rule);
            $rule->save();
            $num++;
        }

        return $num;
    }

    function save($refetch=false) {
        if ($this->dirty)
            $this->updated = SqlFunction::NOW();
        return parent::save($refetch || $this->dirty);
    }

    static function create($vars,&$errors) {
        $filter = new static($vars);
        if ($filter->save())
            return $filter;
    }

    function validate_actions($vars, &$errors) {
        //allow the save if it is to set a filter flag
        if ($vars['pass'])
            return true;

        if (!is_array(@$vars['actions']))
            return;
      foreach ($vars['actions'] as $sort=>$v) {
          if (is_array($v)) {
              $info = $v['type'];
              $sort = $v['sort'] ?: $sort;
          } else
              $info = substr($v, 1);
          $action = new FilterAction(array(
              'type'=>$info,
              'sort' => (int) $sort,
          ));
          $errors = array();
          $action->setConfiguration($errors, $vars);
          $config = json_decode($action->ht['configuration'], true);
          if (is_numeric($action->ht['type'])) {
              foreach ($config as $key => $value) {
                  if ($key == 'topic_id') {
                      $action->ht['type'] = 'topic';
                      $config['topic_id'] = $value;
                  }
                  if ($key == 'dept_id') {
                      $action->ht['type'] = 'dept';
                      $config['dept_id'] = $value;
                  }
              }
          }

          // do not throw an error if we are deleting an action
          if (substr($v, 0, 1) != 'D') {
              switch ($action->ht['type']) {
                case 'dept':
                  $dept = Dept::lookup($config['dept_id']);
                  if (!$dept || !$dept->isActive()) {
                    $errors['err'] = sprintf(__('Unable to save: Please choose an active %s'), 'Department');
                  }
                  break;
                case 'topic':
                  $topic = Topic::lookup($config['topic_id']);
                  if (!$topic || !$topic->isActive()) {
                    $errors['err'] = sprintf(__('Unable to save: Please choose an active %s'), 'Help Topic');
                  }
                  break;
                default:
                  foreach ($config as $key => $value) {
                    if (!$value) {
                      $errors['err'] = sprintf(__('Unable to save: Please insert a value for %s'), ucfirst($action->ht['type']));
                    }
                  }
                  break;
              }
          }
      }
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
            }
            else {
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
            case 'I': # existing filter action
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

class FilterRule
extends VerySimpleModel {
    static $meta = array(
        'table' => FILTER_RULE_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'filter' => array(
                'constraint' => array('filter_id' => 'Filter.id'),
            ),
        ),
    );

    function getId() {
        return $this->id;
    }

    function isActive() {
        return ($this->isactive);
    }

    function getHashtable() {
        return $this->ht;
    }

    function getInfo() {
        return $this->getHashtable();
    }

    function getFilterId() {
        return $this->filter_id;
    }

    function getFilter() {
        return $this->filter;
    }

    function update($vars, &$errors) {
        if (!$vars['filter_id'])
            $errors['err']=__('Parent filter ID required');

        if ($errors)
            return false;

        $this->what = $vars['what'];
        $this->how = $vars['how'];
        $this->val = $vars['val'];
        $this->isactive = isset($vars['isactive']) ? (int) $vars['isactive'] : 1;

        if (isset($vars['notes']))
            $this->notes = Format::sanitize($vars['notes']);

        if ($this->save())
            return true;
    }

    function save($refetch=false) {
        if ($this->dirty)
            $this->updated = SqlFunction::NOW();

        return parent::save($refetch || $this->dirty);
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
        foreach ($this->getAllActive() as $filter)
            $this->filters[] = $filter;

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
        $filters = Filter::objects()->filter([
            'isactive' => 1,
            'target__in' => array('Any', $this->getTarget()),
        ]);

        #Take into account email ID.
        if ($this->vars['emailId'])
            $filters = $filters->filter([
                'email_id__in' => array(0, $this->vars['emailId'])
            ]);

        return $filters->order_by('execorder')->all();
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
    static function isAutoReply($headers) {

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
