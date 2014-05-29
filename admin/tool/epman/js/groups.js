
/**
 * Academic group list router.
 *
 */
var AcademicGroupsRouter = Backbone.Router.extend({

    position : {
        year : null,
        groupid : null,
    },

    routes : {
        "programs/:programid(/years/:year)" : function (programid, year) {
            this.handleRoute({ my : false, programid : programid }, { year : year });
        },
        "my(/programs/:programid)(/years/:year)" : function (programid, year) {
            this.handleRoute({ my : true, programid : programid }, { year : year });
        },
        "all(/programs/:programid)(/years/:year)" : function (programid, year) {
            this.handleRoute({ my : false, programid : programid }, { year : year });
        },
        "(:groupid)" : function (groupid) {
            this.handleRoute({ my : false }, { groupid : groupid });
        },
    },

    initialize : function (options) {
        this.filter = options.filter;
        this.groupList = options.groupList;
        this.navbar = options.navbar;
        this.listenTo(this.groupList, "render", this.jump);
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
        if (!_.isEmpty(params) && params.groupid) {
            window.location.replace(window.location.pathname + "#" + params.groupid);
        } else {
            if (filter.programid) {
                filter.programid = Number(filter.programid) || undefined;
            }
            var prefix = (filter.my ? "/my" : "/all") + 
                (filter.programid ? ("/programs/" + filter.programid) : "");
            if (!position.year && !position.groupid) {
                position.year = 1;
                this.navigate(prefix + "/years/" + position.year, { trigger: false, replace : true });
            }
            this.position = position;
            this.filter.apply(filter, position, { navigate : false });
            this.navbar.render(prefix);
        }
    },

    jump : function () {
        if (this.position.groupid) {
            this.groupList.expand(this.position.groupid, { jump : true });
            this.navigate(window.location.hash + "#", { trigger : false, replace : true });
        } else if (this.position.year) {
            gotop();
            this.navigate(window.location.hash + "#", { trigger : false, replace : true });
        }
    },
        
});

/**
 * Academic group model.
 */
var AcademicGroup = Model.extend({

    urlBase : "/groups",

    defaults : {
        assistants : [],
        students : [],
    },

});

/**
 * Models a collection of the academic groups.
 *
 */
var AcademicGroups = Collection.extend({

    model: AcademicGroup,
    urlBase : "/groups",
    filter : {},
    position : {},

    load : function (filter, position) {
        this.filter = filter;
        this.position = _.clone(position);
        if (this.position.year) {
            this.position.year = this.position.year;
        }
        if (_.isUndefined(this.position.groupid) && _.isUndefined(this.position.year)) {
            this.position.year = 1;
        }
        if (this.filter.my) {
            _.extend(this.urlParams, { userid : user.id });
        } else {
            this.urlParams = _.omit(this.urlParams, "userid");
        }
        if (this.filter.programid) {
            _.extend(this.urlParams, { programid : this.filter.programid });
        } else {
            this.urlParams = _.omit(this.urlParams, "programid");
        }
        if (this.position.groupid) {
            _.extend(this.urlParams, { yeargroupid : this.position.groupid });
        } else {
            this.urlParams = _.omit(this.urlParams, "yeargroupid");
            _.extend(this.urlParams, { year : this.position.year });
        }
        console.log("Fetching groups from " + this.url());
        this.fetch({ reset : true });
    },

});

/**
 * Renders the complete academic group.
 */
var AcademicGroupView = View.extend({

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
            g : this.model.toJSON(),
            year : this.model.get('year'),
            action : options.action,
        };
        this.$header.find(".link-button").off("click");
        this.$header.html(getTemplate("#record-template .record-header")(data));
        this.$body.find(".link-button").off("click");
        this.$body.html(getTemplate("#record-body-template", ".group-students > .section-header")(data));
        this.$body.show();
        var $studentsHeader = this.$body.find(".group-students > .section-header");
        var $students = this.$body.find(".group-student-list");
        var addColumn = function ($list, students, size, letter) {
            var slice = _.head(students, size);
            $list.append(getTemplate("#student-list-template")({
                students : _.map(slice, function (s) {
                    return _.defaults(s, {
                        lastname : "?",
                        firstname : "?",
                    });
                }, this),
                letter : letter,
                action : options.action,
            }));
            if (students.length > slice.length) {
                addColumn($list, _.rest(students, size), size, _.last(slice).lastname[0]);
            }
        };
        $students.empty();
        var entrants = _.filter(data.g.students, function (s) {
            return s.period == null;
        });
        if (!_.isEmpty(entrants)) {
            $students.append(getTemplate("#students-period-template")({
                action: options.action,
                period : null,
                students : entrants,
            }));
            addColumn($students.find(".period-student-list").last(), entrants, Math.ceil(entrants.length / 3));
        }
        _.each(data.g.program.periods, function (period) {
            var students = _.filter(data.g.students, function (s) {
                return s.period == period;
            });
            $students.append(getTemplate("#students-period-template")({
                action : options.action,
                period : period,
                students : students,
            }));
            if (!_.isEmpty(students)) {
                addColumn($students.find(".period-student-list").last(), students, Math.ceil(students.length / 3));
            }
        });
        
        var self = this;
        this.$header.find("[role='edit-button']").click(function () {
            (new GroupDialog({
                model : self.model,
                el : "#group-dialog-template",
            })).open();
        });
        this.$header.find("[role='delete-button']").click(function () {
            (new YesNoDialog({
                yes : function () {
                    self.model.destroy({ wait : true });
                },
            })).open({ message : i18n["Delete_the_academic_group_?"] });
        });
        var updateHeader = function () {
            var $studentMarkers = $students.find("input[name='selectedStudents']");
            var studentMarkers = getMarkers($studentMarkers);
            $studentsHeader.html(getTemplate("#record-body-template .group-students > .section-header")(_.extend({}, data, {
                action : _.extend({}, data.action, {
                    markers : studentMarkers,
                }),
            })));
            $studentsHeader.find("[role='add-students-button']").click(function () {
                (new AddStudentsDialog({
                    model : self.model,
                    el : "#add-students-dialog-template",
                })).open();
            });
            if (options.action.deleteStudents || options.action.copyStudents) {
                if (options.action.deleteStudents) {
                    $studentsHeader.find("[role='delete-students-button']").click(function (e) {
                        (new YesNoDialog({
                            yes : function () {
                                self.model.save({
                                    students : _.map(_.filter(studentMarkers, function (m) {
                                        return !_.first(_.values(m));
                                    }), function (m) {
                                        return _.first(_.keys(m));
                                    }),
                                }, {
                                    wait : true,
                                    patch : true,
                                });
                                self.render({ action : { "return" : true } });
                            },
                        })).open({ message : i18n["Delete_selected_students_?"] });
                    });
                } else if (options.action.copyStudents) {
                    $studentsHeader.find("[role='copy-students-button']").click(function (e) {
                        clipboard("students", _.map(_.filter(studentMarkers, function (m) {
                            return _.first(_.values(m));
                        }), function (m) {
                            return _.first(_.keys(m));
                        }));
                        self.render({ action : { "return" : true } });
                    });
                }
                $studentsHeader.find("[role='cancel-action-button']").one("click", function (e) {
                    self.render({ action : { "return" : true } });
                });
                $studentsHeader.find("[role='select-all-button']").one("click", function () {
                    $studentMarkers.each(function (i, e) { e.checked = !allMarked(studentMarkers) });
                    updateHeader();
                });
                $studentMarkers.one("change", updateHeader);
            } else {
                $studentsHeader.find("[role='delete-students-button']").one("click", function (e) {
                    self.render({ action : { deleteStudents : true } });
                });
                $studentsHeader.find("[role='copy-students-button']").one("click", function (e) {
                    self.render({ action : { copyStudents : true } });
                });
                $studentsHeader.find("[role='paste-students-button']").click(function (e) {
                    self.model.save({
                        students : _.union(self.model.get("students"), clipboard.getany("students")),
                    }, {
                        patch : true,
                    });
                });
            }
            clipboard.once("add:students", updateHeader);
            clipboard.once("remove:students", updateHeader);
        }
        updateHeader();

        if (options.action.deleteStudents || options.action.copyStudents) {
            $("#group-" + self.model.id + "-students")[0].scrollIntoView();
            $("body").css({ "overflow-y" : "hidden" });
            $students.css({ height : "100vh", "overflow-y" : "scroll" });
            disableCheckFooter();
        }

        if (options.action["return"]) {
            $students.css({ height : "", "overflow-y" : "" });
            this.$header[0].scrollIntoView();
            $("body").css({ "overflow-y" : "scroll" });
            enableCheckFooter();
        }

        return this;
    },

    syncing : function (status) {
        this.$body.toggleClass("loading", status);
    },

});

/**
 * Renders the list of the academic groups.
 *
 * @param options {
 *     collection : AcademicGroups,
 * }
 *
 */
var AcademicGroupsList = View.extend({

    expandedGroups : {},

    events : {
        "click .record-header.show-more" : function (e) {
            if (!$(e.target).hasClass("record-header")) {
                return true;
            }
            this.toggle($(e.target).data("id"));
        },
    },

    toggle : function (id, options) {
        var group = this.collection.get(id);
        if (!group) {
            return false;
        }
        var $r = $("#group-" + id);
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
            var groupView = new AcademicGroupView({
                el : ("#" + id),
                $el : $r,
                $header : $rh,
                $body : $rb,
                model : group,
            });
            this.expandedGroups[id] = groupView;
            group.fetch();
        } else {
            $rb.hide();
            $r.toggleClass("collapsed", true);
            $r.toggleClass("expanded", false);
            this.expandedGroups[id] = null;
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
        console.log("Render out the academic group list");
        this.$el.empty();
        this.$el.show();
        this.$el.append(getTemplate("#list-section-template")({
            f : this.collection.filter,
            g : null,
            year : !this.collection.isEmpty() ? this.collection.first().get("year") : this.collection.position.year,
        }));
        if (!this.collection.isEmpty()) {
            this.collection.forEach(function (group) {
                this.$el.append(getTemplate("#record-template")({
                    f : this.collection.filter,
                    g : group.toJSON(),
                    year : group.get('year'),
                }));
            }, this);
        } else {
            console.log("Empty");
        }

        this.trigger("render");
        return this;
    },

});

/**
 * The page's filter view.
 */
var AcademicGroupsFilter = View.extend({

    filter : {
    },

    position : {
    },

    events : {
        "click #filter-my" : function (e) {
            this.navigate(_.extend({}, this.filter, { my : !this.filter.my }));
        },
    },

    configure : function (options) {
        this.groups = options.groups;
        if (_.isUndefined(user.id) || _.isNull(user.id)) {
            this.$el.find('#filter-my').hide();
            console.warn("No current user Id specified. Hide the 'My' filter");
        }
        this.program = new EducationProgramSelect({
            $el : this.$("#filter-program"),
            template : getTemplate("#programselect-template"),
            searchlistTemplate : getTemplate("#program-searchlist-template"),
            max : 1,
        });
        this.listenTo(this.program.selectedCollection, "add", function (selected) {
            this.navigate(_.extend({}, this.filter, { programid : selected.id }));
        });
        this.listenTo(this.program.selectedCollection, "remove", function (selected) {
            this.navigate(_.omit(this.filter, "programid"));
        });
    },

    render : function () {
        this.program.reset(this.filter.programid);
        this.$el.find('#filter-my').toggleClass("on", this.filter.my);

        return this;
    },

    apply : function (filter, position, options) {
        options = _.defaults(options || {}, {
            navigate : true,
        });
        if (!_.isEqual(this.filter, filter) || !_.isEqual(this.position, position)) {
            console.log("Filter: " + JSON.stringify(filter) + ", Position: " + JSON.stringify(position));
            if (_.isUndefined(user.id) || _.isNull(user.id)) {
                _.extend(filter, { my : false });
            }
            this.filter = filter;
            this.position = position;
            this.render();
            if (options.navigate) {
                this.navigate();
            }
            this.groups.load(this.filter, this.position);
        } else {
            this.trigger("norender");
        }
    },

    navigate : function (filter, position) {
        filter = filter || this.filter;
        position = position || this.position;
        Backbone.history.navigate(
            (filter.my ? "/my" : "/all") +
                (filter.programid ? ("/programs/" + filter.programid) : "") +
                (position.year ? ("/years/" + position.year) : ""),
            { trigger : true, replace : true });
    },

});

var GroupDialog = Dialog.extend({

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
        this.program = new EducationProgramSelect({
            template : getTemplate("#programselect-template"),
            searchlistTemplate : getTemplate("#program-searchlist-template"),
            max : 1,
        });
        this.selectorValidations.push({
            selector : this.program,
            validator : function (programs) {
                return !_.isEmpty(programs);
            },
        });
    },

    render : function () {
        this.$el.html(getTemplate("#group-dialog-template")({
            g : this.model.toJSON(),
            minyear : this.minyear,
            maxyear : this.maxyear,
        }));
        this.$("[name='year']").spinner({
            min : this.minyear,
            max : this.maxyear,
        }).spinner("value", this.model.get('year') || this.minyear);
        this.responsible.reset(this.model.get('responsible'), { $el : this.$("[role='select-responsible']") });
        this.assistants.reset(this.model.get('assistants'), { $el : this.$("[role='select-assistants']") });
        var program = this.model.get("program");
        if (!program) {
            program = this.collection.filter.programid;
        }
        this.program.reset(program, { $el : this.$("[role='select-program']") });
    },

    ok : function () {
        var self = this;
        this.model.save({
            name : this.$("[name='name']").val(),
            year : Number(this.$("[name='year']").val()),
            program : _.first(this.program.selectedCollection.pluck('id')),
            responsible : _.first(this.responsible.selectedCollection.pluck('id')),
            assistants : this.assistants.selectedCollection.pluck('id'),
            students : undefined,
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

var AddStudentsDialog = Dialog.extend({

    events : {
        "click [name='add-to-list']" : function () {
            (new User()).save({
                username : this.$("[name='username']").val(),
                lastname : this.$("[name='lastname']").val(),
                firstname : this.$("[name='firstname']").val(),
                email : this.$("[name='email']").val(),
            }, {
                wait : true,
                success : function (student) {
                    this.students.selectedCollection.add(student);
                    this.$("[role='userdata']").clear();
                },
            });
        },
    },

    configure : function (options) {
        this.students = new UserSelect({
            template : getTemplate("#userselect-template"),
            searchlistTemplate : getTemplate("#user-search-list-template"),
        });
        this.selectorValidations.push({
            selector : this.students,
            validator : function (students) {
                return !_.isEmpty(students);
            },
        });
    },

    render : function () {
        var self = this;
        this.$el.html(getTemplate("#add-students-dialog-template")({
            m : this.model.toJSON(),
        }));
        this.students.reset(this.model.get('students'), { $el : this.$("[role='select-students']") });
    },

    ok : function () {
        var self = this;
        this.model.save({
            students : this.students.selectedCollection.pluck("id"),
        }, {
            wait : true,
            patch :  true,
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

    var groups = new AcademicGroups([], {});
    var groupList = new AcademicGroupsList({
        el : "#group-list",
        collection : groups,
    });
    
    var filter = new AcademicGroupsFilter({
        el : "#filter",
        groups : groups,
    });

    var navbar = new NavigationPanel({
        header : "#tool-epman .year-links",
        footer : "#tool-epman [role='page-footer'] .year-links",
    });

    var router = new AcademicGroupsRouter({
        filter : filter,
        groupList : groupList,
        navbar : navbar,
    });

    Backbone.history.start({ pushState: false });

    $("#add-group-button").click(function () {
        (new GroupDialog({
            model : new AcademicGroup({}, {}),
            collection : groups,
            el : "#group-dialog-template",
        })).open();
    });

    enableCheckFooter();
};

$(initPage);
