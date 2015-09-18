<?php /*
if(($subnav=$nav->getSubMenu()) && is_array($subnav)){
    $activeMenu=$nav->getActiveMenu();
    if($activeMenu>0 && !isset($subnav[$activeMenu-1]))
        $activeMenu=0;
    foreach($subnav as $k=> $item) {
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
}
*/ ?>
<!-- .item is required for overflow  of parent Q -->
<li class="item">
    <a class="open" href="#"><i class="icon-sort-down pull-right"></i>Open</a>
    <!-- Start Dropdown -->
    <div class="customQ-dropdown">
        <ul class="scroll-height">
            <!-- SubQ class: only if top level Q has subQ -->
            <li class="subQ">
                <i class="icon-caret-down"></i>
                <!-- Edit Queue -->
                <div class="editQ">
                    <i class="icon-cog"></i>
                    <div class="manageQ">
                        <ul>
                            <li class="positive">
                                <a href="#"><i class="icon-fixed-width icon-plus-sign"></i>Add Queue</a>
                            </li>
                            <li class="danger">
                                <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <!-- End Edit Queue -->
                <!-- Display Latest Ticket count -->
                <span>(80)</span>
                <a class="truncate">Open testing to see how long the top queue can be</a>
                <ul>
                    <li>
                        <!-- Edit Queue -->
                        <div class="editQ">
                            <i class="icon-cog"></i>
                            <div class="manageQ">
                                <ul>
                                    <li>
                                        <a href="#"><i class="icon-fixed-width icon-pencil"></i>Edit</a>
                                    </li>
                                    <li class="danger">
                                        <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <!-- End Edit Queue -->
                        <span>(60)</span>
                        <a class="truncate">testing to see how long I can make this go</a>
                    </li>
                </ul>
            </li>
            <li>
                <!-- Edit Queue -->
                <div class="editQ">
                    <i class="icon-cog"></i>
                    <div class="manageQ">
                        <ul>
                            <li class="positive">
                                <a href="#"><i class="icon-fixed-width icon-plus-sign"></i>Add Queue</a>
                            </li>
                            <li class="danger">
                                <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <!-- End Edit Queue -->
                <span class="disabled">(0)</span>
                <a href="#">Answered</a>
            </li>
            <!-- Dropdown Titles -->
            <li>
                <h4>Personal Queue</h4>
            </li>
            <li>
                <div class="editQ">
                    <i class="icon-cog"></i>
                    <div class="manageQ">
                        <ul>
                            <li>
                                <a href="#"><i class="icon-fixed-width icon-pencil"></i>Edit</a>
                            </li>
                            <li class="danger">
                                <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <span>(33)</span>
                <a class="truncate">testing to see how long I can make this go</a>
            </li>
        </ul>
        <!-- Add Queue button sticky at the bottom -->
        <div class="add-queue">
            <a class="flush-right" onclick="javascript:
                $.dialog('ajax.php/tickets/search', 201);">
                <div class="add pull-right"><i class="green icon-plus-sign"></i></div>
                <span>Add personal queue</span>
            </a>
        </div>
    </div>
</li>
<li class="item">
    <a class="mine" href="#"><i class="icon-sort-down pull-right"></i>My Tickets</a>
    <div class="customQ-dropdown">
        <ul class="scroll-height">
            <li>
                <div class="editQ"><i class="icon-cog"></i>
                    <div class="manageQ">
                        <ul>
                            <li>
                                <a href="#"><i class="icon-fixed-width icon-plus-sign"></i>Add Queue</a>
                            </li>
                            <li class="danger">
                                <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <a href="#">My Tickets</a>
            </li>
        </ul>
        <div class="add-queue">
            <a class="flush-right" onclick="javascript:
            $.dialog('ajax.php/tickets/search', 201);">
                <div class="add pull-right"><i class="green icon-plus-sign"></i></div>
                <span>Add personal queue</span>
            </a>
        </div>
    </div>
</li>

<li class="item">
    <a href="#"><i class="icon-sort-down pull-right"></i>Search</a>
    <div class="customQ-dropdown">
        <ul class="scroll-height">
            <li>
                <h4>Shared Searches</h4>
            </li>
            <li>
                <div class="editQ"><i class="icon-cog"></i>
                    <div class="manageQ">
                        <ul>
                            <li>
                                <a href="#"><i class="icon-fixed-width icon-share"></i>Share</a>
                            </li>
                            <li>
                                <a href="#"><i class="icon-fixed-width icon-pencil"></i>Edit</a>
                            </li>
                            <li class="danger">
                                <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <a class="truncate" href="#">item queue</a>
            </li>
            <li>
                <h4>My Searches</h4>
            </li>
            <li>
                <div class="editQ"><i class="icon-cog"></i>
                    <div class="manageQ">
                        <ul>
                            <li>
                                <a href="#"><i class="icon-fixed-width icon-share"></i>Share</a>
                            </li>
                            <li>
                                <a href="#"><i class="icon-fixed-width icon-pencil"></i>Edit</a>
                            </li>
                            <li class="danger">
                                <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <a class="truncate" href="#">item queue</a>
            </li>

        </ul>
        <div class="add-queue">
            <a class="flush-right" onclick="javascript:
            $.dialog('ajax.php/tickets/search', 201);">
                <div class="add pull-right"><i class=" icon-plus-sign"></i></div>
                <span>Add saved search</span>
            </a>
        </div>
    </div>
</li>
<li class="item">
    <a class="closed" href="#"><i class="icon-sort-down pull-right"></i>Closed</a>
    <div class="customQ-dropdown">
        <ul class="scroll-height">
            <li>
                <div class="editQ"><i class="icon-cog"></i>
                    <div class="manageQ">
                        <ul>
                            <li>
                                <a href="#"><i class="icon-fixed-width icon-plus-sign"></i>Add Queue</a>
                            </li>
                            <li class="danger">
                                <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <a href="#">Closed</a>
            </li>
        </ul>
        <div class="add-queue">
            <a class="flush-right" onclick="javascript:
            $.dialog('ajax.php/tickets/search', 201);">
                <div class="add pull-right"><i class="green icon-plus-sign"></i></div>
                <span>Add personal queue</span>
            </a>
        </div>
    </div>
</li>
<li class="item">
    <a class="new" href="#">New Ticket</a>
</li>
<li class="item">
    <a class="closed" href="#"><i class="icon-sort-down pull-right"></i>Custom item 1</a>
    <div class="customQ-dropdown">
        <ul class="scroll-height">
            <li class="subQ">
                <i class="icon-caret-down"></i>
                <div class="editQ">
                    <i class="icon-cog"></i>
                    <div class="manageQ">
                        <ul>
                            <li class="positive">
                                <a href="#"><i class="icon-fixed-width icon-plus-sign"></i>Add Queue</a>
                            </li>
                            <li class="danger">
                                <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <a class="truncate">Open</a>
                <ul>
                    <li>
                        <div class="editQ">
                            <i class="icon-cog"></i>
                            <div class="manageQ">
                                <ul>
                                    <li>
                                        <a href="#"><i class="icon-fixed-width icon-pencil"></i>Edit</a>
                                    </li>
                                    <li class="danger">
                                        <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <a class="truncate">testing to see how long I can make this go</a>
                    </li>
                </ul>
            </li>
            <li>
                <div class="editQ">
                    <i class="icon-cog"></i>
                    <div class="manageQ">
                        <ul>
                            <li class="positive">
                                <a href="#"><i class="icon-fixed-width icon-plus-sign"></i>Add Queue</a>
                            </li>
                            <li class="danger">
                                <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <a href="#">Answered</a>
            </li>
            <li>
                <h4>Personal Queue</h4>
            </li>
            <li>
                <div class="editQ">
                    <i class="icon-cog"></i>
                    <div class="manageQ">
                        <ul>
                            <li>
                                <a href="#"><i class="icon-fixed-width icon-pencil"></i>Edit</a>
                            </li>
                            <li class="danger">
                                <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <a class="truncate">testing to see how long I can make this go</a>
            </li>
        </ul>
        <div class="add-queue">
            <a class="flush-right" onclick="javascript:
                $.dialog('ajax.php/tickets/search', 201);">
                <div class="add pull-right"><i class="green icon-plus-sign"></i></div>
                <span>Add personal queue</span>
            </a>
        </div>
    </div>
</li>
<li class="item">
    <a class="closed" href="#"><i class="icon-sort-down pull-right"></i>Custom item 2</a>
    <div class="customQ-dropdown">
        <ul class="scroll-height">
            <li class="subQ">
                <i class="icon-caret-down"></i>
                <div class="editQ">
                    <i class="icon-cog"></i>
                    <div class="manageQ">
                        <ul>
                            <li class="positive">
                                <a href="#"><i class="icon-fixed-width icon-plus-sign"></i>Add Queue</a>
                            </li>
                            <li class="danger">
                                <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <a class="truncate">Open</a>
                <ul>
                    <li>
                        <div class="editQ">
                            <i class="icon-cog"></i>
                            <div class="manageQ">
                                <ul>
                                    <li>
                                        <a href="#"><i class="icon-fixed-width icon-pencil"></i>Edit</a>
                                    </li>
                                    <li class="danger">
                                        <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <a class="truncate">testing to see how long I can make this go</a>
                    </li>
                </ul>
            </li>
            <li>
                <div class="editQ">
                    <i class="icon-cog"></i>
                    <div class="manageQ">
                        <ul>
                            <li class="positive">
                                <a href="#"><i class="icon-fixed-width icon-plus-sign"></i>Add Queue</a>
                            </li>
                            <li class="danger">
                                <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <a href="#">Answered</a>
            </li>
            <li>
                <h4>Personal Queue</h4>
            </li>
            <li>
                <div class="editQ">
                    <i class="icon-cog"></i>
                    <div class="manageQ">
                        <ul>
                            <li>
                                <a href="#"><i class="icon-fixed-width icon-pencil"></i>Edit</a>
                            </li>
                            <li class="danger">
                                <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <a class="truncate">testing to see how long I can make this go</a>
            </li>
        </ul>
        <div class="add-queue">
            <a class="flush-right" onclick="javascript:
                $.dialog('ajax.php/tickets/search', 201);">
                <div class="add pull-right"><i class="green icon-plus-sign"></i></div>
                <span>Add personal queue</span>
            </a>
        </div>
    </div>
</li>
<li class="item">
    <a class="closed" href="#"><i class="icon-sort-down pull-right"></i>Custom item 3</a>
    <div class="customQ-dropdown">
        <ul class="scroll-height">
            <li class="subQ">
                <i class="icon-caret-down"></i>
                <div class="editQ">
                    <i class="icon-cog"></i>
                    <div class="manageQ">
                        <ul>
                            <li class="positive">
                                <a href="#"><i class="icon-fixed-width icon-plus-sign"></i>Add Queue</a>
                            </li>
                            <li class="danger">
                                <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <a class="truncate">Open</a>
                <ul>
                    <li>
                        <div class="editQ">
                            <i class="icon-cog"></i>
                            <div class="manageQ">
                                <ul>
                                    <li>
                                        <a href="#"><i class="icon-fixed-width icon-pencil"></i>Edit</a>
                                    </li>
                                    <li class="danger">
                                        <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <a class="truncate">testing to see how long I can make this go</a>
                    </li>
                </ul>
            </li>
            <li>
                <div class="editQ">
                    <i class="icon-cog"></i>
                    <div class="manageQ">
                        <ul>
                            <li class="positive">
                                <a href="#"><i class="icon-fixed-width icon-plus-sign"></i>Add Queue</a>
                            </li>
                            <li class="danger">
                                <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <a href="#">Answered</a>
            </li>
            <li>
                <h4>Personal Queue</h4>
            </li>
            <li>
                <div class="editQ">
                    <i class="icon-cog"></i>
                    <div class="manageQ">
                        <ul>
                            <li>
                                <a href="#"><i class="icon-fixed-width icon-pencil"></i>Edit</a>
                            </li>
                            <li class="danger">
                                <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <a class="truncate">testing to see how long I can make this go</a>
            </li>
        </ul>
        <div class="add-queue">
            <a class="flush-right" onclick="javascript:
                $.dialog('ajax.php/tickets/search', 201);">
                <div class="add pull-right"><i class="green icon-plus-sign"></i></div>
                <span>Add personal queue</span>
            </a>
        </div>
    </div>
</li>
<li class="item">
    <a class="closed" href="#"><i class="icon-sort-down pull-right"></i>Custom item 4</a>
    <div class="customQ-dropdown">
        <ul class="scroll-height">
            <li class="subQ">
                <i class="icon-caret-down"></i>
                <div class="editQ">
                    <i class="icon-cog"></i>
                    <div class="manageQ">
                        <ul>
                            <li class="positive">
                                <a href="#"><i class="icon-fixed-width icon-plus-sign"></i>Add Queue</a>
                            </li>
                            <li class="danger">
                                <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <a class="truncate">Open</a>
                <ul>
                    <li>
                        <div class="editQ">
                            <i class="icon-cog"></i>
                            <div class="manageQ">
                                <ul>
                                    <li>
                                        <a href="#"><i class="icon-fixed-width icon-pencil"></i>Edit</a>
                                    </li>
                                    <li class="danger">
                                        <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <a class="truncate">testing to see how long I can make this go</a>
                    </li>
                </ul>
            </li>
            <li>
                <div class="editQ">
                    <i class="icon-cog"></i>
                    <div class="manageQ">
                        <ul>
                            <li class="positive">
                                <a href="#"><i class="icon-fixed-width icon-plus-sign"></i>Add Queue</a>
                            </li>
                            <li class="danger">
                                <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <a href="#">Answered</a>
            </li>
            <li>
                <h4>Personal Queue</h4>
            </li>
            <li>
                <div class="editQ">
                    <i class="icon-cog"></i>
                    <div class="manageQ">
                        <ul>
                            <li>
                                <a href="#"><i class="icon-fixed-width icon-pencil"></i>Edit</a>
                            </li>
                            <li class="danger">
                                <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <a class="truncate">testing to see how long I can make this go</a>
            </li>
        </ul>
        <div class="add-queue">
            <a class="flush-right" onclick="javascript:
                $.dialog('ajax.php/tickets/search', 201);">
                <div class="add pull-right"><i class="green icon-plus-sign"></i></div>
                <span>Add personal queue</span>
            </a>
        </div>
    </div>
</li>
<li class="item">
    <a class="closed" href="#"><i class="icon-sort-down pull-right"></i>Custom item 5</a>
    <div class="customQ-dropdown">
        <ul class="scroll-height">
            <li class="subQ">
                <i class="icon-caret-down"></i>
                <div class="editQ">
                    <i class="icon-cog"></i>
                    <div class="manageQ">
                        <ul>
                            <li class="positive">
                                <a href="#"><i class="icon-fixed-width icon-plus-sign"></i>Add Queue</a>
                            </li>
                            <li class="danger">
                                <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <a class="truncate">Open</a>
                <ul>
                    <li>
                        <div class="editQ">
                            <i class="icon-cog"></i>
                            <div class="manageQ">
                                <ul>
                                    <li>
                                        <a href="#"><i class="icon-fixed-width icon-pencil"></i>Edit</a>
                                    </li>
                                    <li class="danger">
                                        <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <a class="truncate">testing to see how long I can make this go</a>
                    </li>
                </ul>
            </li>
            <li>
                <div class="editQ">
                    <i class="icon-cog"></i>
                    <div class="manageQ">
                        <ul>
                            <li class="positive">
                                <a href="#"><i class="icon-fixed-width icon-plus-sign"></i>Add Queue</a>
                            </li>
                            <li class="danger">
                                <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <span>(80)</span>
                <a href="#">Answered</a>
            </li>
            <li>
                <h4>Personal Queue</h4>
            </li>
            <li>
                <div class="editQ">
                    <i class="icon-cog"></i>
                    <div class="manageQ">
                        <ul>
                            <li>
                                <a href="#"><i class="icon-fixed-width icon-pencil"></i>Edit</a>
                            </li>
                            <li class="danger">
                                <a href="#"><i class="icon-fixed-width icon-trash"></i>Delete</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <a class="truncate">testing to see how long I can make this go</a>
            </li>
        </ul>
        <div class="add-queue">
            <a class="flush-right" onclick="javascript:
                $.dialog('ajax.php/tickets/search', 201);">
                <div class="add pull-right"><i class="green icon-plus-sign"></i></div>
                <span>Add personal queue</span>
            </a>
        </div>
    </div>
</li>
