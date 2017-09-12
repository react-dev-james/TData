<template>
    <div>
        <md-table-card>
            <div class="padding-15">
                <h1 class="md-title pull-left">Users</h1>
                <div class="pull-right">
                    <md-menu md-size="4">
                        <md-button class="md-icon-button" md-menu-trigger>
                            <md-icon>filter_list</md-icon>
                            <md-tooltip md-direction="top">Filter Results</md-tooltip>
                        </md-button>

                        <md-menu-content>
                            <md-menu-item v-for="(filter,index) in filters" @selected="onFilter(filter)" :key="index">
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

                    <md-button  class="md-icon-button" @click.native="state.selectedUser = {}; $refs.addUserModal.open();">
                        <md-icon>person_add</md-icon>
                        <md-tooltip md-direction="top">Add User</md-tooltip>
                    </md-button>

                </div>
            </div>

            <div class="clearfix"></div>

            <div v-if="state.searchSelected" class="col-xs-12">
                <md-input-container class="md-theme-dark" md-inline>
                    <label>Search Users</label>
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
                        <md-table-head md-sort-by="name">Name</md-table-head>
                        <md-table-head md-sort-by="email">Email</md-table-head>
                        <md-table-head md-sort-by="role">Role</md-table-head>
                        <md-table-head md-sort-by="created_at">Created</md-table-head>
                        <md-table-head md-sort-by="created_at">Options</md-table-head>
                    </md-table-row>
                </md-table-header>

                <md-table-body>
                    <md-table-row v-for="(user, rowIndex) in shared.users" :key="rowIndex" :md-item="user" >
                        <md-table-cell>
                            {{ user.name }}
                        </md-table-cell>
                        <md-table-cell>
                            {{ user.email }}
                        </md-table-cell>
                        <md-table-cell>
                            <span class="label label-info pading-10-5">{{ user.role }}</span>
                        </md-table-cell>
                        <md-table-cell>
                            {{ user.created_at }}
                        </md-table-cell>
                        <md-table-cell>
                            <md-button target="blank" class="md-icon-button" @click.native="editUser(user)">
                                <md-icon>edit</md-icon>
                            </md-button>
                            <md-button @click.native="loginAsUser(user)" target="blank" class="md-icon-button">
                                <md-icon>assignment_ind</md-icon>
                                <md-tooltip md-direction="top">Login as {{ user.name }}</md-tooltip>
                            </md-button>
                            <md-button class="md-icon-button" @click.native="confirmDeleteUser(user)">
                                <md-icon>delete</md-icon>
                            </md-button>
                        </md-table-cell>
                    </md-table-row>
                </md-table-body>
            </md-table>
            <md-table-pagination
                    :md-size="options.pager.size"
                    :md-total="totalUsers"
                    :md-page="options.pager.page"
                    md-label="Users"
                    md-separator="of"
                    :md-page-options="[20, 50, 100]"
                    @pagination="onPagination"></md-table-pagination>

            <div class="margin-top-40">&nbsp;</div>
        </md-table-card>

        <md-dialog-confirm
                md-title="Delete this user?"
                md-content-html="This action can not be reversed and the users data will be permenantly deleted."
                md-ok-text="Delete User"
                md-cancel-text="Cancel"
                @close="deleteUser"
                ref="delete-user">
        </md-dialog-confirm>

        <!-- Add New User Modal -->
        <ui-modal ref="addUserModal" title="Add / Edit User" size="large">

            <md-input-container v-bind:class="{ 'md-input-invalid' : errors.name  }">
                <label>Name</label>
                <md-input type="text" v-model="state.selectedUser.name"></md-input>
                <span v-if="errors.name" class="md-error">{{ errors.name[0] }}</span>
            </md-input-container>

            <md-input-container v-bind:class="{ 'md-input-invalid' : errors.email  }">
                <label>Email</label>
                <md-input type="text" v-model="state.selectedUser.email"></md-input>
                <span v-if="errors.email" class="md-error">{{ errors.email[0] }}</span>
            </md-input-container>

            <md-input-container v-bind:class="{ 'md-input-invalid' : errors.role }">
                <label>Role</label>
                <md-select  v-model="state.selectedUser.role">
                    <md-option value="admin">Admin</md-option>
                    <md-option value="customer">Client</md-option>
                </md-select>
                <span v-if="errors.role" class="md-error">{{ errors.role[0] }}</span>
            </md-input-container>

            <md-input-container md-has-password v-bind:class="{ 'md-input-invalid' : errors.password  }">
                <label>Password</label>
                <md-input type="password" v-model="state.selectedUser.password"></md-input>
                <span v-if="errors.password" class="md-error">{{ errors.password[0] }}</span>
            </md-input-container>

            <md-input-container md-has-password v-bind:class="{ 'md-input-invalid' : errors.password_confirmation  }">
                <label>Confirm Password</label>
                <md-input type="password" v-model="state.selectedUser.password_confirmation"></md-input>
            </md-input-container>

            <div slot="footer">
                <md-button class='md-warning' @click.native="$refs.addUserModal.close();">Cancel</md-button>
                <md-button class='md-primary md-raised' @click.native="saveUser()">Save
                    <md-spinner v-if="state.saving" :md-size="10" md-indeterminate class="md-accent margin-top-10 margin-left-5"></md-spinner></md-button>
            </div>
        </ui-modal>


    </div>
</template>

<script type="text/babel">
    export default {
        mounted() {
            console.log(' Admin users component ready.')
            this.refreshTable();
        },
        data: () => ({
            state: {
                searchSelected: false,
                loading: false,
                selectedUser: {},
                saving: false
            },
            errors : {},
            options: {
                pager: {
                    page: 1,
                    size: 20
                },
                sort: {
                    name: 'created_at',
                    type: 'desc'
                },
                search: '',
                filter: null
            },
            filters: [
                {
                    'name': 'Admins Only',
                    'id': 'filter-admins'
                },
                {
                    'name': 'Clients Only',
                    'id': 'filter-clients'
                },
            ],
            totalUsers: 5000,
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
                this.refreshTable();
            },
            onSearch(term) {
                this.options.search = term;
                this.refreshTable();
            },
            refreshTable()
            {
                this.state.loading = true;
                this.options.page = this.options.pager.page;
                this.$http.get('/apiv1/users', {params: this.options}).then((response) => {

                    this.shared.users = response.body.data;
                    this.options.pager.page = response.body['current_page'];
                    this.totalUsers = response.body.total;
                    this.state.loading = false;

                }, (response) => {
                    console.log("Error loading users");
                    console.log(response);
                });
            },
            saveUser: function () {
                this.errors = {}
                this.state.saving = true;
                var url = '/apiv1/users/create';
                if (this.state.selectedUser.id) {
                    url = '/apiv1/users/update/' + this.state.selectedUser.id;
                }

                this.$http.post(url, this.state.selectedUser).then((response) => {
                    console.log(response);
                    this.state.saving = false;
                    this.$root.showNotification(response.body.message);
                    this.refreshTable();
                    this.$refs.addUserModal.close();
                }, (response) => {

                    this.state.saving = false;
                    this.errors = response.body;
                });
            },
            editUser(user) {
                this.state.selectedUser = user;
                this.$refs.addUserModal.open();
            },
            loginAsUser(user) {
                this.$root.showNotification('Logging you in as ' + user.name + "...");
                setTimeout(function () {
                    window.location = "/admin/users/loginAs/" + user.id;
                }, 2000);
            },
            confirmDeleteUser(user) {
                this.state.selectedUser = user;
                this.$refs['delete-user'].open();
            },
            deleteUser(confirmation) {
                if (confirmation !== 'ok') {
                    return;
                }

                if (!this.state.selectedUser || !this.state.selectedUser.id) {
                    this.$root.showNotification('Invalid user selected, please refresh the users and try again.');
                }

                this.$http.post('/apiv1/users/delete/' + this.state.selectedUser.id, this.state.selectedUser).then((response) => {

                    this.$root.showNotification(response.body.message);
                    this.refreshTable();

                }, (response) => {
                    this.$root.showNotification(response.body.message);
                    console.log(response);
                });

                this.state.selectedUser = {}

            },
            openDialog(ref) {
                this.$refs[ref].open();
            }

        }
    }
</script>