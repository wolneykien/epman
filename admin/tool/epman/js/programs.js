
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
    url: function () {
        if (_.isEmpty(this.urlParams)) {
            return this.urlBase;
        } else {
            return this.urlBase + '?' + $.param(this.urlParams);
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
 * Renders the list of the education programs.
 *
 * @param options {
 *     collection : EducationPrograms,
 * }
 *
 */
var EducationProgramsList = Backbone.View.extend({

    initialize : function (options) {
        this.template = _.template(this.$el.html ());
        this.listenTo(this.collection, 'reset', this.render);
        this.listenTo(this.collection, 'request', function() {
            console.log("Loading the education programs");
            this.$el.empty ();
            this.$el.toggleClass("loading", true);
            this.$el.show();
        });
        this.listenTo(this.collection, 'sync', function() {
            console.log("Done loading the education programs");
            this.$el.toggleClass("loading", false);
        });
    },

    render : function () {
        console.log("Render out the eduction program list");
        this.$el.empty ();
        var year = null;
        if (!this.collection.isEmpty()) {
            this.collection.forEach(function (program) {
                var newyear = (program.get('year') != year);
                year = program.get('year');
                this.$el.append(this.template(
                    { f: this.collection.filter,
                      p : program.toJSON(),
                      openyear : newyear,
                      year : year,
                      closeyear : newyear,
                    }
                ));
            }, this);
        } else {
            console.log("Empty");
        }
        if (year != null) {
            this.$el.append(this.template(
                { f: this.collection.filter,
                  p : null,
                  openyear : false,
                  year : year,
                  closeyear : true,
                }
            ));
            year++;
            while (year < 7) {
                this.$el.append(this.template(
                    { f: this.collection.filter,
                      p : null,
                      openyear : true,
                      year : year,
                      closeyear : true,
                    }
                ));
                year++;
            }
        }
        this.$el.show();
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
};

$(initPage);
