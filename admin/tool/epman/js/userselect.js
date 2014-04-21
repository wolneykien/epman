
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

    configure : function (options) {
        if (!this.searchCollection) {
            this.searchCollection = new Users();
        }
    },

    reset : function (arg) {
        var users = [];
        if (arg) {
            if (!_.isArray(arg)) {
                arg = [arg];
            }
            users = _.map(arg, function (user) {
                if (_.isObject(user)) {
                    return new User(user);
                } else if (_.isNumber(user)) {
                    return new User({ id : user }, { fetch : true });
                }
                return null;
            });
        }
        this.selectedCollection.reset(_.filter(users, function (user) {
            return user != null;
        }));
    },

});
