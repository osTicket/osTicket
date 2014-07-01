<?php
if(($tabs=$nav->getTabs()) && is_array($tabs)){
    foreach($tabs as $name =>$tab) {
        echo sprintf('<li class="%s"><a href="%s">%s</a>',$tab['active']?'active':'inactive',$tab['href'],$tab['desc']);
        if(!$tab['active'] && ($subnav=$nav->getSubMenu($name))){
            echo "<ul>\n";
            foreach($subnav as $k => $item) {
                if (!($id=$item['id']))
                    $id="nav$k";

                echo sprintf(
                    '<li><a class="%s" href="%s" title="%s" id="%s">%s</a></li>',
                    $item['iconclass'],
                    $item['href'], $item['title'],
                    $id, $item['desc']);
            }
            echo "\n</ul>\n";
        }
        echo "\n</li>\n";
    }
} ?>
