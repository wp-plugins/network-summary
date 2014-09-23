(function ($) {
    function RssBuilder() {
        var $feed = $('#custom-feed-input');
        var categories = [];

        $('.category').each(function () {
            var sites = [];
            $(this).find('.site-checkbox').each(function (index, element) {
                sites.push(
                    {
                        id: parseInt($(this).data('site')),
                        subscribed: function () {
                            return element.checked;
                        }
                    }
                );
            });

            categories.push(
                {
                    id: parseInt($(this).find('.category-checkbox').data('category')),
                    element: $(this).find('.category-checkbox')[0],
                    subscribed: function () {
                        return this.element.checked;
                    },
                    sites: sites
                }
            );
        });

        var sites = [];
        $('#no-category').find('.site-checkbox').each(function (index, element) {
            sites.push(
                {
                    id: parseInt($(this).data('site')),
                    subscribed: function () {
                        return element.checked;
                    }
                }
            );
        });

        categories.push(
            {
                id: null,
                element: null,
                subscribed: function () {
                    return false
                },
                sites: sites
            }
        );

        this.buildFeed = function () {
            var url = $feed.data('base-url');
            var c = [];
            var s = [];
            $.each(categories, function (index, category) {
                if (category.subscribed()) {
                    c.push(category.id);
                } else {
                    $.each(category.sites, function (index, site) {
                        if (site.subscribed() && s.indexOf(site.id) == -1) {
                            s.push(site.id);
                        }
                    });
                }
            });
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

            if (c.length > 0 || s.length > 0) {
                url += '?';
            }
            if (c.length == 1) {
                url += 'category=' + c[0];
            }
            if (c.length > 1) {
                url += serialize(c, 'category');
            }
            if (s.length > 0) {
                if (c.length > 0) {
                    url += '&';
                }
                url += serialize(s, 'sites');
            }
            $feed.val(url);
        };

        this.validate = function (feed) {
            var base = new RegExp($feed.data('base-url'));
            var query = /(\?((category=[0-9]+&?)|(category%5B[0-9]+%5D=[0-9]+&?)+|sites%5B[0-9]+%5D=[0-9]+&?)*)?/;
            var regex = new RegExp(base.source + query.source, 'g');
            return feed.match(regex);
        };

        this.parse = function (feed) {
            var params = decodeURI(feed.substring(feed.search(/\?/) + 1));

            var c = params.match(/category(\[[^\]*]\])?=\d+/g);
            var s = params.match(/sites(\[[^\]*]\])?=\d+/g);
            var result = {
                categories: [],
                sites: []
            };
            if (c != null) {
                for (var i = 0; i < c.length; i++) {
                    result.categories.push(parseInt(c[i].substr(c[i].search(/=/) + 1)));
                }
            }
            if (s != null) {
                for (i = 0; i < s.length; i++) {
                    result.sites.push(parseInt(s[i].substr(s[i].search(/=/) + 1)));
                }
            }
            return result;
        };
    }

    var rssBuilder = new RssBuilder();

    function buildFeed() {
        rssBuilder.buildFeed();
        $('.rss-message.valid').hide();
        $('.rss-message.error').hide();
    }

    $('.select-all').click(function () {
        $(this).closest('.category, #no-category').find('input.site-checkbox').prop('checked', true).trigger('change');
    });

    $('.deselect-all').click(function () {
        $(this).closest('.category, #no-category').find('input.site-checkbox').prop('checked', false).trigger('change');
    });

    $('.category-checkbox').change(function () {
        var status = this.checked;
        var $elements = $(this).closest('.category').find('input.site-checkbox');
        //$(this).closest('.category').find('.sites').hide(100);
        $elements.prop('disabled', status).prop('checked', status);
        buildFeed();
        $elements.each(function (index, element) {
            var id = $(element).data('site');
            $('.site-checkbox[data-site=' + id + ']').not(':disabled').prop('checked', status);
        });
    });

    $('.site-checkbox').change(function () {
        var id = $(this).data('site');
        $('.site-checkbox[data-site=' + id + ']').not(':disabled').prop('checked', this.checked);
        buildFeed();
    });

    $('#custom-feed-reset').click(function () {
        $('.category-checkbox, .site-checkbox').prop('checked', false).trigger('change');
        buildFeed();
    });

    $('#custom-feed-input').change(function () {
        var feed = $(this).val();
        if (rssBuilder.validate(feed)) {
            $('.rss-message.error').hide();
            $('.rss-message.valid').show();

            var result = rssBuilder.parse(feed);

            $('.category-checkbox').each(function () {
                if (result.categories.indexOf(parseInt($(this).data('category'))) > -1) {
                    $(this).prop('checked', true).trigger('change');
                }
            });

            $('.site-checkbox').each(function () {
                if (result.sites.indexOf(parseInt($(this).data('site'))) > -1) {
                    $(this).prop('checked', true);
                }
            });
        } else {
            $('.rss-message.valid').hide();
            $('.rss-message.error').show();
        }
    });
})(jQuery);