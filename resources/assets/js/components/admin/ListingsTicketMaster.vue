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
                    <h1 class="md-title">Event Listings - TM</h1>
                    <span v-if="options.currentFilter" class="text-muted">Viewing Current Listings</span>
                    <span v-else class="text-muted">Viewing Past Listings</span>
                </div>
                <div class="pull-right">
                    <md-theme md-name="secondary" class="display-inline-block">
                    <md-button md-condensed class="md-primary md-raised" @click.native="onDateFilter('monday')">
                        Mon
                    </md-button>
                    <md-button md-condensed class="md-primary md-raised" @click.native="onDateFilter('tuesday')">
                       Tues
                    </md-button>
                    <md-button md-condensed class="md-primary md-raised" @click.native="onDateFilter('wednesday')">
                        Wed
                    </md-button>
                    <md-button md-condensed class="md-primary md-raised" @click.native="onDateFilter('thursday')">
                        Thurs
                    </md-button>
                    <md-button md-condensed class="md-primary md-raised" @click.native="onDateFilter('weekend')">
                        Fri
                    </md-button>
                    <md-button md-condensed class="md-primary md-raised" @click.native="onDateFilter('new')">
                        New
                    </md-button>
                    </md-theme>
                    <md-button v-if="options.currentFilter" md-condensed class="md-accent md-raised" @click.native="options.currentFilter = !options.currentFilter; refreshTable();">
                        Past
                    </md-button>
                    <md-button v-if="!options.currentFilter" md-condensed class="md-accent md-raised" @click.native="options.currentFilter = !options.currentFilter; refreshTable();">
                        Current
                    </md-button>
                    <md-button md-condensed class="md-accent md-raised" @click.native="onFilter({name: 'targeted', id: 'filter-targeted'})">
                        Targeted
                    </md-button>
                    <md-button md-condensed class="md-accent md-raised" @click.native="onFilter({name: 'excluded', id: 'filter-excluded'})">
                        Excluded
                    </md-button>
                    <md-button v-if="tableView == 'simple'" md-condensed class="md-accent md-raised" @click.native="toggleTableView">
                        Detailed
                    </md-button>
                    <md-button v-if="tableView == 'detailed'" md-condensed class="md-accent md-raised" @click.native="toggleTableView">
                        Simple
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

                    <md-select multiple v-model="selectedColumns" class="inline-md-select" >
                        <md-button class="md-icon-button" md-menu-trigger slot="icon">
                            <md-icon>view_column</md-icon>
                        </md-button>
                        <md-option v-for="(col, index) in columns"
                                   :key="index"
                                   :value="col">
                            {{ col.title }}
                        </md-option>
                    </md-select>

                    <md-button class="md-icon-button" @click.native="refreshTable()">
                        <md-icon>cached</md-icon>
                        <md-tooltip md-direction="top">Refresh Table</md-tooltip>
                    </md-button>

                    <md-button class="md-icon-button" @click.native="openUploadDialog()">
                        <md-icon>cloud_upload</md-icon>
                        <md-tooltip md-direction="top">Upload master data</md-tooltip>
                    </md-button>

                    <md-button v-if="isFiltered || state.searchSelected" class="md-icon-button" @click.native="clearFilters">
                        <md-icon>clear</md-icon>
                        <md-tooltip md-direction="top">Clear Search</md-tooltip>
                    </md-button>

                    <md-button v-if="!state.searchSelected" class="md-icon-button" @click.native="openSearch">
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
                            <md-option value="venue_name">Venue Name</md-option>
                        </md-select>
                    </md-input-container>
                </div>
                <div class="col-xs-12 col-sm-9">
                    <md-input-container class="md-theme-dark" md-inline>
                        <label>Search Listings</label>
                        <md-input v-model="options.search" @change="onSearch" id="mainSearchInput"></md-input>
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

        <div v-if="options.dateFilter.length > 0" class="col-lg-12 bg-grey-200">
            <h4 class="margin-top-10 pull-left">Current Filter: {{ options.dateFilter }}</h4>
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

            <md-table v-else :md-sort="options.sort.name" :md-sort-type="options.sort.type" @sort="onSort" @select="onSelected" >
                <md-table-header>
                    <md-table-row>
                        <md-table-head>Options</md-table-head>
                        <md-table-head v-if="columnActive('event_name')" md-sort-by="event_name">Event</md-table-head>
                        <md-table-head v-if="columnActive('attraction_name')" md-sort-by="attraction_name">Performer</md-table-head>
                        <md-table-head v-if="columnActive('venue_name')" md-sort-by="venue_name" >Venue</md-table-head>
                        <md-table-head v-if="columnActive('roi_high')" md-sort-by="roi_high" >ROI</md-table-head>
                        <md-table-head v-if="columnActive('roi_second_highest')" md-sort-by="roi_second_highest" >ROI 2nd</md-table-head>
                        <md-table-head v-if="columnActive('roi_low')" md-sort-by="roi_low">ROI Low</md-table-head>
                        <md-table-head v-if="columnActive('weighted_sold')" md-sort-by="weighted_sold">Weighted Sold</md-table-head>
                        <md-table-head v-if="columnActive('sh_sold')" md-sort-by="sh_sold" >SH Sold</md-table-head>
                        <md-table-head v-if="columnActive('tn_avg_sale')" md-sort-by="tn_avg_sale">TN Sold</md-table-head>
                        <md-table-head v-if="columnActive('roi_net')" md-sort-by="roi_net" >Net</md-table-head>
                        <md-table-head v-if="columnActive('tot_per_event')" md-sort-by="tot_per_event">Per Event</md-table-head>
                        <md-table-head v-if="columnActive('sfc_roi')" md-sort-by="sfc_roi" >SFC ROI</md-table-head>
                        <md-table-head v-if="columnActive('sfc_roi_dollar')" md-sort-by="sfc_roi_dollar" >SFC ROI $</md-table-head>
                        <md-table-head v-if="columnActive('sfc_cogs')" md-sort-by="sfc_cogs">SFC COGS</md-table-head>
                        <md-table-head v-if="columnActive('total_sold')" md-sort-by="total_sold">Total Sold All</md-table-head>
                        <md-table-head v-if="columnActive('total_sales')" md-sort-by="total_sales" >SH Tix</md-table-head>
                        <md-table-head v-if="columnActive('tn_tix_sold')" md-sort-by="tn_tix_sold" >TN Total</md-table-head>
                        <md-table-head v-if="columnActive('max_price')" md-sort-by="max_price">High</md-table-head>
                        <md-table-head v-if="columnActive('second_highest_price')" md-sort-by="second_highest_price">2nd High</md-table-head>
                        <md-table-head v-if="columnActive('min_price')" md-sort-by="min_price" >Low</md-table-head>
                        <md-table-head v-if="columnActive('venue_capacity')" md-sort-by="venue_capacity" >Capacity</md-table-head>
                        <md-table-head v-if="columnActive('event_day')" md-sort-by="event_day" >Day</md-table-head>
                        <md-table-head v-if="columnActive('sale_date')" md-sort-by="first_onsale_datetime" >Date</md-table-head>
                        <md-table-head v-if="columnActive('venue_state_code')" md-sort-by="venue_state_code" >State</md-table-head>
                        <md-table-head v-if="columnActive('buy')">Buy</md-table-head>
                    </md-table-row>
                </md-table-header>

                <md-table-body>
                    <md-table-row v-for="(listing, rowIndex) in shared.listings" :md-item="listing"  :key="rowIndex"
                                  :class="[{'bg-targeted' : listing.event_state_slug == 'targeted'}, {'bg-excluded' : listing.event_state_slug == 'excluded'}] ">
                        <md-table-cell>
                            <button class="btn btn-small btn-danger margin-right-5 padding-5" @click="updateStatus(listing, 'excluded', rowIndex)">
                                <md-icon>close</md-icon>
                            </button>
                            <button class="btn btn-small btn-primary margin-right-5 padding-5" @click="associateListing(listing)">
                                <md-icon>attach_file</md-icon>
                            </button>
                            <button class="btn btn-small btn-success padding-5 targetListingButton"
                                    :data-clipboard-text="`${listing.attraction_name ? listing.attraction_name : 'N/A'} ${listing.venue_state_code} (${listing.roi_high ? listing.roi_high : 'NA'}%) - ${listing.tot_per_event ? listing.tot_per_event : 'NA'}`"
                                    @click="updateStatus(listing, 'targeted', rowIndex)">
                                <md-icon>stars</md-icon>
                            </button>
                        </md-table-cell>
                        <md-table-cell v-if="columnActive('event_name')">
                        <span>
                           {{ listing.event_name|limitTo(20) }}
                            <md-tooltip md-direction="top">{{ listing.event_name }}</md-tooltip>
                        </span>

                        </md-table-cell>
                        <md-table-cell v-if="columnActive('attraction_name')">
                            {{ listing.attraction_name ? listing.attraction_name : '-'|limitTo(20) }}
                            <md-tooltip md-direction="top">{{ listing.attraction_name ? listing.attraction_name : '-' }}</md-tooltip>
                        </md-table-cell>
                        <md-table-cell v-if="columnActive('venue_name')">
                            {{ listing.venue_name|limitTo(20) }}
                            <md-tooltip md-direction="top">{{ listing.venue_name }}</md-tooltip>
                        </md-table-cell>
                        <md-table-cell v-if="columnActive('roi_high')">
                                <span v-if="listing.roi_high >= 40" class="label label-success">
                                    {{ `${listing.roi_high}%` }}<span v-if="listing.max_price_offer === null && listing.price_range_max !== null"> *</span>
                                </span>
                                <span v-if="listing.roi_high >= 20 && listing.roi_high < 40" class="label label-success-light">
                                    {{ `${listing.roi_high}%` }}<span v-if="listing.max_price_offer === null && listing.price_range_max !== null"> *</span>
                                </span>
                                <span v-if="listing.roi_high < 20 && listing.roi_high > 0" class="label bg-grey-400">
                                    {{ `${listing.roi_high}%` }}<span v-if="listing.max_price_offer === null && listing.price_range_max !== null"> *</span>
                                </span>
                                <span v-if="listing.roi_high < 0" class="label label-danger">
                                    {{ `${listing.roi_high}%` }}<span v-if="listing.max_price_offer === null && listing.price_range_max !== null"> *</span>
                                </span>
                        </md-table-cell>
                        <md-table-cell v-if="columnActive('roi_second_highest')">
                                <span v-if="listing.roi_second_highest >= 40" class="label label-success">
                                    {{ `${listing.roi_second_highest}%` }}<span v-if="listing.second_highest_price_offer === null && listing.price_range_max !== null"> *</span>
                                </span>
                                <span v-if="listing.roi_second_highest >= 20 && listing.roi_second_highest < 40" class="label label-success-light">
                                    {{ `${listing.roi_second_highest}%` }}<span v-if="listing.second_highest_price_offer === null && listing.price_range_max !== null"> *</span>
                                </span>
                                <span v-if="listing.roi_second_highest < 20 && listing.roi_second_highest > 0" class="label bg-grey-400">
                                    {{ `${listing.roi_second_highest}%` }}<span v-if="listing.second_highest_price_offer === null && listing.price_range_max !== null"> *</span>
                                </span>
                                <span v-if="listing.roi_second_highest < 0" class="label label-danger">
                                    {{ `${listing.roi_second_highest}%` }}<span v-if="listing.second_highest_price_offer === null && listing.price_range_max !== null"> *</span>
                                </span>
                        </md-table-cell>
                        <md-table-cell v-if="columnActive('roi_low')">
                                <span v-if="listing.roi_low >= 40" class="label label-success">
                                    {{ `${listing.roi_low}%` }}<span v-if="listing.min_price_offer === null && listing.price_range_min !== null"> *</span>
                                </span>
                                <span v-if="listing.roi_low >= 20 && listing.roi_low < 40" class="label label-success-light">
                                    {{ `${listing.roi_low}%` }}<span v-if="listing.min_price_offer === null && listing.price_range_min !== null"> *</span>
                                </span>
                                <span v-if="listing.roi_low < 20 && listing.roi_low > 0" class="label bg-grey-400">
                                    {{ `${listing.roi_low}%` }}<span v-if="listing.min_price_offer === null && listing.price_range_min !== null"> *</span>
                                </span>
                                <span v-if="listing.roi_low < 0" class="label label-danger">
                                    {{ `${listing.roi_low}%` }}<span v-if="listing.min_price_offer === null && listing.price_range_min !== null"> *</span>
                                </span>
                        </md-table-cell>
                        <md-table-cell v-if="columnActive('weighted_sold')">
                            <span class="">{{ listing.weighted_sold > 0 ? listing.weighted_sold : '-' }}</span>
                        </md-table-cell>
                        <md-table-cell v-if="columnActive('sh_sold')">
                            <span class="">
                                {{ listing.sh_sold !== null ? listing.sh_sold : '-' }}
                            </span>
                        </md-table-cell>
                        <md-table-cell v-if="columnActive('tn_avg_sale')">
                            <span class="">{{ listing.tn_avg_sale !== null ? `${listing.tn_avg_sale}` : '-' }}</span>
                        </md-table-cell>
                        <md-table-cell v-if="columnActive('roi_net')">
                            <span v-if="listing.roi_net >= 1500" class="label label-success">{{ listing.roi_net !== null ? `${listing.roi_net}` : '-' }}
                            </span>
                            <span v-if="listing.roi_net >= 800 && listing.roi_net < 1500" class="label label-success-light">{{ listing.roi_net !== null ? `${listing.roi_net}` : '-' }}
                            </span>
                            <span v-if="listing.roi_net < 800" class="label bg-grey-400">{{ listing.roi_net !== null ? `${listing.roi_net}` : '-' }}
                            </span>
                        </md-table-cell>
                        <md-table-cell v-if="columnActive('tot_per_event')">
                            <span v-if="listing.tot_per_event <= 9" class="label label label-danger" >{{ listing.tot_per_event !== null ? `${listing.tot_per_event}` : '-' }}</span>
                            <span v-if="listing.tot_per_event >= 20" class="label label label-success" >{{ listing.tot_per_event !== null  ? `${listing.tot_per_event}` : '-' }}</span>
                            <span v-if="listing.tot_per_event > 9 && listing.tot_per_event < 20"class="label bg-grey-400" >{{ listing.tot_per_event !== null ? `${listing.tot_per_event}` : '-' }}</span>
                        </md-table-cell>
                        <md-table-cell v-if="columnActive('sfc_roi')">
                            <span v-if="listing.sfc_roi > .20" class="label label-success">
                                {{ listing.sfc_roi !== null ? `${Math.ceil(listing.sfc_roi * 100)}%` : '-' }}
                            </span>
                            <span v-if="listing.sfc_roi >= 0 && listing.sfc_roi <= .20" class="label bg-grey-400">
                                {{ listing.sfc_roi !== null ? `${Math.ceil(listing.sfc_roi * 100)}%` : '-' }}
                            </span>
                            <span v-if="listing.sfc_roi < 0" class="label label-danger">
                                {{ listing.sfc_roi !== null ? `${Math.ceil(listing.sfc_roi * 100)}%` : '-' }}
                            </span>
                        </md-table-cell>
                        <md-table-cell v-if="columnActive('sfc_roi_dollar')">
                            <span class="">{{ listing.sfc_roi_dollar !== null ? `$${listing.sfc_roi_dollar}` : '-' }}</span>
                        </md-table-cell>
                        <md-table-cell v-if="columnActive('sfc_cogs')">
                            <span class="">{{ listing.sfc_cogs !== null ? `$${listing.sfc_cogs}` : '-' }}</span>
                        </md-table-cell>
                        <md-table-cell v-if="columnActive('total_sold')">
                            <span class="">{{listing.total_sold !== null ? listing.total_sold : '-' }}</span>
                        </md-table-cell>
                        <md-table-cell v-if="columnActive('total_sales')">
                            <span class="">
                                {{listing.total_sold !== null ? listing.total_sold - listing.tn_tix_sold : '-' }}
                            </span>
                        </md-table-cell>
                        <md-table-cell v-if="columnActive('tn_tix_sold')">
                            <span class="">{{ listing.tn_tix_sold !== null ? `${listing.tn_tix_sold}` : '-' }}</span>
                        </md-table-cell>
                        <md-table-cell v-if="columnActive('max_price')" class="col-border-left">
                            <span class="">${{ listing.max_price_offer !== null ? Math.round(listing.max_price): listing.max_price !== null ? `${Math.round(listing.max_price)}*` : 'N/A' }}</span>
                        </md-table-cell>
                        <md-table-cell v-if="columnActive('second_highest_price')">
                            <span class="">${{ listing.second_highest_price_offer !== null ? Math.round(listing.second_highest_price) : listing.second_highest_price !== null ? `${Math.round(listing.second_highest_price)}*` : 'N/A' }}</span>
                        </md-table-cell>
                        <md-table-cell v-if="columnActive('min_price')" class="col-border-right">
                            <span class="">${{ listing.min_price_offer !== null ? Math.round(listing.min_price) : listing.min_price !== null ? `${Math.round(listing.min_price)}*` : 'N/A' }}</span>
                        </md-table-cell>
                        <md-table-cell v-if="columnActive('venue_capacity')">
                            <span class="">{{ listing.venue_capacity }}</span>
                        </md-table-cell>
                        <md-table-cell v-if="columnActive('event_day')">
                            <span class="" style="text-transform: capitalize;">{{ listing.event_day }}</span>
                        </md-table-cell>
                        <md-table-cell v-if="columnActive('sale_date')">
                            <span class="">{{ formatDateTime(listing.first_onsale_timestamp) }}</span>
                        </md-table-cell>
                        <md-table-cell v-if="columnActive('venue_state_code')">
                            <span class="">{{ listing.venue_state_code }}</span>
                        </md-table-cell>
                        <md-table-cell v-if="columnActive('buy')">
                            <div v-if="listing.ticket_url">
                            <a class="buyUrlLink" :data-clipboard-text="listing.ticket_url"
                               :href="listing.ticket_url" target="_blank"><md-icon>shopping_cart</md-icon>
                            </a>
                            </div>
                            <span v-else>-</span>
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
                    :md-page-options="[20, 50, 100,250]"
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

    <ui-modal ref="uploadDataModal" title="Upload master data" size="medium">
        <p>Please select a file and the upload will start automatically</p>
        <form style="display: flex;">
            <ui-progress-circular
                    style="margin-right: 10px;"
                    color="primary"
                    :size="18"
                    v-show="state.dataUploadInProgress"></ui-progress-circular>
            <input type="file" ref="dataMaster" name="data-master" @change="uploadData()" v-if="state.dataUploadReady">
        </form>
        <div slot="footer">
            <ui-button @click="$refs.uploadDataModal.close();">Cancel</ui-button>
        </div>
    </ui-modal>

    <ui-modal ref="associateModal" title="Associate This Listing With Secondary Data" size="large">
        <ui-button size="small pull-right margin-top-10 margin-bottom-10" @click="state.addManualLookup = !state.addManualLookup">Add Manual Lookup</ui-button>
        <h4 class="margin-bottom-5">{{ shared.listing.event_name }}</h4>

        <div v-if="state.addManualLookup" class="margin-bottom-20">
            <md-input-container>
                <label>Match Name</label>
                <md-input v-model="manualLookupName"></md-input>
            </md-input-container>
            <md-button class="pull-right md-primary md-raised" @click="saveManualLookup(shared.listing)">Save</md-button>
        </div>
        <div class="clearfix"></div>
        <span v-if="shared.listing.data">This listing is already associated with <span class="text-underline">{{ shared.listing.data.category }} </span> <span class="label label-info pull-right font-size-12">{{ shared.listing.confidence }}% Confidence</span></span>

        <md-input-container class="margin-top-10">
            <label>Search for matching data</label>
            <md-input id='dataSearchInput' v-model="options.dataSearch" @change="onDataSearch"></md-input>
        </md-input-container>

        <md-list class="md-double-line" v-if="!state.dataLoading" style="max-height: 600px; overflow: auto;">
            <md-list-item v-for="(item,index) in shared.dataSearch" :key="index">
                <div class="md-list-text-container">
                    <span class="label label-info">{{ item.category }}  </span>
                    <!--<span>{{ item.upcoming_events }} Upcoming Events </span>-->

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

            this.columns.forEach((column) => {
            	/* Hide some columns by default */
            	if (column.name == 'event_name' || column.name == 'event_day' || column.name == 'sh_sold'
                    || column.name == 'total_sales' || column.name == 'tn_tix_sold' || column.name == 'tn_avg_sale'
                        || column.name === 'sfc_cogs' ) {
            		return;
                }

            	this.selectedColumns.push(column);
            });

            let clipboard = new Clipboard('.buyUrlLink');
			let clipboardt = new Clipboard('.targetListingButton');


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
                reportItems: [],
				addManualLookup: false,
                dataUploadInProgress: false,
                dataUploadReady: true,
            },
            manualLookupName: '',
            _: window._,
            shared: window.appShared,
            selectedColumns: [
            ],
            tableView: 'simple',
            columns: [
                {id : 1, name: 'event_name', title: 'Event'},
                {id : 2, name: 'attraction_name', title: 'Performer'},
                {id : 3, name: 'venue_name', title: 'Venue'},
                {id : 4, name: 'roi_high', title: 'ROI'},
                {id : 5, name: 'roi_low', title: 'ROI Low'},
                {id : 6, name: 'sh_sold', title: 'SH Sold'},
                {id : 7, name: 'total_sales', title: 'SH Tix'},
                {id : 8, name: 'max_price', title: 'High'},
                {id : 9, name: 'min_price', title: 'Low'},
                {id : 10, name: 'venue_capacity', title: 'Venue Capacity'},
                {id : 11, name: 'event_day', title: 'Day'},
                {id : 12, name: 'sale_date', title: 'Date'},
                {id : 13, name: 'venue_state_code', title: 'State'},
                {id : 14, name: 'buy', title: 'Buy'},
                {id : 15, name: 'sfc_roi', title: 'SCF ROI'},
                {id : 16, name: 'sfc_roi_dollar', title: 'SFC ROI Dollar'},
                {id : 17, name: 'sfc_cogs', title: 'SFC COGS'},
                {id : 18, name: 'tot_per_event', title: 'Per Event'},
                {id : 19, name: 'roi_net', title: 'Net'},
                {id : 20, name: 'tn_tix_sold', title: 'TN Total'},
                {id : 21, name: 'tn_avg_sale', title: 'TN Sold'},
                {id : 22, name: 'weighted_sold', title: 'Weighted Sold'},
                {id : 23, name: 'total_sold', title: 'Total Sold All'},
                {id : 24, name: 'second_highest_price', title: '2nd High'},
                {id : 25, name: 'roi_second_highest', title: 'ROI 2nd'},
            ],
            options: {
                pager: {
                    page: 1,
                    size: 250
                },
                sort : {
                    name: 'roi_second_highest',
                    type: 'asc' //asc is actually = to desc, it gets flipped when calling API
                },
				multiSort: [],
                search: '',
                dataSearch: '',
                searchField: 'all',
                filter: 'filter-all',
                dateFilter: '',
                currentFilter: true,
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
        	getSaleDate(listing) {

        		if (!listing.sales || listing.sales.length == 0) {
        			return "N/A";
                }

                if (!this.options.dateFilter || this.options.dateFilter.length == 0 || this.options.dateFilter == 'new') {
        			return listing.sales[0].nice_date;
                }

                let sale = null;
        		listing.sales.forEach((saleData) => {
        			if (saleData.nice_day == this.options.dateFilter) {
        				sale = saleData.nice_date;
                    }

                    if (this.options.dateFilter == 'weekend' && (saleData.nice_day == 'friday' || saleData.nice_day == 'saturday' || saleData.nice_day == 'sunday')) {
        				if (!sale) {
							sale = saleData.nice_date;
                        }
                    }
                });

        		if (sale) {
        			return sale;
                }

                return "N/A";

            },
            formatDateTime(timestamp) {
        	    const dateObject = new Date(timestamp * 1000);
                const dateOptions = {
                    weekday: 'short',
                    month: 'short',
                    day: 'numeric',
                    hour12: true,
                    hour: 'numeric',
                    minute: 'numeric'
                };

        	    return dateObject.toLocaleDateString('en-US', dateOptions);
            },
        	openSearch() {
        		this.state.searchSelected = !this.state.searchSelected;
				setTimeout(() => {
					document.getElementById("mainSearchInput").focus();
					document.getElementById("mainSearchInput").select();
				}, 50);
            },
        	columnActive(name) {
        		let active = false;
        	    this.selectedColumns.forEach((column) => {
        	    	if (column.name == name) {
        	    		active = true;
                    }
                });

        	    return active;
            },
            toggleTableView() {
        	  let detailedColumns = ['sh_sold','total_sales','tn_tix_sold','tn_avg_sale','sfc_cogs'];

        	  let actualColumns = [];
              detailedColumns.forEach((detailColumn) => {
                this.columns.forEach((column) => {
                  if (column.name == detailColumn) {
                    actualColumns.push(column);
                  }
                });
              });

        	  /* Switch to detailed view */
        	  if (this.tableView == 'simple') {
                actualColumns.forEach((detailColumn) => {
                  let hasColumn = false;
                  this.selectedColumns.forEach((column) => {
                    if (column.name == detailColumn.name) {
                      hasColumn = true;
                    }
                  });

                  if (!hasColumn) {
                    this.selectedColumns.push(detailColumn);
                  }
                });
                this.tableView = 'detailed';
                return;
              }

                /* Switch to detailed view */
              if (this.tableView == 'detailed') {
                let newColumns = [];
                this.selectedColumns.forEach((column) => {
                  let includeColumn = true;
                  actualColumns.forEach((detailColumn) => {
                    if (column.name == detailColumn.name) {
                      includeColumn = false;
                    }
                  });

                  if (includeColumn) {
                    newColumns.push(column);
                  }

                });

                this.selectedColumns = newColumns;
                this.tableView = 'simple';
                return;
              }

            },
        	associateListing(listing) {
        	    this.shared.listing = listing;
        	    this.$refs.associateModal.open();
				setTimeout(() => {
					document.getElementById("dataSearchInput").focus();
					document.getElementById("dataSearchInput").select();
				}, 50);
            },
			saveManualLookup(listing) {
                let lookup = {
                	event_name: listing.event_name,
                	match_name: this.manualLookupName
                }
				this.$http.post(`/apiv1/tm/lookups`, lookup).then((response) => {

					this.$root.showNotification(response.body.message);

				}).catch((response) => {
                	if (response.body.message) {
						this.$root.showNotification(response.body.message);
                    } else {
						this.$root.showNotification('Error adding new lookup. Try again.');
                    }
                });
			},
			updateStatus(listing, status, rowIndex) {
        	    console.log(listing);
				this.$http.post(`/apiv1/tm/listings/status/${listing.event_id}/${status}`).then((response) => {

					this.shared.listings[rowIndex] = response.body.results;
					this.$root.showNotification(response.body.message);
					this.$forceUpdate();
					//this.refreshTable();

                    // only send webhook for targeted
                    if( status === 'targeted' ) { this.sendZapierWebHook(listing); }

				}, (response) => {
					console.log("Error updating listing status. Try again.");
					console.log(response);
				});
			},
            sendZapierWebHook(listing) {
                this.$http.post(`/apiv1/tm/listings/sendZapierWebHook/${listing.event_id}`).then((response) => {

                    this.$root.showNotification(response.body.message);

                }, (response) => {
                    this.$root.showNotification("Error sending Zapier webhook.");
                    console.log("Error sending Zapier webhook.");
                    console.log(response);
                });
            },
            associateListingWithData(listing, data) {
				this.$http.post(`/apiv1/tm/listings/associate/${listing.event_id}/${data.id}`).then((response) => {

					this.$root.showNotification(response.body.message);
					this.shared.listing = response.body.results;
					this.$refs.associateModal.close();
				}, (response) => {
					console.log("Error associating listing. Try again.");
					console.log(response);
				});

            },
            openUploadDialog() {
        	    // make sure loading icon is not displayed
                this.state.dataUploadInProgress = false;

                // clear files
                this.clearDataUpload();

                // open the dialog box
        	    this.$refs.uploadDataModal.open();
            },
            uploadData() {
        	    // get file
                const file = this.$refs.dataMaster.files[0];
                const formData = new FormData();
                formData.append('data-master', file);

                // set header
                const headers = {'Content-Type': 'multipart/form-data'};

                // set loading spinner
                this.state.dataUploadInProgress = true;

                // send file
                this.$http.post('/apiv1/data-master/upload', formData, {headers: headers})
                    .then((response) => {
                        // send notification
                        this.$root.showNotification('Data upload was successful. ' + response.body.message);

                        // hide progress spinner and close dialog
                        this.state.dataUploadInProgress = false;
                        this.$refs.uploadDataModal.close();
                    }, (response) => {
                        console.log(response);

                        // send error notification
                        this.$root.showNotification('Error uploading data: ' + response.statusText);

                        // hide progress spinner and close dialog
                        this.state.dataUploadInProgress = false;
                        this.$refs.uploadDataModal.close();
                    });
            },
            clearDataUpload() {
                this.state.dataUploadReady = false;
                this.$nextTick(() => {
                    this.state.dataUploadReady = true;
                })
            },
            clearFilters() {
              this.options.search = '';
              this.options.filter = '';
              this.options.dateFilter = '';
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
			onDateFilter(filter) {
				this.options.dateFilter = filter;
				this.refreshTable();
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

					this.$http.get('/apiv1/tm/dataSearch', {params: dataOptions}).then((response) => {

						this.shared.dataSearch = response.body.data;
						this.state.dataLoading = false;

					}, (response) => {
						this.state.dataLoading = false;
						console.log("Error loading data.");
						console.log(response);
					});
				}.bind(this), 100)
			}, 300),
            onSearch(term) {
                this.options.search = term;
                this.options.pager.page = 1;
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

                this.$http.get('/apiv1/tm/listings', {params : this.options}).then((response) => {

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

                this.$http.post('/apiv1/tm/listings/delete/' + this.state.selectedListing.id, this.state.selectedListing).then((response) => {

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
