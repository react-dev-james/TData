<template>
    <div>
        <md-table-card>
            <div class="padding-15">
                <h1 class="md-title pull-left">Jobs</h1>
                <div class="pull-right">
                    <md-menu md-size="4">
                        <md-button class="md-icon-button" md-menu-trigger>
                            <md-icon>filter_list</md-icon>
                            <md-tooltip md-direction="top">Filter Results</md-tooltip>
                        </md-button>

                        <md-menu-content>
                            <md-menu-item v-for="filter in filters" @selected="onFilter(filter)">
                                <span>{{ filter.name }}</span>
                            </md-menu-item>
                        </md-menu-content>
                    </md-menu>


                    <md-button class="md-icon-button" @click.native="refreshTable()">
                        <md-icon>cached</md-icon>
                        <md-tooltip md-direction="top">Refresh Table</md-tooltip>
                    </md-button>

                    <md-button v-if="isFiltered || state.searchSelected" class="md-icon-button" @click.native="clearFilters">
                        <md-icon>clear</md-icon>
                        <md-tooltip md-direction="top">Clear Search</md-tooltip>
                    </md-button>

                    <md-button v-if="!state.searchSelected" class="md-icon-button" @click.native="state.searchSelected = !state.searchSelected">
                        <md-icon>search</md-icon>
                    </md-button>

                    <md-button  class="md-icon-button" @click.native="state.selectedJob = {}; $refs.addJobModal.open();">
                        <md-icon>add_location</md-icon>
                        <md-tooltip md-direction="top">Add Location</md-tooltip>
                    </md-button>

                </div>
            </div>

            <div class="clearfix"></div>

            <div v-if="state.searchSelected" class="col-xs-12">
                <md-input-container class="md-theme-dark" md-inline>
                    <label>Search Jobs</label>
                    <md-input v-model="options.search" @change="onSearch"></md-input>
                    <md-button class="md-input-button" @click.native="state.searchSelected = !state.searchSelected">
                        <md-icon>search</md-icon>
                    </md-button>
                </md-input-container>
            </div>

            <div v-if="state.activeFilter" class="col-lg-12 bg-grey-200">
                <h4 class="margin-top-10 pull-left">Current Filter: {{ state.activeFilter.name }}</h4>
                <md-button class="md-icon-button pull-right" @click.native="clearFilters">
                    <md-icon>clear</md-icon>
                    <md-tooltip md-direction="top">Clear Filter</md-tooltip>
                </md-button>
                <div class="clearfix"></div>
            </div>

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
                        <div class="background-masker content-spacer"></div>
                        <div class="background-masker content-row"></div>
                        <div class="background-masker content-spacer"></div>
                        <div class="background-masker content-row"></div>
                        <div class="background-masker content-spacer"></div>
                    </div>
                </div>
            </div>
            <!-- End place holders -->

            <md-table v-else :md-sort="options.sort.name" :md-sort-type="options.sort.type" @sort="onSort" @select="onSelected">
                <md-table-header>
                    <md-table-row>
                        <md-table-head md-sort-by="name">ID</md-table-head>
                        <md-table-head md-sort-by="type">Level</md-table-head>
                        <md-table-head md-sort-by="job_type">Job Type</md-table-head>
                        <md-table-head>Message</md-table-head>
                        <md-table-head>Logs</md-table-head>
                        <md-table-head md-sort-by="created_at">Created</md-table-head>
                    </md-table-row>
                </md-table-header>

                <md-table-body>
                    <md-table-row v-for="(job, rowIndex) in shared.jobs" :key="rowIndex" :md-item="job">
                        <md-table-cell>
                            {{ job.id }}
                        </md-table-cell>
                        <md-table-cell>
                            <span class="label label-info pading-10-5">{{ job.type }}</span>
                        </md-table-cell>
                        <md-table-cell>
                            <span class="label label-info pading-10-5">{{ job.job_type }}</span>
                        </md-table-cell>
                        <md-table-cell>
                            {{ job.message }}
                        </md-table-cell>
                        <md-table-cell>
                            <md-button class="md-primary font-size-12" style="width: 110px;" @click.native="state.selectedJob = job; $refs.viewJobModal.open();">
                                View Logs ({{ job.logs.length }})
                            </md-button>
                        </md-table-cell>
                        <md-table-cell>
                            {{ job.created_at }}
                            <md-button @click.native="requeueJob(job)" class="margin-right-10">
                                <md-icon>queue</md-icon>
                            </md-button>
                        </md-table-cell>
                    </md-table-row>
                </md-table-body>
            </md-table>
            <md-table-pagination
                    :md-size="options.pager.size"
                    :md-total="totalJobs"
                    :md-page="options.pager.page"
                    md-label="Jobs"
                    md-separator="of"
                    :md-page-options="[20, 50, 100]"
                    @pagination="onPagination"></md-table-pagination>

            <div class="margin-top-40">&nbsp;</div>
        </md-table-card>

        <!-- Add New Job Modal -->
        <ui-modal ref="viewJobModal" title="View Logs" size="large">

            <ul class="list-group">
                <li v-for="job in state.selectedJob.logs" class="list-group-item padding-5">{{ job}}</li>
            </ul>

            <div slot="footer">
                <md-button class='md-warning' @click.native="$refs.viewJobModal.close();">Close</md-button>
            </div>
        </ui-modal>

        <!-- Add New Job Modal -->
        <ui-modal ref="addJobModal" title="Add New Location" size="large">
            <md-input-container v-bind:class="{ 'md-input-invalid' : errors.city  }">
                <label>City</label>
                <md-input type="text" v-model="state.selectedJob.city"></md-input>
                <span v-if="errors.city" class="md-error">{{ errors.city[0] }}</span>
            </md-input-container>

            <md-input-container v-bind:class="{ 'md-input-invalid' : errors.state  }">
                <label>State</label>
                <md-input type="text" v-model="state.selectedJob.state"></md-input>
                <span v-if="errors.state" class="md-error">{{ errors.state[0] }}</span>
            </md-input-container>

            <md-input-container v-bind:class="{ 'md-input-invalid' : errors.country  }">
                <label>Country</label>
                <md-input type="text" v-model="state.selectedJob.country"></md-input>
                <span v-if="errors.country" class="md-error">{{ errors.country[0] }}</span>
            </md-input-container>

            <div slot="footer">
                <md-button class='md-warning' @click.native="$refs.addJobModal.close();">Cancel</md-button>
                <md-button class='md-primary md-raised' @click.native="saveJob()">Save
                    <md-spinner v-if="state.saving" :md-size="10" md-indeterminate class="md-accent margin-top-10 margin-left-5"></md-spinner></md-button>
            </div>
        </ui-modal>


    </div>
</template>

<script type="text/babel">
    export default {
        mounted() {
            console.log(' Admin jobs component ready.')
            this.refreshTable();
        },
        data: () => ({
            state: {
                searchSelected: false,
                loading: false,
                selectedJob: {},
                saving: false,
                activeFilter: null,
            },
            errors : {},
            options: {
                pager: {
                    page: 1,
                    size: 20
                },
                sort: {
                    name: 'created_at',
                    type: 'asc'
                },
                search: '',
                filter: null
            },
            filters: [
                {
                    'name': 'Stats Updates',
                    'id': 'filter-stats'
                },
                {
                    'name': 'Rate Updates',
                    'id': 'filter-rates'
                },
                {
                    'name': 'Listing Updates',
                    'id': 'filter-listings'
                },
                {
                    'name': 'Property Updates',
                    'id': 'filter-properties'
                },
				{
					'name': 'Subset Updates',
					'id': 'filter-subsets'
				},
				{
					'name': 'Blocked Bookings Updates',
					'id': 'filter-blocks'
				}
            ],
            totalJobs: 5000,
            shared: window.appShared,
            searchTerm: null,
            notifyPosition: 'right',
            allowHtmlNotifications: true,
            queueNotifications: true,
        }),
        computed: {
            isFiltered: function () {
                return this.options.search || this.options.filter;
            }
        },
        methods: {
            clearFilters() {
                this.options.search = '';
                this.options.filter = '';
                this.state.activeFilter = null;
                this.state.searchSelected = false;
                this.refreshTable();
            },
            onPagination(pageOptions) {
                this.options.pager = pageOptions;
                this.refreshTable();
            },
            onSort(sortOptions) {
                this.options.sort = sortOptions;
                this.refreshTable();
            },
            onSelected(selectedRows) {
                this.options.selected = selectedRows;
            },
            onFilter(filter) {
                this.options.filter = filter.id;
                this.state.activeFilter = filter;
                this.refreshTable();
            },
            onSearch(term) {
                this.options.search = term;
                this.refreshTable();
            },
            requeueJob(job) {
                this.$http.post('/apiv1/jobs/requeue', job).then((response) => {
                    this.$root.showNotification(response.body.message);
                }, (response) => {
                    this.$root.showNotification(response.body.message);
                });
            },
            saveJob() {
                this.state.saving = true;
                this.$http.post('/apiv1/jobs/queue', this.state.selectedJob).then((response) => {
                    this.state.saving = false;
                    this.$root.showNotification(response.body.message);
                    this.$refs.addJobModal.close();
                }, (response) => {
                    this.state.saving = false;
                    this.errors = response.body;
                });
            },
            refreshTable()
            {
                this.state.loading = true;
                this.options.page = this.options.pager.page;
                this.$http.get('/apiv1/jobs', {params: this.options}).then((response) => {

                    this.shared.jobs = response.body.data;
                    this.options.pager.page = response.body['current_page'];
                    this.totalJobs = response.body.total;
                    this.state.loading = false;

                }, (response) => {
                    console.log("Error loading jobs");
                    console.log(response);
                });
            },
            openDialog(ref) {
                this.$refs[ref].open();
            }

        }
    }
</script>