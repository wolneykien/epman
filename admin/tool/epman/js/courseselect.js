
var Course = Model.extend({

    defaults : {
        type : 0,
    },

    urlBase : "/courses",

});

var Courses = Collection.extend({

    model : Course,
    urlBase : "/courses",

    configure : function (options) {
        if (options.fetch) {
            this.fetch({ reset : true });
        }
    },

});

var CourseSelect = MultiSelect.extend({

    collectionType : Courses,

});
