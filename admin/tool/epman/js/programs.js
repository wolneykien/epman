
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
        if (typeof options.restRoot !== 'undefined') {
            this.url = options.restRoot + this.url;
        }
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
        this.$el.empty ();
        if (!this.collection.isEmpty()) {
            this.collection.forEach(function (program) {
                this.$el.append(this.template({ program : program }));
            }, this);
        }
        return this;
    },

});
