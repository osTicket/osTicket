<?php
if($nav && ($tabs=$nav->getTabs()) && is_array($tabs)){
    foreach($tabs as $name =>$tab) {
        if ($tab['href'][0] != '/')
            $tab['href'] = ROOT_PATH . 'scp/' . $tab['href'];
        echo sprintf('<li class="%s %s"><a href="%s">%s</a>',
            isset($tab['active']) ? 'active':'inactive',
            @$tab['class'] ?: '',
            $tab['href'],$tab['desc']);
        if(!isset($tab['active']) && ($subnav=$nav->getSubMenu($name))){
            echo "<ul>\n";
            foreach($subnav as $k => $item) {
                if (isset($item['id']) && !($id=$item['id']))
                    $id="nav$k";
                if ($item['href'][0] != '/')
                    $item['href'] = ROOT_PATH . 'scp/' . $item['href'];

                echo sprintf(
                    '<li><a class="%s" href="%s" title="%s" id="%s">%s</a></li>',
                    $item['iconclass'],
                    $item['href'], $item['title'] ?? null,
                    $id ?? null, $item['desc']);
            }
            echo "\n</ul>\n";
        }
        echo "\n</li>\n";
    }
} ?>
