<?php
require('admin.inc.php');
require_once(INCLUDE_DIR."/class.dynamic_forms.php");

$list=null;
if($_REQUEST['id'] && !($list=DynamicList::lookup($_REQUEST['id'])))
    $errors['err']='Unknown or invalid dynamic list ID.';

if($_POST) {
    $fields = array('name', 'name_plural', 'sort_mode', 'notes');
    switch(strtolower($_POST['do'])) {
        case 'update':
            foreach ($fields as $f)
                if (isset($_POST[$f]))
                    $list->set($f, $_POST[$f]);
            if ($list->isValid())
                $list->save(true);
            foreach ($list->getItems() as $item) {
                $id = $item->get('id');
                if ($_POST["delete-$id"] == 'on') {
                    $item->delete();
                    continue;
                }
                foreach (array('sort','value','extra') as $i)
                    if (isset($_POST["$i-$id"]))
                        $item->set($i, $_POST["$i-$id"]);
                if ($item->isValid())
                    $item->save();
            }
            break;
        case 'add':
            $list = DynamicList::create(array(
                'name'=>$_POST['name'],
                'notes'=>$_POST['notes']));
            if ($list->isValid())
                $list->save();
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
            if ($item->isValid())
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
