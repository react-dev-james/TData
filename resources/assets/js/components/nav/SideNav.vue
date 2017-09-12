<!--

Usage: <bomb-sidenav> </bomb-sidenav>
Requires: window.appShared.user

-->

<template>
    <md-sidenav class="md-left md-fixed" ref="sideNav">
        <md-toolbar class="md-account-header">
            <md-list class="md-transparent">
                <md-list-item class="md-avatar-list">
                    <md-avatar class="md-large">
                        <img src="https://placeimg.com/64/64/people/8" alt="People">
                    </md-avatar>
                    <div class="pull-left margin-left-10">
                        <h4>{{ shared.user.name }}</h4>
                        <span class="font-size-10">{{ shared.user.email }}</span>
                    </div>
                </md-list-item>
            </md-list>
        </md-toolbar>

        <md-list>
            <md-list-item href="/logout" class="md-primary md-raised">
                <md-icon>exit_to_app</md-icon>
                <span>Logout</span>
            </md-list-item>

            <md-list-item href="/dashboard" class="md-primary md-raised">
                <md-icon>dashboard</md-icon>
                <span>Dashboard</span>
            </md-list-item>

            <md-list-item  href="/profile/edit" class="md-primary md-raised">
                <md-icon>edit</md-icon>
                <span>Edit Profile</span>
            </md-list-item>

        </md-list>
    </md-sidenav>
</template>


<script>
    export default {
        mounted() {
            console.log(' Side Nav component ready.')
        },
        props: ['loggedIn'],
        data() {
            return {
                shared: window.appShared
            }
        },
        methods : {
            toggle() {
                this.$refs['sideNav'].toggle();
            }
        }
    }
</script>
