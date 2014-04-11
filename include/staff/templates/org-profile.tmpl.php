<?php
$info=($_POST && $errors)?Format::input($_POST):@Format::htmlchars($org->getInfo());

if (!$info['title'])
    $info['title'] = Format::htmlchars($org->getName());
?>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery.multiselect.min.js"></script>
<link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/jquery.multiselect.css"/>
<h3><?php echo $info['title']; ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<hr/>
<?php
if ($info['error']) {
    echo sprintf('<p id="msg_error">%s</p>', $info['error']);
} elseif ($info['msg']) {
    echo sprintf('<p id="msg_notice">%s</p>', $info['msg']);
} ?>
<ul class="tabs">
    <li><a href="#tab-profile" class="active"
        ><i class="icon-edit"></i>&nbsp;Fields</a></li>
    <li><a href="#contact-settings"
        ><i class="icon-fixed-width icon-cogs faded"></i>&nbsp;Settings</a></li>
</ul>
<form method="post" class="org" action="<?php echo $action; ?>">

<div class="tab_content" id="tab-profile" style="margin:5px;">
<?php
$action = $info['action'] ? $info['action'] : ('#orgs/'.$org->getId());
if ($ticket && $ticket->getOwnerId() == $user->getId())
    $action = '#tickets/'.$ticket->getId().'/user';
?>
    <input type="hidden" name="id" value="<?php echo $org->getId(); ?>" />
    <table width="100%">
    <?php
        if (!$forms) $forms = $org->getForms();
        foreach ($forms as $form)
            $form->render();
    ?>
    </table>
</div>

<div class="tab_content" id="contact-settings" style="display:none;margin:5px;">
    <table style="width:100%">
        <tbody>
            <tr>
                <td width="180">
                    Account Manager:
                </td>
                <td>
                    <select name="manager">
                        <option value="0" selected="selected">&mdash; None &mdash;</option><?php
                        if ($users=Staff::getAvailableStaffMembers()) { ?>
                            <optgroup label="Staff Members (<?php echo count($users); ?>)">
<?php                       foreach($users as $id => $name) {
                                $k = "s$id";
                                echo sprintf('<option value="%s" %s>%s</option>',
                                    $k,(($info['manager']==$k)?'selected="selected"':''),$name);
                            }
                            echo '</optgroup>';
                        }

                        if ($teams=Team::getActiveTeams()) { ?>
                            <optgroup label="Teams (<?php echo count($teams); ?>)">
<?php                       foreach($teams as $id => $name) {
                                $k="t$id";
                                echo sprintf('<option value="%s" %s>%s</option>',
                                    $k,(($info['manager']==$k)?'selected="selected"':''),$name);
                            }
                            echo '</optgroup>';
                        } ?>
                    </select>
                    <br/><span class="error"><?php echo $errors['manager']; ?></span>
                </td>
            </tr>
            <tr>
                <td width="180">
                    Auto-Assignment:
                </td>
                <td>
                    <input type="checkbox" name="assign-am-flag" value="1" <?php echo $info['assign-am-flag']?'checked="checked"':''; ?>>
                    Assign tickets from this organization to the <em>Account Manager</em>
            </tr>
            <tr>
                <td width="180">
                    Primary Contacts:
                </td>
                <td>
                    <select name="contacts[]" id="primary_contacts" multiple="multiple">
<?php               foreach ($org->allMembers() as $u) { ?>
                        <option value="<?php echo $u->id; ?>" <?php
                            if ($u->isPrimaryContact())
                            echo 'selected="selected"'; ?>><?php echo $u->getName(); ?></option>
<?php               } ?>
                    </select>
                    <br/><span class="error"><?php echo $errors['contacts']; ?></span>
                </td>
            <tr>
                <th colspan="2">
                    Automated Collaboration:
                </th>
            </tr>
            <tr>
                <td width="180">
                    Primary Contacts:
                </td>
                <td>
                    <input type="checkbox" name="collab-pc-flag" value="1" <?php echo $info['collab-pc-flag']?'checked="checked"':''; ?>>
                    Add to all tickets from this organization
                </td>
            </tr>
            <tr>
                <td width="180">
                    Organization Members:
                </td>
                <td>
                    <input type="checkbox" name="collab-all-flag" value="1" <?php echo $info['collab-all-flag']?'checked="checked"':''; ?>>
                    Add to all tickets from this organization
                </td>
            </tr>
            <tr>
                <th colspan="2">
                    Main Domain
                </th>
            </tr>
            <tr>
                <td style="width:180px">
                    Auto Add Members From:
                </td>
                <td>
                    <input type="text" size="40" maxlength="60" name="domain"
                        value="<?php echo $info['domain']; ?>" />
                    <br/><span class="error"><?php echo $errors['domain']; ?></span>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<div class="clear"></div>

<hr>
<p class="full-width">
    <span class="buttons" style="float:left">
        <input type="reset" value="Reset">
        <input type="button" name="cancel" class="<?php
echo $account ? 'cancel' : 'close'; ?>"  value="Cancel">
    </span>
    <span class="buttons" style="float:right">
        <input type="submit" value="Update Organization">
    </span>
</p>
</form>

<script type="text/javascript">
$(function() {
    $('a#editorg').click( function(e) {
        e.preventDefault();
        $('div#org-profile').hide();
        $('div#org-form').fadeIn();
        return false;
     });

    $(document).on('click', 'form.org input.cancel', function (e) {
        e.preventDefault();
        $('div#org-form').hide();
        $('div#org-profile').fadeIn();
        return false;
    });
    $("#primary_contacts").multiselect({'noneSelectedText':'Select Contacts'});
});
</script>
