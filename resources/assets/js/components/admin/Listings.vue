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
                       {{ listing.event_name|limitTo(20) }}
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
                    <h1 class="md-title">Event Listings</h1>
                </div>
                <div class="pull-right">
                    <md-button md-condensed class="md-accent md-raised" @click.native="onFilter({name: 'targeted', id: 'filter-targeted'})">
                        Targeted
                    </md-button>
                    <md-button md-condensed class="md-accent md-raised" @click.native="onFilter({name: 'excluded', id: 'filter-excluded'})">
                        Excluded
                    </md-button>
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

                    <!--
                    <md-button v-if="!state.sortSelected" class="md-icon-button" @click.native="state.sortSelected = !state.sortSelected">
                        <md-icon>sort</md-icon>
                    </md-button>
                    -->

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
                            <md-option value="event_name">Event Name</md-option>
                            <md-option value="venue_city">Venue City</md-option>
                            <md-option value="venue">Venue Name</md-option>
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
                        <md-table-head>Options</md-table-head>
                        <md-table-head md-sort-by="event_name">Event</md-table-head>
                        <md-table-head md-sort-by="performer">Performer</md-table-head>
                        <md-table-head md-sort-by="venue" >Venue</md-table-head>
                        <md-table-head md-sort-by="roi_sh" >ROI (SH)</md-table-head>
                        <md-table-head md-sort-by="roi_low">ROI Low</md-table-head>
                        <md-table-head md-sort-by="avg_sale_price" >SH Sold</md-table-head>
                        <md-table-head md-sort-by="avg_sale_price_past" >SH Past</md-table-head>
                        <md-table-head md-sort-by="total_sales" >SH Tix</md-table-head>
                        <md-table-head md-sort-by="total_sales_past" >SH Past</md-table-head>
                        <md-table-head md-sort-by="high_ticket_price">High</md-table-head>
                        <md-table-head md-sort-by="low_ticket_price" >Low</md-table-head>
                        <md-table-head md-sort-by="total_listed">Available</md-table-head>
                        <md-table-head md-sort-by="sale_status" >Sale Status</md-table-head>
                        <md-table-head md-sort-by="venue_capacity" >Capacity</md-table-head>
                        <md-table-head md-sort-by="event_day" >Day</md-table-head>
                        <md-table-head md-sort-by="event_date" >Date</md-table-head>
                        <md-table-head md-sort-by="venue_state" >State</md-table-head>
                    </md-table-row>
                </md-table-header>

                <md-table-body>
                    <md-table-row v-for="(listing, rowIndex) in shared.listings" :md-item="listing"  :key="rowIndex" :class="{'bg-targeted' : listing.status == 'targeted'}">
                        <md-table-cell>
                            <button class="btn btn-small btn-danger margin-right-5 padding-5" @click="updateStatus(listing, 'excluded')">
                                <md-icon>close</md-icon>
                            </button>
                            <button class="btn btn-small btn-primary margin-right-5 padding-5" @click="associateListing(listing)">
                                <md-icon>attach_file</md-icon>
                            </button>
                            <button class="btn btn-small btn-success padding-5" @click="updateStatus(listing, 'targeted')">
                                <md-icon>stars</md-icon>
                            </button>
                        </md-table-cell>
                        <md-table-cell>
                        <span>
                           {{ listing.event_name|limitTo(20) }}
                            <md-tooltip md-direction="top">{{ listing.event_name }}</md-tooltip>
                        </span>

                        </md-table-cell>
                        <md-table-cell>
                            {{ listing.performer ? listing.performer : 'N/A'|limitTo(20) }}
                            <md-tooltip md-direction="top">{{ listing.performer ? listing.performer : 'N/A' }}</md-tooltip>
                        </md-table-cell>
                        <md-table-cell>
                            {{ listing.venue|limitTo(20) }}
                            <md-tooltip md-direction="top">{{ listing.venue }}</md-tooltip>
                            </span>
                        </md-table-cell>
                        <md-table-cell>
                            <span v-if="listing.stats && listing.stats.roi_sh > 40" class="label label-success">{{ listing.stats ? `${listing.stats.roi_sh}%` : 'N/A' }}</span>
                            <span v-else class="label label-danger">{{ listing.stats ? `${listing.stats.roi_sh}%` : 'N/A' }}</span>
                        </md-table-cell>
                        <md-table-cell>
                            <span v-if="listing.stats && listing.stats.roi_low > 200" class="label label-success">{{ listing.stats ? `${listing.stats.roi_low}%` : 'N/A' }}</span>
                            <span v-else class="label label-danger">{{ listing.stats ? `${listing.stats.roi_low}%` : 'N/A' }}</span>
                        </md-table-cell>
                        <md-table-cell>
                            <span class="">{{ listing.data.length > 0 ? listing.data[0].avg_sale_price : 'N/A' }}</span>
                        </md-table-cell>
                        <md-table-cell>
                            <span class="">{{ listing.data.length > 0 ? listing.data[0].avg_sale_price_past : 'N/A' }}</span>
                        </md-table-cell>
                        <md-table-cell>
                            <span class="">{{listing.data.length > 0 ? listing.data[0].total_sales : 'N/A' }}</span>
                        </md-table-cell>
                        <md-table-cell>
                            <span class="">{{ listing.data.length > 0 ? listing.data[0].total_sales_past : 'N/A' }}</span>
                        </md-table-cell>
                        <md-table-cell>
                            <span class="">${{ listing.high_ticket_price }}</span>
                        </md-table-cell>
                        <md-table-cell>
                            <span class="">${{ listing.low_ticket_price }}</span>
                        </md-table-cell>
                        <md-table-cell>
                            <span class="">{{ listing.data.length > 0 ? listing.data[0].total_listed : 'N/A' }}</span>
                        </md-table-cell>
                        <md-table-cell>
                            <span class="">{{ listing.sale_status }}</span>
                        </md-table-cell>
                        <md-table-cell>
                            <span class="">{{ listing.venue_capacity }}</span>
                        </md-table-cell>
                        <md-table-cell>
                            <span class="">{{ listing.event_day }}</span>
                        </md-table-cell>
                        <md-table-cell>
                            <span class="">{{ listing.nice_date }}</span>
                        </md-table-cell>
                        <md-table-cell>
                            <span class="">{{ listing.venue_state }}</span>
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

    <ui-modal ref="associateModal" title="Associate This Listing With Secondary Data" size="large">
        <h4 class="margin-bottom-5">{{ shared.listing.event_name }}</h4>
        <span v-if="shared.listing.data.length > 0">This listing is already associated with <span class="text-underline">{{ shared.listing.data[0].category }} </span> <span class="label label-info pull-right">{{ shared.listing.data[0].pivot.confidence }}% Confidence</span></span>

        <md-input-container class="margin-top-10">
            <label>Search for matching data</label>
            <md-input id='dataSearchInput' v-model="options.dataSearch" @change="onDataSearch"></md-input>
        </md-input-container>

        <md-list class="md-double-line" v-if="!state.dataLoading" style="max-height: 600px; overflow: auto;">
            <md-list-item v-for="(item,index) in shared.dataSearch" :key="index">
                <div class="md-list-text-container">
                    <span class="label label-info">{{ item.category }}  </span>
                    <span>{{ item.upcoming_events }} Upcoming Events </span>

                </div>

                <md-button class="md-icon-button md-list-action" @click.native="associateListingWithData(shared.listing,item)">
                    <md-icon class="md-primary">attach_file</md-icon>
                </md-button>

            </md-list-item>

            <md-list-item v-if="shared.dataSearch.length == 0 && options.dataSearch">
                <div class="md-list-text-container">
                    <span>No matching data found, enter a search term.</span>
                </div>
            </md-list-item>
        </md-list>

        <!-- Cloaked Place Holder Loading Content -->
        <div v-if="state.dataLoading" class="margin-top-20 text-center">
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
            <ui-button @click="$refs.associateModal.close();">Cancel</ui-button>
        </div>
    </ui-modal>



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
                dataLoading: false,
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
                    size: 100
                },
                sort : {
                    name: 'roi_sh',
                    type: 'asc'
                },
				multiSort: [],
                search: '',
                dataSearch: '',
                searchField: 'all',
                filter: 'filter-all',
                reportId: null
            },
            filters: [
				{
					'name': 'All',
					'id': 'filter-all'
				},
				{
					'name': 'Targeted',
					'id': 'filter-targeted'
				},
				{
					'name': 'Excluded',
					'id': 'filter-excluded'
				},
                {
                    'name' : 'On Sale / Pre Sale Only',
                    'id' : 'filter-on-sale'
                },
                {
                    'name' : 'Future Sales Only',
                    'id' : 'filter-future'
                },
            ],
			sortColumns: [
				{name: 'Creation Date', field: 'created_at'},
				{name: 'Event Name', field: 'event_name'},
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
        	associateListing(listing) {
        	    this.shared.listing = listing;
        	    this.$refs.associateModal.open();
				setTimeout(() => {
					document.getElementById("dataSearchInput").focus();
					document.getElementById("dataSearchInput").select();
				}, 50);
            },
			updateStatus(listing, status) {
				this.$http.post(`/apiv1/listings/status/${listing.id}/${status}`).then((response) => {

					this.$root.showNotification(response.body.message);
					this.refreshTable();


				}, (response) => {
					console.log("Error updating listing status. Try again.");
					console.log(response);
				});
			},
            associateListingWithData(listing, data) {
				this.$http.post(`/apiv1/listings/associate/${listing.id}/${data.id}`).then((response) => {

					this.$root.showNotification(response.body.message);
					this.shared.listing = response.body.results;
					this.refreshTable();

				}, (response) => {
					console.log("Error associating listing. Try again.");
					console.log(response);
				});

            },
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
			onDataSearch(term) {
				this.options.dataSearch = term;
				this.performDataSearch();
			},
			performDataSearch: _.debounce(function () {
				this.state.dataLoading = true;

				setTimeout(function () {
					let dataOptions = {
						dataSearch: this.options.dataSearch
					};

					this.$http.get('/apiv1/dataSearch', {params: dataOptions}).then((response) => {

						this.shared.dataSearch = response.body.data;
						this.state.dataLoading = false;

					}, (response) => {
						this.state.dataLoading = false;
						console.log("Error loading data.");
						console.log(response);
					});
				}.bind(this), 500)
			}, 300),
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
				this.$forceUpdate();
                this.options.page = this.options.pager.page;
                if (this.shared.activeReport) {
					this.options.reportId = this.shared.activeReport.id;
                }

                this.$http.get('/apiv1/listings', {params : this.options}).then((response) => {

                    this.shared.listings = response.body.data;
                    this.options.pager.page = response.body['current_page'];
                    this.totalListings = response.body.total;
                    this.state.loading = false;
                    this.$forceUpdate();

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