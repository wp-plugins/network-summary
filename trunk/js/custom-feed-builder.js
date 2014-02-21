(function ($) {
    function RssBuilder($element) {
        var baseUrl = $element.data('base-url');
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
                if (categories.length > 0) {
                    url += '&';
                } else {
                    url += '?';
                }
                url += serialize(sites, 'sites');
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

        this.reset = function () {
            categories = [];
            sites = [];
            this.buildFeed();
        };

        this.validate = function (feed) {
            return feed.match(/^http:\/\/magicjudges\.aleaiactaest\.ch\/feed\/rss2-network\/(\?((category=[0-9]+&?)|(category%5B[0-9]+%5D=[0-9]+&?)+|sites%5B[0-9]+%5D=[0-9]+&?)*)?$/);
        };

        this.parse = function (feed) {
            this.reset();
            var params = decodeURI(feed.substring(feed.search(/\?/) + 1));

            var c = params.match(/category(\[[^\]*]\])?=\d+/g);
            var s = params.match(/sites(\[[^\]*]\])?=\d+/g);
            if (c != null) {
                for (var i = 0; i < c.length; i++) {
                    if (c[i] !== null) {
                        categories.push(parseInt(c[i].substr(c[i].search(/=/) + 1)));
                    }
                }
            }
            if (s != null) {
                for (i = 0; i < s.length; i++) {
                    sites.push(parseInt(s[i].substr(s[i].search(/=/) + 1)));
                }
            }
            this.buildFeed();
        };

        this.getCategories = function () {
            return categories;
        };

        this.getSites = function () {
            return sites;
        };
    }

    var $feed = $('#custom-feed-input');
    var rssBuilder = new RssBuilder($feed);

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
        $('.rss-message.error').hide();
        $('.rss-message.valid').hide();
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
        $('.rss-message.error').hide();
        $('.rss-message.valid').hide();
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
        $('.rss-message.error').hide();
        $('.rss-message.valid').hide();
    });

    $('#custom-feed-reset').click(function () {
        $('.site-checkbox').prop('checked', false);
        rssBuilder.reset();
        $('.rss-message.error').hide();
        $('.rss-message.valid').hide();
    });

    $feed.change(function () {
        var feed = $feed.val();
        if (rssBuilder.validate(feed)) {
            $('.rss-message.error').hide();
            $('.rss-message.valid').show();

            rssBuilder.parse(feed);

            $('.site-checkbox').each(function () {
                if (rssBuilder.getCategories().indexOf(parseInt($(this).data('category'))) > -1 ||
                    rssBuilder.getSites().indexOf(parseInt($(this).data('site'))) > -1) {
                    $(this).prop('checked', true);
                }
            });
        } else {
            $('.rss-message.valid').hide();
            $('.rss-message.error').show();
        }
    });
})(jQuery);