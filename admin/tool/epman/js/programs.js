
/**
 * Education program list router.
 *
 */
var EducationProgramsRouter = Backbone.Router.extend({

    position : {
        year : null,
        programid : null,
    },

    routes : {
        "(years/:year)" : function (year) {
            this.handleRoute({ my : false }, { year : year });
        },
        "my(/years/:year)" : function (year) {
            this.handleRoute({ my : true }, { year : year });
        },
        "all(/years/:year)" : function (year) {
            this.handleRoute({ my : false }, { year : year });
        },
        "(:programid)" : function (programid) {
            this.handleRoute({ my : false }, { programid : programid });
        },
    },

    initialize : function (options) {
        this.filter = options.filter;
        this.programList = options.programList;
        this.navbar = options.navbar;
        this.listenTo(this.programList, "render", this.jump);
        this.listenTo(this.filter, "norender", this.jump);
    },

    fixRoute : function () {
        if (window.location.hash != "") {
            var fragment = window.location.hash.replace(/^#/, "");
            if (fragment.length > 1 && fragment.substr(-1) == "#") {
                this.navigate(fragment.replace(/#+$/, ""), { trigger : true, replace : true });
                return true;
            }
        }
        return false;
    },

    handleRoute : function (filter, position) {
        if (this.fixRoute()) {
            return;
        }
        var params = $.params();
        if (!_.isEmpty(params) && params.programid) {
            window.location.replace(window.location.pathname + "#" + params.programid);
        } else {
            this.position = position;
            this.filter.apply(filter, { navigate : false });
            this.navbar.render(filter.my ? "/my" : "/all");
        }
    },

    jump : function () {
        var $el = null;
        if (this.position.programid) {
            this.programList.expand(this.position.programid, { jump : true });
            this.navigate(window.location.hash + "#", { trigger : false, replace : true });
        } else if (this.position.year) {
            $el = $("#year-" + this.position.year);
        }
        if ($el && $el.size() > 0) {
            $el[0].scrollIntoView();
            this.navigate(window.location.hash + "#", { trigger : false, replace : true });
        }
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
        if (_.isArray(resp.modules)) {
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
            this.listenTo(modules, "sort", function (collection, options) {
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

    acquire : function (another) {
        this.set(_.extend({}, another, {
            programid : this.get("programid") || null,
            id : this.get("id") || null,
        }));
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

    comparator : function(module1, module2) {
        if (module1.get("period") != module2.get("period")) {
            return Math.sign(module1.get("period") - module2.get("period"));
        } else {
            return Math.sign(module1.get("startdate") - module2.get("startdate"));
        }
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
                this.sort();
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
    },

    render : function (options) {
        options = _.defaults(options || {}, {
            action : {},
        });
        var data = {
            f : this.model.collection.filter,
            p : this.model.toJSON({ withUndo : true }),
            year : this.model.get('year'),
            action : options.action,
        };
        this.$header.find(".link-button").off("click");
        this.$header.html(getTemplate("#record-template .record-header")(data));
        this.$body.find(".link-button").off("click");
        this.$body.html(getTemplate("#record-body-template", ".program-modules > .section-header")(data));
        this.$body.show();
        var $modulesHeader = this.$body.find(".program-modules > .section-header");
        var $modules = this.$body.find(".program-module-list");
        var period = null;
        var endDays = null;
        var aboveId = undefined;
        _.each(data.p.modules, function (m) {
            var mdata = _.extend({}, data, { m : m });
            var startDays = Math.ceil(m.startdate / (24 * 3600));
            if (period == null || period.num != m.period) {
                if (endDays != null && startDays != (endDays + 1)) {
                    var length = startDays - endDays - 1;
                    if (length > 0) {
                        $modules.append(getTemplate("#vacation-template")({ length : length, aboveId : aboveId, belowId : m.id }));
                    } else {
                        $modules.append(getTemplate("#overlap-template")({ length : -length, aboveId : aboveId, belowId : m.id }));
                    }
                    endDays = startDays - 1;
                }
                $modules.append(getTemplate("#modules-period-template")(mdata));
                period = {
                    $el : $modules.find("#module-" + m.id + "-period-" + (m.period + 1)),
                    num : m.period,
                };
            }
            if (endDays != null && startDays != (endDays + 1)) {
                var length = startDays - endDays - 1;
                if (length > 0) {
                    period.$el.append(getTemplate("#vacation-template")({ length : length, aboveId : aboveId, belowId : m.id }));
                } else {
                    period.$el.append(getTemplate("#overlap-template")({ length : -length, aboveId : aboveId, belowId : m.id }));
                }
            }
            period.$el.append(getTemplate("#module-template")(mdata));
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
        this.$header.find("[role='delete-button']").click(function () {
            (new YesNoDialog({
                yes : function () {
                    self.model.destroy({ wait : true });
                },
            })).open({ message : i18n["Delete_the_education_program_?"] });
        });
        var updateHeader = function () {
            var $moduleMarkers = $modules.find("input[name='selectedModules']");
            var moduleMarkers = getMarkers($moduleMarkers);
            $modulesHeader.html(getTemplate("#record-body-template .program-modules > .section-header")(_.extend({}, data, {
                action : _.extend({}, data.action, {
                    markers : moduleMarkers,
                }),
            })));
            $modulesHeader.find("[role='add-module-button']").click(function () {
                (new ModuleDialog({
                    collection : self.model.get('modules'),
                    model : new EducationProgramModule({ programid : self.model.id, length : 30 }, {}),
                    el : "#module-dialog-template",
                })).open();
            });
            if (options.action.deleteModules || options.action.copyModules) {
                if (options.action.deleteModules) {
                    $modulesHeader.find("[role='delete-modules-button']").click(function (e) {
                        (new YesNoDialog({
                            yes : function () {
                                _.each(moduleMarkers, function (m) {
                                    if (_.first(_.values(m))) {
                                        $modules.toggleClass("loading", true);
                                        self.model.get('modules').get(_.first(_.keys(m))).destroy({
                                            success : function () {
                                                $modules.toggleClass("loading", false);
                                            },
                                            error : function () {
                                                $modules.toggleClass("loading", false);
                                            },
                                        });
                                    }
                                });
                                self.render({ action : { "return" : true } });
                            },
                        })).open({ message : i18n["Delete_selected_modules_?"] });
                    });
                } else if (options.action.copyModules) {
                    $modulesHeader.find("[role='copy-modules-button']").click(function (e) {
                        clipboard("modules", _.map(_.filter(moduleMarkers, function (m) {
                            return _.first(_.values(m));
                        }), function (m) {
                            return self.model.get("modules").get(_.first(_.keys(m))).toJSON();
                        }));
                        self.render({ action : { "return" : true } });
                    });
                }
                $modulesHeader.find("[role='cancel-action-button']").one("click", function (e) {
                    self.render({ action : { "return" : true } });
                });
                $modulesHeader.find("[role='select-all-button']").one("click", function () {
                    var checked = !allMarked(moduleMarkers);
                    $moduleMarkers.each(function (i, e) { e.checked = checked });
                    updateHeader();
                });
                $moduleMarkers.off("change", updateHeader);
                $moduleMarkers.one("change", updateHeader);
            } else {
                $modulesHeader.find("[role='delete-modules-button']").one("click", function (e) {
                    self.render({ action : { deleteModules : true } });
                });
                $modulesHeader.find("[role='copy-modules-button']").one("click", function (e) {
                    self.render({ action : { copyModules : true } });
                });
                $modulesHeader.find("[role='paste-modules-button']").click(function (e) {
                    _.each(clipboard.getany("modules"), function (pm) {
                        var nm = new EducationProgramModule({ programid : self.model.id }, {});
                        nm.acquire(pm);
                        $modules.toggleClass("loading", true);
                        nm.save({}, {
                            success : function (model) {
                                self.model.get("modules").add(model);
                                $modules.toggleClass("loading", false);
                            },
                            error : function () {
                                $modules.toggleClass("loading", false);
                            },
                        });
                    });
                });
            }
            clipboard.once("add:modules", updateHeader);
            clipboard.once("remove:modules", updateHeader);
        }
        updateHeader();
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

        if (options.action.deleteModules || options.action.copyModules) {
            $("#program-" + self.model.id + "-modules")[0].scrollIntoView();
            $("body").css({ "overflow-y" : "hidden" });
            $modules.css({ height : "100vh", "overflow-y" : "scroll" });
            disableCheckFooter();
        }

        if (options.action["return"]) {
            $modules.css({ height : "", "overflow-y" : "" });
            this.$header[0].scrollIntoView();
            $("body").css({ "overflow-y" : "scroll" });
            enableCheckFooter();
        }

        var modules = this.model.get("modules");
        if (modules) {
            this.stopListening(modules);
            this.listenTo(modules, "request", function (model, xhr, options) {
                $modules.toggleClass("loading", true);
            });
            this.listenTo(modules, "sync", function (model, resp, options) {
                $modules.toggleClass("loading", false);
            });
            this.listenTo(modules, "error", function (model, xhr, options) {
                $modules.toggleClass("loading", false);
            });
        }

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
            this.toggle($(e.target).data("id"));
        },
    },

    toggle : function (id, options) {
        var program = this.collection.get(id);
        if (!program) {
            return false;
        }
        var $r = $("#program-" + id);
        if ($r.size() == 0) {
            return false;
        }
        var $rh = $r.find(".record-header");
        var $rb = $r.find(".record-body");
        if (!_.isObject(options)) {
            options = { status : options };
        }
        if (_.isUndefined(options.status)) {
            options.status = !$r.hasClass("expanded");
        }
        if (options.jump) {
            $r[0].scrollIntoView();
        }
        if (options.status) {
            $r.toggleClass("collapsed", false);
            $r.toggleClass("expanded", true);
            var programView = new EducationProgramView({
                el : ("#" + id),
                $el : $r,
                $header : $rh,
                $body : $rb,
                model : program,
            });
            this.expandedPrograms[id] = programView;
            program.fetch();
        } else {
            $rb.hide();
            $r.toggleClass("collapsed", true);
            $r.toggleClass("expanded", false);
            this.expandedPrograms[id] = null;
        }
    },

    expand : function (id, options) {
        this.toggle(id, _.extend({}, options, {
            status : true,
        }));
    },

    collapse : function (id, options) {
        this.toggle(id, _.extend({}, options, {
            status : false,
        }));
    },

    configure : function (options) {
        this.listenTo(this.collection, "sort", this.render);
        this.listenTo(this.collection, "change:year", this.render);
    },

    render : function () {
        console.log("Render out the education program list");
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
                        this.$el.append(getTemplate("#list-section-template")({
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
                section.$el.append(getTemplate("#record-template")(data));
            }, this);
        } else {
            console.log("Empty");
        }

        var maxyear = toolEpmanPageOptions.maxyear || 6;
        for (var y = section.year + 1; y <= maxyear; y++) {
            this.$el.append(getTemplate("#list-section-template")({
                f : this.collection.filter,
                p : null,
                year : y,
            }));
        }

        this.trigger("render");
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
        "click #filter-my" : function (e) {
            this.navigate({ my : !this.filter.my });
        },
    },

    initialize : function (options) {
        this.programs = options.programs;
        if (_.isUndefined(user.id) || _.isNull(user.id)) {
            this.$el.find('#filter-my').hide();
            console.warn("No current user Id specified. Hide the 'My' filter");
        }
    },

    render : function () {
        this.$el.find('#filter-my').toggleClass("on", this.filter.my);
        return this;
    },

    apply : function (filter, options) {
        options = _.defaults(options || {}, {
            navigate : true,
        });
        if (!_.isEqual(this.filter, filter)) {
            console.log("Filter: " + JSON.stringify(filter));
            if (_.isUndefined(user.id) || _.isNull(user.id)) {
                _.extend(filter, { my : false });
            }
            this.filter = filter;
            this.render();
            if (options.navigate) {
                this.navigate();
            }
            this.programs.load(this.filter);
        } else {
            this.trigger("norender");
        }
    },

    navigate : function (filter) {
        filter = filter || this.filter;
        Backbone.history.navigate(filter.my ? "my" : "all", { trigger : true, replace : true });
    },

});

var ProgramDialog = Dialog.extend({

    responsible : null,
    assistants : null,

    minyear : 1,
    maxyear : toolEpmanPageOptions.maxyear || 6,

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

    configure : function (options) {
        this.responsible = new UserSelect({
            template : getTemplate("#userselect-template"),
            searchlistTemplate : getTemplate("#user-search-list-template"),
            defValue : user.id ? user.toJSON() : null,
            max : 1,
        });
        this.assistants = new UserSelect({
            template : getTemplate("#userselect-template"),
            searchlistTemplate : getTemplate("#user-search-list-template"),
        });
    },

    render : function () {
        this.$el.html(getTemplate("#program-dialog-template")({
            p : this.model.toJSON(),
            minyear : this.minyear,
            maxyear : this.maxyear,
        }));
        this.$("[name='year']").spinner({
            min : this.minyear,
            max : this.maxyear,
        }).spinner("value", this.model.get('year') || this.minyear);
        this.responsible.reset(this.model.get('responsible'), { $el : this.$("[role='select-responsible']") });
        this.assistants.reset(this.model.get('assistants'), { $el : this.$("[role='select-assistants']") });
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

    configure :  function (options) {
        this.courses = new CourseSelect({
            template : getTemplate("#courseselect-template"),
            searchlistTemplate : getTemplate("#course-searchlist-template"),
        });
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
        this.courses.reset(this.model.get('courses'), { $el : this.$("[role='select-courses']") });
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
                } else {
                    self.collection.sort();
                }
            },
        });
    },

});

var NavigationPanel = View.extend({

    configure : function (options) {
        this.$header = $(options.header);
        this.$footer = $(options.footer);
    },
    
    render : function (prefix) {
        if (prefix.length > 0) {
            prefix = prefix + "/";
        }
        prefix = "#" + prefix;
        var data = {
            yearLinks : _.map(_.range(1, (toolEpmanPageOptions.maxyear || 6) + 1), function (y) {
                return { year : y, href : prefix + "years/" + y };
            }),
        };
        this.$header.html(getTemplate("#year-links-template")(data));
        this.$footer.html(getTemplate("#year-links-template")(data));
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
    }
    _.defaults(i18n, options.i18n);

    var programs = new EducationPrograms([], {});
    var programList = new EducationProgramsList({
        el : "#program-list",
        collection : programs,
    });
    
    var filter = new EducationProgramsFilter({
        el : "#filter",
        programs : programs,
    });

    var navbar = new NavigationPanel({
        header : "#tool-epman .year-links",
        footer : "#tool-epman [role='page-footer'] .year-links",
    });

    var router = new EducationProgramsRouter({
        filter : filter,
        programList : programList,
        navbar : navbar,
    });

    Backbone.history.start({ pushState: false });

    $("#add-program-button").click(function () {
        (new ProgramDialog({
            model : new EducationProgram({}, {}),
            collection : programs,
            el : "#program-dialog-template",
        })).open();
    });

    enableCheckFooter();
};

$(initPage);
