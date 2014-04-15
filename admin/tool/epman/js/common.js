/**
  * Globals
  */
var i18n = {};
var restOptions = {
    restRoot : "/",
    restParams : {},
};

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

var Model = Backbone.Model.extend({

    urlBase : "/",
    urlParams : {},
    url : function () {
        if (this.collection) {
            return this.collection.url(this.id);
        } else {
            if (id) {
                return getUrl(this.urlBase, this.urlParams, this.id);
            } else {
                return null;
            }
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

    dialog : null,

    initialize : function (options) {
        _.extend(this, options || {});
        if (!this.dialogOptions) {
            this.dialogOptions = {};
        }
        this.configure(options);
    },

    configure : function (options) {
    },

    open : function (options) {
        if (this.dialog != null) {
            this.dialog.dialog("destroy");
            this.dialog = null;
        }

        this.render();

        var options = _.extend({}, this.dialogOptions, options || {}, { autoOpen : true });
        options = _.defaults(options, {
            modal : true,
            dialogClass : 'no-close',
            width : '48%',
            buttons : [
                {
                    text : i18n["OK"],
                    click : function () {
                        $(this).dialog ("close");
                    }
                },
                {
                    text : i18n["Cancel"],
                    click : function () {
                        $(this).dialog ("close");
                    }
                }
            ],
        });

        this.dialog = this.$el.find('.dialog').dialog(options);
    },

});

var MultiSelect = Backbone.View.extend({

    selectedCollection : null,
    searchCollection : null,
    max : null,
    searchLimit : 10,
    template : null,
    searchlistTemplate : null,
    keyword : "",

    events : {
        "input [role='keyword-input']" : function (e) {
            this.search($(e.target).val());
        },
        "keypress [role='keyword-input']" : function (e) {
            if ((e.keyCode ? e.keyCode : e.which) == 13) {
                this.select();
            }
        },
        "click [role='search-item']" : function (e) {
            this.select($(e.currentTarget).attrs()["data-id"]);
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
        if (!this.max || this.selectedCollection.length < this.max) {
            this.search(this.keyword);
        } else {
            this.search("");
        }
    },

    configure : function (options) {
    },

    render : function () {
        this.undelegateEvents();
        this.$el.html(this.template({
            collection : this.selectedCollection.toJSON(),
        }));
        this.$("[role='search']").toggle(!this.max || this.selectedCollection.length < this.max);
        this.$searchlist = this.$("[role='search-list']");
        var input = this.$("[role='keyword-input']");
        input.val(this.keyword);
        this.delegateEvents();
    },

    update : function () {
        this.$searchlist.toggleClass("loading", false);
        if (!this.searchCollection.isEmpty()) {            
            this.$searchlist.html(this.searchlistTemplate({
                collection : this.searchCollection.toJSON(),
                keyword : this.keyword,
            }));
        } else {
            this.$searchlist.hide();
            this.$searchlist.empty();
        }
    },

    search : function (keyword) {
        if (!keyword) {
            keyword = "";
        }
        this.keyword = keyword;
        if (keyword.length > 0) {
            this.searchCollection.urlParams.search = keyword;
            this.searchCollection.urlParams.limit = this.searchLimit;
            this.$searchlist.toggleClass("loading", true);
            this.$searchlist.show();
            this.searchCollection.fetch({ reset : true });
        } else {
            this.searchCollection.reset();
        }
    },

    select : function (id) {
        if (!this.searchCollection.isEmpty()) {
            var selected = id ? this.searchCollection.get(id) : this.searchCollection.first();
            if (selected) {
                this.search("");
                this.trigger("select", selected);
                this.selectedCollection.add([selected]);
            }
        }
    },

});
