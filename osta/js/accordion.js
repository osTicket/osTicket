$(document).ready(function(){
  
  var $table = $('.table-accordion'),
      $triggers = $table.find('.trigger'),
      $details = $table.find('.details');
  
  $details.hide(); 

  $triggers.on('click', function() {
    var trigger = $(this),
        details = trigger.parent().find('.details');
    
    details.slideToggle();
    updateArrows.call(trigger);
  });
});

function updateArrows() {
  var openedClass = "glyphicon-menu-up",
      closedClass = "glyphicon-menu-down"
  if (this.hasClass('glyphicon-menu-down')) {
      this.addClass(openedClass).removeClass(closedClass);
    } else {
      this.addClass(closedClass).removeClass(openedClass);
    }
}