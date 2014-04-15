
var User = Model.extend({

    urlBase : "/users",

});

var Users = Collection.extend({

    model : User,
    urlBase : "/users",

});

var UserSelect = MultiSelect.extend({

    configure : function (options) {
        _.extend(this, _.pick(options,
            'selectedCollection',
            'searchCollection',
        ));
    },

});
