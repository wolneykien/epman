
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

    parse : function (resp, options) {
        if (!this.isNew() && _.isArray(resp.modules)) {
            var modules = new EducationProgramModules(resp.modules, { programid : this.id });
            resp.modules = modules;
            this.listenTo(modules, "change", function (model, options) {
                this.trigger("change", this, options);
                this.trigger("change:modules", this, this.get('modules').toJSON(), options);
            });
            this.listenTo(modules, "add", function (model, collection, options) {
                this.trigger("change", this, options);
                this.trigger("change:modules", this, this.get('modules').toJSON(), options);
            });
            this.listenTo(modules, "remove", function (model, collection, options) {
                this.trigger("change", this, options);
                this.trigger("change:modules", this, this.get('modules').toJSON(), options);
            });
            this.listenTo(modules, "reset", function (collection, options) {
                this.trigger("change", this, options);
                this.trigger("change:modules", this, this.get('modules').toJSON(), options);
            });
        }
        return resp;
    },

});

/**
 * Models a collection of the education programs.
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
 * Education program module model.
 */
var EducationProgramModule = Model.extend({

    urlBase : "/programs/:programid/modules",

    defaults : {
        courses : [],
    },

    configure : function (attrs, options) {
        _.extend(this.urlParams, { programid : attrs.programid });
    },

});

/**
 * Education program module collection.
 */
var EducationProgramModules = Collection.extend({

    model : EducationProgramModule,
    urlBase : "/programs/:programid/modules",

    configure : function (options) {
        this.programid = options.programid;
        _.extend(this.urlParams, { programid : this.programid });
    },

    comparator : function(module) {
        return module.get("startdate");
    },

    shiftAbove : function (idOrModule) {
        this.shift(idOrModule, -1);
    },

    shiftBelow : function (idOrModule) {
        this.shift(idOrModule, 1);
    },

    shift : function (current, step, delta, options) {
        options = options || {};
        if (_.isNumber(current)) {
            current = this.get(current);
        }
        var idx = this.indexOf(current);
        if (idx >= 0 && current.get("startdate")) {
            if (_.isUndefined(delta)) {
                if ((idx + step) >= 0 && (idx + step) < this.length) {
                    idx = idx + step;
                    var next = this.at(idx);
                    if (next.get("startdate")) {
                        if (step < 0 && next.get("length")) {
                            delta = current.get("startdate") - next.get("startdate") - next.get("length") * 24 * 3600;
                        } else if (step > 0 && current.get("length")) {
                            delta = current.get("startdate") + current.get("length") * 24 * 3600 - next.get("startdate");
                        }
                        current = next;                        
                    }
                }
            }

            if (delta) {
                current.save({ startdate : current.get("startdate") + delta }, { silent : true });
                if (!options.irreversible) {
                    current.setRollback({ startdate : _.bind(this.shift, this, current, step, -delta, { irreversible : true }) });
                }
                idx = idx + step;            
                while (idx >= 0 && idx < this.length) {
                    current = this.at(idx);
                    current.save({ startdate : current.get("startdate") + delta }, { silent : true });
                    idx = idx + step;
                }
                if (!options.silent) {
                    this.trigger("reset", this);
                }
            }
        }
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
        this.listenTo(this.model, "change", this.render);
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
        var aboveId = undefined;
        _.each(data.p.modules, function (m) {
            var startDays = Math.ceil(m.startdate / (24 * 3600));
            if (period == null || period.num != m.period) {
                if (endDays != null && startDays != (endDays + 1)) {
                    var length = startDays - endDays - 1;
                    if (length > 0) {
                        $modules.append(templates.vacation({ length : length, aboveId : aboveId, belowId : m.id }));
                    } else {
                        $modules.append(getTemplate("#overlap-template")({ length : -length, aboveId : aboveId, belowId : m.id }));
                    }
                    endDays = startDays - 1;
                }
                $modules.append(templates.period({ m : m }));
                period = {
                    $el : $modules.find("#module-" + m.id + "-period-" + (m.period + 1)),
                    num : m.period,
                };
            }
            if (endDays != null && startDays != (endDays + 1)) {
                var length = startDays - endDays - 1;
                if (length > 0) {
                    period.$el.append(templates.vacation({ length : length, aboveId : aboveId, belowId : m.id }));
                } else {
                    period.$el.append(getTemplate("#overlap-template")({ length : -length, aboveId : aboveId, belowId : m.id }));
                }
            }
            period.$el.append(templates.module({ m : m }));
            endDays = startDays + m.length - 1;
            aboveId = m.id;
        }, this);
        
        var self = this;
        this.$header.find("[role='edit-button']").click(function () {
            (new ProgramDialog({
                model : self.model,
                el : "#program-dialog-template",
            })).open();
        });
        this.$body.find("[role='add-module-button']").click(function () {
            (new ModuleDialog({
                collection : self.model.get('modules'),
                model : new EducationProgramModule({ programid : self.model.id, length : 30 }, {}),
                el : "#module-dialog-template",
            })).open();
        });
        $modules.find("[role='edit-button']").click(function (e) {
            var modules = self.model.get('modules');
            var module = modules.get($(e.target).data("id"));
            (new ModuleDialog({
                collection : modules,
                model : module,
                el : "#module-dialog-template",
            })).open();
        });
        $modules.find("[role='shift-above-button']").click(function (e) {
            self.model.get('modules').shiftAbove($(e.target).data("id"));
        });
        $modules.find("[role='shift-below-button']").click(function (e) {
            self.model.get('modules').shiftBelow($(e.target).data("id"));
        });
        $modules.find("[role='rollback-button']").click(function (e) {
            self.model.get('modules').get($(e.target).data("id")).rollback();
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

    configure : function (options) {
        this.listenTo(this.collection, "change:year", this.render);
    },

    render : function () {
        console.log("Render out the eduction program list");
        this.$el.empty();
        this.$el.show();
        var section = { year : 0 };
        if (!this.collection.isEmpty()) {
            this.collection.forEach(function (program) {
                var data = {
                    f : this.collection.filter,
                    p : program.toJSON(),
                    year : program.get('year'),
                };
                if (section.year != data.year) {
                    for (var y = section.year + 1; y <= data.year; y++) {
                        this.$el.append(templates.listSection({
                            f : this.collection.filter,
                            p : null,
                            year : y,
                        }));
                    }
                    _.extend(section, {
                        $el : this.$("#year-" + data.year),
                        year : data.year,
                    });
                }
                section.$el.append(templates.record(data));
            }, this);
        } else {
            console.log("Empty");
        }

        for (var y = section.year + 1; y < 7; y++) {
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

    minyear : 1,
    maxyear : 6,

    validations : {
        "[name='name']" : function (val) {
            return !_.isEmpty(val);
        },
        "[name='year']" : function (val, $el) {
            val = $el.spinner("value");
            return _.isNumber(val) &&
                   val >= this.minyear &&
                   val <= this.maxyear;
        },
    },

    render : function () {
        this.$el.html(templates.programDialog({
            p : this.model.toJSON(),
            minyear : this.minyear,
            maxyear : this.maxyear,
        }));
        this.$("[name='year']").spinner({
            min : this.minyear,
            max : this.maxyear,
        }).spinner("value", this.model.get('year') || this.minyear);
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
        this.responsible.reset(this.model.get('responsible'));
        this.assistants.reset(this.model.get('assistants'));
    },

    ok : function () {
        var self = this;
        this.model.save({
            name : this.$("[name='name']").val(),
            year : Number(this.$("[name='year']").val()),
            description : this.$("[name='description']").val(),
            responsible : _.first(this.responsible.selectedCollection.pluck('id')),
            assistants : this.assistants.selectedCollection.pluck('id'),
        }, {
            wait : true,
            success : function (model) {
                if (!model.collection && self.collection) {
                    self.collection.add(model);
                }
            },
        });
    },

});

var ModuleDialog = Dialog.extend({

    courses : null,

    validations : {
        "[name='period']" : function (val, $el) {
            val = $el.spinner("value");
            return _.isNumber(val) && val >= 1;
        },
        "[name='startdate']" : function (val) {
            return !_.isEmpty(val);
        },
        "[name='enddate']" : function (val) {
            return !_.isEmpty(val);
        },
        "[name='length']" : function (val, $el) {
            val = $el.spinner("value");
            return _.isNumber(val) && val >= 1;
        },
    },

    render : function () {
        var self = this;
        this.$el.html(getTemplate("#module-dialog-template", "[role='days']")({
            m : this.model.toJSON(),
        }));
        this.$("[name='period']").spinner({ min : 1 }).spinner("value", (this.model.get('period') + 1) || 1);
        this.$("[name='startdate']").datepicker({
            defaultDate: "+1w",
            changeMonth: true,
            numberOfMonths: 3,
            dateFormat : i18n['dateFormat'],
        });
        if (this.model.get("startdate")) {
            this.$("[name='startdate']").datepicker("setDate", new Date(this.model.get("startdate") * 1000));
        }
        this.$("[name='enddate']").datepicker({
            defaultDate: "+2w",
            changeMonth: true,
            numberOfMonths: 3,
            dateFormat : i18n['dateFormat'],
        });
        if (this.model.get("startdate") && this.model.get("length")) {
            this.$("[name='enddate']").datepicker("setDate", new Date((this.model.get("startdate") + (this.model.get("length") - 1) * 24 * 3600) * 1000));
        }
        this.$("[name='length']").spinner({
            min : 1,
        }).spinner("value", this.model.get('length') || 30);
        this.courses = new CourseSelect({
            $el : this.$("[role='select-courses']"),
            template : getTemplate("#courseselect-template"),
            searchlistTemplate : getTemplate("#course-searchlist-template"),
            selectedCollection : new Courses(),
        });
        this.courses.reset(this.model.get('courses'));
    },

    fix : function ($input) {
        var updated = [];
        if (!$input) {
            return updated;
        }
        if ($input.is(this.$("[name='startdate']"))) {
            var start = $input.datepicker("getDate");
            if (start) {
                var $end = this.$("[name='enddate']");
                $end.datepicker("option", "minDate", start);
                updated.push($end);
                var len = this.$("[name='length']").spinner("value");
                if (len) {
                    $end.datepicker("setDate", new Date(start.getTime() + (len - 1) * 24 * 3600 * 1000));
                }
            }
        }
        if ($input.is(this.$("[name='enddate']"))) {
            var end = $input.datepicker("getDate");
            if (end) {
                var $start = this.$("[name='startdate']");
                $start.datepicker("option", "maxDate", end);
                updated.push($start);
                var start = $start.datepicker("getDate");
                if (start) {
                    var $len = this.$("[name='length']");
                    $len.spinner("value", (end.getTime() - start.getTime()) / (24 * 3600 * 1000) + 1);
                    updated.push($len);
                }
            }
        }
        if ($input.is(this.$("[name='length']"))) {
            var start = this.$("[name='startdate']").datepicker("getDate");
            var len = $input.spinner("value");
            if (start && len) {
                var $end = this.$("[name='enddate']");
                $end.datepicker("setDate", new Date(start.getTime() + (len - 1) * 24 * 3600 * 1000));
                updated.push($end);
            }
            if (len) {
                this.$("[role='days']").html(getTemplate("#module-dialog-template [role='days']")({ m : { length : len } }));
            }
        }
        return updated;
    },

    ok : function () {
        var self = this;
        this.model.save({
            startdate : Math.round(this.$("[name='startdate']").datepicker("getDate").getTime() / 1000),
            length : Number(this.$("[name='length']").val()),
            period : Number(this.$("[name='period']").val() - 1),
            courses : this.courses.selectedCollection.map(function (course) {
                return course.pick('id', 'type');
            }),
        }, {
            wait : true,
            success : function (model) {
                if (!model.collection && self.collection) {
                    self.collection.add(model);
                }
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

    $("#add-program-button").click(function () {
        (new ProgramDialog({
            model : new EducationProgram({}, {}),
            collection : programs,
            el : "#program-dialog-template",
        })).open();
    });

    $(window).scroll (checkFooter);
    checkFooter();
};

$(initPage);
