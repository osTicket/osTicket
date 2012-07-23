/*********************************************************************
    jquery.multifile.js

    Multifile plugin that allows users to upload multiple files at once in unobstructive manner - cleaner interface.

    Allows limiting number of files and file type(s) using file extension.

    NOTE:
    * Files are not uploaded until the form is submitted
    * Server side file type validation is a MUST
    * Plugin doesn't take into account PHP related limitations e.g max uploads + max size.

    Peter Rotich <peter@osticket.com>
    Copyright (c) 2006-2012 osTicket
    http://www.osticket.com

    Credits:
    The plugin borrows heavily from a plugin by Rocky Meza @ fusionbox
    https://github.com/fusionbox/jquery-multifile

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

;(function($, global, undefined) {
        
    $.fn.multifile = function(options) {
        var container = null;
        var options = $.extend({}, $.fn.multifile.defaults, options);

        options.allowedFileTypes = $.map(options.file_types.toLowerCase().split(','), $.trim);
        options.inputTemplate = options.inputTemplate || $.fn.multifile.inputTemplate;

        container = options.container || null;


        return this.each(function() {

            var settings = options;
            var $container
                
            , addInput = function(event) {
            
                var $this = $(this)
                , fObj = $(this).closest('form')
                , new_input = $.fn.multifile.cloneInput($this)
                , file = $.fn.multifile.getFileObject(this);

                if(fObj.data('files') == undefined)
                    fObj.data('files', 0);

                if(fObj.data('files')>=settings.max_uploads || (fObj.data('files')+file.count)>settings.max_uploads) {
                    alert('You have reached the maximum number of files ('+ settings.max_uploads+') allowed per upload');
                } else if($.fn.multifile.checkFileTypes(file, settings.allowedFileTypes)) {
                    $this.hide();
                    
                    settings
                    .inputTemplate(file)
                    .appendTo($container)
                    .on('click', 'input',  bindRemoveInput($this, file));

                    fObj.data('files', fObj.data('files')+file.count);
                    if(fObj.data('files')<settings.max_uploads)
                        $this.after(new_input);

                } else {
                    var msg = 'Selected file type is NOT allowed';
                    if(file.count>1)
                        msg = 'File type of one or more of the selected files is NOT allowed';

                    alert('Error: '+msg);
                    
                    $this.replaceWith(new_input);
                }
        
            }
      
            , bindRemoveInput = function($input, file) {

                return function(event) {

                    event.preventDefault();
           
                    if(confirm('Are you sure you want to remove '+file.name+'?')) {
                        var fObj = $(this).closest('form');

                        fObj.data('files', fObj.data('files')-file.count);
                        if(fObj.data('files')<settings.max_uploads && (fObj.data('files')+file.count)>=settings.max_uploads)
                            $input.after($.fn.multifile.cloneInput($input).show());
                        
                        $input.remove();
                        $(this).parent().remove();
                    }

                    return false;
                };
        
            };
    
            if ( container ) {
                if ( typeof container == 'string' ) 
                    $container = $(container, $(this).closest('form'));
                else
                    $container = container;
            } else {
                $container = $('<div class="uploads" />');
                $(this).after($container);
            }

            $(this).bind('change.multifile', addInput);
  
        });
  };

  $.fn.multifile.inputTemplate = function(file) {
    return $('<label style="padding-right:5px;"><input type="checkbox" name="uploads[]" value="' + file.name + '" checked="checked"> ' + file.name + '</label>');
  };

  $.fn.multifile.checkFileTypes = function(file, allowedFileTypes) {
     
      //Wildcard.
      if(allowedFileTypes[0]=='.*')
          return true;

      var filenames = $.map(file.name.toLowerCase().split(','), $.trim);
      for (var i = 0, _len = filenames.length; i < _len; i++)
          if(filenames[i] && $.inArray('.'+filenames[i].split('.').pop(), allowedFileTypes) == -1)
              return false;

      return true;
  };

  //Clone file input and clear the value without triggering a warning!
  $.fn.multifile.cloneInput = function(input) {

      var $clone = input.clone(true);
                      
      if ($.browser.msie) {
          $clone.replaceWith(function () { return $(this).clone(true); });
      } else {
          $clone.val('');
      }

      return $clone;
  }

  //Get file object 
  $.fn.multifile.getFileObject = function(input) {
    var file = {};

    file.count = 1; 
    // check for HTML5 FileList support
    if ( !!global.FileList ) {
      if ( input.files.length == 1 )
        file.name = input.files[0].name;
      else { //Multi-select
        // We do this in order to support `multiple` files.
        // You can't display them separately because they 
        // belong to only one file input.  It is impossible
        // to remove just one of the files.
        file.name = input.files[0].name;
        for (var i = 1, _len = input.files.length; i < _len; i++)
          file.name += ', ' + input.files[i].name;

        file.count = i;
      }
    } else {
      file.name = input.value;
    }

    return file;
  };

  //Default options 
  $.fn.multifile.defaults = { 
                              max_uploads: 1,
                              file_types: '.*'
                            };
})(jQuery, this);
