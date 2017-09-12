<!--

Usage: <bomb-login> </bomb-login>
Properties: redirect-url [Where to redirect after a successful login]

-->

<template>
    <form novalidate @submit.stop.prevent="submit">
        <md-input-container v-bind:class="{ 'md-input-invalid' : errors.email || errors.login }">
            <label>Email</label>
            <md-input type="text" v-model="form.email"></md-input>
            <span v-if="errors.email" class="md-error">{{ errors.email[0] }}</span>
            <span v-if="errors.login" class="md-error">{{ errors.login[0] }}</span>
        </md-input-container>

        <md-input-container md-has-password v-bind:class="{ 'md-input-invalid' : errors.password || errors.login }">
            <label>Password</label>
            <md-input type="password" v-model="form.password"></md-input>
            <span v-if="errors.password" class="md-error">{{ errors.password[0] }}</span>
        </md-input-container>

        <p class="margin-top-10"> <a href="/password/reset" class="link-primary">Forgot your password?</a></p>

        <md-button v-if="state.loggingIn" class="md-raised md-primary">
            Login
            <md-spinner :md-size="10" md-indeterminate class="md-accent margin-top-10 margin-left-5"></md-spinner>
        </md-button>
        <md-button v-if="!state.loggingIn" @click.native="login()" class="md-raised md-primary">Login</md-button>

        <p class="margin-top-10">Don't have an account? <a href="/register" class="link-primary">Sign Up</a></p>
    </form>
</template>


<script>
    export default {
        mounted() {
            console.log(' Login component ready.')
        },
        props: ['redirectUrl'],
        data() {
            return {
                errors: {
                    email: null,
                    password: null,
                    login: null
                },
                form: {
                    email: null,
                    password: null,
                    redirect: this.redirectUrl,
                },
                state: {
                    loggingIn: false
                }
            }
        },
        methods: {
            login: function () {
                this.errors = {}
                this.state.loggingIn = true;
                this.$http.post('/apiv1/login', this.form).then((response) => {
                    console.log(response);
                    this.state.loggingIn = false;
                    window.location = response.body.redirect;
                }, (response) => {
                    console.log(response);
                    this.state.loggingIn = false;
                    this.errors = response.body;
                });
            }
        }
    }
</script>
