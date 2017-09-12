<!--

Usage: <sage-login> </sage-login>
Properties: redirect-url [Where to redirect after a successful login]

-->

<template>
    <form novalidate @submit.stop.prevent="submit">

        <md-input-container v-bind:class="{ 'md-input-invalid' : errors.name  }">
            <label>Name</label>
            <md-input type="text" v-model="form.name"></md-input>
            <span v-if="errors.name" class="md-error">{{ errors.name[0] }}</span>
        </md-input-container>
        
        <md-input-container v-bind:class="{ 'md-input-invalid' : errors.email  }">
            <label>Email</label>
            <md-input type="text" v-model="form.email"></md-input>
            <span v-if="errors.email" class="md-error">{{ errors.email[0] }}</span>
        </md-input-container>

        <md-input-container md-has-password v-bind:class="{ 'md-input-invalid' : errors.password  }">
            <label>Password</label>
            <md-input type="password" v-model="form.password"></md-input>
            <span v-if="errors.password" class="md-error">{{ errors.password[0] }}</span>
        </md-input-container>

        <md-input-container md-has-password v-bind:class="{ 'md-input-invalid' : errors.password_confirmation  }">
            <label>Confirm Password</label>
            <md-input type="password" v-model="form.password_confirmation"></md-input>
        </md-input-container>

        <md-button v-if="state.loggingIn" class="md-raised md-primary">
            Sign Up
            <md-spinner :md-size="10" md-indeterminate class="md-accent margin-top-10 margin-left-5"></md-spinner>
        </md-button>

        <md-button v-if="!state.loggingIn" @click.native="register()" class="md-raised md-primary">Sign Up</md-button>

        <p class="margin-top-10">Aready have an account? <a href="/login" class="link-primary">Login</a></p>
    </form>
</template>


<script>
    export default {
        mounted() {
            console.log('Register component ready.')
        },
        props: ['redirectUrl'],
        data() {
            return {
                errors: {
                    name: null,
                    email: null,
                    password: null,
                    login: null
                },
                form: {
                    name: null,
                    email: null,
                    password: null,
                    password_confirmation: null,
                    redirect: this.redirectUrl,
                },
                state: {
                    loggingIn: false
                }
            }
        },
        methods: {
            register: function () {
                this.errors = {}
                this.state.loggingIn = true;
                this.$http.post('/apiv1/register', this.form).then((response) => {
                    console.log(response);
                    this.state.loggingIn = false;
                    window.location = this.form.redirect;
                }, (response) => {
                    console.log(response);
                    this.state.loggingIn = false;
                    this.errors = response.body;
                });
            }
        }
    }
</script>
