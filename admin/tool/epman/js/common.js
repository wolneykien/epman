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
        url = url.substr(0, match.index) + pathParams[match[1]] + url.substr(match.lastindex);
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
    if (templates[selector]) {
        return templates[selector];
    } else {
        templates[selector] = _.template($(selector).html());
        return templates[selector];
    }
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

    initialize : function (attrs, options) {
        options = _.defaults(options || {}, restOptions);
        if (options.restRoot) {
            this.urlBase = options.restRoot.replace(/\/$/, "") + this.urlBase;
        }
        _.extend(this.urlParams, options.restParams);
        this.configure(attrs, options);
    },

    configure : function (attrs, options) {
    },

    save : function (attrs, options) {
        options = _.defaults(options, {
            wait : true,
            error : function (model, xhr, options) {
                logXHR(xhr);
                (new RestErrorDialog()).open({ xhr : xhr });
            },
        });
        Backbone.Model.prototype.save.apply(this, [attrs, options]);
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
            this.onInput(e, $(e.target), $(e.target).val());
        },
        "change *" : function (e) {
            this.onChange(e, $(e.target), $(e.target).val());
        },
        "spin *" : function (e, spinner) {
            this.onChange(e, $(e.target), spinner.value);
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
                        self.ok();
                        $(this).dialog ("close");
                    }, this),
                },
                {
                    name : "cancel",
                    text : i18n["Cancel"],
                    click : _.partial(function (self) {
                        self.cancel();
                        $(this).dialog ("close");
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

    validate : function ($input, val) {
        this.undelegateEvents();
        val = this.fix($input, val);
        var valid = _.reduce(this.validations, function (valid, validator, selector) {
            var $element = this.$(selector);
            validator = _.bind(validator, this);
            var passed = validator($element.val(), $element, $input, val);
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

    fix : function ($input, val) {
        return val;
    },

    toggleValid : function ($element, flag) {
        $element.toggleClass("invalid", !flag);
    },

    onInput : function (e, $input, val) {
        if (!this.filterEvent(e, val)) {
            return false;
        }
        this.toggleButton("ok", this.validate($input, val));
    },

    onChange : function (e, $input, val) {
        if (!this.filterEvent(e, val)) {
            return false;
        }
        this.toggleButton("ok", this.validate($input, val));
    },

    onOpen : function () {
        this.toggleButton("ok", this.validate());
    },

    filterEvent : function (e, val) {
        if ($.data(e.target, "prevVal") == val) {
            return false;
        } else {
            $.data(e.target, "prevVal", val);
            return true;
        }
    },

});

var MessageDialog = Dialog.extend({

    initialize : function (options) {
        Dialog.prototype.initialize.apply(this, arguments);
        if (!_.isUndefined(options) && !_.isUndefined(options.template)) {
            this.template = options.template;
        }
        this.buttons = [
            {
                text : i18n["Close"],
                click : function (self) {
                    $(this).dialog ("close");
                },
            },
        ];
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
