
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

    collectionType : EducationPrograms,

});
