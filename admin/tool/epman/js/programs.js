
/**
 * User account description.
 */
var user = {};

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
var EducationProgram = Backbone.Model.extend({

    url : function () {
        if (this.collection) {
            return this.collection.url(this.id);
        } else {
            return null;
        }
    },

    initialize : function (attrs, options) {
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
var EducationPrograms = Backbone.Collection.extend({

    model: EducationProgram,
    urlBase : "/programs",
    urlParams : {},
    filter : {},
    url: function (id) {
        var url = this.urlBase;
        if (id) {
            url = url + "/" + id;
        }
        if (_.isEmpty(this.urlParams)) {
            return url;
        } else {
            return url + '?' + $.param(this.urlParams);
        }
    },

    initialize : function (models, options) {
        if (options.restRoot) {
            this.urlBase = options.restRoot + this.urlBase;
        }
        _.extend(this.urlParams, options.restParams);
        console.log("Education program URL: " + this.url());
    },

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
var EducationProgramView = Backbone.View.extend({

    initialize : function (options) {
        this.template = options.template;
        this.$header = options.$header;
        this.headerTemplate = options.headerTemplate;
        this.$body = options.$body;
        this.bodyTemplate = options.bodyTemplate;
        this.moduleTemplate = options.moduleTemplate;
        this.periodTemplate = options.periodTemplate;
        this.vacationTemplate = options.vacationTemplate;
        this.listenTo(this.model, 'change', this.render);
        this.listenTo(this.model, 'request', function(model) {
            console.log("Loading the education program #" + this.model.id);
            this.$body.empty();
            this.$body.toggleClass("loading", true);
            this.$body.show();
        });
        this.listenTo(this.model, 'sync', function() {
            console.log("Done loading the education program #" + this.model.id);
            this.$body.toggleClass("loading", false);
            if (_.isEmpty(this.model.changed)) {
                this.render();
            }
        });
    },

    render : function () {
        console.log("Render out the eduction program #" + this.model.id);
        var data = {
            f : this.model.collection.filter,
            p : this.model.toJSON(),
            year : this.model.get('year'),
        };
        this.$header.html(this.headerTemplate(data));
        this.$body.html(this.bodyTemplate(data));
        var $modules = this.$body.find(".program-module-list");
        var period = null;
        var endDays = null;
        _.each(data.p.modules, function (m) {
            var startDays = Math.ceil(m.startdate / (24 * 3600));
            if (period == null || period.num != m.period) {
                if (endDays != null && startDays != (endDays + 1)) {
                    $modules.append(this.vacationTemplate({ length : (startDays - endDays - 1) }));
                    endDays = startDays - 1;
                }
                $modules.append(this.periodTemplate({ m : m }));
                period = {
                    $el : $modules.find("#module-" + m.id + "-period-" + (m.period + 1)),
                    num : m.period,
                };
            }
            if (endDays != null && startDays != (endDays + 1)) {
                period.$el.append(this.vacationTemplate({ length : (startDays - endDays - 1) }));
            }
            period.$el.append(this.moduleTemplate({ m : m }));
            endDays = startDays + m.length;
        }, this);
        return this;
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
var EducationProgramsList = Backbone.View.extend({

    expandedPrograms : {},

    events : {
        "click .record-header.show-more" : function (e) {
            if (!$(e.target).hasClass("record-header")) {
                return true;
            }
            var rh = $(e.currentTarget);
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
                    template : this.recordTemplate,
                    $header : rh,
                    headerTemplate : this.recordHeaderTemplate,
                    $body : rb,
                    bodyTemplate : this.recordBodyTemplate,
                    moduleTemplate : this.moduleTemplate,
                    periodTemplate : this.periodTemplate,
                    vacationTemplate : this.vacationTemplate,
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

    initialize : function (options) {
        var section = $("#list-section-template");
        this.sectionTemplate = _.template(section.html());
        var record = $("#record-template");
        this.recordTemplate = _.template(record.html());
        var recordHeader = record.find(".record-header");
        this.recordHeaderTemplate = _.template(recordHeader.html());
        var recordBody = $("#record-body-template");
        this.recordBodyTemplate = _.template(recordBody.html());
        var module = $("#module-template");
        this.moduleTemplate = _.template(module.html());
        var period = $("#modules-period-template");
        this.periodTemplate = _.template(period.html());
        var vacation = $("#vacation-template");
        this.vacationTemplate = _.template(vacation.html());
        this.listenTo(this.collection, 'reset', this.render);
        this.listenTo(this.collection, 'request', function(collection) {
            if (collection != this.collection) return;
            console.log("Loading the education programs");
            this.$el.empty();
            this.$el.toggleClass("loading", true);
            this.$el.show();
        });
        this.listenTo(this.collection, 'sync', function(collection) {
            if (collection != this.collection) return;
            console.log("Done loading the education programs");
            this.$el.toggleClass("loading", false);
        });
    },

    render : function () {
        console.log("Render out the eduction program list");
        this.$el.empty();
        this.$el.show();
        if (!this.collection.isEmpty()) {
            var section = null;
            this.collection.forEach(function (program) {
                var data = {
                    f : this.collection.filter,
                    p : program.toJSON(),
                    year : program.get('year'),
                };
                if (section == null || section.year != data.year) {
                    this.$el.append(this.sectionTemplate(data));
                    section = {
                        $el : this.$("#year-" + data.year),
                        year : data.year,
                    };
                }
                section.$el.append(this.recordTemplate(data));
            }, this);

            for (y = section.year + 1; y < 7; y++) {
                this.$el.append(this.sectionTemplate({
                    f : this.collection.filter,
                    p : null,
                    year : y,
                }));
            }
        } else {
            console.log("Empty");
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

/* Init */

var initPage = function () {
    console.log("Init page");
    var options = toolEpmanPageOptions || {};
    Backbone.emulateHTTP = options.emulateHTTP || false;
    Backbone.emulateJSON = options.emulateJSON || false;

    _.extend(user, options.user);

    var programs = new EducationPrograms([], {
        restRoot : options.restRoot,
        restParams : options.restParams,
    });
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

    $(window).scroll (checkFooter);
    checkFooter();
};

$(initPage);
