(function ($) {
    function RssBuilder($element) {
        var baseUrl = $element.val();
        var categories = [];
        var sites = [];
        var output = $element;

        function serialize(obj, prefix) {
            var str = [];
            for (var p in obj) {
                var k = prefix ? prefix + "[" + p + "]" : p, v = obj[p];
                str.push(typeof v == "object" ?
                    serialize(v, k) :
                    encodeURIComponent(k) + "=" + encodeURIComponent(v));
            }
            return str.join("&");
        }

        this.buildFeed = function () {
            var url = baseUrl;

            if (categories.length == 1) {
                url += '?category=' + categories[0];
            }
            if (categories.length > 1) {
                url += '?' + serialize(categories, 'category');
            }
            if (sites.length > 0) {
                url += '?' + serialize(sites, 'sites');
            }
            output.val(url);
        };

        this.addCategory = function (category) {
            if (categories.indexOf(category) === -1) {
                categories.push(category);
            }
        };

        this.removeCategory = function (category) {
            var index = categories.indexOf(category);
            if (index > -1) {
                categories.splice(index, 1);
            }
        };

        this.addSite = function (site) {
            if (sites.indexOf(site) === -1) {
                sites.push(site);
            }
        };

        this.removeSite = function (site) {
            var index = sites.indexOf(site);
            if (index > -1) {
                sites.splice(index, 1);
            }
        };
    }

    var rssBuilder = new RssBuilder($('#custom-feed-input'));


    $('.select-all').click(function () {
        var $checkboxes = $(this).closest('.category').find('input.site-checkbox')
        $checkboxes.prop('checked', true);

        $checkboxes.each(function () {
            var siteId = parseInt($(this).data('site'));
            rssBuilder.removeSite(siteId);
        });

        var categoryId = parseInt($(this).data('category'));
        rssBuilder.addCategory(categoryId);
        rssBuilder.buildFeed();
    });

    $('.deselect-all').click(function () {
        var $checkboxes = $(this).closest('.category').find('input.site-checkbox');
        $checkboxes.prop('checked', false);

        $checkboxes.each(function () {
            var siteId = parseInt($(this).data('site'));
            rssBuilder.removeSite(siteId);
        });

        var categoryId = parseInt($(this).data('category'));
        rssBuilder.removeCategory(categoryId);

        rssBuilder.buildFeed();
    });

    $('.site-checkbox').change(function () {
        var categoryId = parseInt($(this).data('category'));
        var siteId = parseInt($(this).data('site'));
        var $category = $(this).closest('.category');

        if (this.checked) {
            if ($category.find('.site-checkbox').not(':checked').length === 0) {
                $category.find('.site-checkbox').each(function () {
                    var siteId = parseInt($(this).data('site'));
                    rssBuilder.removeSite(siteId);
                });
                rssBuilder.addCategory(categoryId);
            } else {
                rssBuilder.addSite(siteId);
            }
        } else {
            rssBuilder.removeCategory(categoryId);
            $category.find('.site-checkbox:checked').each(function () {
                var siteId = parseInt($(this).data('site'));
                rssBuilder.addSite(siteId);
            });
            rssBuilder.removeSite(siteId);
        }
        rssBuilder.buildFeed();
    });
})(jQuery);