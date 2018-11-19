<?php
if ($ticket->isChild())
    $parent = Ticket::lookup($ticket->getPid());
else
    $children = Ticket::getChildTickets($ticket->getId());

if (count($children) != 0 || $ticket->isChild()) { ?>
    <form action="#tickets/<?php echo $ticket->getId(); ?>/relations" method="POST"
        name='relations' id="relations" style="padding-top:7px;">
  <?php csrf_token(); ?>
    <table class="list" border="0" cellspacing="1" cellpadding="2" width="920">
         <thead>
             <tr>
                 <?php
                 if (1) {?>
                 <th width="8px">&nbsp;</th>
                 <?php
                 } ?>
                 <th width="70"><?php echo __('Number'); ?></th>
                 <th width="100"><?php echo __('Subject'); ?></th>
                 <th width="100"><?php echo __('Department'); ?></th>
                 <th width="300"><?php echo __('Assignee'); ?></th>
                 <th width="200"><?php echo __('Create Date'); ?></th>
             </tr>
         </thead>
         <tbody class="tasks">
         <?php
         //adriane: reduce redundancy here pls
         if ($children) {
             foreach($children as $child) {
                 $child = Ticket::lookup($child[0]);
                 ?>
                 <tr>
                     <td width="8px">&nbsp;</td>
                     <td>
                         <a class="Icon <?php echo strtolower($child->getSource()); ?>Ticket preview"
                            data-preview="#tickets/<?php echo $child->getId(); ?>/preview"
                            href="tickets.php?id=<?php echo $child->getId(); ?>"><?php
                            echo $child->getNumber(); ?></a>
                     </td>
                     <td><?php echo $child->getSubject(); ?></td>
                     <td><?php echo $child->getDeptName(); ?></td>
                     <td><?php echo $child->getAssignee(); ?></td>
                     <td><?php echo Format::datetime($child->getCreateDate()); ?></td>
                 </tr>
             <?php
             }
         }

         elseif ($parent) { ?>
             <tr>
                 <td width="8px">&nbsp;</td>
                 <td>
                     <a class="Icon <?php echo strtolower($parent->getSource()); ?>Ticket preview"
                        data-preview="#tickets/<?php echo $parent->getId(); ?>/preview"
                        href="tickets.php?id=<?php echo $parent->getId(); ?>"><?php
                        echo $parent->getNumber(); ?></a>
                 </td>
                 <td><?php echo $parent->getSubject(); ?></td>
                 <td><?php echo $parent->getDeptName(); ?></td>
                 <td><?php echo $parent->getAssignee(); ?></td>
                 <td><?php echo Format::datetime($parent->getCreateDate()); ?></td>
             </tr>
         <?php }
         ?>
         </tbody>
     </table>
</form>
<?php }
