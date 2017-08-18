<?php
if(($subnav=$nav->getSubMenu()) && is_array($subnav)){
    $activeMenu=$nav->getActiveMenu(); 
    if($activeMenu>0 && !isset($subnav[$activeMenu-1]))
        $activeMenu=0;
    $info = $nav->getSubNavInfo();
?>

<?php
    foreach($subnav as $k=> $item) {
        if (is_callable($item)) {
            $item($tab, $nav);
            continue;
        }
        // if($item['droponly']) continue;
        // $class=$item['iconclass'];
        // if ($activeMenu && $k+1==$activeMenu
                // or (!$activeMenu
                    // && (strpos(strtoupper($item['href']),strtoupper(basename($_SERVER['SCRIPT_NAME']))) !== false
                        // or ($item['urls']
                            // && in_array(basename($_SERVER['SCRIPT_NAME']),$item['urls'])
                            // )
                        // )))
            // $class="$class active";
        // if (!($id=$item['id']))
            // $id="subnav$k";

        //Extra attributes
        $attr = '';

    }

}
?>

