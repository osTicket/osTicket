<?php
/*********************************************************************
    class.filter.php

    Email Filter Class

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

    function Filter($id){
        $this->id=0;
        $this->load($id);
    }

    function load($id=0) {

        if(!$id && !($id=$this->getId()))
            return false;

        $sql='SELECT filter.*,count(rule.id) as rule_count '
            .' FROM '.EMAIL_FILTER_TABLE.' filter '
            .' LEFT JOIN '.EMAIL_FILTER_RULE_TABLE.' rule ON(rule.filter_id=filter.id) '
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

    function getId(){
        return $this->id;
    }

    function getName(){
        return $this->ht['name'];
    }

    function getNotes(){
        return $this->ht['notes'];
    }

    function getInfo(){
        return  $this->ht;
    }

    function getNumRules(){
        return $this->ht['rule_count'];
    }

    function getExecOrder(){
        return $this->ht['execorder'];
    }

    function isActive(){
        return ($this->ht['isactive']);
    }

    function isSystemBanlist() {
        return !strcasecmp($this->getName(),'SYSTEM BAN LIST');
    }

    function getDeptId(){
        return $this->ht['dept_id'];
    }

    function getPriorityId(){
        return $this->ht['priority_id'];
    }

    function getSLAId(){
        return $this->ht['sla_id'];
    }

    function getStaffId(){
        return $this->ht['staff_id'];
    }

    function getTeamId(){
        return $this->ht['team_id'];
    }

    function stopOnMatch(){
        return ($this->ht['stop_on_match']);
    }

    function matchAllRules(){
        return ($this->ht['match_all_rules']);
    }

    function rejectEmail(){
        return ($this->ht['reject_email']);
    }

    function useReplyToEmail(){
        return ($this->ht['use_replyto_email']);
    }

    function disableAlerts(){
        return ($this->ht['disable_autoresponder']);
    }
     
    function sendAlerts(){
        return (!$this->disableAlerts());
    }

    function getRules(){
        if (!$this->ht['rules']) {
            $rules=array();
            //We're getting the rules...live because it gets cleared on update.
            $sql='SELECT * FROM '.EMAIL_FILTER_RULE_TABLE.' WHERE filter_id='.db_input($this->getId());
            if(($res=db_query($sql)) && db_num_rows($res)){
                while($row=db_fetch_array($res))
                    $rules[]=array('w'=>$row['what'],'h'=>$row['how'],'v'=>$row['val']);
            }
            $this->ht['rules'] = $rules;
        }
        return $this->ht['rules'];
    }

    function getFlatRules(){ //Format used on html... I'm ashamed 

        $info=array();
        if(($rules=$this->getRules())){
            foreach($rules as $k=>$rule){
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

        return FilterRule::create($rule,$errors);
    }

    function removeRule($what, $how, $val) {

        $sql='DELETE FROM '.EMAIL_FILTER_RULE_TABLE
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
        if (isset($this->ht['rules'])) {
            foreach ($this->ht['rules'] as $rule) {
                if (array("w"=>$what, "h"=>$how, "v"=>$val) == $rule) {
                    return True;
                }
            }
            return False;
        } else {
            # Fetch from database
            return 0 != db_count(
                "SELECT COUNT(*) FROM ".EMAIL_FILTER_RULE_TABLE
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
     * $email is an ARRAY, which has valid keys
     *  *from - email address of sender
     *   name - name of sender
     *   subject - subject line of the email
     *   body - body content of the email (no attachments, please)
     *   reply-to - reply-to email address
     *   reply-to-name - name of sender to reply-to
     *   headers - array of email headers
     *   emailid - osTicket email id of recipient
     */
    function matches($email) {
        $what = array(
            "email"     => $email['from'],
            "subject"   => $email['subject'],
            # XXX: Support reply-to too ?
            "name"      => $email['name'],
            "body"      => $email['body']
            # XXX: Support headers
        );
        $how = array(
            # how => array(function, null or === this, null or !== this)
            "equal"     => array("strcmp", 0),
            "not_equal" => array("strcmp", null, 0),
            "contains"  => array("strpos", null, false),
            "dn_contain"=> array("strpos", false)
        );
        $match = false;
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
    function apply(&$ticket, $email=null) {
        # TODO: Disable alerting
        # XXX: Does this imply turning it on as well? (via ->sendAlerts())
        if ($this->disableAlerts()) $ticket['autorespond']=false;
        #       Set owning department (?)
        if ($this->getDeptId())     $ticket['deptId']=$this->getDeptId();
        #       Set ticket priority (?)
        if ($this->getPriorityId()) $ticket['pri']=$this->getPriorityId();
        #       Set SLA plan (?)
        if ($this->getSLAId())      $ticket['slaId']=$this->getSLAId();
        #       Auto-assign to (?)
        #       XXX: Unset the other (of staffId or teamId) (?)
        if ($this->getStaffId())    $ticket['staffId']=$this->getStaffId();
        elseif ($this->getTeamId()) $ticket['teamId']=$this->getTeamId();
        #       Override name with reply-to information from the EmailFilter
        #       match
        if ($this->useReplyToEmail() && $email['reply-to']) {
            $ticket['email'] = $email['reply-to'];
            if ($email['reply-to-name']) 
                $ticket['name'] = $email['reply-to-name'];
        }
    }

    function update($vars,&$errors){

        if(!Filter::save($this->getId(),$vars,$errors))
            return false;

        $this->reload();
       
        return true;
    }

    function delete(){
        
        $id=$this->getId();
        $sql='DELETE FROM '.EMAIL_FILTER_TABLE.' WHERE id='.db_input($id).' LIMIT 1';
        if(db_query($sql) && ($num=db_affected_rows())){
            db_query('DELETE FROM '.EMAIL_FILTER_RULE_TABLE.' WHERE filter_id='.db_input($id));
        }

        return $num;
    }

    /** static functions **/
    function create($vars,&$errors){
        return Filter::save(0,$vars,$errors);
    }

    function getIdByName($name){

        $sql='SELECT id FROM '.EMAIL_FILTER_TABLE.' WHERE name='.db_input($name);
        if(($res=db_query($sql)) && db_num_rows($res))
            list($id)=db_fetch_row($res);

        return $id;
    }

    function lookup($id){
        return ($id && is_numeric($id) && ($f= new Filter($id)) && $f->getId()==$id)?$f:null;
    }

    function validate_rules($vars,&$errors){
        return self::save_rules(0,$vars,$errors);
    }

    function save_rules($id,$vars,&$errors){

        $matches=array('name','email','subject','body','header');
        $types=array('equal','not_equal','contains','dn_contain');

        $rules=array();
        for($i=1; $i<=25; $i++) { //Expecting no more than 25 rules...
            if($vars["rule_w$i"] || $vars["rule_h$i"]){
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
            }elseif($vars["rule_v$i"]){
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
        db_query('DELETE FROM '.EMAIL_FILTER_RULE_TABLE.' WHERE filter_id='.db_input($id));
        $num=0;
        foreach($rules as $rule) {
            $rule['filter_id']=$id;
            if(FilterRule::create($rule, $errors))
                $num++;
        }

        return $num; 
    }

    function save($id,$vars,&$errors){


        if(!$vars['execorder'])
            $errors['execorder']='Order required';
        elseif(!is_numeric($vars['execorder']))
            $errors['execorder']='Must be numeric value';
            
        if(!$vars['name'])
            $errors['name']='Name required';
        elseif(($sid=self::getIdByName($vars['name'])) && $sid!=$id)
            $errors['name']='Name already in-use';

        if(!$errors && !self::validate_rules($vars,$errors) && !$errors['rules'])
            $errors['rules']='Unable to validate rules as entered';

        if($errors) return false;

        $sql=' updated=NOW() '.
             ',isactive='.db_input($vars['isactive']).
             ',name='.db_input($vars['name']).
             ',execorder='.db_input($vars['execorder']).
             ',email_id='.db_input($vars['email_id']).
             ',dept_id='.db_input($vars['dept_id']).
             ',priority_id='.db_input($vars['priority_id']).
             ',sla_id='.db_input($vars['sla_id']).
             ',match_all_rules='.db_input($vars['match_all_rules']).
             ',stop_onmatch='.db_input(isset($vars['stop_onmatch'])?1:0).
             ',reject_email='.db_input(isset($vars['reject_email'])?1:0).
             ',use_replyto_email='.db_input(isset($vars['use_replyto_email'])?1:0).
             ',disable_autoresponder='.db_input(isset($vars['disable_autoresponder'])?1:0).
             ',notes='.db_input($vars['notes']);
       

        //Auto assign ID is overloaded...
        if($vars['assign'] && $vars['assign'][0]=='s')
             $sql.=',team_id=0,staff_id='.db_input(preg_replace("/[^0-9]/", "",$vars['assign']));
        elseif($vars['assign'] && $vars['assign'][0]=='t')
            $sql.=',staff_id=0,team_id='.db_input(preg_replace("/[^0-9]/", "",$vars['assign']));
        else
            $sql.=',staff_id=0,team_id=0 '; //no auto-assignment!

        if($id) {
            $sql='UPDATE '.EMAIL_FILTER_TABLE.' SET '.$sql.' WHERE id='.db_input($id);
            if(!db_query($sql))
                $errors['err']='Unable to update the filter. Internal error occurred';
        }else{
            $sql='INSERT INTO '.EMAIL_FILTER_TABLE.' SET '.$sql.',created=NOW() ';
            if(!db_query($sql) || !($id=db_insert_id()))
                $errors['err']='Unable to add filter. Internal error';
        }

        if($errors || !$id) return false;

        //Success with update/create...save the rules. We can't recover from any errors at this point.
        self::save_rules($id,$vars,$xerrors);
      
        return true;
    }
}

class FilterRule {

    var $id;
    var $ht;

    var $filter;

    function FilterRule($id,$filterId=0){
        $this->id=0;
        $this->load($id,$filterId);
    }

    function load($id,$filterId=0) {

        $sql='SELECT rule.* FROM '.EMAIL_FILTER_RULE_TABLE.' rule '
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

    function delete(){
        
        $sql='DELETE FROM '.EMAIL_FILTER_RULE_TABLE.' WHERE id='.db_input($this->getId()).' AND filter_id='.db_input($this->getFilterId());

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
            $sql='UPDATE '.EMAIL_FILTER_RULE_TABLE.' SET '.$sql.' WHERE id='.db_input($id).' AND filter_id='.db_input($vars['filter_id']);
            if(db_query($sql))
                return true;

        } else {
            $sql='INSERT INTO '.EMAIL_FILTER_RULE_TABLE.' SET created=NOW(), filter_id='.db_input($vars['filter_id']).', '.$sql;
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
 * Applies rules defined in the staff control panel "Email Filters". Each
 * filter can have up to 25 rules (*currently). This will attempt to match
 * the incoming email against the defined rules, and, if the email matches,
 * the ticket will be modified as described in the filter
 */
class EmailFilter {
    /**
     * Construct a list of filters to handle a new ticket generated from an
     * email or something with information common to email (such as API
     * calls, etc).
     *
     * $email is an ARRAY, which has valid keys
     *  *from - email address of sender
     *   name - name of sender
     *   subject - subject line of the email
     *   email-id - id of osTicket email recipient address
     *  ---------------
     *  @see Filter::matches() for a complete list of supported keys
     *
     * $slow - if TRUE, every (active) filter will be fetched from the
     *         database and matched against the email. Otherwise, a subset
     *         of filters from the database that appear to have rules that
     *         deal with the data in the email will be considered. @see
     *         ::quickList() for more information.
     */
    function EmailFilter($email, $slow=false) {
        $this->email = $email;
        if ($slow) {
            $this->build($this->getAllActive());
        } else {
            $this->build(
                $this->quickList($email['from'], $email['name'],
                    $email['subject']));
        }
    }
    
    function build($res) {
        $this->filters = array();
        while (list($id) = db_fetch_row($res))
            array_push($this->filters, new Filter($id));
        return $this->filters;
    }
    /**
     * Fetches the short list of filters that match the email received in the
     * constructor. This function is memoized so subsequent calls will
     * return immediately.
     */
    function getMatchingFilterList() {
        if (!isset($this->short_list)) {
            $this->short_list = array();
            foreach ($this->filters as $filter)
                if ($filter->matches($this->email))
                    $this->short_list[] = $filter;
        }
        return $this->short_list;
    }
    /**
     * Determine if the filters that match the received email indicate that
     * the email should be rejected
     *
     * Returns FALSE if the email should be acceptable. If the email should
     * be rejected, the first filter that matches and has rejectEmail set is
     * returned.
     */
    function shouldReject() {
        foreach ($this->getMatchingFilterList() as $filter) {
            # Set reject if this filter indicates that the email should
            # be blocked; however, don't unset $reject, because if it
            # was set by another rule that did not set stopOnMatch(), we
            # should still honor its configuration
            if ($filter->rejectEmail()) return $filter;
        }
        return false;
    }
    /**
     * Determine if any filters match the received email, and if so, apply
     * actions defined in those filters to the ticket-to-be-created.
     */
    function apply(&$ticket) {
        foreach ($this->getMatchingFilterList() as $filter) {
            $filter->apply($ticket, $this->email);
            if ($filter->stopOnMatch()) break;
        }
    }
    
    /* static */ function getAllActive() {
        $sql="SELECT id FROM ".EMAIL_FILTER_TABLE." WHERE isactive"
           ." ORDER BY execorder";

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
    /* static */ function quickList($addr, $name=false, $subj=false, 
            $emailid=0) {
        $sql="SELECT DISTINCT filter_id FROM ".EMAIL_FILTER_RULE_TABLE." rule"
           ." INNER JOIN ".EMAIL_FILTER_TABLE." filter"
           ." ON (filter.id=rule.filter_id)"
           ." WHERE filter.isactive";
        # Filter by recipient email-id if specified
        if ($emailid) #TODO: Fix the logic here...
            $sql.=" AND filter.email_id=".db_input($emailid);
        # Include rules for sender-email, sender-name and subject as
        # requested
        $sql.=" AND ((what='email' AND LOCATE(val,".db_input($addr)."))";
        if ($name) 
            $sql.=" OR (what='name' AND LOCATE(val,".db_input($name)."))";
        if ($subj) 
            $sql.=" OR (what='subject' AND LOCATE(val,".db_input($subj)."))";
        # Also include filters that do not have any rules concerning either
        # sender-email-addresses or sender-names or subjects
        $sql.=") OR filter.id IN ("
               ." SELECT filter_id "
               ." FROM ".EMAIL_FILTER_RULE_TABLE." rule"
               ." INNER JOIN ".EMAIL_FILTER_TABLE." filter"
               ." ON (rule.filter_id=filter.id)"
               ." GROUP BY filter_id"
               ." HAVING COUNT(*)-COUNT(NULLIF(what,'email'))=0";
        if ($name!==false) $sql.=" AND COUNT(*)-COUNT(NULLIF(what,'name'))=0";
        if ($subj!==false) $sql.=" AND COUNT(*)-COUNT(NULLIF(what,'subject'))=0";
        # Also include filters that do not have match_all_rules set to and
        # have at least one rule 'what' type that wasn't considered
        $sql.=") OR filter.id IN ("
               ." SELECT filter_id"
               ." FROM ".EMAIL_FILTER_RULE_TABLE." rule"
               ." INNER JOIN ".EMAIL_FILTER_TABLE." filter"
               ." ON (rule.filter_id=filter.id)"
               ." WHERE what NOT IN ('email'"
        # Handle sender-name and subject if specified
               .(($name!==false)?",'name'":"")
               .(($subj!==false)?",'subject'":"")
               .") AND filter.match_all_rules = false"
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
            .' FROM '.EMAIL_FILTER_TABLE.' filter'
            .' INNER JOIN '.EMAIL_FILTER_RULE_TABLE.' rule'
            .' ON (filter.id=rule.filter_id)'
            .' WHERE filter.reject_email'
            .'   AND filter.match_all_rules=0'
            .'   AND filter.email_id=0'
            .'   AND filter.isactive'
            .'   AND rule.isactive '
            .'   AND rule.what="email"'
            .'   AND LOCATE(rule.val,'.db_input($addr).')';

        # XXX: Use MB_xxx function for proper unicode support
        $addr = strtoupper($addr);
        $how=array('equal'      => array('strcmp', 0),
                   'contains'   => array('strpos', null, false));

        if ($res=db_query($sql)) {
            while ($row=db_fetch_array($res)) {
                list($func, $pos, $neg) = $how[$row['how']];
                if (!$func) continue;
                $res = call_user_func($func, $addr, $row['val']);
                if (($neg === null && $res === $pos) || $res !== $neg)
                    return $row['id'];
            }
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
        $auto_headers = array(
            'Auto-Submitted'    => 'AUTO-REPLIED',
            'Precedence'        => array('AUTO_REPLY', 'BULK', 'JUNK', 'LIST'),
            'Subject'           => array('OUT OF OFFICE', 'AUTO-REPLY:', 'AUTORESPONSE'),
            'X-Autoreply'       => 'YES',
            'X-Auto-Response-Suppress' => 'OOF',
            'X-Autoresponse'    => '',
            'X-Auto-Reply-From' => ''
        );
        foreach ($auto_headers as $header=>$find) {
            if ($value = strtoupper($headers[$header])) {
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
        }
        return false;
    }
}
?>
