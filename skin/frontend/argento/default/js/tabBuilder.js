/**
 * Do not remove or change this notice.
 * TabBuilder - Prototype and Scriptaculous tabs plug-in
 * Copyright (c) 2008 - 2009 Templates Master www.templates-master.com
 *
 * @author Templates Master www.templates-master.com
 * @version 1.1
 */

var TabBuilder = Class.create();
TabBuilder.prototype = {
    config:
    {
        effect: 'none',
        duration: 300,
        tabContainer: '.tab-container',
        tab: '.block-highlight'
    },

    initialize: function(settings)
    {
        Object.extend(this.config, settings);
        $$(this.config.tabContainer).each(function(el) {
            if ($(el).select(this.config.tab).length) {
                this.buildTabs(el)
                    .setActiveTab(el, 0)
                    .addObservers(el);
            }
        }.bind(this));
    },

    buildTabs: function(container)
    {
        var tabs = new Element('ol').addClassName('tabs'),
            tabsContent = new Element('div').addClassName('content');

        container.insert({'bottom': tabs});
        tabs.insert({'after': tabsContent});

        $(container).select(this.config.tab).each(function(el) {
            var tab = $(el).select('.block-title')[0].wrap('li');
            tabs.insert({'bottom': tab});

            var tabContent = $(el).select('.block-content')[0];
            tabContent.removeClassName('block-content').addClassName('tab');
            tabsContent.insert({'bottom': tabContent});
            $(el).remove();
        });
        tabs.down('li:first-child').addClassName('first');
        tabs.down('li:last-child').addClassName('last');
        return this;
    },

    setActiveTab: function(container, index)
    {
        this._switchTabDisplay(container, index);
        return this;
    },

    addObservers: function(container)
    {
        var that = this;
        $(container).select('.tabs li').each(function(el, index) {
            el.observe('click', function() {
                that.setActiveTab(container, index);
            });
            el.observe('mouseover', function(el) {
                $(this).addClassName('over');
            });
            el.observe('mouseout', function(el) {
                $(this).removeClassName('over');
            });
        });
        return this;
    },

    _switchTabDisplay: function(container, index)
    {
        $(container).select('.tabs li, .content .tab').invoke('removeClassName', 'active');
        $(container).select('.tabs li')[index].addClassName('active');
        $(container).select('.content .tab')[index].addClassName('active');
        $(container).select('.content .tab').invoke('setStyle', {'display': 'none'});
        $(container).select('.content .tab')[index].setStyle({'display': 'block'});
    }
};
