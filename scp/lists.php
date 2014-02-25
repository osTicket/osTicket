<?php
require('admin.inc.php');
require_once(INCLUDE_DIR."/class.dynamic_forms.php");

$list=null;
if($_REQUEST['id'] && !($list=DynamicList::lookup($_REQUEST['id'])))
    $errors['err']='Unknown or invalid dynamic list ID.';

if($_POST) {
    $fields = array('name', 'name_plural', 'sort_mode', 'notes');
    $required = array('name');
    switch(strtolower($_POST['do'])) {
        case 'update':
            foreach ($fields as $f)
                if (in_array($f, $required) && !$_POST[$f])
                    $errors[$f] = sprintf('%s is required',
                        mb_convert_case($f, MB_CASE_TITLE));
                elseif (isset($_POST[$f]))
                    $list->set($f, $_POST[$f]);
            if ($errors)
                $errors['err'] = 'Unable to update custom list. Correct any error(s) below and try again.';
            elseif ($list->save(true))
                $msg = 'Custom list updated successfully';
            else
                $errors['err'] = 'Unable to update custom list. Unknown internal error';

            foreach ($list->getItems() as $item) {
                $id = $item->get('id');
                if ($_POST["delete-$id"] == 'on') {
                    $item->delete();
                    continue;
                }
                foreach (array('sort','value','extra') as $i)
                    if (isset($_POST["$i-$id"]))
                        $item->set($i, $_POST["$i-$id"]);
                $item->save();
            }
            break;
        case 'add':
            foreach ($fields as $f)
                if (in_array($f, $required) && !$_POST[$f])
                    $errors[$f] = sprintf('%s is required',
                        mb_convert_case($f, MB_CASE_TITLE));
            $list = DynamicList::create(array(
                'name'=>$_POST['name'],
                'name_plural'=>$_POST['name_plural'],
                'sort_mode'=>$_POST['sort_mode'],
                'notes'=>$_POST['notes']));

            if ($errors)
                $errors['err'] = 'Unable to create custom list. Correct any error(s) below and try again.';
            elseif ($list->save(true))
                $msg = 'Custom list added successfully';
            else
                $errors['err'] = 'Unable to create custom list. Unknown internal error';

            break;

        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = 'You must select at least one API key';
            } else {
                $count = count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'delete':
                        $i=0;
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($t=DynamicList::lookup($v)) && $t->delete())
                                $i++;
                        }
                        if ($i && $i==$count)
                            $msg = 'Selected custom lists deleted successfully';
                        elseif ($i > 0)
                            $warn = "$i of $count selected lists deleted";
                        elseif (!$errors['err'])
                            $errors['err'] = 'Unable to delete selected custom lists'
                                .' &mdash; they may be in use on a custom form';
                        break;
                }
            }
            break;
    }

    if ($list) {
        for ($i=0; isset($_POST["sort-new-$i"]); $i++) {
            if (!$_POST["value-new-$i"])
                continue;
            $item = DynamicListItem::create(array(
                'list_id'=>$list->get('id'),
                'sort'=>$_POST["sort-new-$i"],
                'value'=>$_POST["value-new-$i"],
                'extra'=>$_POST["extra-new-$i"]
            ));
            $item->save();
        }
        # Invalidate items cache
        $list->_items = false;
    }
}

$page='dynamic-lists.inc.php';
if($list || ($_REQUEST['a'] && !strcasecmp($_REQUEST['a'],'add')))
    $page='dynamic-list.inc.php';

$nav->setTabActive('manage');
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
