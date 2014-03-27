
// Redifine Underscore template parameters
//
_.templateSettings = {
    evaluate    : /<@([\s\S]+?)@>/g,
    interpolate : /<@=([\s\S]+?)@>/g,
    escape      : /<@-([\s\S]+?)@>/g
};

var replaceHtmlEntities = (
    function () {
        var translate_re = /&(nbsp|amp|quot|lt|gt);/g;
        var translate = {
            "nbsp": " ",
            "amp" : "&",
            "quot": "\"",
            "lt"  : "<",
            "gt"  : ">"
        };

        return function (s) {
            return (
                s.replace (
                    translate_re,
                    function (match, entity) {
                        return translate [entity];
                    })
            );
        }
    }
) ();

_.template_orig = _.template;
_.template = function () {
    arguments [0] = replaceHtmlEntities (arguments [0]);
    return _.template_orig.apply (_, arguments);
}

$.params = function (search) {
    var search = search || window.location.search;
    if (search) {
        return _.reduce (
            search.substring (1).split (/&/),
            function (res, keyval) {
                var key = keyval.substring (keyval.indexOf ('='), 0) || keyval;
                var val = keyval.indexOf ('=') > 0 ? keyval.substring (keyval.indexOf ('=') + 1) : true;
                var r = {};
                if (res [key]) {
                    if (_.isArray (res [key])) {
                        res [key].push (val);
                        return res;
                    } else {
                        r [key] = [ res [key], val ];
                    }
                } else {
                    r [key] = val;
                }
                return _.defaults (r, res);
            },
            {});
    } else {
        return {};
    }
}
