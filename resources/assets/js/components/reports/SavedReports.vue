<template>
    <div>

        <ui-modal ref="savedReportsModal" title="Load a Saved Report" size="large">
            <md-list class="md-double-line" v-if="!state.loading" style="max-height: 600px; overflow: auto;">
                <md-list-item v-for="(report,index) in shared.savedReports">
                    <div class="md-list-text-container">
                        <span>{{ report.name }}</span>
                        <span>{{ report.nice_type }} - Saved {{ report.nice_date}} </span>
                    </div>

                    <md-button class="md-icon-button md-list-action" @click.native="loadReport(report)">
                        <md-icon class="md-primary">open_in_browser</md-icon>
                    </md-button>
                    <md-button class="md-icon-button md-list-action" @click.native="selectedReport = report; $refs['delete-report'].open();">
                        <md-icon class="md-primary">delete</md-icon>
                    </md-button>

                </md-list-item>

                <md-list-item v-if="shared.savedReports.length == 0">
                    <div class="md-list-text-container">
                        <span>No saved reports found.</span>
                    </div>
                </md-list-item>
            </md-list>

            <!-- Cloaked Place Holder Loading Content -->
            <div v-if="state.loading" class="margin-top-20 text-center">
                <div class="padding-30 text-center">
                    <div class="animated-background">
                        <div class="background-masker content-row"></div>
                        <div class="background-masker content-spacer"></div>
                        <div class="background-masker content-row"></div>
                        <div class="background-masker content-spacer"></div>
                        <div class="background-masker content-row"></div>
                        <div class="background-masker content-spacer"></div>
                        <div class="background-masker content-row"></div>
                    </div>
                </div>
            </div>
            <!-- End place holders -->

            <div slot="footer">
                <ui-button @click="$refs.savedReportsModal.close();">Cancel</ui-button>
            </div>
        </ui-modal>

        <md-dialog-confirm
                md-title="Delete this report?"
                md-content-html="This action can not be reversed. Please confirm you would like to delete this report."
                md-ok-text="Delete Report"
                md-cancel-text="Cancel"
                @close="deleteReport"
                ref="delete-report">
        </md-dialog-confirm>

    </div>
</template>

<script>
    export default {
        mounted() {
            console.log("Saved reports component ready.")
            this.refreshReports();
        },
        props: {

        },
        data: () => ({
            _: window._,
            shared: window.appShared,
            selectedReport: null,
            state: {
                loading: true,
            },
            options: {
                type: 'all',
                search : '',
            }
        }),
        computed: {

        },
        methods: {
            open(type) {
              if (this.options.type != type) {
                  this.options.type = type;
                  this.refreshReports();
              }

                this.$refs.savedReportsModal.open();
            },
            deleteReport(confirmation) {

                if (confirmation != 'ok') {
                    return;
                }

                this.$http.post('/apiv1/reports/delete/' + this.selectedReport.id).then((response) => {
                    this.$root.showNotification( response.body.message );
                    this.refreshReports();
                }, (response) => {
                    console.log("Error deleting report.");
                    this.$root.showNotification(response.body.message);
                });

            },
            loadReport(report) {
              window.location = report.url;
            },
            refreshReports()
            {
                this.state.loading = true;
                this.$http.get('/apiv1/reports', {params: this.options}).then((response) => {

                this.shared.savedReports = response.body;
                this.state.loading = false;
                }, (response) => {
                    console.log("Error loading reports.");
                    this.$root.showNotification("Error loading saved reports");
                });

            }

        }
    }
</script>