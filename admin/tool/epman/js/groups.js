
/**
 * Academic group list router.
 *
 */
var AcademicProgramsRouter = Backbone.Router.extend({

    position : {
        year : null,
        groupid : null,
    },

    routes : {
        "(/programs/:programid)(/years/:year)" : function (programid, year) {
            this.handleRoute({ my : false, programid : programid }, { year : year });
        },
        "my(/programs/:programid)(/years/:year)" : function (programid, year) {
            this.handleRoute({ my : true, programid : programid }, { year : year });
        },
        "all(/programs/:programid)(/years/:year)" : function (programid, year) {
            this.handleRoute({ my : false, programid : programid }, { year : year });
        },
        "(:groupid)" : function (groupid) {
            if (window.location.hash != "") {
                var fragment = window.location.hash.replace(/^#/, "");
                if (fragment.length > 1 && fragment.substr(-1) == "#") {
                    this.navigate(fragment.replace(/#+$/, ""), { trigger : true });
                    return;
                }
            }
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

    handleRoute : function (filter, position) {
        var params = $.params();
        if (!_.isEmpty(params) && params.groupid) {
            window.location.assign(window.location.pathname + "#" + params.groupid);
        } else {
            this.position = position;
            this.filter.apply(filter, position, { navigate : false });
            this.navbar.render((filter.programid ? ("/programs/" + filter.programid) : "") +
                               (filter.my ? "/my" : "/all"));
        }
    },

    jump : function () {
        var $el = null;
        if (this.position.groupid) {
            this.groupList.expand(this.position.groupid, { jump : true });
            this.navigate(window.location.hash + "#", { trigger : false });
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
        this.position = position;
        if (_.isUndefined(position.groupid) && _.isUndefined(position.year)) {
            this.position.year = 0;
        }
        if (filter.my) {
            _.extend(this.urlParams, { userid : user.id });
        } else {
            this.urlParams = _.omit(this.urlParams, "userid");
        }
        if (filter.programid) {
            _.extend(this.urlParams, { programid : filter.programid });
        } else {
            this.urlParams = _.omit(this.urlParams, "programid");
        }
        if (this.position.groupid) {
            _.extend(this.urlParams, { yeargroupid : position.groupid });
        } else {
            this.urlParams = _.omit(this.urlParams, "yeargroupid");
            _.extend(this.urlParams, { year : position.year });
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
        this.listenTo(this.model, "change", this.render);
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
        var addColumn = function (students, size, letter) {
            var slice = _.head(students, size);
            $students.append(getTemplate("#student-list-template")({
                students : slice,
                letter : letter,
            }));
            if (students.length > slice.length) {
                addColumn(_.rest(students, size), size, _.last(slice).lastname[0]);
            }
        };
        $students.empty();
        addColumn(data.g.students, data.g.students / 3);
        
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
                    el : "#student-dialog-template",
                })).open();
            });
            if (options.action.deleteStudents || options.action.copyStudents) {
                if (options.action.deleteStudents) {
                    $studentsHeader.find("[role='delete-students-button']").click(function (e) {
                        (new YesNoDialog({
                            yes : function () {
                                self.model.save({
                                    students : _.map(_.filter(studentMarkers, function (m) {
                                        return _.first(_.values(m));
                                    }), function (m) {
                                        return _.first(_.keys(m));
                                    }),
                                }, {
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
        this.listenTo(this.collection, "reset", this.render);
        this.listenTo(this.collection, "add", this.render);
        this.listenTo(this.collection, "remove", this.render);
        this.listenTo(this.collection, "sort", this.render);
        this.listenTo(this.collection, "change:year", this.render);
    },

    render : function () {
        console.log("Render out the academic group list");
        this.$el.empty();
        this.$el.show();
        var section = { year : 0 };
        if (!this.collection.isEmpty()) {
            this.$el.append(getTemplate("#list-section-template")({
                f : this.collection.filter,
                g : null,
                year : this.collection.first().get("year"),
            }));
            this.collection.forEach(function (group) {
                section.$el.append(getTemplate("#record-template")({
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
            this.navigate({ my : !this.filter.my });
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
            selectedCollection : new EducationPrograms(),
            max : 1,
        });
        this.listenTo(this.program.selectedCollection, "add", function (selected) {
            this.navigate(_.extend(this.filter, { programid : selected.first().id });
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
        if (!_.isEqual(this.filter, filter) && !_.isEqual(this.position, position)) {
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

    navigate : function (filter) {
        filter = filter || this.filter;
        Backbone.history.navigate(
            (filter.my ? "/my" : "/all") +
                (filter.programid ? ("/programs/" + filter.programid) : ""),
            { trigger : true });
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
            $el : this.$("[role='select-responsible']"),
            template : getTemplate("#userselect-template"),
            searchlistTemplate : getTemplate("#user-search-list-template"),
            selectedCollection : new Users(),
            defValue : user.id ? user.toJSON() : null,
            max : 1,
        });
        this.assistants = new UserSelect({
            $el : this.$("[role='select-assistants']"),
            template : getTemplate("#userselect-template"),
            searchlistTemplate : getTemplate("#user-search-list-template"),
            selectedCollection : new Users(),
        });
    },

    render : function () {
        this.$el.html(getTemplate("#group-dialog-template")({
            p : this.model.toJSON(),
            minyear : this.minyear,
            maxyear : this.maxyear,
        }));
        this.$("[name='year']").spinner({
            min : this.minyear,
            max : this.maxyear,
        }).spinner("value", this.model.get('year') || this.minyear);
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
            patch : true,
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
            $el : this.$("[role='select-students']"),
            template : getTemplate("#userselect-template"),
            searchlistTemplate : getTemplate("#user-search-list-template"),
            selectedCollection : new Users(),
        });
    },

    render : function () {
        var self = this;
        this.$el.html(getTemplate("#add-students-dialog-template", "[role='days']")({
            m : this.model.toJSON(),
        }));
        this.students.reset(this.model.get('students'));
    },

    ok : function () {
        var self = this;
        this.model.save({
            students : this.students.selectedCollection.pluck("id"),
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