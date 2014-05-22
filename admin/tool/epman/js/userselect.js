
var User = Model.extend({

    urlBase : "/users",

});

var Users = Collection.extend({

    model : User,
    urlBase : "/users",

    configure : function (options) {
        if (options.fetch) {
            this.fetch({ reset : true });
        }
    },

});

var UserSelect = MultiSelect.extend({
    
    collectionType : Users,

});
