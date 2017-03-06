<?php
if (!defined('OSTSCPINC') || !$thisstaff
        || !$thisstaff->hasPerm(Ticket::PERM_CREATE, false))
        die('Access Denied');

$info=array();
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);

if (!$info['topicId'])
    $info['topicId'] = $cfg->getDefaultTopicId();

$forms = array();
if ($info['topicId'] && ($topic=Topic::lookup($info['topicId']))) {
    foreach ($topic->getForms() as $F) {
        if (!$F->hasAnyVisibleFields())
            continue;
        if ($_POST) {
            $F = $F->instanciate();
            $F->isValidForClient();
        }
        $forms[] = $F;
    }
}

if ($_POST)
    $info['duedate'] = Format::date(strtotime($info['duedate']), false, false, 'UTC');

//if(!$user) {
//  $user = User::lookupByemail($thisstaff->getEmail());
 //}
?>
<form action="tickets.php?a=open" method="post" id="save"  enctype="multipart/form-data" class="ticket_open_content">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="create">
 <input type="hidden" name="a" value="open">

    <div class="ticket_open_title">
        <h2><?php echo __('Open a New Ticket');?></h2>
    </div>

 <table class="ticket_open" width="100%" border="0" cellspacing="0" cellpadding="2">
    <thead>
    <!-- This looks empty - but beware, with fixed table layout, the user
         agent will usually only consult the cells in the first row to
         construct the column widths of the entire toable. Therefore, the
         first row needs to have two cells -->
        <tr><td style="padding:0;"></td><td style="padding:0;"></td></tr>
    </thead>
    <tbody class="open_ticket_userinformation">
        <tr id="open_ticket_userinformation">
            <th colspan="2" style="min-width:120px;" width="160">
                <em><strong><?php echo __('User Information'); ?></strong>: </em>
                <div class="error"><?php echo $errors['user']; ?></div>
            </th>
        </tr>
        <?php
        if ($user) { ?>
        <tr id="open_ticket_userdata"><td><strong><?php echo __('User'); ?>:</strong></td><td>
            <div id="user-info">
                <input type="hidden" name="uid" id="uid" value="<?php echo $user->getId(); ?>" />
            <a href="#" onclick="javascript:
                $.userLookup('ajax.php/users/<?php echo $user->getId(); ?>/edit',
                        function (user) {
                            $('#user-name').text(user.name);
                            $('#user-email').text(user.email);
                        });
                return false;
                "><i class="icon-user"></i>
                <span id="user-name"><?php echo Format::htmlchars($user->getName()); ?></span>
                &lt;<span id="user-email"><?php echo $user->getEmail(); ?></span>&gt;
                </a>
                <a class="inline button" style="overflow:inherit" href="#"
                    onclick="javascript:
                        $.userLookup('ajax.php/users/select/'+$('input#uid').val(),
                            function(user) {
                                $('input#uid').val(user.id);
                                $('#user-name').text(user.name);
                                $('#user-email').text('<'+user.email+'>');
                        });
                        return false;
                    "><i class="icon-retweet"></i> <?php echo __('Change'); ?></a>
            </div>
        </td></tr>
        <?php
        } else { //Fallback: Just ask for email and name
            ?>
        <tr id="open_ticket_userdata">
            <td style="min-width:120px;" width="160" class="required"> <br><?php echo __('Email Address'); ?>: </td>
            <td>
                <div class="attached input">
                   <input type="text" size=45 name="email" id="user-email" class="attached requiredfield""
                        autocomplete="off" autocorrect="off" value="<?php echo $info['email']; ?>" /> </span>
                <a href="?a=open&amp;uid={id}" data-dialog="ajax.php/users/lookup/form"
                    class="attached button requiredfield"><i class="icon-search requiredfield"></i></a>
                </div>
                <span class="error">*</span>
                <div class="error"><?php echo $errors['email']; ?></div>
            </td>
        </tr>
        <tr id="open_ticket_userdata">
            <td style="min-width:120px;" width="160" class="required"> <?php echo __('Full Name'); ?>: </td>
            <td>
                <span style="display:inline-block;">
                    <input type="text" size=45 name="name" id="user-name" class="requiredfield" style="height: 15px;" value="<?php echo $info['name']; ?>" /> </span>
                <span class="error">*</span>
                <div class="error"><?php echo $errors['name']; ?></div>
            </td>
        </tr>
        <?php
        } ?>

        <?php
        if($cfg->notifyONNewStaffTicket()) {  ?>
        <tr  id="open_ticket_userdata">
            <td width="160"><strong><?php echo __('Ticket Notice'); ?>:</strong></td>
            <td>
            <input type="checkbox" name="alertuser" <?php echo (!$errors || $info['alertuser'])? 'checked="checked"': ''; ?>><?php
                echo __('Send alert to user.'); ?>
            </td>
        </tr>
        <?php
        } ?>
    </tbody>
    <tbody class="open_ticket_informationdata">
        <tr id="open_ticket_informationoptions">
            <th colspan="2">
                <em><strong><?php echo __('Ticket Information');?></strong>:</em>
            </th>
        </tr>
        <tr id="open_ticket_informationdata">
            <td width="160" class="required">
                <?php echo __('Ticket Source');?>:
            </td>
            <td>
                <select name="source" class="requiredfield">
                    <?php
                    $source = $info['source'] ?: 'Phone';
                    $sources = Ticket::getSources();
                    unset($sources['Web'], $sources['API']);
                    foreach ($sources as $k => $v)
                        echo sprintf('<option value="%s" %s>%s</option>',
                                $k,
                                ($source == $k ) ? 'selected="selected"' : '',
                                $v);
                    ?>
                </select>
                &nbsp;<font class="error"><b>*</b>&nbsp;<?php echo $errors['source']; ?></font>
            </td>
        </tr>
        <tr id="open_ticket_informationdata">
            <td width="160" class="required">
                <?php echo __('Help Topic'); ?>:
            </td>
            <td >
                    <input id="cc" name="topicId" class="easyui-combotree " style="width:250px; height:24px; background:#ffffcc"></input>
                &nbsp;<font class="error"><b>*</b>&nbsp;<?php echo $errors['topicId']; ?></font>
            </td>
        </tr>
        <tr id="open_ticket_informationdata" style="display:none;">
            <td width="160">
                <?php echo __('Department'); ?>:
            </td>
            <td>
                <select name="deptId">
                    <option value="" selected >&mdash; <?php echo __('Select Department'); ?>&mdash;</option>
                    <?php
                    if($depts=Dept::getDepartments(array('dept_id' => $thisstaff->getDepts()))) {
                        foreach($depts as $id =>$name) {
                            if (!($role = $thisstaff->getRole($id))
                                || !$role->hasPerm(Ticket::PERM_CREATE)
                            ) {
                                // No access to create tickets in this dept
                                continue;
                            }
                            echo sprintf('<option value="%d" %s>%s</option>',
                                    $id, ($info['deptId']==$id)?'selected="selected"':'',$name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<font class="error"><?php echo $errors['deptId']; ?></font>
            </td>
        </tr>
         <tr  id="open_ticket_informationdata">
            <td width="160">
                <?php echo __('SLA Plan');?>:
            </td>
            <td>
                <select name="slaId">
                    <option value="0" selected="selected" >&mdash; <?php echo __('System Default');?> &mdash;</option>
                    <?php
                    if($slas=SLA::getSLAs()) {
                        foreach($slas as $id =>$name) {
                            echo sprintf('<option value="%d" %s>%s</option>',
                                    $id, ($info['slaId']==$id)?'selected="selected"':'',$name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['slaId']; ?></font>
            </td>
         </tr>

         <tr id="open_ticket_informationdata">
            <td width="160">
                <?php echo __('Due Date');?>:
            </td>
            <td>
                <input class="dp" id="duedate" name="duedate" value="<?php echo Format::htmlchars($info['duedate']); ?>" size="12" autocomplete=OFF>
                &nbsp;&nbsp;
                <?php
                $min=$hr=null;
                if($info['time'])
                    list($hr, $min)=explode(':', $info['time']);

                echo Misc::timeDropdown($hr, $min, 'time');
                ?>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['duedate']; ?> &nbsp; <?php echo $errors['time']; ?></font>
                <em><?php echo __('Time is based on your time zone');?> (GMT <?php echo Format::date(false, false, 'ZZZ'); ?>)</em>
            </td>
        </tr>

        <?php
        if($thisstaff->hasPerm(Ticket::PERM_ASSIGN, false)) { ?>
        <tr id="open_ticket_informationdata">
            <td width="160"><?php echo __('Assign To');?>:</td>
            <td>
                <select id="assignId" name="assignId">
                    <option value="0" selected="selected">&mdash; <?php echo __('Select an Agent OR a Team');?> &mdash;</option>
                    <?php
                    if(($users=Staff::getAvailableStaffMembers())) {
                        echo '<OPTGROUP label="'.sprintf(__('Agents (%d)'), count($users)).'">';
                        foreach($users as $id => $name) {
                            $k="s$id";
                            echo sprintf('<option value="%s" %s>%s</option>',
                                        $k,(($info['assignId']==$k)?'selected="selected"':''),$name);
                        }
                        echo '</OPTGROUP>';
                    }

                    if(($teams=Team::getActiveTeams())) {
                        echo '<OPTGROUP label="'.sprintf(__('Teams (%d)'), count($teams)).'">';
                        foreach($teams as $id => $name) {
                            $k="t$id";
                            echo sprintf('<option value="%s" %s>%s</option>',
                                        $k,(($info['assignId']==$k)?'selected="selected"':''),$name);
                        }
                        echo '</OPTGROUP>';
                    }
                    ?>
                </select>&nbsp;<span class='error'>&nbsp;<?php echo $errors['assignId']; ?></span>
            </td>
        </tr>
        <?php } ?>
        </tbody>
        <tbody id="dynamic-form">
        <?php
            foreach ($forms as $form) {
                print $form->getForm()->getMedia();
                include(STAFFINC_DIR .  'templates/dynamic-form.tmpl.php');
            }
        ?>
        </tbody>
       
</table>
<p style="text-align:center;">
    <input type="submit" name="submit" class="save pending" value="<?php echo _P('action-button', 'Submit');?>">
    <input type="reset"  name="reset"  value="<?php echo __('Reset');?>">
    <input type="button" name="cancel" value="<?php echo __('Cancel');?>" onclick="javascript:
        $('.richtext').each(function() {
            var redactor = $(this).data('redactor');
            if (redactor && redactor.opts.draftDelete)
                redactor.draft.deleteDraft();
        });
        window.location.href='tickets.php';
    ">
</p>
</form>
<script type="text/javascript">
$(function() {
    $('input#user-email').typeahead({
        source: function (typeahead, query) {
            $.ajax({
                url: "ajax.php/users?q="+query,
                dataType: 'json',
                success: function (data) {
                    typeahead.process(data);
                }
            });
        },
        onselect: function (obj) {
            $('#uid').val(obj.id);
            $('#user-name').val(obj.name);
            $('#user-email').val(obj.email);
        },
        property: "/bin/true"
    });
    
  $.extend($.fn.tree.methods,{
    getLevel: function(jq, target){
        return $(target).find('span.tree-indent,span.tree-hit').length;
    }
});

$(document).ready(function(){
    var val = <?php echo Topic::getHelpTopicsTree();?> ;
    
    $('#cc').combotree({ 
        onChange: function (r) { 
            var c = $('#cc');
            var t = c.combotree('tree');  // get tree object
            var node = t.tree('getSelected');
            var nodeLevel = t.tree('getLevel',node.target);
            parentArry = new Array();
            var parentArry = new Array();
                var parents = getParentArry(t,node,nodeLevel,parentArry);
                var parentStr = "";
                if(parents.length > 0){
                    var parentStr = "";
                    for(var i = 0; i < parents.length; i++){
                        parentStr += parents[i].text + " / ";
                    }
                }
             $('#cc').combotree('setText', parentStr + node.text);            
              
        } 

    });
    $('#cc').combotree({ 
        onSelect: function (r) { 
        
            //Loads the dynamic form on selection
            var data = $(':input[name]', '#dynamic-form').serialize();
            $.ajax(
              'ajax.php/form/help-topic/' + r.id,
              {
                data: data,
                dataType: 'json',
                success: function(json) {
                  $('#dynamic-form').empty().append(json.html);
                  $(document.head).append(json.media);
                }
              });
              
              
        } 

    });

    $('#cc').combotree('loadData', val);
    
    function getParentArry(tree,selectedNode,nodeLevel,parentArry){
            //end condition: level of selected node equals 1, means it's root
           if(nodeLevel == 1){
              return parentArry;
           }else{//if selected node isn't root
              nodeLevel -= 1;
              //the parent of the node
              var parent = $(tree).tree('getParent',selectedNode.target);
              //record the parent of selected to a array
              parentArry.unshift(parent);
              //recursive, to judge whether parent of selected node has more parent
              return getParentArry(tree,parent,nodeLevel,parentArry);
            }
        }
    $('#cc').combotree('setText', '— <?php echo __('Select Help Topic'); ?> —');
     
       
});
   <?php
    // Popup user lookup on the initial page load (not post) if we don't have a
    // user selected
    if (!$_POST && !$user) {?>
    setTimeout(function() {
      $.userLookup('ajax.php/users/lookup/form', function (user) {
        window.location.href = window.location.href+'&uid='+user.id;
      });
    }, 100);
    <?php
    } ?>
});
</script>

