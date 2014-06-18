/**
 * Templates Master http://templates-master.com
 *
 * Modified menu script.
 * - Dropdown autoalignment
 * - Check for link inside el, before operating
 * - Dropdown width calculating
 *
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Varien
 * @package     js
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * @classDescription simple Navigation with replacing old handlers
 * @param {String} id id of ul element with navigation lists
 * @param {Object} settings object with settings
 */
var navPro = function() {

    var main = {
        obj_nav :   $(arguments[0]) || $("nav"),

        settings :  {
            show_delay   : 0,
            hide_delay   : 0,
            _ie6         : /MSIE 6.+Win/.test(navigator.userAgent),
            _ie7         : /MSIE 7.+Win/.test(navigator.userAgent),
            constraint_el: $$('.main')[0],
            dropdown_side: 'right',
            fit_width    : true
        },

        fitColumnWidth: function() {
            // find next levels without dropdown wrapper
            var columns = this.obj_nav.select('.nav-li-column > .nav-column-wrapper > .nav-column').reverse();
            columns.each(function(column) {
                var parentLi,       parentUl,       oldLiWidth,
                    newLiWidth,     oldUlWidth,     newUlWidth,
                    dimension,      ulSiblings,     dropdown,
                    oldDropdownWidth, newDropdowWidth;

                do {
                    parentLi = column.up('.nav-li-column');
                    parentUl = column.up('.nav-column');

                    if (!parentLi) {
                        break;
                    }

                    oldLiWidth = parseInt(parentLi.getStyle('width'));
                    newLiWidth = parseInt(column.getStyle('width'));
                    dimension  = column.getStyle('width').replace(newLiWidth, '');

                    ulSiblings = column.siblings();
                    ulSiblings.each(function(ulSibling) {
                        newLiWidth += parseInt(ulSibling.getStyle('width'));
                    });

                    if (oldLiWidth >= newLiWidth) {
                        break;
                    }

                    parentLi.setStyle({
                        width: newLiWidth + dimension
                    });

                    oldUlWidth = parseInt(parentUl.getStyle('width'));
                    newUlWidth = oldUlWidth + (newLiWidth - oldLiWidth);
                    parentUl.setStyle({
                        width: newUlWidth + dimension
                    });

                    dropdown = parentUl.up('.nav-dropdown');
                    oldDropdownWidth = parseInt(dropdown.getStyle('width'));
                    newDropdowWidth = oldDropdownWidth + (newUlWidth - oldUlWidth);
                    dropdown.setStyle({
                        width: newDropdowWidth + dimension
                    });

                    column = parentUl;
                } while (true);
            });
        },

        init :  function(obj, level) {
            obj.lists = obj.childElements();
            obj.lists.each(function(el,ind){
                main.handlNavElement(el);
                if((main.settings._ie6 || main.settings._ie7) && level){
                    main.ieFixZIndex(el, ind, obj.lists.size());
                }
            });
            if(main.settings._ie6 && !level){
                document.execCommand("BackgroundImageCache", false, true);
            }
        },

        handlNavElement :   function(list) {
            if(list !== undefined){
                list.hover(
                    function(){
                        main.toggleOverClass(list, true);
                        main.fireNavEvent(list, true);
                    },
                    function(){
                        main.toggleOverClass(list, false);
                        main.fireNavEvent(list, false);
                    }
                );
                if (list.select('ul.nav-ul').length) {
                    // tm modification start
                    // our menu rendered in multiple rows,
                    // so every row should be initialized
                    list.select('ul.nav-ul').each(function(el) {
                        main.init(el, true);
                    });
                    // tm modification end
                }
            }
        },

        ieFixZIndex : function(el, i, l) {
            if(el.tagName.toString().toLowerCase().indexOf("iframe") == -1){
                el.style.zIndex = l - i;
            } else {
                el.onmouseover = "null";
                el.onmouseout = "null";
            }
        },

        fireNavEvent :  function(elm, ev) {
            if (!elm.hasClassName('nav-style-dropdown')) {
                return;
            }

            var dropdown = elm.childElements()[2];
            if (!dropdown) {
                return;
            }
            if (ev) {
                main.show(dropdown);
            } else {
                main.hide(dropdown);
            }
        },

        toggleOverClass: function(elm, state) {
            if (state) {
                elm.addClassName('over');
                elm.down('a') && elm.down('a').addClassName('over');
            } else {
                elm.removeClassName('over');
                elm.down('a') && elm.down('a').removeClassName('over');
            }
        },

        show : function (sub_elm) {
            if (sub_elm.hide_time_id) {
                clearTimeout(sub_elm.hide_time_id);
            }
            var self = this;
            sub_elm.show_time_id = setTimeout(function() {
                if (sub_elm.hasClassName("shown-sub")) {
                    return;
                }
                sub_elm.addClassName("shown-sub");

                // tm modification start
                // fix position of the dropdown to make it fully visible
                self.setDropdownPosition(sub_elm);
                // tm modification end
            }, main.settings.show_delay);
        },

        hide : function (sub_elm) {
            if (sub_elm.show_time_id) {
                clearTimeout(sub_elm.show_time_id);
            }
            sub_elm.hide_time_id = setTimeout(function(){
                if (sub_elm.hasClassName("shown-sub")) {
                    sub_elm.removeClassName("shown-sub");
                }
            }, main.settings.hide_delay);
        },

        setDropdownPosition: function(sub_elm)
        {
            var self         = this,
                parentLi     = sub_elm.up('li'),
                top          = 0,
                left         = 0,
                right        = 0,
                dropdownSide = self.settings.dropdown_side;

            if (!parentLi) {
                return;
            }

            if (parentLi.up().hasClassName('navpro-inline')) {
                top = parentLi.getHeight() - 1;
            } else {
                top   = 10;
                left  = parentLi.getWidth() - 40;
                right = left;
            }
            sub_elm.setStyle({
                left: left + 'px',
                top : top + 'px'
            });
            if ('left' === dropdownSide) {
                sub_elm.setStyle({
                    right: right + 'px',
                    top  : top + 'px',
                    left : 'auto'
                });
            }

            var parentOffset   = parentLi.viewportOffset(),
                elSize         = sub_elm.getDimensions(),
                elOffset       = sub_elm.viewportOffset(),
                constraintSize   = self.settings.constraint_el.getDimensions(),
                constraintOffset = self.settings.constraint_el.viewportOffset(),
                viewportSize   = document.viewport.getDimensions(),
                viewportOffset = document.viewport.getScrollOffsets(),
                elRight        = elOffset.left + elSize.width,
                contraintRight = constraintOffset.left + constraintSize.width,
                limitRight     = Math.min(contraintRight, viewportSize.width),
                limitLeft      = Math.max(constraintOffset.left, viewportOffset.left);

            if ('right' === dropdownSide && elRight > limitRight) {
                left = left - (elRight - limitRight);
                if (left < 30 && !parentLi.up().hasClassName('navpro-inline')) {
                    // try to use alignment to the left
                    var elLeft = parentOffset.left + parentLi.getWidth() - elSize.width - right;
                    if (left < 10 && elLeft > 0) {
                        sub_elm.setStyle({
                            right: right + 'px',
                            left : 'auto'
                        });
                        return;
                    }
                    left = 40; // we should leave the gap to allow to move cursor down to the next li
                } else if (parentOffset.left < Math.abs(left)) {
                    left = -parentOffset.left;
                }
                sub_elm.setStyle({
                    left: left + 'px'
                });
            } else if ('left' === dropdownSide && elOffset.left < 0
                && ((elSize.width + elOffset.left) < (viewportSize.width - (parentOffset.left + left)))) {

                sub_elm.setStyle({
                    left: left + 'px'
                });
            }
        }
    };
    if (arguments[1]) {
        main.settings = Object.extend(main.settings, arguments[1]);
    }
    if (main.obj_nav) {
        main.init(main.obj_nav, false);
        if (main.settings.fit_width) {
            main.fitColumnWidth();
        }
    }
};

/**
 * Do not remove or edit this.
 * Modified by Templates-Master,
 * to provide support of multiple navigation levels
 * of one accordion instance.
 *
 * www.templates-master.com
 */
// accordion.js v2.0
//
// Copyright (c) 2007 stickmanlabs
// Author: Kevin P Miller | http://www.stickmanlabs.com
//
// Accordion is freely distributable under the terms of an MIT-style license.
//

var accordion = Class.create();
accordion.prototype = {
    currentAccordion: null,
    duration        : null,
    effects         : [],
    animating       : false,

    initialize: function(container, options)
    {
        this.options = Object.extend({
            resizeSpeed: 8,
            classNames: {
                toggle      : 'nav-accordion-toggler',
                toggleActive: 'nav-accordion-toggler-active'
            },
            defaultSize: {
                height: null,
                width : null
            },
            direction: 'vertical',
            onEvent  : 'click'
        }, options || {});

        this.duration = ((11-this.options.resizeSpeed)*0.15);

        var accordions = $$('#'+container+' .'+this.options.classNames.toggle);
        accordions.each(function(accordion) {
            Event.observe(accordion, this.options.onEvent, this.activate.bind(this, accordion), false);
            if (this.options.onEvent == 'click') {
                accordion.onclick = function() {return false;};
            }

            if (this.options.direction == 'horizontal') {
                var accordion_options = $H({width: '0px'});
            } else {
                var accordion_options = $H({height: '0px'});
            }

            this.currentAccordion = $(accordion.next(0)).setStyle(accordion_options.toJSON());
        }.bind(this));

        this.showActive(container);
    },

    showActive: function(container)
    {
        var self = this;
        $$('#' + container + ' .nav-style-accordion.active > .nav-dropdown').each(function(el) {
            el.previous(0).addClassName(self.options.classNames.toggleActive);
            el.setStyle({ height: 'auto' });
        });
    },

    activate: function(accordion)
    {
        if (this.animating) {
            return false;
        }

        this.effects = [];

        if (this.options.direction == 'horizontal') {
            this.scaleX = true;
            this.scaleY = false;
        } else {
            this.scaleX = false;
            this.scaleY = true;
        }

        this.currentAccordion = $(accordion.next(0));

        if (accordion.hasClassName(this.options.classNames.toggleActive)) {
            this.deactivate();
        } else {
            this._handleAccordion();
        }
    },

    deactivate: function()
    {
        this.currentAccordion.previous(0).removeClassName(this.options.classNames.toggleActive);

        new Effect.Scale(this.currentAccordion, 0, {
            duration: this.duration,
            scaleContent: false,
            scaleX: this.scaleX,
            scaleY: this.scaleY,
            transition: Effect.Transitions.sinoidal,
            queue: {
                position: 'end',
                scope: 'accordionAnimation'
            },
            scaleMode: {
                originalHeight: this.options.defaultSize.height ? this.options.defaultSize.height : this.currentAccordion.scrollHeight,
                originalWidth: this.options.defaultSize.width ? this.options.defaultSize.width : this.currentAccordion.scrollWidth
            },
            afterFinish: function() {
                this.animating = false;
            }.bind(this)
        });
    },

    _handleAccordion: function()
    {
        this.effects.push(
            new Effect.Scale(this.currentAccordion, 100, {
                sync: true,
                scaleFrom: 0,
                scaleContent: false,
                scaleX: this.scaleX,
                scaleY: this.scaleY,
                transition: Effect.Transitions.sinoidal,
                scaleMode: {
                    originalHeight: this.options.defaultSize.height ? this.options.defaultSize.height : this.currentAccordion.scrollHeight,
                    originalWidth: this.options.defaultSize.width ? this.options.defaultSize.width : this.currentAccordion.scrollWidth
                }
            })
        );

        var opened = this._getOpened();
        if (opened) {
            opened.previous(0).removeClassName(this.options.classNames.toggleActive);
            this.effects.push(
                new Effect.Scale(opened, 0, {
                    sync: true,
                    scaleContent: false,
                    scaleX: this.scaleX,
                    scaleY: this.scaleY,
                    transition: Effect.Transitions.sinoidal
                })
            );
        }

        this.currentAccordion.previous(0).addClassName(this.options.classNames.toggleActive);
        new Effect.Parallel(this.effects, {
            duration: this.duration,
            queue: {
                position: 'end',
                scope: 'accordionAnimation'
            },
            beforeStart: function() {
                this.animating = true;
            }.bind(this),
            afterFinish: function() {
                this.currentAccordion.setStyle({ height: 'auto' });
                this.animating = false;
            }.bind(this)
        });
    },

    _getOpened: function()
    {
        var topLevel = this.currentAccordion.hasClassName('level0'),
            up       = topLevel ? 'li' : 'ul',
            siblings = this.currentAccordion.up(up).siblings(),
            opened   = false,
            self     = this,
            selector = '> .nav-accordion-toggler';

        if (!topLevel) {
            selector = '> li ' + selector;
        }

        siblings.each(function(el) {
            el.select(selector).each(function(toggler) {
                if (toggler.hasClassName(self.options.classNames.toggleActive)) {
                    opened = toggler.next(0);
                    throw $break;
                }
            });
        });

        return opened;
    }
};

function transformMenu(id) {
    var mode = 'accordion',
        menu = $(id),
        classMapping = {
            'nav-style-dropdown'  : 'nav-style-accordion',
            'nav-dropdown-toggler': 'nav-accordion-toggler'
        },
        renameFrom, renameTo;

    for (var i in classMapping) {
        if ('accordion' === mode) {
            renameFrom = i;
            renameTo   = classMapping[i];
        } else {
            renameFrom = classMapping[i];
            renameTo   = i;
        }
        menu.select('.' + renameFrom).each(function(el) {
            el.removeClassName(renameFrom);
            el.addClassName(renameTo);
        });
    }

    menu.select('.nav-column, .nav-dropdown, .nav-li-column').each(function(el) {
        el.setStyle({width: 'auto'});
    });
    menu.select('.nav-dropdown').each(function(el) {
        el.setStyle({
            'padding': '0px'
        });
    });
};

document.observe("dom:loaded", function() {
    $$('.navpro-mobile-toggle').each(function(el) {
        el.observe('click', function(e) {
            var menu = $(el.readAttribute('data-menu'));
            if (!menu) {
                return;
            }
            menu.toggleClassName('shown');
        });
    });
});


// protoHover
// a simple hover implementation for prototype.js
// Sasha Sklar and David Still
(function() {
    // copied from jquery
    var withinElement = function(evt, el) {
        // Check if mouse(over|out) are still within the same parent element
        var parent = evt.relatedTarget;

        // Traverse up the tree
        while (parent && parent != el) {
            try {
                parent = parent.parentNode;
            } catch (error) {
                parent = el;
            }
        }
        // Return true if we actually just moused on to a sub-element
        return parent == el;
    };

    // Extend event with mouseEnter and mouseLeave
    Object.extend(Event, {
        mouseEnter: function(element, f, options) {
            element = $(element);

            // curry the delay into f
            var fc = (options && options.enterDelay)?(function(){window.setTimeout(f, options.enterDelay);}):(f);

            if (Prototype.Browser.IE) {
                element.observe('mouseenter', fc);
            } else {
                element.hovered = false;

                element.observe('mouseover', function(evt) {
                    // conditions to fire the mouseover
                    // mouseover is simple, the only change to default behavior is we don't want hover fireing multiple times on one element
                    if (!element.hovered) {
                        // set hovered to true
                        element.hovered = true;

                        // fire the mouseover function
                        fc(evt);
                    }
                });
            }
        },
        mouseLeave: function(element, f, options) {
            element = $(element);

            // curry the delay into f
            var fc = (options && options.leaveDelay)?(function(){window.setTimeout(f, options.leaveDelay);}):(f);

            if (Prototype.Browser.IE) {
                element.observe('mouseleave', fc);
            } else {
                element.observe('mouseout', function(evt) {
                    // get the element that fired the event
                    // use the old syntax to maintain compatibility w/ prototype 1.5x
                    var target = Event.element(evt);

                    // conditions to fire the mouseout
                    // if we leave the element we're observing
                    if (!withinElement(evt, element)) {
                        // fire the mouseover function
                        fc(evt);

                        // set hovered to false
                        element.hovered = false;
                    }
                });
            }
        }
    });


    // add method to Prototype extended element
    Element.addMethods({
        'hover': function(element, mouseEnterFunc, mouseLeaveFunc, options) {
            options = Object.extend({}, options) || {};
            Event.mouseEnter(element, mouseEnterFunc, options);
            Event.mouseLeave(element, mouseLeaveFunc, options);
        }
    });
})();
