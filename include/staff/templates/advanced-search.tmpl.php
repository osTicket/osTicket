<?php
$matches=Filter::getSupportedMatches();
$basicForm = array(
    'status' => new TicketStateField(array(
        'label'=>__('Status'),
    )),
    'dept' => new ChoiceField(array(
        'label'=>__('Department'),
        'choices' => Dept::getDepartments(),
    )),
    'assignee' => new ChoiceField(array(
        'label' => __('Assigned To'),
        'choices' => Staff::getStaffMembers(),
    )),
    'closed' => new ChoiceField(array(
        'label' => __('Closed By'),
        'choices' => Staff::getStaffMembers(),
    )),
);

$basic = array(
    'status'    =>  new TicketStatusChoiceField(array(
        'label' => __('Ticket Status'),
    )),
    'dept'      =>  new DepartmentChoiceField(array(
        'label' => __('Department'),
    )),
    'assignee'  =>  new AssigneeChoiceField(array(
        'label' => __('Assignee'),
    )),
    'topic'     =>  new HelpTopicChoiceField(array(
        'label' => __('Help Topic'),
    )),
    'created'   =>  new DateTimeField(array(
        'label' => __('Created'),
    )),
);
?>
<div id="advanced-search">
<h3><?php echo __('Advanced Ticket Search');?></h3>
<a class="close" href=""><i class="icon-remove-circle"></i></a>
<hr/>
<form action="ajax.php/tickets/advanced-search" method="post" name="search">
    <input type="hidden" name="a" value="search">
    <fieldset class="query">
        <input type="input" id="query" name="query" size="20" placeholder="<?php echo __('Keywords') . ' &mdash; ' . __('Optional'); ?>">
    </fieldset>

<?php
foreach ($basic as $name=>$field) { ?>
    <fieldset>
        <input type="checkbox" name="fields[]" value="<?php echo $name; ?>"
            onchange="javascript:
                $('#search-<?php echo $name; ?>').slideToggle($(this).is(':checked'));
                $('#method-<?php echo $name; ?>-' + $('#search-<?php echo $name; ?>').find(':selected').val()).slideDown('fast');">
        <?php echo $field->getLabel(); ?>
        <div class="search-dropdown" style="display:none" id="search-<?php echo $name; ?>">
            <select style="min-width:150px" name="method-<?php echo $name; ?>" onchange="javascript:
                $(this).parent('div').find('.search-value').slideUp('fast');
                $('#method-<?php echo $name; ?>-' + $(this).find(':selected').val()).slideDown('fast');
">
<?php foreach ($field->getSearchMethods() as $method=>$label) { ?>
                <option value="<?php echo $method; ?>"><?php echo $label; ?></option>
<?php } ?>
            </select>
<?php foreach ($field->getSearchMethods() as $method=>$label) { ?>
            <span class="search-value" style="display:none;" id="method-<?php echo $name . '-' . $method; ?>">
<?php
        if ($f = $field->getSearchWidget($method))
            print $f->render();
?>
            </span>
<?php } ?>
        </div>
    </fieldset>
<?php
} ?>
</form>

<hr/>
<div>
    <div class="pull-right">
        <button><i class="icon-save"></i> Save Search</button>
    </div>
</div>
<link rel="stylesheet" type="text/css" href="<?php echo ROOT_PATH; ?>css/jquery.multiselect.css"/>
