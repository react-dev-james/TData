<template>
<div>
    <md-whiteframe class="bg-white margin-bottom-20 padding-15 md-table-card md-card display-block" v-if="state.reportItems.length > 0">
        <h1 class="md-title margin-bottom-10 pull-left">Selected Listings For Reports</h1>
        <md-button class="pull-right md-warn" @click.native="state.reportItems = []">
            Clear All
        </md-button>
        <div class="clearfix"></div>
        <div class="col-lg-12">
            <div class="col-lg-3 margin-top-5 margin-top-sm-15 margin-top-xs-15" v-for="(listing, rowIndex) in state.reportItems" :key="rowIndex">
                <div class="padding-5 border-1 border-grey-100 bg-grey-100 border-radius-5">
                    <span>
                       {{ listing.name|limitTo(20) }}
                        <md-tooltip md-direction="top">{{ listing.name }}</md-tooltip>
                    </span>

                    <span class="pull-right">
                    <md-icon @click.prevent.native="removeReportItem(rowIndex)" class="pull-right cursor-pointer">clear</md-icon>
                </span>
                </div>
            </div>
        </div>
        <trans-report-select :report-items="state.reportItems" item-type="listings" max-items="50" :stacked="false"></trans-report-select>
        <div class="clearfix"></div>
    </md-whiteframe>

    <md-table-card>

            <div class="padding-15">
                <div class="pull-left">
                <h1 class="md-title">Listings <span class="text-muted" v-if="shared.activeReport">({{ shared.activeReport.name }})</span></h1>
                <a v-if="shared.activeReport" href="/admin/listings" class="text-muted">Clear Custom Report</a>
                </div>
                <div class="pull-right">
                    <md-menu md-size="4">
                        <md-button class="md-icon-button" md-menu-trigger>
                            <md-icon>filter_list</md-icon>
                            <md-tooltip md-direction="top">Filter Results</md-tooltip>
                        </md-button>

                        <md-menu-content>
                            <md-menu-item v-for="(filter,index) in filters" @selected="onFilter(filter)" :key="index" >
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

                    <md-button v-if="!state.sortSelected" class="md-icon-button" @click.native="state.sortSelected = !state.sortSelected">
                        <md-icon>sort</md-icon>
                    </md-button>

                    <md-button v-if="state.sortSelected" class="md-icon-button" @click.native="state.sortSelected = !state.sortSelected">
                        <md-icon>clear_all</md-icon>
                    </md-button>

                </div>
            </div>


            <div class="clearfix"></div>

            <div v-if="state.sortSelected" class="col-xs-12">
                <trans-table-sort :columns="sortColumns" @multiSort="onMultiSort"></trans-table-sort>
            </div>

            <div v-if="state.searchSelected" class="col-xs-12">
                <div class="col-xs-12 col-sm-3">
                    <md-input-container class="margin-bottom-5 margin-top-10">
                        <label>Select a Field</label>
                        <md-select v-model="options.searchField">
                            <md-option value="all">All Fields</md-option>
                            <md-option value="city">City</md-option>
                            <md-option value="name">Name</md-option>
                            <md-option value="host_name">Host Name</md-option>
                        </md-select>
                    </md-input-container>
                </div>
                <div class="col-xs-12 col-sm-9">
                    <md-input-container class="md-theme-dark" md-inline>
                        <label>Search Listings</label>
                        <md-input v-model="options.search" @change="onSearch"></md-input>
                        <md-button class="md-input-button" @click.native="state.searchSelected = !state.searchSelected">
                            <md-icon>search</md-icon>
                        </md-button>
                    </md-input-container>
                </div>
            </div>

        <div v-if="state.activeFilter" class="col-lg-12 bg-grey-200">
            <h4 class="margin-top-10 pull-left">Current Filter: {{ state.activeFilter.name }}</h4>
            <md-button class="md-icon-button pull-right" @click.native="clearFilters">
                <md-icon>clear</md-icon>
                <md-tooltip md-direction="top">Clear Filter</md-tooltip>
            </md-button>
            <div class="clearfix"></div>
        </div>

        <div v-if="selectedListings > 0" class="col-lg-12 bg-grey-200">
            <h4 class="margin-top-10">Selected {{ selectedListings }} Listing(s)</h4>
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
                        <md-table-head md-sort-by="city">Location</md-table-head>
                        <md-table-head md-sort-by="bedrooms" >Rooms</md-table-head>
                        <md-table-head md-sort-by="beds" >Beds</md-table-head>
                        <md-table-head md-sort-by="current_rate" >Rate</md-table-head>
                        <md-table-head md-sort-by="percent_booked" >Occupancy</md-table-head>
                        <md-table-head md-sort-by="price_per_bed" >Per Bed</md-table-head>
                        <md-table-head md-sort-by="projected_revenue" >Revenue</md-table-head>
                        <md-table-head md-sort-by="profit_score">Score</md-table-head>
                        <md-table-head md-sort-by="capacity" >Capacity</md-table-head>
                        <md-table-head md-sort-by="room_type">Type</md-table-head>
                        <md-table-head md-sort-by="outlier">Outlier</md-table-head>
                        <md-table-head >Options</md-table-head>
                    </md-table-row>
                </md-table-header>

                <md-table-body>
                    <md-table-row v-for="(listing, rowIndex) in shared.listings" :md-item="listing" :md-selection="true" :key="rowIndex">
                        <md-table-cell>
                        <span>
                           {{ listing.name|limitTo(24) }}
                            <md-tooltip md-direction="top">{{ listing.name }}</md-tooltip>
                        </span>

                        </md-table-cell>
                        <md-table-cell>
                            <span v-if="listing.locations[0]">
                               <span v-if="listing.locations[0].country == 'United States'">
                                     {{ listing.locations[0].city }}, {{ listing.locations[0].state }}
                                </span>
                                <span v-else>
                                     {{ listing.locations[0].city }}, {{ listing.locations[0].country }}
                                </span>
                            </span>
                            <span v-else>N/A</span>
                        </md-table-cell>
                        <md-table-cell>
                        <span class="label label-info pading-10-5">{{ listing.bedrooms }}</span>
                        </md-table-cell>
                        <md-table-cell>
                        <span class="label label-info pading-10-5">{{ listing.beds }}</span>
                        </md-table-cell>
                        <md-table-cell>
                            <span class="label label-info pading-10-5">${{ listing.current_rate }}</span>
                        </md-table-cell>
                        <md-table-cell>
                            <span class="label label-info pading-10-5">{{ listing.stats ? listing.stats.percent_booked : 'N/A' }}%</span>
                        </md-table-cell>
                        <md-table-cell>
                            <span class="label label-info pading-10-5">${{ listing.stats ? listing.stats.price_per_bed : 'N/A' }}</span>
                        </md-table-cell>
                        <md-table-cell>
                            <span class="label label-info pading-10-5">${{ listing.stats ? listing.stats.projected_revenue  : 'N/A'}}</span>
                        </md-table-cell>
                        <md-table-cell>
                            <span class="label label-info pading-10-5">{{ listing.profit_score ? listing.profit_score  : 'N/A'}}</span>
                        </md-table-cell>
                        <md-table-cell>
                            <span class="label label-info pading-10-5">{{ listing.capacity }}</span>
                        </md-table-cell>
                        <md-table-cell>
                            <span >{{ listing.room_type }}</span>
                        </md-table-cell>
                        <md-table-cell>
                            <trans-listing-outlier-status :listing="listing"></trans-listing-outlier-status>

                        </md-table-cell>
                        <md-table-cell>
                            <md-menu md-size="5" md-direction="top left">
                                <md-button class="md-icon-button md-list-action" md-menu-trigger>
                                    <md-icon clas="md-primary">more_vert</md-icon>
                                </md-button>
                                <md-menu-content>
                                    <md-menu-item :href="listing.source_link" target="_blank">
                                            <md-icon>hotel</md-icon>
                                            <span>View on AirBnb</span>
                                    </md-menu-item>
                                    <md-menu-item @click.native="confirmDeleteListing(listing)">
                                        <md-icon >delete</md-icon>
                                        <span>Delete</span>
                                    </md-menu-item>
                                </md-menu-content>
                            </md-menu>
                        </md-table-cell>
                    </md-table-row>
                </md-table-body>
            </md-table>
            <md-table-pagination
                    :md-size="options.pager.size"
                    :md-total="totalListings"
                    :md-page="options.pager.page"
                    md-label="Listings"
                    md-separator="of"
                    :md-page-options="[20, 50, 100]"
                    @pagination="onPagination"></md-table-pagination>

            <div class="margin-top-40">&nbsp;</div>
    </md-table-card>

    <md-dialog-confirm
            md-title="Delete this listing?"
            md-content-html="This action can not be reversed and the listings data will be permenantly deleted."
            md-ok-text="Delete Listing"
            md-cancel-text="Cancel"
            @close="deleteListing"
            ref="delete-listing">
    </md-dialog-confirm>



</div>
</template>

<script type="text/babel">
    export default {
        mounted() {
            console.log(' Admin listings component ready v2.')
            this.refreshTable();

        },
        data: () => ({
            state: {
                searchSelected : false,
				sortSelected: false,
                loading: false,
                selectedListing : null,
                selectedListings : null,
                activeFilter: null,
                reportItems: []
            },
            _: window._,
            shared: window.appShared,
            columns: ['name','source','bedrooms','beds','capacity','location','type','current_rate'],
            options: {
                pager: {
                    page: 1,
                    size: 20
                },
                sort : {
                    name: 'created_at',
                    type: 'desc'
                },
				multiSort: [],
                search: '',
                searchField: 'all',
                filter: null,
                reportId: null
            },
            filters: [
                {
                    'name' : 'Homes Only',
                    'id' : 'filter-homes'
                },
                {
                    'name' : 'Condos Only',
                    'id' : 'filter-condos'
                },
                {
                    'name' : 'Outliers Only',
                    'id' : 'filter-outliers'
                },
                {
                    'name': 'No Outliers',
                    'id': 'filter-no-outliers'
                },
            ],
			sortColumns: [
				{name: 'Creation Date', field: 'created_at'},
				{name: 'Name', field: 'name'},
				{name: 'Type', field: 'room_type'},
				{name: 'Number of Rooms', field: 'bedrooms'},
				{name: 'Number of Beds', field: 'beds'},
				{name: 'Capacity', field: 'capacity'},
				{name: 'Rate', field: 'current_rate'},
				{name: 'Occupancy Rate', field: 'percent_booked'},
				{name: 'Price Per Bed', field: 'price_per_bed'},
				{name: 'Monthly Revenue', field: 'projected_revenue'},
				{name: 'Profit Score', field: 'profit_score'},
				{name: 'Outlier Status', field: 'outlier'},
				{name: 'Source', field: 'source'},
			],
            selectedListings: 0,
            totalListings: 50000,
            searchTerm: null,
        }),
        filters: {
            limitTo: function (string, value) {
                if (!string) {
                    return '';
                }

                if (string.length <= value) {
                    return string;
                }

                return string.substring(0, value) + '...';
            },
            capitalize: function (text) {
                return text[0].toUpperCase() + text.slice(1)
            }
        },
        computed: {
            isFiltered : function() {
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
			onMultiSort(sortOptions) {
				this.options.multiSort = sortOptions;
				this.refreshTable();
			},
            onSort(sortOptions) {
                this.options.sort = sortOptions;
                this.refreshTable();
            },
            onSelected(selectedRows) {
                var vm = this;
                this.state.selectedListings = selectedRows;
                /* Add any selected rows to the selected report items if not already in list */
                _.each(selectedRows, function (item, key) {
                    var isDuplicate = false;
                    _.each(vm.state.reportItems, function (existingItem, existingkey) {
                        if (existingItem === item) {
                            isDuplicate = true;
                        }
                    });

                    if (!isDuplicate) {
                        vm.state.reportItems.push(item);
                    }
                });

                this.selectedListings = _.keys(this.state.selectedListings).length;
            },
            removeReportItem(index) {
                this.state.reportItems.splice(index, 1);
            },
            onFilter(filter) {
                this.state.activeFilter = filter;
                this.options.filter = filter.id;
                this.refreshTable();
            },
            onSearch(term) {
                this.options.search = term;
                this.performSearch();
            },
            performSearch: _.debounce(function () {
                setTimeout(function () {
                    this.refreshTable();
                }.bind(this), 500)
            }, 300),
            refreshTable()
            {
                this.state.loading = true;
                this.options.page = this.options.pager.page;
                if (this.shared.activeReport) {
					this.options.reportId = this.shared.activeReport.id;
                }

                this.$http.get('/apiv1/listings', {params : this.options}).then((response) => {

                    this.shared.listings = response.body.data;
                    this.options.pager.page = response.body['current_page'];
                    this.totalListings = response.body.total;
                    this.state.loading = false;

                }, (response) => {
                    console.log("Error loading listings");
                    console.log(response);
                });
            },
            confirmDeleteListing(listing) {
                this.state.selectedListing = listing;
                this.$refs['delete-listing'].open();
            },
            deleteListing(confirmation) {
              if (confirmation !== 'ok') {
                  return;
              }

              if (!this.state.selectedListing || !this.state.selectedListing.id) {
                  this.$root.showNotification('Invalid listing selected, please refresh the listings and try again.');
              }

                this.$http.post('/apiv1/listings/delete/' + this.state.selectedListing.id, this.state.selectedListing).then((response) => {

                    this.$root.showNotification(response.body.message);
                    this.refreshTable();

                }, (response) => {
                    this.$root.showNotification(response.body.message);
                    console.log(response);
                });

              this.selectedListing = null;

            },
            openRootDialog(ref) {
                this.$root.openDialog(ref);
            },
            openDialog(ref) {
                this.$refs[ref].open();
            },
            closeDialog(ref) {
                this.$refs[ref].open();
            }

        }
    }
</script>