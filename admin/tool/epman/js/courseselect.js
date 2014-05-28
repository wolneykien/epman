
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

    formatResults : function (keywords, collection) {
        return findAllMatches(keywords, collection.map(function (course) {
            course = course.toJSON();
            course.catname = course.category + ": " + course.name;
            course.catshortname = course.category + ": " + course.name + " (" + course.shortname + ")";
            return course;
        }));
    },

});

var CourseSelect = MultiSelect.extend({

    collectionType : Courses,

});
