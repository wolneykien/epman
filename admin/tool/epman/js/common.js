/**
  * Globals
  */
var user = {};
var i18n = {};
var restOptions = {
    restRoot : "/",
    restParams : {},
};
var templates = {};

function decline(key, arg) {
    var cases = i18n[key];
    if (_.isFunction(cases)) {
        return cases(arg);
    } else if (!_.isArray(cases)) {
        try {
            cases = JSON.parse(cases, function (k, v) {
                if (/^\/.+\/([gimy]+)?$/.test(v)) {
                    return RegExp.prototype.constructor.apply(this, _.rest(v.split('/')));
                } else {
                    return v;
                }
            });
        } catch (e) {
            cases = [ cases ];
        }
    }
    cases = _.reduce(cases, function (res, val) {
        if (val instanceof RegExp) {
            res.push({ regexp : val });
        } else if (res.length > 0 &&
                   !_.isUndefined(res[res.length - 1].regexp) &&
                   _.isUndefined(res[res.length - 1].val))
        {
            res[res.length - 1].val = val;
        } else {
            res.push({ regexp : /^.*$/, val : val });
        }
        return res;
    }, []);
    var f = function (arg) {
        arg = "" + arg;
        var val = _.find(cases, function (val) {
            return (val.regexp && val.regexp.test(arg));
        });
        if (!val && !_.isEmpty(cases)) {
            return arg.replace(/^.*$/, (_.last(cases)).val);
        } else {
            return arg.replace(val.regexp, val.val);
        }
    }

    i18n[key] = f;
    return f(arg);
}

function findAllMatches (pat, value) {
    if (value == null) {
        return [];
    }
    if (!(pat instanceof RegExp)) {
        pat = new RegExp(pat, "gi");
    }

    if (_.isString(value)) {
        var slices = [];
        var match = pat.exec(value);
        var pos = 0;
        while (match != null) {
            if (match.index > pos) {
                slices.push({
                    slice : value.substr(pos, match.index),
                    match : false,
                });
            }
            slices.push({
                slice : match[0],
                match : true,
            });
            pos = match.index + match[0].length;
            match = pat.exec(value);
        }
        if (pos < value.length) {
            slices.push({
                slice : value.substr(pos),
                match : false,
            });
        }
        _.extend(slices, {
            toString : function () {
                return _.reduce(this, function (res, slice) {
                    return res + slice.slice;
                }, "");
            },
            matching : _.filter(slices, function (slice) {
                return slice.match;
            }),
            noMatches : function () {
                return _.isEmpty(this.matching);
            },
            hasMatches : function () {
                return !_.isEmpty(this.matching);
            },
            format : function (emph) {
                if (!emph) {
                    emph = function (slice) {
                        return "<b>" + slice + "</b>";
                    };
                }
                return _.reduce(this, function (res, slice) {
                    return res + (slice.match ? emph(slice.slice) : slice.slice);
                }, "");
            },
        });

        return slices;

    } else if (_.isArray(value)) {
        return _.map(value, _.partial(findAllMatches, pat));
    } else if (_.isObject(value)) {
        return _.reduce(value, function (res, val, key) {
            res[key] = findAllMatches(pat, val);
            return res;
        }, {});
    } else {
        return findAllMatches(pat, "" + value);
    }
}

function getUrl (urlBase, urlParams, id) {
    var urlParams = _.extend({}, urlParams);
    var pathParams = {};
    var pat = /:([^:\/]+)/;
    var url = urlBase;
    var match = pat.exec(url);
    while (match != null) {
        pathParams[match[1]] = urlParams[match[1]] || pathParams[match[1]];
        urlParams = _.omit(urlParams, match[1]);
        var next = match.index + match[0].length;
        url = url.substr(0, match.index) + pathParams[match[1]] + (next < url.length ? url.substr(next) : "");
        match = pat.exec(url);
    }
    if (id) {
        url = url + "/" + id;
    }
    if (_.isEmpty(urlParams)) {
        return url;
    } else {
        return url + '?' + $.param(urlParams);
    }
}

function getTemplate (selector) {
    var template = templates[selector];
    if (!template) {
        template = _.template($(selector).html());
        templates[selector] = template;
    }
    _.each(_.rest(arguments), function (arg) {
        var fullselector = selector + " " + arg;
        if (!templates[fullselector]) {
            templates[fullselector] = _.template($(fullselector).html());
        }
    });

    return template;
}

function logXHR (xhr) {
    try {
        var jsonResp = JSON.parse(xhr.responseText);
        console.error(
            "" + xhr.status + ": " +
            (jsonResp.exception ? ("(" + jsonResp.exception + ") ") : "") +
            (jsonResp.message ? jsonResp.message : "(error message is unknown)")
        );
        if (jsonResp.debuginfo) {
            console.warn(jsonResp.debuginfo);
        }
    } catch (e) {
        console.error(xhr.responseText);
    }
}

function checkFooter () {
    var $main = $("#tool-epman");
    var $filterPanel = $main.find("[role='page-header']");
    var $footerPanel = $main.find("[role='page-footer']");
    if ($(window).scrollTop () > ($filterPanel.offset().top + $filterPanel.height())) {
        $footerPanel.css(
            { left : $filterPanel.offset().left,
              width : $filterPanel.width(),
            });
        $footerPanel.show();
    } else {
        $footerPanel.hide();
    }
}

function enableCheckFooter () {
    $(window).scroll(checkFooter);
    checkFooter();
}

function disableCheckFooter () {
    var $footerPanel = $("#tool-epman [role='page-footer']");
    $(window).scroll(function () {
        $footerPanel.hide();
    });
    $footerPanel.hide();
}

function getMarkers (sel) {
    return _.map($(sel).map(function (i, e) {
        var m = {};
        m[$(e).data("id") || i] = e.checked;
        return m;
    }), function (val) {
        return val;
    });
}

function allMarked (markers) {
    if (!markers) {
        return false;
    }
    if (!_.isArray(markers)) {
        markers = getMarkers(markers);
    }
    return _.every(markers, function (m) { return _.first(_.values(m)) });
}

function someMarked (markers) {
    if (!markers) {
        return false;
    }
    if (!_.isArray(markers)) {
        markers = getMarkers(markers);
    }
    return _.some(markers, function (m) { return _.first(_.values(m)) });
}


var Model = Backbone.Model.extend({

    urlBase : "/",
    urlParams : {},
    url : function () {
        if (this.collection) {
            return this.collection.url(this.id);
        } else {
            return getUrl(this.urlBase, this.urlParams, this.id);
        }
    },
    undo : undefined,

    initialize : function (attrs, options) {
        options = _.defaults(options || {}, restOptions);
        if (options.restRoot) {
            this.urlBase = options.restRoot.replace(/\/$/, "") + this.urlBase;
        }
        _.extend(this.urlParams, options.restParams);
        this.undo = {};
        this.configure(attrs, options);
    },

    configure : function (attrs, options) {
    },

    sync : function (method, model, options) {
        options = _.defaults(options || {}, { wait : true });
        var onerror = options.error;
        var onsuccess = options.success;
        _.extend(options, {
            error : function (xhr) {
                logXHR(xhr);
                (new RestErrorDialog()).open({ xhr : xhr });
                if (onerror) {
                    onerror.apply(this, arguments);
                }
            },
            success : function () {
                this.undo = {};
                if (onsuccess) {
                    onsuccess.apply(this, arguments);
                }
            },
        });
        return Backbone.sync.apply(this, [method, model, options]);
    },

    rollback : function (keys) {
        this.undo = this.undo || {};
        var attrs = {};
        keys = _.union(arguments);
        if (keys.length == 0) {
            keys = this.keys();
        }
        _.each(keys, function (key) {
            if (this.undo[key]) {
                var undoval = this.undo[key];
                delete this.undo[key];
                if (_.isFunction(undoval)) {
                    undoval();
                } else {
                    attrs[key] = undoval;
                }
            }
        }, this);
        if (!_.isEmpty(attrs)) {
            this.set(attrs);
        }
    },

    setRollback : function (rollbacks) {
        this.undo = _.extend(this.undo || {}, rollbacks);
    },

    toJSON : function (options) {
        var json = _.clone(this.attributes);
        _.each(json, function (val, key) {
            if (_.isObject(val) && _.isFunction(val.toJSON)) {
                json[key] = val.toJSON(options);
            }
        });
        _.extend(json, { undo : this.undo || {} });

        return json;
    },

});

var Collection = Backbone.Collection.extend({

    urlBase : "",
    urlParams : {},
    url: function (id) {
        return getUrl(this.urlBase, this.urlParams, id);
    },

    initialize : function (models, options) {
        options = _.defaults(options || {}, restOptions);
        if (options.restRoot) {
            this.urlBase = options.restRoot.replace(/\/$/, "") + this.urlBase;
        }
        _.extend(this.urlParams, options.restParams);
        this.configure(options);
    },

    configure : function (options) {
    },

    sync : function (method, model, options) {
        var onerror = options.error;
        options.error = undefined;
        options = _.defaults(options, {
            wait : true,
            error : function (xhr) {
                logXHR(xhr);
                (new RestErrorDialog()).open({ xhr : xhr });
                if (onerror) {
                    onerror.apply(this, arguments);
                }
            },
        });
      return Backbone.sync.apply(this, arguments);
    },

});

var View = Backbone.View.extend({

    initialize : function (options) {
        if (options.$el) {
            this.$el = options.$el;
        }
        this.configure(options);
        if (this.model) {
            this.listenTo(this.model, 'change', this.render);
            this.listenTo(this.model, 'request', this.onRequest);
            this.listenTo(this.model, 'sync', this.onSync);
            this.listenTo(this.model, 'error', this.onError);
        }
        if (this.collection) {
            this.listenTo(this.collection, 'reset', this.render);
            this.listenTo(this.collection, 'add', function (model) {
                this.render({ model : model, action : "add" });
            });
            this.listenTo(this.collection, 'remove', function (model) {
                this.render({ model : model, action : "remove" });
            });
            this.listenTo(this.collection, 'request', function (model, xhr, options) {
                if (model == this.collection) {
                    this.onRequest(model, xhr, options);
                }
            });
            this.listenTo(this.collection, 'sync', function (model, resp, options) {
                if (model == this.collection) {
                    this.onSync(model, resp, options);
                }
            });
            this.listenTo(this.collection, 'error', function (model, xhr, options) {
                if (model == this.collection) {
                    this.onError(model, xhr, options);
                }
            });
        }
    },

    configure : function (options) {
    },

    syncing : function (status) {        
        this.$el.toggleClass("loading", status);
    },

    onRequest : function(model, xhr, options) {
        this.syncing(true);
    },

    onSync : function(model, resp, options) {
        this.syncing(false);
    },

    onError : function(model, xhr, options) {
        this.syncing(false);
    },

});

var Dialog = Backbone.View.extend({

    $templateEl : null,
    modal : true,
    dialogClass : 'no-close',
    width : '48%',
    buttons : [],

    events : {
        "input *" : function (e) {
            this.onInput(e, $(e.target));
        },
        "change *" : function (e) {
            this.onChange(e, $(e.target));
        },
        "spin *" : function (e, spinner) {
            var $spinner = $(e.target);
            $spinner.spinner("value", spinner.value);
            this.onChange(e, $spinner);
            return false;
        },
    },

    validations : {},

    initialize : function (options) {
        _.extend(this, _.pick(options || {},
                  "modal",
                  "dialogClass",
                  "width"));
        if (options.buttons) {
            this.buttons = options.buttons;
        } else {
            this.buttons = [
                {
                    name : "ok",
                    text : i18n["OK"],
                    click : _.partial(function (self) {
                        if (self.ok() != false) {
                            $(this).dialog ("close");
                        }
                    }, this),
                },
                {
                    name : "cancel",
                    text : i18n["Cancel"],
                    click : _.partial(function (self) {
                        if (self.cancel() != false) {
                            $(this).dialog ("close");
                        }
                    }, this),
                }
            ];
        }
        if (options.validations) {
            _.extend(this.validations, options.validations);
        }
        this.configure(options);
    },

    configure : function (options) {
    },

    open : function (options) {
        if (this.$templateEl) {
            return false;
        }

        this.undelegateEvents();
        this.render(options);

        var options = _.extend({
            buttons : this.buttons,
            modal : this.modal,
            dialogClass : this.dialogClass,
            width : this.width,
        }, options || {}, {
            autoOpen : true,
            close : _.partial(function (self) {
                self.$el = self.$templateEl;
                self.$templateEl = null;
                self.close();
            }, this),
        });
        this.$templateEl = this.$el;
        this.$el = this.$el.find('.dialog').dialog(options);
        this.onOpen();
        this.delegateEvents();
    },

    ok : function () {
    },

    cancel : function () {
    },

    close : function () {
    },

    toggleButton : function (id, flag) {
        var $btn = this.$el.parent().find("[role='button'][name='" + id + "']");
        if (flag) {
            $btn.button("enable");
        } else {
            $btn.button("disable");
        }
    },

    validate : function ($input) {
        this.undelegateEvents();
        _.each(this.fix($input), function ($el) {
            if ($el.size() > 0) {
                this.updateValue($el[0], $el.val());
            }
        }, this);
        var valid = _.reduce(this.validations, function (valid, validator, selector) {
            var $element = this.$(selector);
            validator = _.bind(validator, this);
            var passed = validator($element.val(), $element, $input);
            this.toggleValid($element, passed);
            if (!passed) {
                return false;
            } else {
                return valid;
            }
        }, true, this);
        this.delegateEvents();
        return valid;
    },

    fix : function ($input) {
        return [];
    },

    toggleValid : function ($element, flag) {
        $element.toggleClass("invalid", !flag);
    },

    onInput : function (e, $input) {
        if (!this.filterEvent(e)) {
            return false;
        }
        this.toggleButton("ok", this.validate($input));
    },

    onChange : function (e, $input) {
        if (!this.filterEvent(e)) {
            return false;
        }
        this.toggleButton("ok", this.validate($input));
    },

    onOpen : function () {
        this.toggleButton("ok", this.validate());
    },

    filterEvent : function (e) {
        var val = $(e.target).val();
        if ($.data(e.target, "prevVal") == val) {
            return false;
        } else {
            this.updateValue(e.target, val);
            return true;
        }
    },

    updateValue : function (el, val) {
        $.data(el, "prevVal", val);
    },

});

var MessageDialog = Dialog.extend({

    initialize : function (options) {
        options = _.defaults(options || {}, {
            buttons : [
                {
                    text : i18n["Close"],
                    click : _.partial(function (self) {
                        $(this).dialog ("close");
                    }, this),
                },
            ],
            template : getTemplate("#message-dialog-template"),
        });
        Dialog.prototype.initialize.apply(this, arguments);
        this.template = options.template;
    },

    render : function (options) {
        this.$el.html(this.template(options));
    },
    
});

var ErrorDialog = MessageDialog.extend({

    initialize : function (options) {
        var options = _.defaults(options || {}, {
            template : getTemplate("#error-dialog-template"),
        });
        MessageDialog.prototype.initialize.apply(this, [options]);
    },
    
});

var YesNoDialog = MessageDialog.extend({

    initialize : function (options) {
        var options = _.defaults(options || {}, {
            buttons : [
                {
                    text : i18n["Yes"],
                    click : _.partial(function (self) {
                        if (self.yes() != false) {
                            $(this).dialog ("close");
                        }
                    }, this),
                },
                {
                    text : i18n["No"],
                    click : _.partial(function (self) {
                        if (self.no() != false) {
                            $(this).dialog ("close");
                        }
                    }, this),
                },
            ],
        });
        if (options.yes) {
            if (_.isFunction(options.yes)) {
                this.yes = options.yes;
            } else if (_.isObject(yes)) {
                var keys = _.keys(yes);
                if (keys.length > 0 && _.isFunction(yes[keys[0]])) {
                    options.buttons[0].text = keys[0];
                    this.yes = yes[keys[0]];
                }
            }
        }
        if (options.no) {
            if (_.isFunction(options.no)) {
                this.no = options.no;
            } else if (_.isObject(no)) {
                var keys = _.keys(no);
                if (keys.length > 0 && _.isFunction(no[keys[0]])) {
                    options.buttons[0].text = keys[0];
                    this.no = no[keys[0]];
                }
            }
        }
        MessageDialog.prototype.initialize.apply(this, [options]);
    },

    render : function (options) {
        MessageDialog.prototype.render.apply(this, [_.defaults(options, {
            title : i18n["Confirmation"],
        })]);
    },

    yes : function () {
    },

    no : function () {
    },
    
});

var RestErrorDialog = ErrorDialog.extend({

    render : function (options) {
        if (options.xhr && !options.message) {
            try {
                var jsonResp = JSON.parse(options.xhr.responseText);
                options = _.extend({}, options, jsonResp);
            } catch (e) {
                options = _.extend({}, options, { message : options.xhr.responseText });
            }
        }
        return ErrorDialog.prototype.render.apply(this, [options]);
    },

});

function addCompletion(input, template, data) {
    var popup = $(template(data || {}));
    popup.appendTo($(input).parent());
    var origin = $(input).offset();
    origin.top = origin.top + $(input).height();
    var position = function (offX, offY) {
        popup.position({
            of : $(input),
            my : "left top",
            at : "left bottom",
            using : function (offs) {
                popup.css({
                    left : (offs.left - (offX || 0) + "px"),
                    top :  (offs.top - (offY || 0) + "px")
                });
            },
        });
        popup.width($(input).width());
    };
    position($(window).scrollLeft(), $(window).scrollTop());
    $(window).scroll(position);
    $(input).resize(position);

    return popup;
}

var MultiSelect = Backbone.View.extend({

    selectedCollection : null,
    searchCollection : null,
    defValue : null,
    max : null,
    searchLimit : 10,
    template : null,
    searchlistTemplate : null,
    keyword : "",

    events : {
        "click [role='placeholder']" : function (e) {
            this.$("[role='keyword-input']")[0].focus();
        },
        "click [role='multiselect-box']" : function (e) {
            this.$("[role='keyword-input']")[0].focus();
        },
        "blur [role='keyword-input']" : function (e) {
            if ($(e.target).html() == '') {
                this.render();
            }
        },
        "input [role='keyword-input']" : function (e) {
            var $target = $(e.target);
            if ($target.html() == '') {
                this.render();
            } else {
                this.$("[role='placeholder']").hide();
                if ($target.html().match(/^.*(\n|<br>).*$/)) {
                    $target.html($target.html().replace(/(\n|<br>)/, ""));
                    this.select();
                } else {
                    this.search($target.html());
                }
            }
        },
        "keypress [role='keyword-input']" : function (e) {
            if ((e.keyCode ? e.keyCode : e.which) == 13) {
                this.select();
            } else if ((e.keyCode ? e.keyCode : e.which) == 8 && $(e.target).html() == '') {
                this.render();
            }
        },
        "keypress [role='search-list']" : function (e) {
            if (((e.keyCode ? e.keyCode : e.which) == 13 ||
                 (e.keyCode ? e.keyCode : e.which) == 0 ||
                 e.key == "Spacebar"))
            {
                this.select();
            }
        },
        "click [role='search-list-item']" : function (e) {
            this.select($(e.target).data("id"));
        },
        "click [role='delete-button']" : function (e) {
            this.deleteItem($(e.currentTarget).parent().data("id"));
        },
    },

    initialize : function (options) {
        _.extend(this, _.pick(options || {},
            'selectedCollection',
            'searchCollection',
            'defValue',
            'max',
            'searchLimit',
            'template',
            'searchlistTemplate',
            'keyword',
            '$el'
        ));
        this.configure(options);
        this.listenTo(this.selectedCollection, "reset", this.render);
        this.listenTo(this.selectedCollection, "add", this.render);
        this.listenTo(this.selectedCollection, "remove", this.render);
        this.listenTo(this.searchCollection, "reset", this.update);
        this.listenTo(this.searchCollection, "add", this.update);
        this.listenTo(this.searchCollection, "remove", this.update);
        this.render();
    },

    configure : function (options) {
    },

    render : function () {
        this.undelegateEvents();
        this.$searchlist = null;
        this.$el.html(this.template({
            collection : this.selectedCollection.toJSON(),
            defValue : this.defValue,
            max : this.max,
        }));
        this.$("[role='search']").toggle(!this.max || this.selectedCollection.length < this.max);
        this.$("[role='keyword-input']").blur();
        this.delegateEvents();
    },

    update : function () {
        if (this.$searchlist) {
            this.$searchlist.toggleClass("loading", false);
            if (!this.searchCollection.isEmpty()) {
                this.$searchlist.html($(this.searchlistTemplate({
                    collection : this.formatResults(this.keyword, this.searchCollection),
                    keyword : this.keyword,
                    defValue : this.defValue,
                    max : this.max,
                })).html());
                this.$searchlist.show();
            } else {
                this.$searchlist.empty();
                this.$searchlist.hide();
            }
        }
    },

    search : function (keyword) {
        if (!keyword) {
            keyword = "";
        }
        this.keyword = keyword;
        if (keyword.length > 0) {
            this.searchCollection.urlParams.like = keyword;
            this.searchCollection.urlParams.limit = this.searchLimit;
            if (!this.$searchlist) {
                this.$searchlist = addCompletion(this.$("[role='multiselect-box']"), this.searchlistTemplate, { collection : [], keyword : "" });
            }
            this.$searchlist.toggleClass("loading", true);
            this.$searchlist.show();
            this.searchCollection.fetch({ reset : true });
        } else {
            this.render();
        }
    },

    select : function (id) {
        if (!this.searchCollection.isEmpty()) {
            var selected = id ? this.searchCollection.get(id) : this.searchCollection.first();
            if (selected) {
                this.trigger("select", selected);
                this.selectedCollection.add([selected]);
            }
        }
    },

    deleteItem : function (id) {
        if (id) {
            this.selectedCollection.remove(this.selectedCollection.get(id));
        }
    },

    formatResults : function (keyword, collection) {
        return findAllMatches(keyword, collection.toJSON());
    },

});
