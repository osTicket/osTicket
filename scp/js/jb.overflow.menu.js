/*
 * jQuery UI jb.overflowmenu
 *
 * Copyright 2011, Jesse Baird <jebaird@gmail.com> jebaird.com
 * Dual licensed under the MIT or GPL Version 2 licenses.
 *
 * http://jebaird.com/blog/overflow-menu-jquery-ui-widget
 *
 * Depends:
 *   jquery.ui.core.js
 *   jquery.ui.widget.js
 *
 *
 * suggested markup
 * <nav>
 *  <ul>
 *      <li>
 *  </ul>
 * </nav>
 *
 * $('nav').overflowmenu()
 *
 *
 *
 * events
 *  change - after items are moved to / from the secondary menu
 *  beforeChange - called before items are moved to / from the secondary menu
 *  open - when the secondary menu is shown
 *  close - when the secondary menu is closed
 */

(function( $, undefined ) {

$.widget( "jb.overflowmenu", {
    options: {
        items: '> *',
        itemsParentTag: 'ul',
        label: '<i class="icon-ellipsis-vertical"></i>',
        //call the refresh method when this element changes size, with out a special event window is the only element that this gets called on
        refreshOn: $( window ),

        //attempt to guess the height of the menu, if not the target element needs to have a height
        guessHeight: true
    },

    _create: function() {
        var self = this;

        this.element
            .addClass('jb-overflowmenu');

        this.primaryMenu = this.element
                        .children( this.options.itemsParentTag )
                        .addClass( 'jb-overflowmenu-menu jb-overflowmenu-menu-primary jb-overflowmenu-helper-postion' );

        this._setHeight();

        //TODO: allow the user to change the markup for this because they might not be using ul -> li
        this.secondaryMenuContainer = $(
                            [
                                '<div class="jb-overflowmenu-container jb-overflowmenu-helper-postion">',
                                    '<a href="javascript://" class="jb-overflowmenu-menu-secondary-handle"></a>',
                                    '<' + this.options.itemsParentTag + ' class="jb-overflowmenu-menu jb-overflowmenu-menu-secondary jb-overflowmenu-helper-postion"></' + this.options.itemsParentTag + '>',
                                '</div>'
                            ].join('')
                        )
                        .appendTo( this.element )

        this.secondaryMenu = this.secondaryMenuContainer.find('ul');

        this.secondaryMenuContainer.children( 'a' ).bind( 'click.overflowmenu', function( e ){
            self.toggle();
        });

        //has to be set first
        this._setOption( 'label', this.options.label )
        this._setOption( 'refreshOn', this.options.refreshOn )
        this.secondaryMenuContainer.find('i.icon-sort-down').remove('i.icon-sort-down');
    },

    destroy: function() {
        this.element
            .removeClass('jb-overflowmenu')

        this.primaryMenu
            .removeClass('jb-overflowmenu-menu-primary jb-overflowmenu-helper-postion')
            .find( this.options.items )
            .filter( ':hidden' )
            .css( 'display', '' )

        this.options.refreshOn.unbind( 'resize.overflowmenu' );

        this.secondaryMenuContainer.remove()

        //TODO: possibly clean up the height & right on the ul

        $.Widget.prototype.destroy.apply( this, arguments );
    },


    refresh: function() {

        this._trigger( 'beforeChange', {}, this._uiHash() );

        //move any items in the secondary menu back in to the primary
        this.secondaryMenu
            .children()
            .appendTo( this.primaryMenu )


        var vHeight = this.primaryMenuHeight,
            hWidth = this.secondaryMenuContainer.find('.jb-overflowmenu-menu-secondary-handle')
                .outerWidth(),
            vWidth = this.primaryMenuWidth - hWidth,
            previousRight = this.primaryMenu.offset().left;

            // Items classed 'primary-only' should always be primary
            this._getItems()
                .each(function() {
                    var $this = $(this);
                    if ($this.hasClass('primary-only'))
                      vWidth -= $this.outerWidth(true);
                });

            //get the items, filter out the visible ones
            itemsToHide = this._getItems()
                .filter(function() {
                    var $this = $(this),
                        left = $this.offset().left,
                        dLeft = Math.max(0, left - previousRight);
                    previousRight = left + $this.width();

                    if ($this.hasClass('primary-only'))
                        return false;

                    vWidth -= dLeft + $this.outerWidth(true);
                    return vWidth < 1;
                });

        itemsToHide.appendTo( this.secondaryMenu )
            .find('i.icon-sort-down').remove('i.icon-sort-down');


        if( itemsToHide.length == 0 ){
            this.close();
        }

        //TODO: add the items to the UI Hash
        this._trigger( 'change', {}, this._uiHash() );
        return this;
    },

    //more menu opitons

    open: function(){
        if( this.secondaryMenu.find( this.options.items ).length == 0){
            return;
        }
        this.primaryMenu.css( 'right',  this.primaryMenu.data( 'right' ) )
        this.secondaryMenu.show();
        this._trigger( 'open', {}, this._uiHash() );
        return this;
    },
    close: function(){
        this.secondaryMenu.hide();
        this._trigger( 'close', {}, this._uiHash() );
        return this;
    },
    toggle: function(){
        if( this.secondaryMenu.is( ':visible') ){
            this.close();
        }else{
            this.open();
        }
        return this;
    },
    _getItems: function(){
        return this.primaryMenu.find( this.options.items );
    },
    _setHeight: function(){
        if( this.options.guessHeight ){
            //get the first items height and set that as the height of the parent
            this.primaryMenuHeight = this.primaryMenu.find( this.options.items ).filter(':first').outerHeight();
            this.primaryMenu.css('height', this.primaryMenuHeight )

        }else{
            this.primaryMenuHeight = this.element.innerHeight();
        }
        this.primaryMenuWidth = this.options.width ||
            this.element.innerWidth();

    },
    _setOption: function( key, value ) {
        var self = this;
        if( key == 'refreshOn' && value ){
            this.options.refreshOn.unbind( 'resize.overflowmenu' );

            this.options.refreshOn = $( value )
                                        .bind( 'resize.overflowmenu', function(){
                                            self.refresh();
                                        })
                                        //call to set option
                                        self.refresh();

        }else if( key == 'label' && value ){
            //figure out the width of the hadel and subtract that from the parend with and set that as the right

            var width = this.secondaryMenuContainer.find('.jb-overflowmenu-menu-secondary-handle')
                        .html( value )
                        .outerWidth();
            this.primaryMenu.data( 'right',  width )

        }

        $.Widget.prototype._setOption.apply( this, arguments );
    },
    _uiHash: function(){
        return {
            primary: this.primaryMenu,
            secondary: this.secondaryMenu,
            container: this.secondaryMenuContainer
        };
    }

});





})( jQuery );
