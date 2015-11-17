<?php
if (!$nav || !($subnav=$nav->getSubMenu()) || !is_array($subnav))
    return;

$activeMenu=$nav->getActiveMenu();
if ($activeMenu>0 && !isset($subnav[$activeMenu-1]))
    $activeMenu=0;

$info = $nav->getSubNavInfo();
?>
<nav class="<?php echo @$info['class']; ?>" id="<?php echo $info['id']; ?>">
  <ul id="sub_nav">
<?php
    foreach($subnav as $k=> $item) {
        if (is_callable($item)) {
            if ($item($nav) && !$activeMenu)
                $activeMenu = 'x';
            continue;
        }
        if($item['droponly']) continue;
        $class=$item['iconclass'];
        if ($activeMenu && $k+1==$activeMenu
                or (!$activeMenu
                    && (strpos(strtoupper($item['href']),strtoupper(basename($_SERVER['SCRIPT_NAME']))) !== false
                        or ($item['urls']
                            && in_array(basename($_SERVER['SCRIPT_NAME']),$item['urls'])
                            )
                        )))
            $class="$class active";
        if (!($id=$item['id']))
            $id="subnav$k";

        //Extra attributes
        $attr = '';
        if ($item['attr'])
            foreach ($item['attr'] as $name => $value)
                $attr.=  sprintf("%s='%s' ", $name, $value);

        echo sprintf('<li><a class="%s" href="%s" title="%s" id="%s" %s>%s</a></li>',
                $class, $item['href'], $item['title'], $id, $attr, $item['desc']);
    }
?>
  </ul>
</nav>
