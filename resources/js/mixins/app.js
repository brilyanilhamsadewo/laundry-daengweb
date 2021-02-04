import Permissions from './mixins/Permission.js'
Vue.mixin(Permissions)

import { mapActions, mapGetters } from 'vuex'

computed: {
    ...mapGetters(['isAuth'])
},
methods: {
    ...mapActions('user', ['getUserLogin'])
},
created() {
    if (this.isAuth) {
        this.getUserLogin() //REQUEST DATA YANG SEDANG LOGIN
    }
}