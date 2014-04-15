
var openDialog = function (el, template, data, options) {
    if (dialogs['el'] != null) {
        dialogs['el'].dialog("destroy");
        dialogs['el'] = null;
    }

    var $el = $(el);
    $el.html(template(data));

    var options = _.extend({}, options, { autoOpen : true });
    options = _.defaults({
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

    dialogs['el'] = $el.find('.dialog').dialog(options);
}

var MultiSelect = Backbone.View.extend({

    selectedCollection : null,
    searchCollection : null,
    selectedLimit : null,
    searchLimit : 10,
    template : null,
    searchlistTemplate : null,
    keyword : "",

    events : {
        "change [role='keyword-input']" : function (e) {
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
        _.extend(this, options);
        if (!options.$overlay && this.overlay) {
            this.$overlay = $(this.overlay);
        } else {
            this.$overlay = options.$overlay;
        }
        this.listenTo(this.selectedCollection, "reset", this.render);
        this.listenTo(this.selectedCollection, "add", this.render);
        this.listenTo(this.selectedCollection, "remove", this.render);
        this.listenTo(this.searchCollection, "reset", this.update);
        this.listenTo(this.searchCollection, "add", this.update);
        this.listenTo(this.searchCollection, "remove", this.update);
        this.render();
        this.search(this.keyword);
    },

    render : function () {
        this.undelegateEvents();
        this.$el.html(template({
            collection : this.selectedCollection.toJSON(),
            more : (!this.selectedLimit || this.selectedCollection.length < this.selectedLimit),
        }));
        this.$searchlist = this.$("[role='search-list']");
        var input = this.$("[role='keyword-input']");
        input.val(this.keyword);
        this.delegateEvents();
    },

    update : function () {
        if (!this.searchCollection.isEmpty()) {
            this.$searchList.show();
            this.$searchList.html(this.searchlistTemplate({
                collection : this.searchCollection.toJSON(),
                keyword : this.keyword,
            }));
        } else {
            this.$searchList.hide();
            this.$searchList.empty();
        }
    },

    search : function (keyword) {
        if (!keyword) {
            keyword = "";
        }
        this.keyword = keyword;
        if (keyword.length > 0) {
            this.searchCollection.urlParams.search = keyword;
            this.searchCollection.fetch();
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
