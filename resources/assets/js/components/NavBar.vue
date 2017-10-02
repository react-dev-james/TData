<!--

Usage: <bomb-nav> </bomb-nav>
Properties: current-page [Declares which page should be active in navigation.]

-->

<template>

    <!-- <md-toolbar class="padding-5 no-margin-bottom affix-hug-top full-width bg-white border-bottom-1 border-grey-100" data-spy="affix" data-offset-top="20"> -->
    <md-toolbar class="padding-5 no-margin-bottom full-width bg-white-transparent border-bottom-1 border-grey-400">
        <div class="col-lg-12 col-md-12">
            <div class="pull-left">
                <img src="/img/mjseats.png" height="50" width="50"/>
                <md-button  v-if="shared.user.id"
                           class=""
                            href="/admin/listings"
                           :class="currentPage == 'listings' ? 'link-primary' : 'link-accent' ">
                    Event Listings
                </md-button>
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
                            <md-menu-item v-if="shared.user.is_admin" href="/admin/users">
                                Manage Users
                            </md-menu-item>
                            <md-menu-item v-if="shared.user.is_admin" href="/admin/jobs">
                                View Logs
                            </md-menu-item>
                            <md-menu-item v-if="shared.user.is_admin" @click.native.prevent="$root.showNotification('Placeholder')" href="">
                                Manage Settings
                            </md-menu-item>
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
