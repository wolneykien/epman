
var Course = Model.extend({

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

    configure : function (options) {
        if (!this.searchCollection) {
            this.searchCollection = new Courses();
        }
    },

    reset : function (arg) {
        var courses = [];
        if (arg) {
            if (!_.isArray(arg)) {
                arg = [arg];
            }
            courses = _.map(arg, function (course) {
                if (_.isObject(course)) {
                    return new Course(course);
                } else if (_.isNumber(course)) {
                    return new Course({ id : course }, { fetch : true });
                }
                return null;
            });
        }
        this.selectedCollection.reset(_.filter(courses, function (course) {
            return course != null;
        }));
    },

});
