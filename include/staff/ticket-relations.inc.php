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
                 <th width="8px">&nbsp;</th>
                 <th width="70"><?php echo __('Number'); ?></th>
                 <th width="100"><?php echo __('Subject'); ?></th>
                 <th width="100"><?php echo __('Department'); ?></th>
                 <th width="300"><?php echo __('Assignee'); ?></th>
                 <th width="200"><?php echo __('Create Date'); ?></th>
             </tr>
         </thead>
         <tbody class="tasks">
         <?php
         if ($children) {
             foreach($children as $child) {
                 $child = Ticket::lookup($child[0]);
                 echo $child->getRelatedTickets();
             }
         } elseif ($parent)
             echo $parent->getRelatedTickets();
         ?>
         </tbody>
     </table>
</form>
<?php }
