document.observe('dom:loaded', function() {
    if ('ontouchstart' in document.documentElement) {
        $(document.body).addClassName('touchable');
    } else {
        $(document.body).addClassName('untouchable');
    }
});

var MobileNavigation = Class.create();
MobileNavigation.prototype = {
    status: true,
    config: {
        container: '#nav',
        items    : 'li.parent',
        duration : 0.2
    },

    initialize: function(options) {
        Object.extend(this.config, options || {});
        this.container = $$(this.config.container)[0];
        if (!this.container) {
//            this.config.container = '.nav-container .navpro';
//            this.container = $$(this.config.container)[0];
            return;
        }

        var self = this;
        this.container.select(this.config.items).each(function(el) {
            el.insert({
                bottom: '<a href="#" class="toggle"></a>'
            });
        });
        this.container.select('.toggle').each(function(el) {
            el.observe('click', function(e) {
                e.stop();
                var dropdown = el.previous('ul') || el.previous('.nav-dropdown');
                self.toggle(dropdown);
            });
        });
    },

    toggleAll: function(status) {
        var self    = this;
        status      = status || !this.status;
        this.status = status;

        this.container.select('ul').each(function(el) {
            if (status) {
                self.show(el);
            } else {
                self.hide(el);
            }
        });
    },

    toggle: function(el) {
        if (el.hasClassName('shown')) {
            this.hide(el);
        } else {
            this.show(el);
        }
    },

    show: function(el) {
        el.previous('.toggle') && el.previous('.toggle').addClassName('active');
        el.next('.toggle') && el.next('.toggle').addClassName('active');
        el.addClassName('shown');
    },

    hide: function(el) {
        el.previous('.toggle') && el.previous('.toggle').removeClassName('active');
        el.next('.toggle') && el.next('.toggle').removeClassName('active');
        el.removeClassName('shown');
    },

    isVisible: function(el) {
        return el.visible();
    }
};

/* Product images slider
 *
 * Required markup:
 * <div> - with overflow hidden
 *  <ul> - container with large width
 *   <li></li>
 *   <li></li>
 *  </ul>
 * </div>
 */
var ScrollableList = Class.create();
ScrollableList.prototype = {
    config: {
        container: '.product-gallery ul',
        item     : 'li',
        itemWidth: 250
    },

    initialize: function(options) {
        Object.extend(this.config, options || {});
        this.container = $$(this.config.container)[0];
        this.items     = this.container.select(this.config.item);

        this.updateLayout();
        this.addObservers();
    },

    /**
     * Set the container width accoring to the items count
     */
    updateLayout: function() {
        var item      = this.items[0],
            itemWidth = parseFloat(item.getStyle('margin-left'))
                + parseFloat(item.getStyle('margin-right'))
                + parseFloat(item.getStyle('padding-left'))
                + parseFloat(item.getStyle('padding-right'))
                + this.config.itemWidth;

        this.containerWidth = itemWidth * this.items.length;

        this.container.setStyle({
            width   : this.containerWidth + 'px',
            position: 'relative',
            left    : '0px'
        });
        this.container.select('a').each(function(el) {
            el.setStyle({
                '-webkit-tap-highlight-color': 'rgba(0, 0, 0, 0)'
            });
        });
    },

    addObservers: function() {
        var parent = this.container.up();
        parent.observe('touchstart', this.start.bind(this));
        parent.observe('touchend', this.end.bind(this));
        parent.observe('touchmove', this.move.bind(this));
        // parent.observe('touchcancel', this.cancel.bind(this));
    },

    /**
     * Remember the initial container and finger properties
     */
    start: function(e) {
        var touch = e.targetTouches[0];
        this.startInfo = {
            mode : 'container',
            pageX: touch.pageX,
            pageY: touch.pageY,
            listX: parseFloat(this.container.getStyle('left')),
            listY: parseFloat(this.container.getStyle('top')),
            time : new Date().getTime()
        };
    },

    /**
     * Check the container position and fix it if it shifted too much
     */
    end: function(e) {
//        var touch  = e.changedTouches[0],
//            shiftX = touch.pageX - this.startInfo.pageX,
//            left   = this.startInfo.listX + shiftX,
//            updateLeft = false;

//        if (left >= 0) {
//            left = 0;
//            updateLeft = true;
//        } else {
//            var parentWidth = this.container.up().getWidth();
//            if (Math.abs(left - parentWidth) > this.containerWidth) {
//                left = parentWidth - this.containerWidth;
//                updateLeft = true;
//            }
//        }

//        if (!updateLeft) {
//            return;
//        }

//        this.slideTo(left, 0.1);
    },

    move: function(e) {
        // Check the mode, to prevent the container scrolling,
        //  if the document was scrolled within the same touch event
        // This is done because Android blocks the realtime animation,
        //  if original event (scrolling of the document) was already invoked
        if (this.startInfo.mode == 'document') {
            return;
        }

        var touch  = e.targetTouches[0],
            shiftX = touch.pageX - this.startInfo.pageX,
            shiftY = touch.pageY - this.startInfo.pageY,
            left   = this.startInfo.listX + shiftX;

        // allow to scroll page if movement was vertical
        if (Math.abs(shiftY) > Math.abs(shiftX)) {
            this.startInfo.mode = 'document';
            return;
        }

        // prevent page from scrolling to see realtime animation of the container movement
        e.stop();

        this.slideTo(this.getLeft(left), 0.1);
    },

    /**
     * Slides the container with css3 animation
     *
     * @param int position Container position
     * @param int duration Animation duration in seconds
     */
    slideTo: function(position, duration) {
        this.container.setStyle({
            left: position + 'px',
            '-webkit-transition-property': 'left',
            '-webkit-transition-duration': duration + 's'
        });
    },

    getLeft: function(left) {
        // Disallow to scroll to much
        if (left >= 0) {
            left = 0;
        } else {
            var parentWidth = this.container.up().getWidth();
            if (parentWidth >= this.containerWidth) {
                left = 0;
            } else if (Math.abs(left - parentWidth) > this.containerWidth) {
                left = parentWidth - this.containerWidth;
            }
        }
        return left;
    },

    cancel: function(e) {
        //
    }
};

/*
 * Required markup:
 * <ul class="top-toolbar">
 *  <li><a id="toolbar-item">Title</a><div class="toolbar-popup" id="toolbar-item-popup">hidden content</div></li>
 * </ul>
 */
var PopupToolbar = Class.create();
PopupToolbar.prototype = {
    initialize: function(options) {
        this.config = Object.extend({
            container: '.top-toolbar',
            item     : '> li > a',
            popup    : '.toolbar-popup',
            close    : '.btn-close',
            movePopupBelowContainer: 1
        }, options || {});

        this.container = $$(this.config.container)[0];
        this.items     = this.container.select(this.config.item);

        this.updateLayout();
        this.addObservers();
    },

    updateLayout: function() {
        var self = this;
        if (this.config.movePopupBelowContainer) {
            this.items.each(function(el) {
                var popup = $(el.id + '-popup');
                popup && self.container.insert({
                    after: popup
                });
            });
        }
    },

    addObservers: function() {
        var self = this;
        this.items.each(function(el) {
            var popup = $(el.id + '-popup');
            if (popup) {
                el.observe('click', function(e) {
                    e.stop();
                    self.toggle(popup);
                });
            }
        });

        $$(this.config.popup + ' ' + this.config.close).each(function(el) {
            el.observe('click', function(e) {
                e.stop();
                var popup = el.up('.toolbar-popup');
                popup && self.hide(popup);
            });
        });
    },

    toggle: function(el) {
        if (el.visible()) {
            this.hide(el);
        } else {
            this.show(el);
        }
    },

    show: function(el) {
        if (this.shown) {
            this.shown.hide();
        }
        this.shown = el;
        el.show();
        var form = el.select('form')[0];
        form && form.focusFirstElement();
    },

    hide: function(el) {
        el.hide();
    }
};

var Redirector = Class.create();
Redirector.prototype = {
    config: {
        url: window.location.href,
        query: {}
    },

    initialize: function(options) {
        Object.extend(this.config, options || {});

        var queryIndex = this.config.url.indexOf('?');
        if (queryIndex !== -1) {
            var queryArr = this.config.url.substr(queryIndex + 1).split('&'),
                query = {};
            queryArr.each(function(item) {
                var keyValue = item.split('=');
                query[keyValue[0]] = keyValue[1];
            });
            this.config.query = query;
            this.config.url = this.config.url.substr(0, queryIndex);
        }
    },

    redirect: function(params, reset) {
        reset = reset || false;

        if (!reset) {
            params = Object.extend(this.config.query, params);
        }
        var query = '';
        for (var key in params) {
            query += key + '=' + params[key] + '&';
        }
        if (query) {
            query = '?' + query.substr(0, query.length - 1);
        }

        window.location = this.config.url + query;
    }
};

var ExpandableList = Class.create();
ExpandableList.prototype = {
    initialize: function(options) {
        this.config = Object.extend({
            container: 'ul.expandable-list',
            item     : '> li',
            activeClass: 'current'
        }, options || {});

        this.container = $$(this.config.container)[0];
        this.items     = this.container.select(this.config.item);

        this.updateLayout();
        this.addObservers();
    },

    updateLayout: function() {
        this.container.addClassName('expandable-list');
        this.container.up().addClassName('expandable-wrapper');
        this.collapse();
    },

    addObservers: function() {
        var self = this;
        this.items.each(function(el) {
            el.observe('click', function(e) {
                var el = e.element();
                if (el.tagName != 'LI') {
                    el = el.up('li');
                }
                self.toggle(el);
            });
        });
    },

    toggle: function(el) {
        if (this.expanded) {
            this.collapse(el);
        } else {
            this.expand(el);
        }
    },

    expand: function() {
        this.expanded = true;
        this.container.addClassName('expanded');
        this.items.invoke('show');
    },

    collapse: function(el) {
        this.expanded = false;
        this.container.removeClassName('expanded');
        if (!el) {
            el = this.container.select('.' + this.config.activeClass)[0];
            if (!el) {
                el = this.items[0];
            }
        }
        this.activate(el);
    },

    activate: function(el) {
        this.items.invoke('removeClassName', this.config.activeClass);
        this.items.invoke('hide');
        el.addClassName(this.config.activeClass);
        el.show();
    }
};

var BlockToggler = Class.create();
BlockToggler.prototype = {
    initialize: function(options) {
        this.config = Object.extend({
            block   : '.block',
            header  : ' > .block-title',
            content : ' > .block-content',
            duration: 0.2,
            state   : 'collapsed',
            headerToggler: 1,
            useEffect: true,
            maxWidth: 0
        }, options || {});

        this.updateLayout();
        this.addObservers();

        var self = this;
        if ('collapsed' === this.config.state) {
            $$(this.config.block).each(function(el) {
                self.collapse(el);
            });
        } else {
            $$(this.config.block).each(function(el) {
                self.expand(el);
            });
        }
    },

    updateLayout: function() {
        $$(this.config.block + this.config.header).each(function(el) {
            el.addClassName('block-toggler');
            el.setStyle({
                position: 'relative'
            });
            el.insert({
                bottom: '<a href="#" class="toggle"></a>'
            });
        });
    },

    addObservers: function() {
        var self = this;
        $$(this.config.block + this.config.header + ' .toggle').each(function(el) {
            el.observe('click', function(e) {
                e.stop();
                self.toggle(el.up(self.config.block));
            })
        });
        if (this.config.headerToggler) {
            $$(this.config.block + this.config.header).each(function(el) {
                el.observe('click', function(e) {
                    e.stop();
                    self.toggle(el.up(self.config.block));
                })
            });
        }
    },

    toggle: function(el) {
        if (document.viewport.getWidth() > this.config.maxWidth) {
            return;
        }
        if (el.hasClassName('collapsed')) {
            this.expand(el);
        } else {
            this.collapse(el);
        }
    },

    collapse: function(el) {
        el.addClassName('collapsed');
        // el.up(self.config.header).removeClassName('active');
        if (this.config.useEffect) {
            new Effect.BlindUp(el.down(this.config.content), {
                duration: this.config.duration
            });
        }
    },

    expand: function(el) {
        el.removeClassName('collapsed');
        // el.up(self.config.header).addClassName('active');
        if (this.config.useEffect) {
            new Effect.BlindDown(el.down(this.config.content), {
                duration: this.config.duration
            });
        }
    }
};

/* Make easyslider works good on the mobile devices with various screen width */
var EasysliderMobile = Class.create();
EasysliderMobile.prototype = {
    initialize: function(slider) {
        this.slider = slider;

        var self = this;
        setTimeout(function() {
            self.updateSize();
        }, 500);

    },

    updateSize: function() {
        var slider = this.slider,
            ratio  = parseInt(slider.scroller.getStyle('width')) / parseInt(slider.scroller.getStyle('height')),
            width  = slider.wrapper.up().getWidth();

        var padding = parseFloat(slider.wrapper.getStyle('padding-left'))
            + parseFloat(slider.wrapper.getStyle('padding-right'));

        width -= padding;

        var clone = new Image();
        clone.src = slider.sections[0].down('img').src;
        originalWidth = clone.width;
        if (!originalWidth) {
            originalWidth = parseInt(slider.sections[0].getStyle('width'));
        }

        width  = (width > originalWidth ? originalWidth : width);
        height = width / ratio;

        slider.sectionWidth = width;
        slider.wrapper.setStyle({
            width: width + 'px',
            height: height + 'px'
        });
        slider.scroller.setStyle({
            width: width + 'px',
            height: height + 'px'
        });
        slider.sections.each(function(el) {
            el.setStyle({
                width: width + 'px',
                height: height + 'px'
            });
            el.down('img').setStyle({
                width: width + 'px'
            });
        });
    }
};

/* Don't forget about screen orientation change */
var mobileSliders = [];
document.observe("easyslide:init", function(event) {
    mobileSliders.push(new EasysliderMobile(event.memo.slider));
});

if ('addEventListener' in window) {
    var supportsOrientationChange = "onorientationchange" in window,
        orientationEvent = supportsOrientationChange ? "orientationchange" : "resize";
    window.addEventListener(orientationEvent, function() {
        setTimeout(function() {
            mobileSliders.each(function(mobileSlider) {
                mobileSlider.updateSize();
            });
        }, 500);
    }, false);
}
