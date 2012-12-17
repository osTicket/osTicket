<?php
/*********************************************************************
    class.filter.php

    Ticket Filter

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
class Filter {

    var $id;
    var $ht;

    function Filter($id) {
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

    function stopOnMatch() {
        return ($this->ht['stop_on_match']);
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

    function getFlatRules() { //Format used on html... I'm ashamed 

        $info=array();
        if(($rules=$this->getRules())) {
            foreach($rules as $k=>$rule) {
                $i=$k+1;
                $info["rule_w$i"]=$rule['w'];
                $info["rule_h$i"]=$rule['h'];
                $info["rule_v$i"]=$rule['v'];
            }
        }
        return $info;
    }

    function addRule($what, $how, $val,$extra=array()) {

        $rule= array_merge($extra,array('w'=>$what, 'h'=>$how, 'v'=>$val));
        $rule['filter_id']=$this->getId();

        return FilterRule::create($rule,$errors);               # nolint
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
            'equal'     => array('strcmp', 0),
            'not_equal' => array('strcmp', null, 0),
            'contains'  => array('strpos', null, false),
            'dn_contain'=> array('strpos', false),
            'starts'    => array('strpos', 0),
            'ends'      => array('endsWith', true)
        );

        $match = false;
        # Respect configured filter email-id
        if ($this->getEmailId() 
                && !strcasecmp($this->getTarget(), 'Email')
                && $this->getEmailId() != $what['emailId'])
            return false;

        foreach ($this->getRules() as $rule) {
            list($func, $pos, $neg) = $how[$rule['h']];
            # TODO: convert $what and $rule['v'] to mb_strtoupper and do
            #       case-sensitive, binary-safe comparisons. Would be really
            #       nice to do $rule['v'] on the database side for
            #       performance -- but ::getFlatRules() is a blocker
            $result = call_user_func($func, strtoupper($what[$rule['w']]),
                strtoupper($rule['v']));
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
    /** 
     * If the matches() method returns TRUE, send the initial ticket to this
     * method to apply the filter actions defined
     */
    function apply(&$ticket, $info=null) {
        # TODO: Disable alerting
        # XXX: Does this imply turning it on as well? (via ->sendAlerts())
        if ($this->disableAlerts()) $ticket['autorespond']=false;
        #       Set owning department (?)
        if ($this->getDeptId())     $ticket['deptId']=$this->getDeptId();
        #       Set ticket priority (?)
        if ($this->getPriorityId()) $ticket['priorityId']=$this->getPriorityId();
        #       Set SLA plan (?)
        if ($this->getSLAId())      $ticket['slaId']=$this->getSLAId();
        #       Auto-assign to (?)
        #       XXX: Unset the other (of staffId or teamId) (?)
        if ($this->getStaffId())    $ticket['staffId']=$this->getStaffId();
        elseif ($this->getTeamId()) $ticket['teamId']=$this->getTeamId();
        #       Override name with reply-to information from the TicketFilter
        #       match
        if ($this->useReplyToEmail() && $info['reply-to']) {
            $ticket['email'] = $info['reply-to'];
            if ($info['reply-to-name']) 
                $ticket['name'] = $info['reply-to-name'];
        }

        # Use canned response.
        if ($this->getCannedResponse())
            $ticket['cannedResponseId'] = $this->getCannedResponse();
    }
    /* static */ function getSupportedMatches() {
        return array(
            'name'=>    'Name',
            'email'=>   'Email',
            'subject'=> 'Subject',
            'body'=>    'Body/Text'
        );
    }
    /* static */ function getSupportedMatchTypes() {
        return array(
            'equal'=>       'Equal',
            'not_equal'=>   'Not Equal',
            'contains'=>    'Contains',
            'dn_contain'=>  'Does Not Contain',
            'starts'=>      'Starts With',
            'ends'=>        'Ends With'
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
                'Any' => 'Any',
                'Web' => 'Web Forms',
                'API' => 'API Calls',
                'Email' => 'Emails');
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
        return ($id && is_numeric($id) && ($f= new Filter($id)) && $f->getId()==$id)?$f:null;
    }

    function validate_rules($vars,&$errors) {
        return self::save_rules(0,$vars,$errors);
    }

    function save_rules($id,$vars,&$errors) {

        $matches = array_keys(self::getSupportedMatches());
        $types = array_keys(self::getSupportedMatchTypes());

        $rules=array();
        for($i=1; $i<=25; $i++) { //Expecting no more than 25 rules...
            if($vars["rule_w$i"] || $vars["rule_h$i"]) {
                if(!$vars["rule_w$i"] || !in_array($vars["rule_w$i"],$matches))
                    $errors["rule_$i"]='Invalid match selection';
                elseif(!$vars["rule_h$i"] || !in_array($vars["rule_h$i"],$types))
                    $errors["rule_$i"]='Invalid match type selection';
                elseif(!$vars["rule_v$i"])
                    $errors["rule_$i"]='Value required';
                elseif($vars["rule_w$i"]=='email' && $vars["rule_h$i"]=='equal' && !Validator::is_email($vars["rule_v$i"]))
                    $errors["rule_$i"]='Valid email required for the match type';
                else //for everything-else...we assume it's valid.
                    $rules[]=array('w'=>$vars["rule_w$i"],'h'=>$vars["rule_h$i"],'v'=>$vars["rule_v$i"]);
            }elseif($vars["rule_v$i"]) {
                $errors["rule_$i"]='Incomplete selection';
            }
        }

        if(!$rules && is_array($vars["rules"]))
            # XXX: Validation bypass
            $rules = $vars["rules"];
        elseif(!$rules && !$errors)
            $errors['rules']='You must set at least one rule.';

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
            $errors['execorder'] = 'Order required';
        elseif(!is_numeric($vars['execorder']))
            $errors['execorder'] = 'Must be numeric value';
            
        if(!$vars['name'])
            $errors['name'] = 'Name required';
        elseif(($sid=self::getIdByName($vars['name'])) && $sid!=$id)
            $errors['name'] = 'Name already in-use';

        if(!$errors && !self::validate_rules($vars,$errors) && !$errors['rules'])
            $errors['rules'] = 'Unable to validate rules as entered';

        $targets = self::getTargets();
        if(!$vars['target'])
            $errors['target'] = 'Target required';
        else if(!is_numeric($vars['target']) && !$targets[$vars['target']])
            $errors['target'] = 'Unknown or invalid target';

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
            .',dept_id='.db_input($vars['dept_id'])
            .',priority_id='.db_input($vars['priority_id'])
            .',sla_id='.db_input($vars['sla_id'])
            .',match_all_rules='.db_input($vars['match_all_rules'])
            .',stop_onmatch='.db_input(isset($vars['stop_onmatch'])?1:0)
            .',reject_ticket='.db_input(isset($vars['reject_ticket'])?1:0)
            .',use_replyto_email='.db_input(isset($vars['use_replyto_email'])?1:0)
            .',disable_autoresponder='.db_input(isset($vars['disable_autoresponder'])?1:0)
            .',canned_response_id='.db_input($vars['canned_response_id'])
            .',notes='.db_input($vars['notes']);
       

        //Auto assign ID is overloaded...
        if($vars['assign'] && $vars['assign'][0]=='s')
             $sql.=',team_id=0,staff_id='.db_input(preg_replace("/[^0-9]/", "",$vars['assign']));
        elseif($vars['assign'] && $vars['assign'][0]=='t')
            $sql.=',staff_id=0,team_id='.db_input(preg_replace("/[^0-9]/", "",$vars['assign']));
        else
            $sql.=',staff_id=0,team_id=0 '; //no auto-assignment!

        if($id) {
            $sql='UPDATE '.FILTER_TABLE.' SET '.$sql.' WHERE id='.db_input($id);
            if(!db_query($sql))
                $errors['err']='Unable to update the filter. Internal error occurred';
        }else{
            $sql='INSERT INTO '.FILTER_TABLE.' SET '.$sql.',created=NOW() ';
            if(!db_query($sql) || !($id=db_insert_id()))
                $errors['err']='Unable to add filter. Internal error';
        }

        if($errors || !$id) return false;

        //Success with update/create...save the rules. We can't recover from any errors at this point.
        # Don't care about errors stashed in $xerrors
        self::save_rules($id,$vars,$xerrors);               # nolint
      
        return true;
    }
}

class FilterRule {

    var $id;
    var $ht;

    var $filter;

    function FilterRule($id,$filterId=0) {
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
            $errors['err']='Parent filter ID required';


        if($errors) return false;
      
        $sql=' updated=NOW() '.
             ',what='.db_input($vars['w']).
             ',how='.db_input($vars['h']).
             ',val='.db_input($vars['v']).
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
     *
     *  IF $vars is not provided, every (active) filter will be fetched from the
     *  database and matched against the incoming ticket. Otherwise, a subset
     *  of filters from the database that appear to have rules that
     *  deal with the data in the incoming ticket (based on $vars) will be considered.
     *  @see ::quickList() for more information.
     */
    function TicketFilter($origin, $vars=null) {
        
        //Normalize the target based on ticket's origin.
        $this->target = self::origin2target($origin);
  
        //Extract the vars we care about (fields we filter by!).
         $this->vars = array_filter(array_map('trim', 
                 array(
                     'email'     => $vars['email'],
                     'subject'   => $vars['subject'],
                     'name'      => $vars['name'],
                     'body'      => $vars['message'],
                     'emailId'   => $vars['emailId'])
                 ));
        
         //Init filters.
        $this->build();
    }

    function build() {
        
        //Clear any memoized filters
        $this->filters = array();
        $this->short_list = null;

        //Query DB for "possibly" matching filters.
        $res = $this->vars?$this->quickList():$this->getAllActive();
        if($res) {
            while (list($id) = db_fetch_row($res))
                array_push($this->filters, new Filter($id));
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
     * Determine if the filters that match the received vars indicate that
     * the email should be rejected
     *
     * Returns FALSE if the email should be acceptable. If the email should
     * be rejected, the first filter that matches and has reject ticket set is
     * returned.
     */
    function shouldReject() {
        foreach ($this->getMatchingFilterList() as $filter) {
            # Set reject if this filter indicates that the email should
            # be blocked; however, don't unset $reject, because if it
            # was set by another rule that did not set stopOnMatch(), we
            # should still honor its configuration
            if ($filter->rejectOnMatch()) return $filter;
        }
        return false;
    }
    /**
     * Determine if any filters match the received email, and if so, apply
     * actions defined in those filters to the ticket-to-be-created.
     */
    function apply(&$ticket) {
        foreach ($this->getMatchingFilterList() as $filter) {
            $filter->apply($ticket, $this->vars);
            if ($filter->stopOnMatch()) break;
        }
    }
    
    /* static */ function getAllActive() {

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
     * Fast lookup function to all filters that have at least one rule that
     * matches the received address or name or is not defined to match based
     * on an email-address or sender-name. This method is meant to retrieve
     * all possible filters that could potentially match the given
     * arguments. This method will request the database to make a first pass
     * and eliminate the filters from being considered that would never
     * match the received email.
     * 
     * Returns an array<Filter::Id> which will need to have their respective
     * matches() method queried to determine if the Filter actually matches
     * the email.
     *
     * -----> Disclaimer <------------------
     * It would seem that this would not work; however, bear in mind that
     * this logic is completely backwards from the database design. Rather
     * than determining if the email matches the rules, we're determining if
     * the rules *might* apply to the email. This is a "quick" method,
     * because it does not request the database to fully verify that the
     * rule matches the email. Nor does it fetch the rule or filter
     * information from the database. Whether the filter will completely
     * match or not is determined in the Filter::matches() method.
     */
     function quickList() {

        if(!$this->vars || !$this->vars['email'])
            return $this->getAllActive();

        $sql='SELECT DISTINCT filter_id FROM '.FILTER_RULE_TABLE.' rule '
            .' INNER JOIN '.FILTER_TABLE.' filter '
            .' ON (filter.id=rule.filter_id) '
            .' WHERE filter.isactive '
            ."  AND filter.target IN ('Any', ".db_input($this->getTarget()).') ';

        # Filter by system's email-id if specified
        if($this->vars['emailId'])
            $sql.=' AND (filter.email_id=0 OR filter.email_id='.db_input($this->vars['emailId']).')';
        
        # Include rules for sender-email, sender-name and subject as
        # requested
        $sql.=" AND ((what='email' AND LOCATE(val, ".db_input($this->vars['email']).'))';
        if($this->vars['name']) 
            $sql.=" OR (what='name' AND LOCATE(val, ".db_input($this->vars['name']).'))';
        if($this->vars['subject']) 
            $sql.=" OR (what='subject' AND LOCATE(val, ".db_input($this->vars['subject']).'))';


        # Also include filters that do not have any rules concerning either
        # sender-email-addresses or sender-names or subjects
        $sql.=") OR filter.id IN ("
               ." SELECT filter_id "
               ." FROM ".FILTER_RULE_TABLE." rule"
               ." INNER JOIN ".FILTER_TABLE." filter"
               ." ON (rule.filter_id=filter.id)"
               ." WHERE filter.isactive"
               ." AND filter.target IN('Any', ".db_input($this->getTarget()).")"
               ." GROUP BY filter_id"
               ." HAVING COUNT(*)-COUNT(NULLIF(what,'email'))=0";
        if (!$this->vars['name']) $sql.=" AND COUNT(*)-COUNT(NULLIF(what,'name'))=0";
        if (!$this->vars['subject']) $sql.=" AND COUNT(*)-COUNT(NULLIF(what,'subject'))=0";
        # Also include filters that do not have match_all_rules set to and
        # have at least one rule 'what' type that wasn't considered e.g body 
        $sql.=") OR filter.id IN ("
               ." SELECT filter_id"
               ." FROM ".FILTER_RULE_TABLE." rule"
               ." INNER JOIN ".FILTER_TABLE." filter"
               ." ON (rule.filter_id=filter.id)"
               ." WHERE filter.isactive"
               ." AND filter.target IN ('Any', ".db_input($this->getTarget()).")"
               ." AND what NOT IN ('email'"
        # Handle sender-name and subject if specified
               .((!$this->vars['name'])?",'name'":"")
               .((!$this->vars['subject'])?",'subject'":"")
               .") AND filter.match_all_rules = 0 "
        # Return filters in declared execution order
            .") ORDER BY filter.execorder";

        return db_query($sql);
    }
    /**
     * Quick function to determine if the received email-address is
     * indicated by an active email filter to be banned. Returns the id of
     * the filter that has the address blacklisted and FALSE if the email is
     * not blacklisted.
     *
     * XXX: If more detailed matching is to be supported, perhaps this
     *      should receive an array like the constructor and
     *      Filter::matches() method.
     *      Peter - Let's keep it as a quick scan for obviously banned emails.
     */
    /* static */ function isBanned($addr) {

        $sql='SELECT filter.id, what, how, UPPER(val) '
            .' FROM '.FILTER_TABLE.' filter'
            .' INNER JOIN '.FILTER_RULE_TABLE.' rule'
            .' ON (filter.id=rule.filter_id)'
            .' WHERE filter.reject_ticket'
            .'   AND filter.match_all_rules=0'
            .'   AND filter.email_id=0'
            .'   AND filter.isactive'
            .'   AND rule.isactive '
            .'   AND rule.what="email"'
            .'   AND LOCATE(rule.val,'.db_input($addr).')';

        if(!($res=db_query($sql)) || !db_num_rows($res))
            return false;

        # XXX: Use MB_xxx function for proper unicode support
        $addr = strtoupper($addr);
        $how=array('equal'      => array('strcmp', 0),
                   'contains'   => array('strpos', null, false));
            
        while ($row=db_fetch_array($res)) {
            list($func, $pos, $neg) = $how[$row['how']];
            if (!$func) continue;
            $result = call_user_func($func, $addr, $row['val']);
            if (($neg === null && $result === $pos) || $result !== $neg)
                return $row['id'];
        }

        return false;
    }

    /**
     * Simple true/false if the headers of the email indicate that the email
     * is an automatic response.
     *
     * Thanks to http://wiki.exim.org/EximAutoReply
     * X-Auto-Response-Supress is outlined here,
     *    http://msdn.microsoft.com/en-us/library/ee219609(v=exchg.80).aspx
     */
    /* static */ function isAutoResponse($headers) {

        if($headers && !is_array($headers))
            $headers = Mail_Parse::splitHeaders($headers);

        $auto_headers = array(
            'Auto-Submitted'    => 'AUTO-REPLIED',
            'Precedence'        => array('AUTO_REPLY', 'BULK', 'JUNK', 'LIST'),
            'Subject'           => array('OUT OF OFFICE', 'AUTO-REPLY:', 'AUTORESPONSE'),
            'X-Autoreply'       => 'YES',
            'X-Auto-Response-Suppress' => array('ALL', 'DR', 'RN', 'NRN', 'OOF', 'AutoReply'),
            'X-Autoresponse'    => '',
            'X-Auto-Reply-From' => ''
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
            } elseif (strpos($value, $find) === 0) {
                return true;
            }
        }

        # Bounces also counts as auto-responses.
        if(self::isAutoBounce($headers))
            return true;

        return false;
    }

    function isAutoBounce($headers) {

        if($headers && !is_array($headers))
            $headers = Mail_Parse::splitHeaders($headers);

        $bounce_headers = array(
            'From'          => array('<MAILER-DAEMON@MAILER-DAEMON>', 'MAILER-DAEMON', '<>'),
            'Subject'       => array('DELIVERY FAILURE', 'DELIVERY STATUS', 'UNDELIVERABLE:'),
        );

        foreach ($bounce_headers as $header => $find) {
            if(!isset($headers[$header])) continue;

            $value = strtoupper($headers[$header]);

            if (is_array($find)) {
                foreach ($find as $f)
                    if (strpos($value, $f) === 0)
                        return true;
            } elseif (strpos($value, $find) === 0) {
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

/**
 * Function: endsWith
 *
 * Returns TRUE if the haystack ends with needle and FALSE otherwise.
 * Thanks, http://stackoverflow.com/a/834355
 */
function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}
?>
