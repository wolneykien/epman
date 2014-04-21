/**
  * Globals
  */
var i18n = {};
var restOptions = {
    restRoot : "/",
    restParams : {},
};
var templates = {};

function getUrl(urlBase, urlParams, id) {
    var url = urlBase;
    if (id) {
        url = url + "/" + id;
    }
    if (_.isEmpty(urlParams)) {
        return url;
    } else {
        return url + '?' + $.param(urlParams);
    }
}

function getTemplate(selector) {
    if (templates[selector]) {
        return templates[selector];
    } else {
        templates[selector] = _.template($(selector).html());
        return templates[selector];
    }
}

function logXHR(xhr) {
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
        this.configure(options);
    },

    configure : function (options) {
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
    }

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

var Dialog = Backbone.View.extend({

    $templateEl : null,
    modal : true,
    dialogClass : 'no-close',
    width : '48%',
    buttons : [],

    initialize : function (options) {
        _.extend(this, {
            buttons : [
                {
                    text : i18n["OK"],
                    click : _.partial(function (self) {
                        self.ok();
                        $(this).dialog ("close");
                    }, this),
                },
                {
                    text : i18n["Cancel"],
                    click : _.partial(function (self) {
                        self.cancel();
                        $(this).dialog ("close");
                    }, this),
                }
            ],
        }, _.pick(options || {},
                  "buttons",
                  "modal",
                  "dialogClass",
                  "width"));
        this.configure(options);
    },

    configure : function (options) {
    },

    open : function (options) {
        if (this.$templateEl) {
            return false;
        }

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
    },

    ok : function () {
    },

    cancel : function () {
    },

    close : function () {
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
    max : null,
    searchLimit : 10,
    template : null,
    searchlistTemplate : null,
    keyword : "",

    events : {
        "focus [role='keyword-input']" : function (e) {
            var $target = $(e.target);
            if ($target.hasClass("placeholder")) {
                $(e.target)
                    .html("")
                    .toggleClass("placeholder", false);
            }
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
            } else if ($target.html().match(/^.*(\n|<br>).*$/)) {
                $target.html($target.html().replace(/(\n|<br>)/, ""));
                this.select();
            } else {
                this.search($target.html());
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
                 e.key == "Spacebar") &&
                $(e.target).val() != "")
            {
                this.select($(e.target).val());
            }
        },
        "click [role='search-list']" : function (e) {
            if ($(e.target).val() && $(e.target).val() != "") {
                this.select($(e.target).val());
            }
        },
        "click [role='delete-button']" : function (e) {
            this.deleteItem($(e.currentTarget).parent().data("id"));
        },
    },

    initialize : function (options) {
        _.extend(this, _.pick(options || {},
            'selectedCollection',
            'searchCollection',
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
        }));
        this.$("[role='search']").toggle(!this.max || this.selectedCollection.length < this.max);
        this.$("[role='keyword-input']").blur();
        this.delegateEvents();
    },

    update : function () {
        this.$searchlist.toggleClass("loading", false);
        if (!this.searchCollection.isEmpty()) {
            this.$searchlist.html($(this.searchlistTemplate({
                collection : this.searchCollection.toJSON(),
                keyword : this.keyword,
            })).html());
            this.$searchlist.show();
        } else {
            this.$searchlist.empty();
            this.$searchlist.hide();
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

});
