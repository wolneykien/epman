
/**
 * Globals.
 */
var addProgram;

/**
 * Education program list router.
 *
 * @param options {
 *     programs : EducationPrograms,
 * }
 *
 */
var EducationProgramsRouter = Backbone.Router.extend({

    routes : {
        "" : function () {
            this.navigate("#!", {trigger : true});
        },
        "!(years/:year)" : function (year) {
            this.filter.apply({ my : false });
        },
        "!my(years/:year)" : function (year) {
            this.filter.apply({ my : true });
        },
    },

    initialize : function (options) {
        this.filter = options.filter;
    },
        
});

/**
 * Education program model.
 */
var EducationProgram = Model.extend({

    urlBase : "/programs",

    defaults : {
        assistants : [],
    },

});

/**
 * Models a collection of the education programs.
 *
 * @param options {
 *   restRoot : "REST script URI",
 * }
 *
 */
var EducationPrograms = Collection.extend({

    model: EducationProgram,
    urlBase : "/programs",
    filter : {},

    load : function (filter) {
        this.filter = filter;
        if (filter.my) {
            _.extend(this.urlParams, { userid : user.id });
        } else {
            this.urlParams = _.omit(this.urlParams, "userid");
        }
        console.log("Fetching programs from " + this.url());
        this.fetch({ reset : true });
    },

});

/**
 * Renders the complete education program.
 */
var EducationProgramView = View.extend({

    configure : function (options) {
        this.$header = options.$header;
        this.$body = options.$body;
        this.render();
    },

    render : function () {
        var data = {
            f : this.model.collection.filter,
            p : this.model.toJSON(),
            year : this.model.get('year'),
        };
        this.$header.html(templates.recordHeader(data));
        this.$body.html(templates.recordBody(data));
        this.$body.show();
        var $modules = this.$body.find(".program-module-list");
        var period = null;
        var endDays = null;
        _.each(data.p.modules, function (m) {
            var startDays = Math.ceil(m.startdate / (24 * 3600));
            if (period == null || period.num != m.period) {
                if (endDays != null && startDays != (endDays + 1)) {
                    $modules.append(templates.vacation({ length : (startDays - endDays - 1) }));
                    endDays = startDays - 1;
                }
                $modules.append(templates.period({ m : m }));
                period = {
                    $el : $modules.find("#module-" + m.id + "-period-" + (m.period + 1)),
                    num : m.period,
                };
            }
            if (endDays != null && startDays != (endDays + 1)) {
                period.$el.append(templates.vacation({ length : (startDays - endDays - 1) }));
            }
            period.$el.append(templates.module({ m : m }));
            endDays = startDays + m.length;
        }, this);
        
        var self = this;
        this.$header.find("[role='edit-button']").click(function () {
            var program = new ProgramDialog({
                model : self.model,
                el : "#program-dialog-template",
            });
            program.open();
        });

        return this;
    },

    syncing : function (status) {
        this.$body.toggleClass("loading", status);
    },

});

/**
 * Renders the list of the education programs.
 *
 * @param options {
 *     collection : EducationPrograms,
 * }
 *
 */
var EducationProgramsList = View.extend({

    expandedPrograms : {},

    events : {
        "click .record-header.show-more" : function (e) {
            if (!$(e.target).hasClass("record-header")) {
                return true;
            }
            var rh = $(e.target);
            var r = rh.parent();
            var rb = r.find(".record-body");
            var rid = r.attr("id").replace(/^program-/, "");
            if (!r.hasClass("expanded")) {
                r.toggleClass("collapsed", false);
                r.toggleClass("expanded", true);
                var program = this.collection.get(rid);
                var programView = new EducationProgramView({
                    el : ("#" + rid),
                    $el : r,                    
                    $header : rh,
                    $body : rb,
                    model : program,
                });
                this.expandedPrograms[rid] = programView;
                program.fetch();
            } else {
                rb.hide();
                r.toggleClass("collapsed", true);
                r.toggleClass("expanded", false);
            }
        },
    },

    render : function () {
        console.log("Render out the eduction program list");
        this.$el.empty();
        this.$el.show();
        var section = null;
        if (!this.collection.isEmpty()) {
            this.collection.forEach(function (program) {
                var data = {
                    f : this.collection.filter,
                    p : program.toJSON(),
                    year : program.get('year'),
                };
                if (section == null || section.year != data.year) {
                    this.$el.append(templates.listSection(data));
                    section = {
                        $el : this.$("#year-" + data.year),
                        year : data.year,
                    };
                }
                section.$el.append(templates.record(data));
            }, this);
        } else {
            console.log("Empty");
        }

        if (!section) {
            section = { year : 0};
        }
        for (y = section.year + 1; y < 7; y++) {
            this.$el.append(templates.listSection({
                f : this.collection.filter,
                p : null,
                year : y,
            }));
        }

        return this;
    },

});

/**
 * The page's filter view.
 */
var EducationProgramsFilter = Backbone.View.extend({

    filter : {
    },

    events : {
        "click #my" : function (e) {
            this.apply({ my : !this.filter.my });
        },
    },

    initialize : function (options) {
        this.programs = options.programs;
        if (_.isUndefined(user.id) || _.isNull(user.id)) {
            this.$el.find('#my').hide();
            console.warn("No current user Id specified. Hide the 'My' filter");
        }
    },

    render : function () {
        this.$el.find('#my').toggleClass("on", this.filter.my);
        return this;
    },

    apply : function (filter) {
        if (!_.isEqual(this.filter, filter)) {
            console.log("Filter: " + JSON.stringify(filter));
            if (_.isUndefined(user.id) || _.isNull(user.id)) {
                _.extend(filter, { my : false });
            }
            this.filter = filter;
            this.render();
            this.navigate();
            this.programs.load(this.filter);
        }
    },

    navigate : function () {
        var route = _.filter(
            [ "my" ],
            function (opt) {
                return this.filter[opt];
            },
            this
        ).join("/");
        Backbone.history.navigate("#!" + route);
    },

});

var ProgramDialog = Dialog.extend({

    responsible : null,
    assistants : null,

    validations : {
        "[name='name']" : function (val) {
            return !_.isEmpty(val);
        },
    },

    update : function () {
        this.responsible.reset(this.model.get('responsible'));
        this.assistants.reset(this.model.get('assistants'));
    },

    render : function () {
        this.stopListening(this.model, "change", this.update);
        this.$el.html(templates.programDialog({
            p : this.model.toJSON(),
        }));
        this.responsible = new UserSelect({
            $el : this.$("[role='select-responsible']"),
            template : templates.userselect,
            searchlistTemplate : templates.userSearchList,
            selectedCollection : new Users(),
            defValue : user.id ? user.toJSON() : null,
            max : 1,
        });
        this.assistants = new UserSelect({
            $el : this.$("[role='select-assistants']"),
            template : templates.userselect,
                searchlistTemplate : templates.userSearchList,
            selectedCollection : new Users(),
        });
        this.update();
        this.listenTo(this.model, "change", this.update);
    },

    ok : function () {
        var self = this;
        this.model.save({
            name : this.$("[name='name']").val(),
            description : this.$("[name='description']").val(),
            responsible : _.first(this.responsible.selectedCollection.pluck('id')),
            assistants : this.assistants.selectedCollection.pluck('id'),
        }, {
            wait : true,
            silent : true,
            success : function (model) {
                model.fetch({
                    reset : true,
                    success : function (model) {
                        if (!model.collection && self.collection) {
                            self.collection.add(model);
                        }
                    },
                });
            },
        });
    },

});

/* Init */

var initPage = function () {
    console.log("Init page");
    var options = toolEpmanPageOptions || {};
    Backbone.emulateHTTP = options.emulateHTTP || false;
    Backbone.emulateJSON = options.emulateJSON || false;

    _.extend(restOptions, _.pick(options || {}, 'restRoot', 'restParams'));

    if (options.user && options.user.id) {
        user = new User(options.user);
        user.fetch();
    }
    _.defaults(i18n, options.i18n);

    templates = {
        listSection : _.template($("#list-section-template").html()),
        record : _.template($("#record-template").html()),
        recordHeader : _.template($("#record-template").find(".record-header").html()),
        recordBody : _.template($("#record-body-template").html()),
        module : _.template($("#module-template").html()),
        period : _.template($("#modules-period-template").html()),
        vacation : _.template($("#vacation-template").html()),
        programDialog : _.template($("#program-dialog-template").html()),
        userselect : _.template($("#userselect-template").html()),
        userSearchList : _.template($("#user-search-list-template").html()),
    };

    var programs = new EducationPrograms([], {});
    var programList = new EducationProgramsList({
        el : "#program-list",
        collection : programs,
    });
    
    var filter = new EducationProgramsFilter({
        el : "#filter",
        programs : programs,
    });

    var router = new EducationProgramsRouter({
        filter : filter,
    });
    
    Backbone.history.start ({ pushState: false });

    
    var filterPanel = $('#filter');
    var footerPanel = $('#footer');
    var checkFooter = function () {
        if ($(window).scrollTop () > (filterPanel.offset().top + filterPanel.height())) {
            footerPanel.css(
                { left : filterPanel.offset().left,
                  width : filterPanel.width(),
                });
            footerPanel.show();
        } else {
            footerPanel.hide();
        }
    }

    var addProgram = function () {
        var program = new ProgramDialog({
            model : new EducationProgram({}, {}),
            collection : programs,
            el : "#program-dialog-template",
        });
        program.open();
    };
    $("#add-program-button").click(addProgram);

    $(window).scroll (checkFooter);
    checkFooter();
};

$(initPage);
