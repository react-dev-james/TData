/* Load dependencies */
require('./bootstrap');


/* Initialize Vue */
//import KeenUI from 'keen-ui';
//Vue.use(KeenUI);
import vueSlider from 'vue-slider-component';

var VueMaterial = require('vue-material')
Vue.use(VueMaterial);

import KeenUI from 'keen-ui';
Vue.use(KeenUI);

Vue.material.registerTheme('default', {
    primary: {
		color: 'blue',
		hue: 500
    },
    accent: {
        color: 'blue',
        hue: 500
    },
    warn: {
        color: 'red',
        hue: 600
    },
})

Vue.component('bomb-login', require('./components/auth/Login.vue'));
Vue.component('bomb-register', require('./components/auth/Register.vue'));
Vue.component('bomb-password-reset', require('./components/auth/PasswordReset.vue'));
Vue.component('bomb-password-reset-confirm', require('./components/auth/PasswordResetConfirm.vue'));
Vue.component('bomb-nav', require('./components/NavBar.vue'));
Vue.component('bomb-sidenav', require('./components/nav/SideNav.vue'));

/* Admin Components */
Vue.component('trans-admin-listings', require('./components/admin/Listings.vue'));
Vue.component('trans-admin-users', require('./components/admin/Users.vue'));
Vue.component('trans-table-sort', require('./components/admin/TableSort.vue'));

/* Central vue instance for component commonucation */
window.appBus = new Vue();

const app = new Vue({
    el: '#app',
    components: {
        vueSlider
    },
    mounted() {
        $(document).ready(function () {
            //$('body').removeClass("bg-white").removeClass("md-theme-default").removeClass("md-theme-dark");
        });
    },
    data: {
        shared: window.appShared,
        errors: {},
        snackBarMessage: '',
        sideBarVisible: false,

    },
    methods: {
        openDialog(ref) {
            this.$refs[ref].open();
        },
        closeDialog(ref) {
            this.$refs[ref].close();
        },
        showNotification(message) {
            this.snackBarMessage = message;
            this.$refs.snackbar.open();
        },
        toggleSidebar(ref) {
            if (this.sideBarVisible) {
                $(".sidenav").css('left','-1000px').hide();
                $('.content').css('padding-left', '0px');
            } else{
                $(".sidenav").css('left', '0px').show();
                $('.content').css('padding-left', '240px');
            }

            this.sideBarVisible = !this.sideBarVisible;


        }
    }
});
