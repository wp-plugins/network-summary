(function ($) {
    var categories = [];
    var sites = [];
    var base_url = $('#custom-feed-input').val();

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

    function addCategory(categoryId) {
        if (categories.indexOf(categoryId) === -1) {
            $('#category-' + categoryId).find('.site-id').each(function () {
                var siteId = parseInt($(this).prev('.site-id').val());
                removeSite(siteId);
            });
            categories.push(categoryId);
        }
    }

    function addSite(siteId) {
        if (sites.indexOf(siteId) === -1) {
            sites.push(siteId);
        }
    }

    function removeCategory(categoryId) {
        var index = categories.indexOf(categoryId);
        if (index > -1) {
            categories.splice(index, 1);
        }
        $('#category-' + categoryId).find('.site-checkbox:checked').each(function () {
            var siteId = parseInt($(this).prev('.site-id').val());
            addSite(siteId);
        });
    }

    function removeSite(siteId) {
        var index = sites.indexOf(siteId);
        if (index > -1) {
            sites.splice(index, 1);
        }
    }

    $('.select-all').click(function () {
        $(this).closest('.category').find('input.site-checkbox').prop('checked', true);
        var categoryId = parseInt($(this).closest('.category').find('.category-id').val());
        addCategory(categoryId);
        buildFeed();
    });

    $('.deselect-all').click(function () {
        $(this).closest('.category').find('input.site-checkbox').prop('checked', false);
        var categoryId = parseInt($(this).closest('.category').find('.category-id').val());
        removeCategory(categoryId);
        $(this).closest('.category').find('.site-id').each(function () {
            var siteId = parseInt($(this).val());
            removeSite(siteId);
        });
        buildFeed();
    });

    function buildFeed() {
        var url = base_url;
        if (categories.length == 1) {
            url += '?category=' + categories[0];
        }
        if (categories.length > 1) {
            url += '?' + serialize(categories, 'category');
        }
        if (sites.length > 0) {
            url += '?' + serialize(sites, 'sites');
        }
        $('#custom-feed-input').val(url);
    }

    $('.site-checkbox').change(function () {
        var categoryId = parseInt($(this).closest('.category').find('.category-id').val());
        var siteId = parseInt($(this).prev('.site-id').val());
        if (this.checked) {
            if ($(this).closest('.category').find('.site-checkbox').not(':checked').length === 0) {
                addCategory(categoryId);
            } else {
                addSite(siteId);
            }
        } else {
            removeCategory(categoryId);
            removeSite(siteId);
        }
        buildFeed();
    });
})(jQuery);