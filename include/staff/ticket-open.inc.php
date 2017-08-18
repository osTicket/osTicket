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
<div class="subnav">

    <div class="float-left subnavtitle">
                          
   <?php echo __('Open a New Ticket');?>                       
    
    </div>
    <div class="btn-group btn-group-sm float-right m-b-10" role="group" aria-label="Button group with nested dropdown">
   &nbsp;
      </div>   
   <div class="clearfix"></div> 
</div> 

<div class="card-box">
<div class="row">
<div class="col"> 
<form action="tickets.php?a=open" method="post" id="save"  enctype="multipart/form-data" >
<fieldset> 

<div class="row ticketform">
            <div class='col-sm-4'>
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="create">
 <input type="hidden" name="a" value="open">

    

        <div class="form-group">
                <em><strong><?php echo __('User Information'); ?></strong>: </em>
                <div class="error"><?php echo $errors['user']; ?></div>
            
        </div>
        <?php
        if ($user) { ?>
        <div class="form-group">
        <label><?php echo __('User'); ?>:</label>
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
                <a class="inline btn btn-warning btn-sm" style="overflow:inherit" href="#"
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
            
        </div>
        <?php
        } else { //Fallback: Just ask for email and name
            ?>
            
         

<form class="form-inline">
  
  <label for="inlineFormInputGroup"><?php echo __('Email Address'); ?>: </label>
  <div class="input-group input-group-sm  mb-2 mr-sm-2 mb-sm-0">
    
    <input type="text"  size=45 name="email" id="user-email" class="form-control form-control-sm requiredfield" id="inlineFormInputGroup" autocomplete="off" autocorrect="off" value="<?php echo $info['email']; ?>">
    <div class="input-group-addon"><a href="?a=open&amp;uid={id}" data-dialog="ajax.php/users/lookup/form"><i class="fa fa-search"></i></a></div>
  </div>

 
</form>         
            
            
       
        <div class="form-group">
            <label> <?php echo __('Full Name'); ?>: </label>
           
                
                    <input type="text" size=45 name="name" id="user-name" class="form-control form-control-sm requiredfield" value="<?php echo $info['name']; ?>" /> 
                
                <div class="error"><?php echo $errors['name']; ?></div>
          
        </div>
        <?php
        } ?>

        <?php
        if($cfg->notifyONNewStaffTicket()) {  ?>
        <div class="form-group">
            <label><?php echo __('Ticket Notice'); ?>:</label>
         <div class="form-check">    
            <label class="form-check-label">
      <input class="form-check-input" type="checkbox" name="alertuser" <?php echo (!$errors || $info['alertuser'])? 'checked="checked"': ''; ?>>
      <?php
                echo __('Send alert to user.'); ?>
    </label>
            
        </div>    
        </div>
        <?php
        } ?>
    </div>
    <div class='col-sm-4'>
    
        <div class="form-group">
            
                <em><strong><?php echo __('Ticket Information');?></strong>:</em>
           
        </div>
        <div class="form-group">
            <label>
                <?php echo __('Ticket Source');?>:
            </label>
            
                <select name="source" class="form-control form-control-sm requiredfield">
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
                <?php if ($errors['source']) { ?>
                <span><font class="error"><?php echo $errors['source']; ?></font></span>
                <?php } ?>
            
        </div>
        <div class="form-group">
            <label>
                <?php echo __('Help Topic'); ?>:
            </label>
            
                    <input id="cc" name="topicId" class="easyui-combotree "  style="width:95%;  border-radius: 2px !important;"></input>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['topicId']; ?></font>
            
        </div>
        <div  class="form-group" style="display:none;">
            <label>
                <?php echo __('Department'); ?>:
            <label>
            
                <select name="deptId">
                    <option  class="form-control form-control-sm " value="" selected >&mdash; <?php echo __('Select Department'); ?>&mdash;</option>
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
            
        </div>
        
       </div>
       <div class='col-sm-4'>
       
         <div class="form-group"  style="display:none;">
            <label>
                <?php echo __('SLA Plan');?>:
            </label>
            
                <select  class="form-control form-control-sm " name="slaId">
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
            
         </div>

                 <div class="form-group">
            
                &nbsp;
           
        </div>
        
          
          <div  class="form-group">
             <label><?php echo __('Due Date');?>:</label>
            
            <div class='input-group date' id="datepicker1" >
                    <input type='text' id="duedate" name="duedate" class="form-control form-control-sm"  />
                    <span class="input-group-addon" style="display: inline">
                        <span class="fa fa-calendar"></span>
                    </span>
                </div>
                
                        
        
                
            </div>

        <?php
        if($thisstaff->hasPerm(Ticket::PERM_ASSIGN, false)) { ?>
        <div class="form-group">
            <label><?php echo __('Assign To');?>:</label>
            
                <select class="form-control form-control-sm " id="assignId" name="assignId">
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
            
        </div>
        <?php } ?>
     

    </div>
    </div>
<div class='col-sm-12'><hr></div>    
<div id="dynamic-form">
        
        <?php
            foreach ($forms as $form) {
                print $form->getForm()->getMedia();
                include(STAFFINC_DIR .  'templates/dynamic-form.tmpl.php');
            }
        ?>
        </div>
        
        
 </fieldset>      

<div >
    <input class="btn btn-primary btn-sm" type="submit" name="submit" class="save pending" value="<?php echo _P('action-button', 'Submit');?>">
    <input class="btn btn-warning btn-sm" type="reset"  name="reset"  value="<?php echo __('Reset');?>">
    <input class="btn btn-warning btn-danger btn-sm" type="button" name="cancel" value="<?php echo __('Cancel');?>" onclick="javascript:
        $('.richtext').each(function() {
            var redactor = $(this).data('redactor');
            if (redactor && redactor.opts.draftDelete)
                redactor.draft.deleteDraft();
        });
        window.location.href='tickets.php';
    ">
</div>
</form>
</div>
</div> 
</div> 
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
    
    $('#datepicker1').datetimepicker({
                   useCurrent: false,
                   format: 'MM/DD/YYYY',
                   showClear: true,
                   showTodayButton: true
                   
               });
     
       
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

