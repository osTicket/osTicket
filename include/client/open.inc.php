<?php
if(!defined('OSTCLIENTINC')) die('Access Denied!');
$info=array();
if($thisclient && $thisclient->isValid()) {
    $info=array('name'=>$thisclient->getName(),
                'email'=>$thisclient->getEmail(),
                'phone'=>$thisclient->getPhoneNumber());
}

$info=($_POST && $errors)?Format::htmlchars($_POST):$info;

$form = null;
if (!$info['topicId']) {
    if (array_key_exists('topicId',$_GET) && preg_match('/^\d+$/',$_GET['topicId']) && Topic::lookup($_GET['topicId']))
        $info['topicId'] = intval($_GET['topicId']);
    else
        $info['topicId'] = $cfg->getDefaultTopicId();
}

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

?>
<div class="row">
<div class="page-title">
	<h1><?php echo __('Open a New Ticket');?></h1>
	<p><?php echo __('Please fill in the form below to open a new ticket.');?></p>
</div>
</div>
<form id="ticketForm" method="post" action="open.php" enctype="multipart/form-data">
  <?php csrf_token(); ?>
  <input type="hidden" name="a" value="open">
  <table width="100%"" cellpadding="1" cellspacing="0" border="0">
    <tbody>
<?php
        if (!$thisclient) {
            $uform = UserForm::getUserForm()->getForm($_POST);
            if ($_POST) $uform->isValid();
            $uform->render(false);
        }
        else { ?>
            <tr><td colspan="2"><hr /></td></tr>
        <tr class="form-group"><td><?php echo __('Email'); ?>:</td><td><?php
            echo $thisclient->getEmail(); ?></td></tr>
        <tr class="form-group"><td><?php echo __('Client'); ?>:</td><td><?php
            echo Format::htmlchars($thisclient->getName()); ?></td></tr>
        <?php } ?>
    </tbody>

        <div id="client-helptopic">
            <label class="required" for="topicId"><?php echo __('Help Topic'); ?></label> 
                <input input id="cc" name="topicId" class="easyui-combotree client-helptopic" style="width:250px; height:24px;"></input>  
            <font class="error">*</font><?php echo $errors['topicId']; ?></font>
        </div>
    <table id="dynamic-form">
        <?php foreach ($forms as $form) {
            include(CLIENTINC_DIR . 'templates/dynamic-form.tmpl.php');
        } ?>
    </table>
    <tbody>
    <?php
    if($cfg && $cfg->isCaptchaEnabled() && (!$thisclient || !$thisclient->isValid())) {
        if($_POST && $errors && !$errors['captcha'])
            $errors['captcha']=__('Please re-enter the text again');
        ?>
    <tr class="captchaRow">
        <td class="required"><?php echo __('CAPTCHA Text');?>:</td>
        <td>
            <span class="captcha"><img src="captcha.php" border="0" align="left"></span>
            &nbsp;&nbsp;
            <input id="captcha" type="text" name="captcha" size="6" autocomplete="off">
            <em><?php echo __('Enter the text shown on the image.');?></em>
            <font class="error">*&nbsp;<?php echo $errors['captcha']; ?></font>
        </td>
    </tr>
    <?php
    } ?>
   
    </tbody>
  </table>
  <p class="buttons" >
        <input class="btn btn-success" type="submit" value="<?php echo __('Create Ticket');?>">
        <input class="btn btn-warning" type="reset" name="reset" value="<?php echo __('Reset');?>">
        <input class="btn btn-default" type="button" name="cancel" value="<?php echo __('Cancel'); ?>" onclick="javascript:
            $('.richtext').each(function() {
                var redactor = $(this).data('redactor');
                if (redactor && redactor.opts.draftDelete)
                    redactor.deleteDraft();
            });
            window.location.href='index.php';">
  </p>
</form>
<div class="clearfix"></div>

<script type="text/javascript">
$.extend($.fn.tree.methods,{
    getLevel: function(jq, target){
        return $(target).find('span.tree-indent,span.tree-hit').length;
    }
});

$(document).ready(function(){
    var val = <?php echo Topic::getHelpTopicsTree(true);?> ;
    
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
</script> 
