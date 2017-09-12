<!--

Usage: <sage-password-reset> </sage-password-reset>

-->

<template>
    <div>
    <ui-snackbar-container
            ref="notifyContainer"
            :position="notifyPosition"
            :queue-snackbars="queueNotifications"
            :allowHtml="allowHtmlNotifications"
    ></ui-snackbar-container>
    <form novalidate @submit.stop.prevent="submit">
        <input type="hidden" name="token" value="resetToken">
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
            Reset Password
            <md-spinner :md-size="10" md-indeterminate class="md-accent margin-top-10 margin-left-5"></md-spinner>
        </md-button>
        <md-button v-if="!state.loggingIn" @click.native="login()" class="md-raised md-primary">Reset Password</md-button>

        <p class="margin-top-10">Go back to <a href="/login" class="link-primary">login</a>.</p>
    </form>
    </div>
</template>


<script>
    export default {
        mounted() {
            console.log('Password reset confirm component ready.')
            this.form.email = this.email;
            this.form.token = this.token;
        },
        props: ['token', 'email'],
        data() {
            return {
                errors: {
                    name: null,
                    email: null,
                    password: null,
                },
                form: {
                    token: null,
                    name: null,
                    email: null,
                    password: null,
                    password_confirmation: null
                },
                notifyPosition: 'center',
                allowHtmlNotifications: true,
                queueNotifications: true,
                state: {
                    loggingIn: false
                }
            }
        },
        methods: {
            login: function () {
                this.errors = {}
                this.state.loggingIn = true;
                this.$http.post('/password/reset', this.form).then((response) => {
                    console.log(response);
                    this.state.loggingIn = false;
                    this.showNotification("Your password has been reset. You can now login with your new password.");
                }, (response) => {
                    console.log(response);
                    this.state.loggingIn = false;
                    this.errors = response.body;
                });
            },
            showNotification(message) {
                this.$refs.notifyContainer.createSnackbar({
                    message: message,
                    action: '',
                    actionColor: 'accent',
                    duration: 4000
                });
            },
        }
    }
</script>
