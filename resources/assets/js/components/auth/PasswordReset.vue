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
        <md-input-container v-bind:class="{ 'md-input-invalid' : errors.email || errors.login }">
            <label>Email</label>
            <md-input type="text" v-model="form.email"></md-input>
            <span v-if="errors.email" class="md-error">{{ errors.email[0] }}</span>
            <span v-if="errors.login" class="md-error">{{ errors.login[0] }}</span>
        </md-input-container>

        <md-button v-if="state.loggingIn" class="md-raised md-primary">
            Send Password Reset Link
            <md-spinner :md-size="10" md-indeterminate class="md-accent margin-top-10 margin-left-5"></md-spinner>
        </md-button>
        <md-button v-if="!state.loggingIn" @click.native="login()" class="md-raised md-primary">Send Password Reset Link</md-button>

        <p class="margin-top-10">Go back to <a href="/login" class="link-primary">login</a>.</p>
    </form>
    </div>
</template>


<script>
    export default {
        mounted() {
            console.log('Password reset component ready.')
        },
        data() {
            return {
                errors: {
                    email: null
                },
                notifyPosition: 'right',
                allowHtmlNotifications: true,
                queueNotifications: true,
                form: {
                    email: null,
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
                this.$http.post('/password/email', this.form).then((response) => {
                    console.log(response);
                    this.state.loggingIn = false;
                    this.showNotification("A password reset link has been sent to " + this.form.email);
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
                    duration: 5000
                });
            },
        }
    }
</script>
