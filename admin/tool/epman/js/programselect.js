
var EducationProgram = Model.extend({

    urlBase : "/programs",

    defaults : {
        assistants : [],
    },

});

var EducationPrograms = Collection.extend({

    model: EducationProgram,
    urlBase : "/programs",

});

var EducationProgramSelect = MultiSelect.extend({

    configure : function (options) {
        if (!this.searchCollection) {
            this.searchCollection = new EducationEducationPrograms();
        }
    },

    reset : function (arg) {
        var programs = [];
        if (arg) {
            if (!_.isArray(arg)) {
                arg = [arg];
            }
            programs = _.map(arg, function (program) {
                if (_.isObject(program)) {
                    return new EducationProgram(program);
                } else if (_.isNumber(program)) {
                    return new EducationProgram({ id : program }, { fetch : true });
                }
                return null;
            });
        }
        this.selectedCollection.reset(_.filter(programs, function (program) {
            return program != null;
        }));
    },

});
