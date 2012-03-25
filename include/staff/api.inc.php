<?php
if(!defined('OSTADMININC') || !$thisstaff->isadmin()) die('Access Denied');


$info['phrase']=($errors && $_POST['phrase'])?Format::htmlchars($_POST['phrase']):$cfg->getAPIPassphrase();
$select='SELECT * ';
$from='FROM '.API_KEY_TABLE;
$where='';
$sortOptions=array('date'=>'created','ip'=>'ipaddr');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');
//Sorting options...
if($_REQUEST['sort']) {
    $order_column =$sortOptions[$_REQUEST['sort']];
}

if($_REQUEST['order']) {
    $order=$orderWays[$_REQUEST['order']];
}
$order_column=$order_column?$order_column:'ipaddr';
$order=$order?$order:'ASC';
$order_by=" ORDER BY $order_column $order ";

$total=db_count('SELECT count(*) '.$from.' '.$where);
$pagelimit=1000;//No limit. TODO: Add limit.
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total,$page,$pagelimit);
$pageNav->setURL('admin.php',$qstr.'&sort='.urlencode($_REQUEST['sort']).'&order='.urlencode($_REQUEST['order']));
$query="$select $from $where $order_by";
//echo $query;
$result = db_query($query);
$showing=db_num_rows($result)?$pageNav->showing():'';
$negorder=$order=='DESC'?'ASC':'DESC'; //Negate the sorting..
$deletable=0;
?>
<div class="msg">API Keys</div>
<hr>
<div><b><?php echo $showing; ?></b></div>
 <table width="100%" border="0" cellspacing=1 cellpadding=2>
   <form action="admin.php?t=api" method="POST" name="api" onSubmit="return checkbox_checker(document.forms['api'],1,0);">
   <input type=hidden name='t' value='api'>
   <input type=hidden name='do' value='mass_process'>
   <tr><td>
    <table border="0" cellspacing=0 cellpadding=2 class="dtable" align="center" width="100%">
        <tr>
	        <th width="7px">&nbsp;</th>
	        <th>API Key</th>
            <th width="10" nowrap>Active</th>
            <th width="100" nowrap>&nbsp;&nbsp;IP Address</th>
	        <th width="150" nowrap>&nbsp;&nbsp;
                <a href="admin.php?t=api&sort=date&order=<?php echo $negorder; ?><?php echo $qstr; ?>" title="Sort By Create Date <?php echo $negorder; ?>">Created</a></th>
        </tr>
        <?php
        $class = 'row1';
        $total=0;
        $active=$inactive=0;
        $sids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        if($result && db_num_rows($result)):
            $dtpl=$cfg->getDefaultTemplateId();
            while ($row = db_fetch_array($result)) {
                $sel=false;
                $disabled='';
                if($row['isactive'])
                    $active++;
                else
                    $inactive++;
                    
                if($sids && in_array($row['id'],$sids)){
                    $class="$class highlight";
                    $sel=true;
                }
                ?>
            <tr class="<?php echo $class; ?>" id="<?php echo $row['id']; ?>">
                <td width=7px>
                  <input type="checkbox" name="ids[]" value="<?php echo $row['id']; ?>" <?php echo $sel?'checked':''; ?>
                        onClick="highLight(this.value,this.checked);">
                <td>&nbsp;<?php echo $row['apikey']; ?></td>
                <td><?php echo $row['isactive']?'<b>Yes</b>':'No'; ?></td>
                <td>&nbsp;<?php echo $row['ipaddr']; ?></td>
                <td>&nbsp;<?php echo Format::db_datetime($row['created']); ?></td>
            </tr>
            <?php
            $class = ($class =='row2') ?'row1':'row2';
            } //end of while.
        else: //nothin' found!! ?> 
            <tr class="<?php echo $class; ?>"><td colspan=5><b>Query returned 0 results</b>&nbsp;&nbsp;<a href="admin.php?t=templates">Index list</a></td></tr>
        <?php
        endif; ?>
     
     </table>
    </td></tr>
    <?php
    if(db_num_rows($result)>0): //Show options..
     ?>
    <tr>
        <td align="center">
            <?php
            if($inactive) { ?>
                <input class="button" type="submit" name="enable" value="Enable"
                     onClick='return confirm("Are you sure you want to ENABLE selected keys?");'>
            <?php
            }
            if($active){ ?>
            &nbsp;&nbsp;
                <input class="button" type="submit" name="disable" value="Disable"
                     onClick='return confirm("Are you sure you want to DISABLE selected keys?");'>
            <?php } ?>
            &nbsp;&nbsp;
            <input class="button" type="submit" name="delete" value="Delete" 
                     onClick='return confirm("Are you sure you want to DELETE selected keys?");'>
        </td>
    </tr>
    <?php
    endif;
    ?>
    </form>
 </table>
 <br/>
 <div class="msg">Add New IP</div>
 <hr>
 <div>
   Add a new IP address.&nbsp;&nbsp;<font class="error"><?php echo $errors['ip']; ?></font>
   <form action="admin.php?t=api" method="POST" >
    <input type=hidden name='t' value='api'>
    <input type=hidden name='do' value='add'>
    New IP:
    <input name="ip" size=30 value="<?php echo ($errors['ip'])?Format::htmlchars($_REQUEST['ip']):''; ?>" />
    <font class="error">*&nbsp;</font>&nbsp;&nbsp;
     &nbsp;&nbsp; <input class="button" type="submit" name="add" value="Add">
    </form>
 </div>
 <br/>
 <div class="msg">API Passphrase</div>
 <hr>
 <div>
   Passphrase must be at least 3 words. Required to generate the api keys.<br/>
   <form action="admin.php?t=api" method="POST" >
    <input type=hidden name='t' value='api'>
    <input type=hidden name='do' value='update_phrase'>
    Phrase:
    <input name="phrase" size=50 value="<?php echo Format::htmlchars($info['phrase']); ?>" />
    <font class="error">*&nbsp;<?php echo $errors['phrase']; ?></font>&nbsp;&nbsp;
     &nbsp;&nbsp; <input class="button" type="submit" name="update" value="Submit">
    </form>
    <br/><br/>
    <div><i>Please note that changing the passprase does NOT invalidate existing keys. To regerate a key you need to delete and readd it.</i></div>
 </div>

