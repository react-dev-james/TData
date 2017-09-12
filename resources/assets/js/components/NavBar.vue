<!--

Usage: <bomb-nav> </bomb-nav>
Properties: current-page [Declares which page should be active in navigation.]

-->

<template>

    <!-- <md-toolbar class="padding-5 no-margin-bottom affix-hug-top full-width bg-white border-bottom-1 border-grey-100" data-spy="affix" data-offset-top="20"> -->
    <md-toolbar class="padding-5 no-margin-bottom full-width bg-dark-transparent border-bottom-1 border-grey-400">
        <div class="col-lg-12 col-md-12">
            <div class="pull-left">
                <img src="/img/logo_xs.png" height="60" width="60"/>
                <md-button
                        class="hidden-xs md-primary"
                        :class="currentPage == 'home' ? 'link-primary' : 'link-accent' "
                        href="/">
                    Ticket Data
                </md-button>
                <md-button  v-if="shared.user.id"
                           class="no-margin-top"
                           :class="currentPage == 'listings' ? 'link-primary' : 'link-accent' ">
                    <md-icon>confirmation_number</md-icon>
                    Events
                    <md-icon>keyboard_arrow_down</md-icon>
                </md-button>
                <md-menu md-align-trigger class="hidden-xs hidden-sm pull-right" v-if="shared.user.is_admin">
                    <md-button md-menu-trigger
                               class="no-margin-top"
                               :class="currentPage == 'admin' ? 'link-primary' : 'link-accent' ">
                        <md-icon>supervisor_account</md-icon>
                        Admin
                        <md-icon>keyboard_arrow_down</md-icon>
                    </md-button>
                    <md-menu-content>
                        <md-menu-item href="/admin/users">
                            Manage Users
                        </md-menu-item>
                        <md-menu-item href="/admin/jobs">
                            View Logs
                        </md-menu-item>
                        <md-menu-item @click.native.prevent="$root.showNotification('Placeholder')" href="">
                            Manage Settings
                        </md-menu-item>
                    </md-menu-content>
                </md-menu>
            </div>
            <div class="pull-right">
                    <md-menu md-align-trigger v-if="shared.user.email" class="hidden-xs hidden-sm">
                        <md-button md-menu-trigger
                                   class="margin-top-10"
                                   :class="currentPage == 'account' ? 'link-primary' : 'link-accent' ">
                            <md-icon>person</md-icon>
                            {{ shared.user.name }}
                            <md-icon>keyboard_arrow_down</md-icon>
                        </md-button>
                        <md-menu-content>
                            <md-menu-item href="/logout">
                                Logout
                            </md-menu-item>
                            <md-menu-item @click.native.prevent="$root.showNotification('Placeholder Only')" href="/admin/account">
                                Your Account
                            </md-menu-item>
                        </md-menu-content>
                    </md-menu>
                    <div v-else>
                        <md-button
                                class="hidden-xs md-dense"
                                :class="currentPage == 'register' ? 'link-primary' : 'link-accent' "
                                href="/register">
                            Sign Up
                        </md-button>
                        <md-button
                                class="hidden-xs md-dense"
                                :class="currentPage == 'login' ? 'link-primary' : 'link-accent' "
                                href="/login">
                            Login
                        </md-button>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
    </md-toolbar>


</template>


<script>
    export default {
        mounted() {
            console.log(' Nav component ready.')
        },
        props: ['currentPage'],
        data() {
            return {
                shared: window.appShared
            }
        },
        computed: {
            sideBarVisible: function () {
                return this.$root.sideBarVisible;
            }
        },
        methods: {}
    }
</script>
