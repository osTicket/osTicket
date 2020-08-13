<?php

if(!defined('OSTCLIENTINC') || !$thisclient || !$ticket || !$ticket->checkUserAccess($thisclient)) die('Access Denied!');

?>

<h1>
    <?php echo sprintf(__('Editing Ticket #%s'), $ticket->getNumber()); ?>
</h1>

<form action="tickets.php" method="post">
    <?php echo csrf_token(); ?>
    <input type="hidden" name="a" value="edit"/>
    <input type="hidden" name="id" value="<?php echo Format::htmlchars($_REQUEST['id']); ?>"/>
    
<div class="table-responsive-md">
  <table class="table">
    <tbody id="dynamic-form">
    <?php if ($forms)
        foreach ($forms as $form) {
            $form->render(['staff' => false]);
    } ?>
    </tbody>
</table>
</div>

<hr>
<div class="p-3">
		<div class="row align-items-center" style="padding-bottom: 1rem;">
		<div class="col-md align-self-center">
    		<input type="submit" class="btn btn-outline-primary" value="Update"/>
		</div>
		<div class="col-md align-self-center" style="padding-bottom: 1rem;">
    		<input type="reset" class="btn btn-outline-secondary" value="Reset"/>
		</div>
		<div class="col-md align-self-center" style="padding-bottom: 1rem;">    
    		<input type="button" class="btn btn-outline-danger" value="Cancel" onclick="javascript:window.location.href='index.php';"/>
		</div>			
		</div>
</div>

</form>
