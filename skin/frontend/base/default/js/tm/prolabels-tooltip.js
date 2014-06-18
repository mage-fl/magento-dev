var ProLabelsTooltip = Class.create();
ProLabelsTooltip.prototype = {
    initialize: function(config) {
        this.background = config.background;
        this.textColor  = config.color;
        this.prepareMarkup();
    },

    prepareMarkup: function() {
        var self = this,
            tooltipMargin = '',
            labelWidth = '',
            aHoverCss = '.tt-wrapper li a:hover span.tooltip-label{background-color:'+self.background+';}',
            spanAfterCss = '.tt-wrapper li a span.tooltip-label:after{border-top: 9px solid '+self.background+';}',
            tooltipStyle = aHoverCss + spanAfterCss,
            tooltips = $$('ul.prolabels-content-labels li span.tooltip-label');

        style=document.createElement('style');

        if (style.styleSheet) {
            style.styleSheet.cssText=tooltipStyle;
        } else {
            style.appendChild(document.createTextNode(tooltipStyle));
        }

        document.getElementsByTagName('head')[0].appendChild(style);

        tooltips.each(function(tooltip){
            labelWidth = 60;
            tooltipMargin = '-' + labelWidth + 'px';
            tooltip.setStyle({
                marginLeft      : tooltipMargin,
                backgroundColor : self.background,
                color           : self.textColor
            });
        });
    }
};
