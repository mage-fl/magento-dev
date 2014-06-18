/* <!-- AjaxPro --> */

AjaxPro.toolbar = function(){

    var _currentPage, _totalNum, _limit, _url;

    return {
        getTotal: function(){
            return _totalNum;
        },
        setTotal: function(total){
            _totalNum = total;
            return AjaxPro.toolbar;
        },
        getLimit: function(){
            return _limit;
        },
        setLimit: function(limit){
            _limit = limit;
            return AjaxPro.toolbar;
        },
        getPage: function(){
            return _currentPage;
        },
        setPage: function(page){
            _currentPage = page;
            return AjaxPro.toolbar;
        },
        getUrl: function(){
            return _url;
        },
        setUrl: function(url){
            _url = url;
            return AjaxPro.toolbar;
        },
        isEnd: function () {
            if ( _totalNum <= _limit * _currentPage) {
                return true;
            }
            return false;
        },
        request: function() {
            if (AjaxPro.message.visible()) {
                return;
            }
            if (AjaxPro.toolbar.isEnd()) {
                return;
            }

            if ("object" === typeof ajaxlayerednavigation) {
                var params = {};
                window.location.hash.substr(1).split("&").each(function(arg){
                    arg = arg.split('=');
                    if (arg[0] && arg[1]) {
                        params[arg[0]] = arg[1];
                    }
                });
                AjaxPro.request({
                    'url' : _url.replace('.page.', _currentPage + 1),
                    parameters: params
                });
                return;
            }
            AjaxPro.request({
                'url' : _url.replace('.page.', _currentPage + 1)
            });
        },
        incCurrentPage: function() {
            _currentPage++;
        }
    };
}();

Event.observe(window, 'load', function() {
    // Check for possible page without tm/ajaxpro/catalog/category/init.phtml
    // @see /app/code/local/TM/AjaxPro/Model/Observer.php~534, allowedBlockNames
    if (!AjaxPro.toolbar.getTotal()) {
        return;
    }

    if ("scroll"  == AjaxPro.config.get('catalogCategoryView/type')) {

        Event.observe(window, 'scroll', function() {

            var scrollOffsets = document.viewport.getScrollOffsets(),
            dimensions = document.viewport.getDimensions();

            var currentTopPosition = scrollOffsets[1] + dimensions.height,
            elementTopPosition = $$('.toolbar-bottom').last().offsetTop;

            if (elementTopPosition > currentTopPosition || Ajax.activeRequestCount > 0) {

                return;
            }

            AjaxPro.toolbar.request();
        });

    } else {

        var title = Translator.translate('More Products');
        AjaxPro.toolbar.addButton = function() {

            if ($('ajaxpro-scrolling-button')) {
                return;
            }
            var toolbarBottom = $$('.toolbar-bottom').last();
            if (!toolbarBottom) {
                return;
            }

            toolbarBottom.insert({
                'before': '<button id="ajaxpro-scrolling-button" type="button" title="'+ title +'" class="button">'
                        + '<span><span>'+ title +'</span></span></button>'
            });

            if (AjaxPro.toolbar.isEnd()) {
                $('ajaxpro-scrolling-button').hide();
            }

            Event.observe($('ajaxpro-scrolling-button'), 'click', AjaxPro.toolbar.request);
            return true;
        };

        AjaxPro.toolbar.addButton();
        AjaxPro.observe('addObservers', AjaxPro.toolbar.addButton);
    }


    AjaxPro.toolbar.appendProductList = function(html) {
        $$('.pager .pages').invoke('hide');
        var el = $('ajaxpro-scrolling-button');
        if (!el) {
            el = $$('.toolbar-bottom').last();
        }
        if (el) {
            el.insert({'before': html});
            html.extractScripts().map(function(script) {
                return window.eval.defer(script);
            });

            AjaxPro.toolbar.incCurrentPage();
            if (AjaxPro.toolbar.isEnd() && $('ajaxpro-scrolling-button')) {
                $('ajaxpro-scrolling-button').hide();
            }
        }
    };

    AjaxPro.observe('onComplete:catalog:category:view', function(e) {
        var r = e.memo.response;
        if (!r.custom['product_list']) {
            return false;
        }
        AjaxPro.toolbar.appendProductList(r.custom['product_list']);
    });

    AjaxPro.observe('onComplete:catalogsearch:result:index', function(e) {
        var r = e.memo.response;
        if (!r.custom['product_list']) {
            return false;
        }
        AjaxPro.toolbar.appendProductList(r.custom['product_list']);
    });
});
