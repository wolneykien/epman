
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
        "" : "start",
    },

    initialize : function (options) {
        this.programs = options.programs;
    },

    start : function () {
        console.log("Default route");
        this.programs.fetch({ reset:true });
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
    url: "/programs",

    initialize : function (models, options) {
        if (options.restRoot) {
            this.url = options.restRoot + this.url;
        }
        if (options.restParams && !_.isEmpty(options.restParams)) {
            this.url = this.url + '?' + $.param(options.restParams);
        }
        console.log("Education program URL: " + this.url);
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
var EducationProgramList = Backbone.View.extend({

    initialize : function (options) {
        this.template = _.template(this.$el.html ());
        this.listenTo(this.collection, 'reset', this.render);
    },

    render : function () {
        console.log("Render out the eduction program list");
        this.$el.empty ();
        if (!this.collection.isEmpty()) {
            this.collection.forEach(function (program) {
                this.$el.append(this.template({ p : program }));
            }, this);
        } else {
            console.log("Empty");
        }
        this.$el.show();
        return this;
    },

});


/* Init */

var initPage = function () {
    console.log("Init page");
    var options = toolEpmanPageOptions || {};
    Backbone.emulateHTTP = options.emulateHTTP || false;
    Backbone.emulateJSON = options.emulateJSON || false;

    var programs = new EducationPrograms([], options);
    var programList = new EducationProgramList({
        el : "#program-list",
        collection : programs,
    });
    
    var router = new EducationProgramsRouter({
        programs : programs,
    });
    
    Backbone.history.start ({ pushState: false });
};

$(initPage);
