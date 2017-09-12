<template>
    <div>
        <md-whiteframe class="bg-white margin-bottom-20 padding-15 md-table-card md-card display-block" v-if="state.selectedListings.length > 0">
            <h1 class="md-title margin-bottom-10 pull-left">Selected Listings </h1>
            <md-button class="pull-right md-warn" @click.native="state.selectedListings = []">
                Clear All
            </md-button>
            <div class="clearfix"></div>
            <div class="col-lg-12">
                <div class="col-lg-3 margin-top-5 margin-top-sm-15 margin-top-xs-15" v-for="(listing, rowIndex) in state.selectedListings">
                    <div class="padding-5 border-1 border-grey-100 bg-grey-100 border-radius-5">
                    <span>
                       {{ listing.name|limitTo(20) }}
                        <md-tooltip md-direction="top">{{ listing.name }}</md-tooltip>
                    </span>
                    </div>
                </div>
            </div>
            <div class="pull-right">
                <md-button class="md-primary md-raised" @click.native.prevent="$refs['saveListingsDialog'].open();">Save Selected Listings</md-button>
            </div>
            <div class="clearfix"></div>
        </md-whiteframe>
        <md-card class="">
            <md-toolbar class="md-dense md-transparent">
                <div class="col-lg-6">
                    <h3 class="md-title margin-top-10 no-margin-left">{{ title }}</h3>
                    <span v-if="shared.reportOptions.startDate != null" class="text-muted font-size-14">From {{ shared.reportOptions.startDate }} to {{ shared.reportOptions.endDate}}</span>
                </div>
                <div class="col-lg-6">
                    <div class=" pull-right">
                        <md-switch v-if="shared.reportOptions.source == 'combined'" v-model="filters.airbnb" class="md-primary" @change="toggleAirBnb()">AirBnB</md-switch>
                        <md-switch v-if="shared.reportOptions.source == 'combined'" v-model="filters.homeaway" class="md-primary" @change="toggleHomeAway()">HomeAway</md-switch>
                        <md-switch v-model="state.showMarkers" class="md-primary" @change="toggleMarkers(true)">Listings</md-switch>
                        <md-switch v-model="state.showProperties" class="md-primary" @change="togglePropertyMarkers(true)">Properties</md-switch>
                        <div class="full-width-xs display-inline">
                            <md-button class="md-icon-button" :class="{'md-primary' : state.pinMode}" @click.native="state.pinMode = !state.pinMode">
                                <md-icon>edit_location</md-icon>
                                <md-tooltip md-direction="top">Toggle pin dropping.</md-tooltip>
                            </md-button>
                            <md-button class="md-icon-button" @click.native="state.showFilters = !state.showFilters">
                                <md-icon>filter_list</md-icon>
                            </md-button>
                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>
                <div v-if="state.showFilters">
                    <hr class="margin-bottom-5"/>
                    <md-icon class="pull-right cursor-pointer" @click.native="state.showFilters = !state.showFilters">close</md-icon>
                    <div class="clearfix"></div>
                    <div class="col-lg-3">
                        <label>Occupancy Rate {{ filters.occupancy.value[0] }}% to {{ filters.occupancy.value[1]}}%</label>
                        <vue-slider
                                v-bind="filters.occupancy"
                                v-model="filters.occupancy.value"
                        ></vue-slider>
                    </div>
                    <div class="col-lg-3">
                        <label>Monthly Revenue ${{ filters.revenue.value[0]|numFormat }} to ${{ filters.revenue.value[1]|numFormat }}</label>
                        <vue-slider
                                v-bind="filters.revenue"
                                v-model="filters.revenue.value"
                        ></vue-slider>
                    </div>
                    <div class="col-lg-3">
                        <label>Beds {{ filters.beds.value[0] }} to {{ filters.beds.value[1]}}</label>
                        <vue-slider
                                v-bind="filters.beds"
                                v-model="filters.beds.value"
                        ></vue-slider>
                    </div>
                    <div class="col-lg-3">
                        <label>Rooms {{ filters.rooms.value[0] }} to {{ filters.rooms.value[1]}}</label>
                        <vue-slider
                                v-bind="filters.rooms"
                                v-model="filters.rooms.value"
                        ></vue-slider>
                    </div>
                    <div class="col-lg-3">
                        <label>Profit Score {{ filters.score.value[0] }}% to {{ filters.score.value[1]}}%</label>
                        <vue-slider
                                v-bind="filters.score"
                                v-model="filters.score.value"
                        ></vue-slider>
                    </div>
                    <div class="col-lg-4 padding-top-15 no-padding-xs">
                        <md-switch v-model="filters.condos" class="md-primary" @change="filters.condos = !filters.condos">Show Condos</md-switch>
                        <md-switch v-model="filters.homes" class="md-primary" @change="filters.homes = !filters.homes">Show Houses</md-switch>
                    </div>
                    <div class="clearfix"></div>
                    <div class="margin-top-10">
                        <label>Property Filters</label>
                    </div>
                    <hr class="margin-top-5 margin-bottom-5"/>
                    <div class="col-lg-3">
                        <label>Price ${{ filters.propprice.value[0]|numFormat }} to ${{ filters.propprice.value[1]|numFormat }}</label>
                        <vue-slider
                                v-bind="filters.propprice"
                                v-model="filters.propprice.value"
                        ></vue-slider>
                    </div>
                    <div class="col-lg-3">
                        <label>Square Footage {{ filters.propsqft.value[0]|numFormat }} to {{ filters.propsqft.value[1]|numFormat }}</label>
                        <vue-slider
                                v-bind="filters.propsqft"
                                v-model="filters.propsqft.value"
                        ></vue-slider>
                    </div>
                    <div class="col-lg-3">
                        <label>Bedrooms {{ filters.propbeds.value[0] }} to {{ filters.propbeds.value[1]}}</label>
                        <vue-slider
                                v-bind="filters.propbeds"
                                v-model="filters.propbeds.value"
                        ></vue-slider>
                    </div>
                    <div class="col-lg-3">
                        <label>Bathrooms {{ filters.proprooms.value[0] }} to {{ filters.proprooms.value[1]}}</label>
                        <vue-slider
                                v-bind="filters.proprooms"
                                v-model="filters.proprooms.value"
                        ></vue-slider>
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-lg-4 padding-top-15 no-padding-xs">
                        <md-switch v-model="filters.property_apartments" class="md-primary"
                                   @change="filters.property_apartments = !filters.property_apartments">
                            Show Leases
                        </md-switch>
                        <md-switch v-model="filters.property_homes"  class="md-primary"
                                   @change="filters.property_homes = !filters.property_homes">
                            Show Houses
                        </md-switch>
                    </div>
                    <div class="pull-right text-right">
                        <md-button class="md-primary" @click.native="createMap(true)" v-if="!state.filtering">Apply Filters ({{ markers.length}} Listings)</md-button>
                        <md-button class="md-primary" v-else>Updating Map, Please Wait...</md-button>
                    </div>
                </div>
            </md-toolbar>

            <md-card-area>
                <md-tabs :md-dynamic-height="false" class="md-transparent">
                    <md-tab md-label="Map" md-active>
                        <div class="col-lg-8 col-sm-12 col-xs-12 no-padding no-margin">
                            <div v-if="state.loading" class="text-center">
                                <md-spinner class="md-primary" :md-size="150" md-indeterminate></md-spinner>
                            </div>
                            <div id="map" style="height: 600px;"></div>


                            <h4 class="md-subheading margin-bottom-10 margin-top-10 hidden-xs hidden-sm">
                                <md-icon>info</md-icon>
                                Turn on location markers and select a property to view the property details.
                            </h4>

                            <div class="visible-xs clearfix"></div>
                        </div>

                        <div class="visible-xs clearfix"></div>

                        <div class="col-sm-12 col-lg-4 no-padding-sm-md no-padding-sm-lg no-padding-md-lg max-height-600-lg">
                            <div class="visible-xs margin-top-15">&nbsp;</div>
                            <trans-report-select
                                    ref="reportSelect"
                                    :report-items="shared.locations"
                                    current-report="locationRevenueHeatmap"
                                    item-type="locations"
                                    max-items="400"
                                    :stacked="true">
                            </trans-report-select>

                            <!-- Selected Polygon Shape-->
                            <div v-if="selectedShape" class="no-padding-sm-md no-padding-sm-lg no-padding-md-lg  margin-top-10">
                                <md-card class="margin-top-10">
                                    <md-card-area md-inset>
                                        <md-card-header>
                                            <h4 class="md-title pull-left">
                                                <md-icon>format_shapes</md-icon>
                                                Currently Selected Shape
                                            </h4>
                                            <div class="clearfix"></div>
                                            <div class="md-subhead padding-5-10">
                                                You can save a new region for this selected shape, or remove it from the map. <br/>
                                            </div>
                                            <md-button class="md-icon-button pull-right" @click.native="deleteShape()">
                                                <md-icon class="md-primary">delete</md-icon>
                                            </md-button>
                                            <md-button class="md-icon-button pull-right" @click.native="$refs['saveRegionDialog'].open();">
                                                <md-icon class="md-primary">add_location</md-icon>
                                            </md-button>
                                            <md-button class="md-icon-button pull-right" @click.native="loadRegionListings()">
                                                <md-icon class="md-primary">view_list</md-icon>
                                            </md-button>
                                        </md-card-header>
                                    </md-card-area>

                                </md-card>
                            </div>

                            <!-- Selected Manual Location Marker Details -->
                            <div v-if="state.manualMarkerSelected" class="no-padding-sm-md no-padding-sm-lg no-padding-md-lg  margin-top-10">
                                <md-card class="margin-top-10">
                                    <md-card-area md-inset>
                                        <md-card-header>
                                            <md-icon class="pull-right cursor-pointer" @click.native="state.manualMarkerSelected = false">close</md-icon>
                                            <h4 class="md-title pull-left">
                                                <md-icon>pin_drop</md-icon>
                                                {{ manualMarkerTemplate }}
                                            </h4>
                                            <div class="clearfix"></div>
                                            <md-input-container class="margin-top-15">
                                                <label>Enter a Name For This Pin</label>
                                                <md-input v-model="manualMarkerTemplate"></md-input>
                                            </md-input-container>
                                            <md-button class="md-icon-button pull-right" @click.native="removeCustomMarker()">
                                                <md-icon class="md-primary">delete</md-icon>
                                            </md-button>
                                        </md-card-header>
                                    </md-card-area>

                                </md-card>
                            </div>

                            <!-- Selected Listing Details -->
                            <div v-if="shared.listing.id" class="no-padding-sm-md no-padding-sm-lg no-padding-md-lg  margin-top-10">
                                <md-card class="card-example">
                                    <md-card-area md-inset>
                                        <md-card-header>
                                            <md-icon class="pull-right cursor-pointer" @click.native="shared.listing = {}">close</md-icon>
                                            <h2 class="md-title pull-left">{{ shared.listing.name }} </h2>
                                            <div class="clearfix"></div>
                                            <div class="md-subhead">
                                                <md-icon>location_on</md-icon>
                                                <span>{{ shared.listing.locations[0].city }}, {{ shared.listing.locations[0].state }} - ({{ shared.listing.source}})</span>
                                            </div>
                                        </md-card-header>

                                        <md-card-content>
                                            <label class="label label-info">{{ shared.listing.profit_score }}% Profit Score</label>
                                            <label class="label label-info">{{ shared.listing.stats.percent_booked }}% Occupancy</label>
                                            <label class="label label-info">${{ shared.listing.stats.price_per_bed }} Per Bed</label>
                                            <label class="label label-info">{{ shared.listing.beds }} Beds</label>
                                            <label class="label label-info">{{ shared.listing.bedrooms }} Bedrooms</label>
                                            <label class="label label-info">{{ shared.listing.capacity }} People</label>
                                        </md-card-content>
                                    </md-card-area>

                                    <md-card-content md-inset>
                                        <h3 class="md-subheading margin-bottom-10">Compared to Other Listings in {{ shared.listing.locations[0].city }}, {{ shared.listing.locations[0].state }}</h3>

                                        <span :class="{'text-danger' : listingStats.relative_occupancy < 0, 'text-success' : listingStats.relative_occupancy > 0}">
                                        <md-icon v-if="listingStats.relative_occupancy < 0">trending_down</md-icon>
                                        <md-icon v-else>trending_up</md-icon>
                                        Occupancy Rate is {{ listingStats.relative_occupancy }}%
                                        <span v-if="listingStats.relative_occupancy < 0">Lower</span>
                                        <span v-else>Higher</span> Than Average
                                    </span>
                                        <br/>
                                        <span :class="{'text-danger' : listingStats.relative_beds < 0, 'text-success' : listingStats.relative_beds > 0}">
                                        <md-icon v-if="listingStats.relative_beds < 0">trending_down</md-icon>
                                        <md-icon v-else>trending_up</md-icon>
                                        {{ listingStats.relative_beds }}
                                        <span v-if="listingStats.relative_beds > 0">More</span>
                                        <span v-else>Less</span> Bed(s) Than Average
                                    </span>
                                        <br/>
                                        <span :class="{'text-danger' : listingStats.relative_price_per_bed < 0, 'text-success' : listingStats.relative_price_per_bed > 0}">
                                        <md-icon v-if="listingStats.relative_price_per_bed < 0">trending_down</md-icon>
                                        <md-icon v-else>trending_up</md-icon>
                                        Price Per Bed is ${{ listingStats.relative_price_per_bed }}
                                        <span v-if="listingStats.relative_price_per_bed > 0">Higher</span>
                                        <span v-else>Lower</span> Than Average
                                    </span>
                                        <br/>
                                        <span :class="{'text-danger' : listingStats.relative_rate < 0, 'text-success' : listingStats.relative_rate > 0}">
                                        <md-icon v-if="listingStats.relative_rate < 0">trending_down</md-icon>
                                        <md-icon v-else>trending_up</md-icon>
                                        Nightly Rate is ${{ listingStats.relative_rate }}
                                        <span v-if="listingStats.relative_rate > 0">Higher</span>
                                        <span v-else>Lower</span> Than Average
                                    </span>

                                    </md-card-content>

                                    <md-card-area md-inset v-if="shared.duplicates.length > 0">
                                        <md-card-content v-if="state.loadingDuplicates">
                                            <h3>Loading duplicates...</h3>
                                        </md-card-content>
                                        <md-card-content v-else>
                                            <h3 class="md-subheading margin-bottom-10">{{ shared.duplicates.length }} Duplicate Listing(s) Found</h3>
                                            <hr/>
                                            <div v-for="(listing,index) in shared.duplicates" class="margin-bottom-10">
                                                {{ listing.name|limitTo(45) }} <br/>
                                                <span class="label label-info margin-right-10 padding-5 pull-left">
                                                    {{ listing.pivot.confidence }}% Confidence
                                                </span>
                                                <div class="pull-right">
                                                    <md-menu md-size="5" md-direction="top left">

                                                        <md-icon class="md-primary cursor-pointer" md-menu-trigger>more_horiz</md-icon>

                                                        <md-menu-content>
                                                            <md-menu-item v-if="listing.source == 'airbnb'" :href="listing.source_link" target="_blank">
                                                                <md-icon>hotel</md-icon>
                                                                <span>View on AirBnb</span>
                                                            </md-menu-item>
                                                            <md-menu-item v-if="listing.source == 'homeaway'" :href="listing.source_link" target="_blank">
                                                                <md-icon>hotel</md-icon>
                                                                <span>View on HomeAway</span>
                                                            </md-menu-item>
                                                        </md-menu-content>
                                                    </md-menu>
                                                </div>
                                                <div class="clearfix"></div>
                                            </div>
                                        </md-card-content>
                                    </md-card-area>

                                    <md-card-area md-inset>
                                        <md-card-actions>
                                            <md-menu md-size="5" md-direction="top left">
                                                <md-button class="md-list-action md-primary" md-menu-trigger>
                                                    <md-icon>more_horiz</md-icon>
                                                </md-button>
                                                <md-menu-content>
                                                    <md-menu-item v-if="shared.listing.source == 'airbnb'" :href="shared.listing.source_link" target="_blank">
                                                        <md-icon>hotel</md-icon>
                                                        <span>View on AirBnb</span>
                                                    </md-menu-item>
                                                    <md-menu-item v-if="shared.listing.source == 'homeaway'" :href="shared.listing.source_link" target="_blank">
                                                        <md-icon>hotel</md-icon>
                                                        <span>View on HomeAway</span>
                                                    </md-menu-item>
                                                </md-menu-content>
                                            </md-menu>
                                        </md-card-actions>
                                    </md-card-area>
                                </md-card>
                            </div>

                            <!-- Selected Property Details -->
                            <div v-if="shared.property.id" class="margin-top-10">
                                <md-card class="card-example" v-if="shared.property.source != 'apartments'">
                                    <md-card-area md-inset>
                                        <md-card-header>
                                            <md-icon class="pull-right cursor-pointer" @click.native="shared.property = {}">close</md-icon>
                                            <h2 class="md-title pull-left">Property Sale Details</h2>
                                            <div class="clearfix"></div>
                                            <div class="md-subhead">
                                                <md-icon>location_on</md-icon>
                                                <span>{{ shared.property.address}}</span>
                                            </div>
                                        </md-card-header>

                                        <md-card-content>
                                            <label class="label label-info">${{ shared.property.price}} Sale Price</label>
                                            <label class="label label-info">{{ shared.property.bedrooms}} Bedrooms</label>
                                            <label class="label label-info">{{ shared.property.sq_ft }} Sq Ft</label>
                                            <label class="label label-info">{{ shared.property.bathrooms }} Bathrooms</label>
                                        </md-card-content>
                                    </md-card-area>

                                    <md-card-actions>
                                        <md-menu md-size="5" md-direction="top left">
                                            <md-button class="md-list-action md-primary" md-menu-trigger>
                                                <md-icon>more_horiz</md-icon>
                                            </md-button>
                                            <md-menu-content>
                                                <md-menu-item v-if="shared.property.source == 'zillow'" :href="shared.property.source_link" target="_blank">
                                                    <md-icon>hotel</md-icon>
                                                    <span>View on Zillow</span>
                                                </md-menu-item>
                                                <md-menu-item v-if="shared.property.source == 'realtor'" :href="shared.property.source_link" target="_blank">
                                                    <md-icon>hotel</md-icon>
                                                    <span>View on Realtor.com</span>
                                                </md-menu-item>
                                            </md-menu-content>
                                        </md-menu>
                                    </md-card-actions>
                                </md-card>

                                <md-card v-else class="card-example">
                                    <md-card-area md-inset>
                                        <md-card-header>
                                            <md-icon class="pull-right cursor-pointer" @click.native="shared.property = {}">close</md-icon>
                                            <h2 class="md-title pull-left">Property Lease Details</h2>
                                            <div class="clearfix"></div>
                                            <div class="md-subhead">
                                                <md-icon>phone</md-icon>
                                                <span>{{ shared.property.phone }}</span>
                                            </div>
                                            <div class="md-subhead">
                                                <md-icon>location_on</md-icon>
                                                <span>{{ shared.property.address}}</span>
                                            </div>
                                        </md-card-header>

                                        <md-card-content>
                                            <label class="label label-info">${{ shared.property.price}} Per Month</label>
                                            <label class="label label-info">{{ shared.property.bedrooms}} Bedrooms</label>
                                            <label class="label label-info">{{ shared.property.sq_ft }} Sq Ft</label>
                                            <label class="label label-info">{{ shared.property.bathrooms }} Bathrooms</label>
                                        </md-card-content>
                                    </md-card-area>

                                    <md-card-actions>
                                        <md-menu md-size="5" md-direction="top left">
                                            <md-button class="md-list-action md-primary" md-menu-trigger>
                                                <md-icon>more_horiz</md-icon>
                                            </md-button>
                                            <md-menu-content>
                                                <md-menu-item :href="shared.property.source_link" target="_blank">
                                                    <md-icon>hotel</md-icon>
                                                    <span>View on Apartments.com</span>
                                                </md-menu-item>
                                            </md-menu-content>
                                        </md-menu>
                                    </md-card-actions>
                                </md-card>
                            </div>

                            <!-- Map Loading Details -->
                            <div v-if="!state.finishedLoading" class="no-padding-sm-md no-padding-sm-lg no-padding-md-lg margin-top-10">
                                <md-card class="card-example">
                                    <md-card-area md-inset>
                                        <md-card-header>
                                            <h2 class="md-title">Details</h2>
                                            <div class="clearfix"></div>
                                        </md-card-header>

                                        <md-card-content>
                                            <md-progress md-indeterminate></md-progress>
                                            <span v-for="(message, index) in state.loadingMessages">{{ message }} <br/></span>
                                        </md-card-content>
                                    </md-card-area>

                                </md-card>
                            </div>

                            <!-- Default Location Details -->
                            <div v-if="!shared.listing.id && !shared.property.id && state.finishedLoading" class="margin-top-10">
                                <md-card class="margin-top-10" v-for="(location,rowIndex) in shared.locations">
                                    <md-card-area md-inset>
                                        <md-card-header>
                                            <md-icon class="pull-right cursor-pointer" @click.native="removeItemFromReport(rowIndex)">close</md-icon>
                                            <h3 class="md-title pull-left">{{ location.city }}, {{ location.state }}</h3>
                                            <div class="clearfix"></div>

                                            <div class="md-subhead">
                                                <md-icon>location_on</md-icon>
                                                <span>{{ location.listings.length }} Listing(s)</span>
                                            </div>
                                        </md-card-header>

                                    </md-card-area>

                                    <md-card-content v-if="location.stats">
                                        <label class="label label-info">{{ location.stats.percent_booked }}% Avg Occupancy</label>
                                        <label class="label label-info">${{ location.stats.price_per_bed }} Avg Price Per Bed</label>
                                        <label class="label label-info">{{ location.stats.avg_beds }} Avg Beds</label>
                                        <label class="label label-info">{{ location.stats.avg_capacity }} Avg Capacity</label>
                                    </md-card-content>
                                    <md-card-content v-else>
                                        <label class="label label-info">Stats have not been generated yet.</label>
                                    </md-card-content>

                                </md-card>
                            </div>

                            <div class="margin-top-20">&nbsp;</div>
                            <div class="clearfix"></div>

                        </div>
                    </md-tab>
                    <md-tab md-label="Selected Listings" :md-active="selectedListings.length > 0">
                        <div v-if="selectedListings.length > 0" class="col-xs-12">
                            <trans-table-sort :columns="sortColumns" @multiSort="onMultiSort"></trans-table-sort>
                        </div>
                        <table v-if="selectedListings.length > 0" class="table table-responsive table-condensed listings-table">
                            <thead>
                                <th>
                                    <md-checkbox class="md-primary" @change="selectAllListings($event)"></md-checkbox>
                                </th>
                                <th>Name</th>
                                <th>Location</th>
                                <th>Bedrooms</th>
                                <th>Beds</th>
                                <th>Rate</th>
                                <th>Occupancy</th>
                                <th>Per Bed</th>
                                <th>Revenue</th>
                                <th>Capacity</th>
                                <th>Profit Score</th>
                                <th>Type</th>
                                <th>Outlier</th>
                            </thead>

                            <tbody>
                                <tr v-for="(listing, rowIndex) in selectedListings" :key="rowIndex">
                                    <td>
                                        <md-checkbox class="md-primary" @change="selectSingleListing(listing, $event)"></md-checkbox>
                                    </td>
                                    <td>
                                            <span>
                                               {{ listing.name|limitTo(30) }}
                                                <md-tooltip md-direction="top">{{ listing.name }}</md-tooltip>
                                            </span>
                                    </td>
                                    <td>
                                            <span>
                                                {{ listing.locations[0].city }}, {{ listing.locations[0].state }}
                                            </span>
                                    </td>
                                    <td>
                                        <span class="label label-info pading-10-5">{{ listing.bedrooms }}</span>
                                    </td>
                                    <td>
                                        <span class="label label-info pading-10-5">{{ listing.beds }}</span>
                                    </td>
                                    <td>
                                        <span class="label label-info pading-10-5">${{ listing.current_rate }}</span>
                                    </td>
                                    <td>
                                        <span class="label label-info pading-10-5">{{ listing.stats.percent_booked }}%</span>
                                    </td>
                                    <td>
                                        <span class="label label-info pading-10-5">${{ listing.stats.price_per_bed }}</span>
                                    </td>
                                    <td>
                                        <span class="label label-info pading-10-5">${{ listing.stats.projected_revenue }}</span>
                                    </td>
                                    <td>
                                        <span class="label label-info pading-10-5">{{ listing.capacity }}</span>
                                    </td>
                                    <td>
                                        <span class="label label-info pading-10-5">{{ listing.profit_score }}</span>
                                    </td>
                                    <td>
                                        <span>{{ listing.room_type }}</span>
                                    </td>
                                    <td>
                                        <span v-if='listing.outlier' class="label label-success pading-10-5">Yes</span>
                                        <span v-if='listing.potential_outlier' class="label label-warning pading-10-5">Potential</span>
                                        <span v-if='!listing.potential_outlier && !listing.outlier' class="label label-info pading-10-5">No</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-if="selectedListings.length == 0">Select a region by drawing a new plolygon to populate the selected listings table.</p>
                    </md-tab>

                    <!--
                    <md-tab md-label="Details">
                        <md-table>
                            <md-table-header>
                                <md-table-row>
                                    <md-table-head md-sort-by="city">City</md-table-head>
                                    <md-table-head md-sort-by="state">State</md-table-head>
                                    <md-table-head md-sort-by="num_listings">Listings</md-table-head>
                                    <md-table-head md-sort-by="avg_price">Avg Rate</md-table-head>
                                    <md-table-head md-sort-by="percent_booked">Avg Occupancy</md-table-head>
                                    <md-table-head md-sort-by="price_per_bed">Avg Per Bed</md-table-head>
                                    <md-table-head md-sort-by="projected_revenue">Monthly Revenue</md-table-head>
                                    <md-table-head md-sort-by="avg_bedrooms">Avg Rooms</md-table-head>
                                    <md-table-head md-sort-by="avg_beds">Avg Beds</md-table-head>
                                    <md-table-head md-sort-by="avg_capacity">Avg Capacity</md-table-head>
                                </md-table-row>
                            </md-table-header>

                            <md-table-body>
                                <md-table-row v-for="(location, rowIndex) in shared.locations" :key="rowIndex" :md-item="location">
                                    <md-table-cell>
                                        <span>{{ location.city }}</span>
                                    </md-table-cell>
                                    <md-table-cell>
                                    <span>
                                        {{ location.state }}
                                    </span>
                                    </md-table-cell>
                                    <md-table-cell>
                                        <span class="label label-info pading-10-5">{{ location.num_listings }}</span>
                                    </md-table-cell>
                                    <md-table-cell>
                                        <span class="label label-info pading-10-5">${{ location.stats.avg_rate }}</span>
                                    </md-table-cell>
                                    <md-table-cell>
                                        <span class="label label-info pading-10-5">{{ location.stats.percent_booked }}%</span>
                                    </md-table-cell>
                                    <md-table-cell>
                                        <span class="label label-info pading-10-5">${{ location.stats.price_per_bed }}</span>
                                    </md-table-cell>
                                    <md-table-cell>
                                        <span class="label label-info pading-10-5">${{ location.stats.projected_revenue }}</span>
                                    </md-table-cell>
                                    <md-table-cell>
                                        <span class="label label-info pading-10-5">{{ location.avg_bedrooms }}</span>
                                    </md-table-cell>
                                    <md-table-cell>
                                        <span class="label label-info pading-10-5">{{ location.stats.avg_beds }}</span>
                                    </md-table-cell>
                                    <md-table-cell>
                                        <span class="label label-info pading-10-5">{{ location.stats.avg_capacity }}</span>
                                    </md-table-cell>
                                </md-table-row>
                            </md-table-body>
                        </md-table>
                    </md-tab>
                    -->

                    <!--
                    <md-tab md-label="Outliers">
                        <md-table>
                            <md-table-header>
                                <md-table-row>
                                    <md-table-head md-sort-by="name">Name</md-table-head>
                                    <md-table-head md-sort-by="city">Location</md-table-head>
                                    <md-table-head md-sort-by="bedrooms">Bedrooms</md-table-head>
                                    <md-table-head md-sort-by="beds">Beds</md-table-head>
                                    <md-table-head md-sort-by="current_rate">Rate</md-table-head>
                                    <md-table-head md-sort-by="percent_booked">Occupancy</md-table-head>
                                    <md-table-head md-sort-by="price_per_bed">Per Bed</md-table-head>
                                    <md-table-head md-sort-by="projected_revenue">Revenue</md-table-head>
                                    <md-table-head md-sort-by="capacity">Capacity</md-table-head>
                                    <md-table-head md-sort-by="room_type">Type</md-table-head>
                                    <md-table-head md-sort-by="outlier">Outlier</md-table-head>
                                </md-table-row>
                            </md-table-header>

                            <md-table-body>
                                    <md-table-row v-for="(listing, rowIndex) in outliers" :key="rowIndex" :md-item="listing">
                                        <md-table-cell>
                                            <span>
                                               {{ listing.name }}
                                                <md-tooltip md-direction="top">{{ listing.name }}</md-tooltip>
                                            </span>
                                        </md-table-cell>
                                        <md-table-cell>
                                            <span>
                                                {{ listing.locations[0].city }}, {{ listing.locations[0].state }}
                                            </span>
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
                                            <span class="label label-info pading-10-5">{{ listing.stats.percent_booked }}%</span>
                                        </md-table-cell>
                                        <md-table-cell>
                                            <span class="label label-info pading-10-5">${{ listing.stats.price_per_bed }}</span>
                                        </md-table-cell>
                                        <md-table-cell>
                                            <span class="label label-info pading-10-5">${{ listing.stats.projected_revenue }}</span>
                                        </md-table-cell>
                                        <md-table-cell>
                                            <span class="label label-info pading-10-5">{{ listing.capacity }}</span>
                                        </md-table-cell>
                                        <md-table-cell>
                                            <span>{{ listing.room_type }}</span>
                                        </md-table-cell>
                                        <md-table-cell>
                                           <trans-listing-outlier-status  :listing="listing"></trans-listing-outlier-status>
                                        </md-table-cell>
                                    </md-table-row>
                            </md-table-body>
                        </md-table>
                    </md-tab>
                    -->
                </md-tabs>
            </md-card-area>
            <div class="clearfix"></div>
        </md-card>

        <md-dialog-prompt
                md-title="Save Custom Region"
                md-ok-text="Save Region"
                md-cancel-text="Cancel"
                md-input-placeholder="Enter a name for this region."
                md-input-maxlength="255"
                v-model="regionName"
                @close="saveRegion"
                ref="saveRegionDialog">
        </md-dialog-prompt>

        <md-dialog-prompt
                md-title="Save Selected Listings"
                md-ok-text="Save Listings"
                md-cancel-text="Cancel"
                md-input-placeholder="Enter a name for this report."
                md-input-maxlength="255"
                v-model="state.savedReportName"
                @close="saveListingsReport"
                ref="saveListingsDialog">
        </md-dialog-prompt>

    </div>
</template>

<script>
	import vueSlider from 'vue-slider-component';
	var NumAbbr = require('number-abbreviate');
	var numAbbr = new NumAbbr();

	export default {
		mounted() {
			console.log(' Revenue map component ready.')
			var vm = this;
			if (!_.isArray(this.shared.customMarkers)) {
				this.shared.customMarkers = [];
			}
			vm.loadMapData();

		},
		beforeUpdate() {
			//console.time('beforeUpdate');
		},
		updated() {
			//console.timeEnd('beforeUpdate');
		},
		components: {
			vueSlider
		},
		props: ['title'],
		data: () => ({
			state: {
				loading: false,
				showMarkers: false,
				showFilters: false,
				showProperties: false,
				sortSelected: false,
				finishedLoading: false,
				loadingMessages: [],
				loadingDuplicates: [],
				filtering: false,
				manualMarkerSelected: false,
				pinMode: false, //Is pin dropping enabled
				selectedListings: [],
				savedReportName: ''
			},
			mapPoints: null,
			occupancyFilter: [0, 100],
			revenueFilter: [0, 30000],
			filters: {
				occupancy: {
					width: '100%',
					height: 8,
					min: 0,
					max: 100,
					interval: 1,
					disabled: false,
					show: true,
					tooltip: 'hover',
					piecewise: false,
					value: [0, 100],
					formatter: '{value}%',
					bgStyle: {
						backgroundColor: '#646464'
					},
					tooltipStyle: {
						backgroundColor: '#5c6bc0',
						borderColor: '#5c6bc0'
					},
					processStyle: {
						backgroundColor: '#5c6bc0'
					}
				},
				beds: {
					width: '100%',
					height: 8,
					min: 0,
					max: 30,
					interval: 1,
					disabled: false,
					show: true,
					tooltip: 'hover',
					piecewise: false,
					value: [0, 30],
					formatter: '{value}',
					bgStyle: {
						backgroundColor: '#646464'
					},
					tooltipStyle: {
						backgroundColor: '#5c6bc0',
						borderColor: '#5c6bc0'
					},
					processStyle: {
						backgroundColor: '#5c6bc0'
					}
				},
				rooms: {
					width: '100%',
					height: 8,
					min: 0,
					max: 20,
					interval: 1,
					disabled: false,
					show: true,
					tooltip: 'hover',
					piecewise: false,
					value: [0, 20],
					formatter: '{value}',
					bgStyle: {
						backgroundColor: '#646464'
					},
					tooltipStyle: {
						backgroundColor: '#5c6bc0',
						borderColor: '#5c6bc0'
					},
					processStyle: {
						backgroundColor: '#5c6bc0'
					}
				},
				revenue: {
					width: '100%',
					height: 8,
					min: 0,
					max: 40000,
					interval: 100,
					disabled: false,
					show: true,
					tooltip: 'hover',
					piecewise: false,
					value: [0, 40000],
					formatter: '${value}',
					bgStyle: {
						backgroundColor: '#646464'
					},
					tooltipStyle: {
						backgroundColor: '#5c6bc0',
						borderColor: '#5c6bc0'
					},
					processStyle: {
						backgroundColor: '#5c6bc0'
					}
				},
				score: {
					width: '100%',
					height: 8,
					min: 0,
					max: 100,
					interval: 1,
					disabled: false,
					show: true,
					tooltip: 'hover',
					piecewise: false,
					value: [0, 100],
					formatter: '{value}%',
					bgStyle: {
						backgroundColor: '#646464'
					},
					tooltipStyle: {
						backgroundColor: '#5c6bc0',
						borderColor: '#5c6bc0'
					},
					processStyle: {
						backgroundColor: '#5c6bc0'
					}
				},
				propbeds: {
					width: '100%',
					height: 8,
					min: 0,
					max: 30,
					interval: 1,
					disabled: false,
					show: true,
					tooltip: 'hover',
					piecewise: false,
					value: [0, 30],
					formatter: '{value}',
					bgStyle: {
						backgroundColor: '#646464'
					},
					tooltipStyle: {
						backgroundColor: '#5c6bc0',
						borderColor: '#5c6bc0'
					},
					processStyle: {
						backgroundColor: '#5c6bc0'
					}
				},
				proprooms: {
					width: '100%',
					height: 8,
					min: 0,
					max: 20,
					interval: 1,
					disabled: false,
					show: true,
					tooltip: 'hover',
					piecewise: false,
					value: [0, 20],
					formatter: '{value}',
					bgStyle: {
						backgroundColor: '#646464'
					},
					tooltipStyle: {
						backgroundColor: '#5c6bc0',
						borderColor: '#5c6bc0'
					},
					processStyle: {
						backgroundColor: '#5c6bc0'
					}
				},
				propsqft: {
					width: '100%',
					height: 8,
					min: 0,
					max: 40000,
					interval: 100,
					disabled: false,
					show: true,
					tooltip: 'hover',
					piecewise: false,
					value: [0, 40000],
					formatter: '{value}',
					bgStyle: {
						backgroundColor: '#646464'
					},
					tooltipStyle: {
						backgroundColor: '#5c6bc0',
						borderColor: '#5c6bc0'
					},
					processStyle: {
						backgroundColor: '#5c6bc0'
					}
				},
				propprice: {
					width: '100%',
					height: 8,
					min: 0,
					max: 10000000,
					interval: 10000,
					disabled: false,
					show: true,
					tooltip: 'hover',
					piecewise: false,
					value: [0, 10000000],
					formatter: '${value}',
					bgStyle: {
						backgroundColor: '#646464'
					},
					tooltipStyle: {
						backgroundColor: '#5c6bc0',
						borderColor: '#5c6bc0'
					},
					processStyle: {
						backgroundColor: '#5c6bc0'
					}
				},
				property_homes: true,
                property_apartments: true,
				homes: true,
				condos: true,
				airbnb: true,
				homeaway: true
			},
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
			options: {
				multiSort: []
			},
			styles: null,
			map: null,
			heatmap: null,
			locations: null,
			locationIndex: 0,
			propertyIndex: 0,
			markerThrottle: null,
			propertyThrottle: null,
			propertyCluster: null,
			markerCluster: null,
			avgRevenue: null,
			infoWindows: [],
			markers: [],
			propertyMarkers: [],
			selectedListings: [],
			selectedManualMarker: null,
			selectedShape: null,
			regionName: '',
			manualMarkerTemplate: '',
			_: window._,
			shared: window.appShared,
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
			numFormat: function (value) {
				return numAbbr.abbreviate(value, 1);
			}
		},
		watch: {
			manualMarkerTemplate: function (label) {
				this.selectedManualMarker.info = label;
			},
		},
		computed: {
			outliers: function () {
				var outliers = [];
				_.each(this.shared.locations, function (location) {
					_.each(location.listings, function (listing) {
						if (listing.outlier == true || listing.potential_outlier == true) {
							outliers.push(listing);
						}
					});
				})
				return outliers;
			},
			listingStats: function () {
				if (!this.shared.listing || !this.shared.listing.locations || _.isEmpty(this.shared.listing.locations[0])) {
					return {}
				}
				;

				var location = this.getLocationById(this.shared.listing.locations[0].id);
				return {
					relative_occupancy: Math.round(this.shared.listing.stats.percent_booked - location.stats.percent_booked, 2),
					relative_beds: Math.round(this.shared.listing.beds - location.stats.avg_beds, 2),
					relative_price_per_bed: Math.round(this.shared.listing.stats.price_per_bed - location.stats.price_per_bed, 2),
					relative_rate: Math.round(this.shared.listing.stats.avg_rate - location.stats.avg_rate, 2)
				}
			}
		},
		methods: {
			removeItemFromReport(itemIndex) {

				if (this.shared.locations.length == 1) {
					this.$root.showNotification("You can not remove the last active location from the report.");
					return;
				}
				this.$root.showNotification("Please wait while the report is refreshed.");
				this.shared.locations.splice(itemIndex, 1);
				this.$refs.reportSelect.runReport();
			},
			applyFilters() {
				var vm = this;
				vm.filtering = true;
				setTimeout(function () {
					vm.createMap(true);
				}, 1000);
			},
			onMultiSort(sortOptions) {
				this.options.multiSort = sortOptions;
				this.loadRegionListings();
			},
			selectAllListings(value) {
				if (value) {
					this.state.selectedListings = this.selectedListings;
				} else {
					this.state.selectedListings = [];
				}
			},
			selectSingleListing(listing, event) {

				if (event) {
					this.state.selectedListings.push(listing);
				} else {
					this.state.selectedListings.splice(this.state.selectedListings.indexOf(listing), 1);
				}

			},
			saveListingsReport(confirmation) {
				if (confirmation != 'ok') {
					return;
				}
				if (_.isEmpty(this.state.savedReportName)) {
					this.$root.showNotification("You must enter a name for this group of listings.");
					return;
				}

				var itemIds = _.map(this.state.selectedListings, function (item) {
					return item.id;
				});

				var savedReport = {
					'type': 'customListings',
					'item_type': 'listings',
					'items': itemIds,
					'options': {},
					'name': this.state.savedReportName
				}

				this.$http.post('/apiv1/reports/save/', savedReport).then((response) => {

					this.$root.showNotification(response.body.message);

				}, (response) => {
					this.$root.showNotification(response.body.message);
					console.log(response);
				});

			},
			toggleHomeAway() {
				this.filters.homeaway = !this.filters.homeaway;
				this.state.showMarkers = false;
				this.toggleMarkers(false);
				this.createMap(true);
			},
			toggleAirBnb() {
				this.filters.airbnb = !this.filters.airbnb;
				this.state.showMarkers = false;
				this.toggleMarkers(false);
				this.createMap(true);
			},
			selectMapListing(listing) {
				this.shared.listing = listing;
				this.state.loadingDuplicates = true;
				var apiUrl = '/apiv1/listings/' + this.shared.listing.id;
				this.$http.get(apiUrl).then((response) => {
					this.state.loadingDuplicates = false;
					this.shared.duplicates = response.body.results.duplicates;
				}, (response) => {
					this.$root.showNotification("Error loading duplicate listings.")
					console.log(response.message);
				});
			},
			toggleMarkers(toggle) {
				if (toggle) {
					this.state.showMarkers = !this.state.showMarkers;
				}

				if (this.state.showMarkers == true) {
					//this.setMapOnMarkers(this.map, this.markers);
					this.markerCluster = new MarkerClusterer(this.map, this.markers,
						{
							imagePath: '/js/plugins/maps/m',
							minimumClusterSize: 4,
							maxZoom: 15
						}
					);
				} else {
					if (this.markerCluster) {
						this.markerCluster.clearMarkers();
						this.setMapOnMarkers(null, this.markers);
					}
				}
				console.timeEnd("listingmarkers");
			},
			togglePropertyMarkers(toggle) {
				console.time("propertymarkers");
				if (toggle) {
					this.state.showProperties = !this.state.showProperties;
				}
				if (this.state.showProperties == true) {
					//this.setMapOnMarkers(this.map, this.propertyMarkers);
					this.propertyCluster = new MarkerClusterer(this.map, this.propertyMarkers,
						{
							imagePath: '/js/plugins/maps/h',
							minimumClusterSize: 4,
							maxZoom: 15
						}
					);

				} else {
					if (this.propertyCluster) {
						this.propertyCluster.clearMarkers();
						this.setMapOnMarkers(null, this.propertyMarkers);
					}
				}
				console.timeEnd("propertymarkers");
			},
			addLocationMarker(locations) {
                /* Show Location Markers */
				var vm = this;
				var latLng = new google.maps.LatLng(locations[this.locationIndex].location.lat(), locations[this.locationIndex].location.lng());
				var curListing = vm.getLocation(locations[this.locationIndex].listing_id);

				if (_.isUndefined(curListing)) {
					clearInterval(this.markerThrottle);
					return;
				}

				var curMap = null;

				var iconUrl = vm.shared.baseUrl + "/img/markerIconLabel.png";
				if (curListing.outlier == true || curListing.potential_outlier == true) {
					iconUrl = vm.shared.baseUrl + "/img/outlierIconLabel.png"
				} else if (curListing.room_type == 'condo' || curListing.room_type == 'apartment') {
					iconUrl = vm.shared.baseUrl + "/img/condoIconLabel.png"
				}

				var markerLabel = '';
				if (vm.shared.reportOptions['heatmapWeight'] == 'profit_score') {
					markerLabel = '' + numAbbr.abbreviate(curListing.profit_score, 1);
				} else if (vm.shared.reportOptions['heatmapWeight'] == 'lease_score') {
					markerLabel = '' + numAbbr.abbreviate(curListing.lease_score, 1);
				} else {
					markerLabel = '$' + numAbbr.abbreviate(curListing.stats.projected_revenue, 1);
				}

				var marker = new google.maps.Marker({
					position: latLng,
					map: curMap,
					info: '<div style="min-width: 350px"><h3>' + curListing.name + '</h3>' +
					'<ul class="list-group">' +
					'<li class="list-group-item">' +
					'<span class="text-strong">Projected Revenue</span>' +
					'<span class="pull-right">$' + curListing.stats.projected_revenue + '</span>' +
					'</li>' +
					'</ul></div>',
					listing: curListing,
					icon: {
						labelOrigin: new google.maps.Point(30, 16),
						url: iconUrl
					},
					label: {
						text: markerLabel,
						color: '#FFFFFF',
						fontWeight: '600',
						fontSize: '12'
					}
				});

				vm.markers.push(marker);

				if (curListing) {
					var infoWindow = new google.maps.InfoWindow({
						content: "Loading.."
					});

					marker.addListener('click', function () {
						infoWindow.setContent(this.info);
						infoWindow.open(map, this);
						vm.selectMapListing(this.listing);
					})
				}

				this.locationIndex++;

				if (this.locationIndex >= locations.length) {
					clearInterval(this.markerThrottle);
					console.timeEnd('addLocationMarkers');
					vm.filtering = false;
					this.state.loadingMessages.push("Finished adding  " + locations.length + " location markers.");
					this.toggleMarkers();
					setTimeout(function () {
						vm.state.finishedLoading = true;
					}, 1000);
				}
			},
			addPropertyMarker() {
				var vm = this;
				var propertyIndex = vm.shared.propertyIndex[vm.propertyIndex];
				if (_.isUndefined(propertyIndex) || this.propertyIndex > vm.shared.propertyIndex.length) {
					console.log("...");
					clearInterval(this.propertyThrottle);
					return;
				}

				var property = vm.shared.locations[propertyIndex.location_index].properties[propertyIndex.property_index];

				if (!this.filterProperties(property)) {
					this.propertyIndex++;
					if (this.propertyIndex >= vm.shared.propertyIndex.length) {
						clearInterval(this.propertyThrottle);
						console.timeEnd('addPropertyMarkers');
						this.togglePropertyMarkers();
						this.state.loadingMessages.push("Finished adding  " + vm.shared.propertyIndex.length + " property markers.");
					}
					return;
				}

				var latLng = new google.maps.LatLng(property.lat, property.lng);
				var curMap = null;
				var iconUrl = vm.shared.baseUrl + "/img/houseIconLabel.png";

				var marker = new google.maps.Marker({
					position: latLng,
					map: curMap,
					info: '<div style="min-width: 350px"><h3>' + property.address + '</h3>' +
					'<ul class="list-group">' +
					'<li class="list-group-item">' +
					'<span class="text-strong">Price</span>' +
					'<span class="pull-right">$' + property.price + '</span>' +
					'</li>' +
					'</ul></div>',
					property: property,
					icon: {
						labelOrigin: new google.maps.Point(30, 16),
						url: iconUrl
					},
					label: {
						text: '$' + numAbbr.abbreviate(property.price, 1),
						color: '#FFFFFF',
						fontWeight: '600',
						fontSize: '12'
					}
				});
				vm.propertyMarkers.push(marker);

				var infoWindow = new google.maps.InfoWindow({
					content: "Loading.."
				});

				marker.addListener('click', function () {
					infoWindow.setContent(this.info);
					infoWindow.open(map, this);
					vm.shared.property = this.property;
				});

				this.propertyIndex++;
				if (this.propertyIndex >= vm.shared.propertyIndex.length) {
					clearInterval(this.propertyThrottle);
					console.timeEnd('addPropertyMarkers');
					this.togglePropertyMarkers();
					this.state.loadingMessages.push("Finished adding  " + vm.shared.propertyIndex.length + " property markers.");
				}

			},
			drawMarkers(points) {

				var locations = points;
				var vm = this;
				this.markers = [];

				if (this.markerThrottle) {
					clearInterval(this.markerThrottle);
				}

				this.locationIndex = 0;

                /* Progressively load location markers */
				console.time("addLocationMarkers");
				this.state.loadingMessages.push("Adding location markers...");
				this.markerThrottle = setInterval(function () {
					vm.addLocationMarker(locations);
				}, 3);

                /* Show property markers */
				console.time("addPropertyMarkers");
				vm.propertyIndex = 0;
				if (this.shared.propertyIndex.length > 0 && vm.propertyIndex < vm.shared.propertyIndex.length) {
					this.propertyMarkers = [];

					if (this.propertyThrottle) {
						clearInterval(this.propertyThrottle);
					}

					this.propertyIndex = 0;
					this.state.loadingMessages.push("Adding property markers...");
					this.propertyThrottle = setInterval(function () {
						vm.addPropertyMarker();
					}, 3);
				} else {
					this.togglePropertyMarkers();
				}

                /* Add custom markers from saved reports if set */
				if (_.isArray(this.shared.reportOptions.customMarkers)) {
					_.each(this.shared.reportOptions.customMarkers, function (marker) {
						vm.addManualMarker(marker.position, marker.label);
					});
				}
			},
			addManualMarker(latLng, label) {

				if (_.isUndefined(label)) {
					label = 'New Point of Interest';
				}

				var newMarker = {
					position: latLng,
					map: this.map,
					info: label,
					draggable: true
				}

				var marker = new google.maps.Marker(newMarker);
				var infoWindow = new google.maps.InfoWindow({
					content: "Loading.."
				});

				var vm = this;
				marker.addListener('click', function () {
					infoWindow.setContent(this.info);
					infoWindow.open(map, this);
					vm.selectedManualMarker = this;
					vm.state.manualMarkerSelected = true;
					vm.manualMarkerTemplate = this.info;
				});

				this.shared.customMarkers.push(marker);
				this.selectedManualMarker = marker;
				vm.state.manualMarkerSelected = true;
				vm.manualMarkerTemplate = marker.info;

			},
			removeCustomMarker() {
				this.selectedManualMarker.setMap(null);
				this.state.manualMarkerSelected = false;
			},
			getLocation(id) {

				var curLocation = null;
				_.each(this.shared.locations, function (location, index) {
					_.each(location.listings, function (listing, listingIndex) {
						if (listing.id == id) {
							curLocation = listing;
						}
					});
				});

				return curLocation;

			},
			getLocationById(id) {
				var curLocation = null;
				_.each(this.shared.locations, function (location, index) {
					if (location.id == id) {
						curLocation = location;
					}
				});
				return curLocation;
			},
			setMapOnMarkers(map, markers) {
				for (var i = 0; i < markers.length; i++) {
					markers[i].setMap(map);
				}
			},
			loadMapData() {
				this.state.loadingMessages.push("Loading map data...");
				var apiUrl = decodeURI(window.location.href.replace("admin", "apiv1"));
				this.$http.get(apiUrl).then((response) => {

					this.shared.locations = response.body.locations;
					this.shared.listingIndex = response.body.listingIndex;
					this.shared.propertyIndex = response.body.propertyIndex;
					this.mapPoints = response.body.heatMapData;

					var vm = this;
					setTimeout(function () {
						vm.state.loadingMessages.push("Creating map object...");
						vm.createMap();
					}, 500);
				}, (response) => {
					this.$root.showNotification("Error loading map data.")
					console.log(response.message);
				});
			},
			createMap(redrawMarkers) {

				console.time("createmap");
				var vm = this;
				console.time("getMapPoints");
				var points = vm.getPoints();
				console.timeEnd("getMapPoints");
				if (points.length == 0) {
					this.$root.showNotification("No listings match your filter criteria, please revise and try again.");
					return;
				}
				this.styles = new google.maps.StyledMapType(
					[
						{
							"elementType": "geometry",
							"stylers": [
								{
									"color": "#1d2c4d"
								}
							]
						},
						{
							"elementType": "labels.text.fill",
							"stylers": [
								{
									"color": "#8ec3b9"
								}
							]
						},
						{
							"elementType": "labels.text.stroke",
							"stylers": [
								{
									"color": "#1a3646"
								}
							]
						},
						{
							"featureType": "administrative.country",
							"elementType": "geometry.stroke",
							"stylers": [
								{
									"color": "#4b6878"
								}
							]
						},
						{
							"featureType": "administrative.land_parcel",
							"elementType": "labels.text.fill",
							"stylers": [
								{
									"color": "#64779e"
								}
							]
						},
						{
							"featureType": "administrative.province",
							"elementType": "geometry.stroke",
							"stylers": [
								{
									"color": "#4b6878"
								}
							]
						},
						{
							"featureType": "landscape.man_made",
							"elementType": "geometry.stroke",
							"stylers": [
								{
									"color": "#334e87"
								}
							]
						},
						{
							"featureType": "landscape.natural",
							"elementType": "geometry",
							"stylers": [
								{
									"color": "#023e58"
								}
							]
						},
						{
							"featureType": "poi",
							"elementType": "geometry",
							"stylers": [
								{
									"color": "#283d6a"
								}
							]
						},
						{
							"featureType": "poi",
							"elementType": "labels.text.fill",
							"stylers": [
								{
									"color": "#6f9ba5"
								}
							]
						},
						{
							"featureType": "poi",
							"elementType": "labels.text.stroke",
							"stylers": [
								{
									"color": "#1d2c4d"
								}
							]
						},
						{
							"featureType": "poi.park",
							"elementType": "geometry.fill",
							"stylers": [
								{
									"color": "#023e58"
								}
							]
						},
						{
							"featureType": "poi.park",
							"elementType": "labels.text.fill",
							"stylers": [
								{
									"color": "#3C7680"
								}
							]
						},
						{
							"featureType": "road",
							"elementType": "geometry",
							"stylers": [
								{
									"color": "#304a7d"
								}
							]
						},
						{
							"featureType": "road",
							"elementType": "labels.text.fill",
							"stylers": [
								{
									"color": "#98a5be"
								}
							]
						},
						{
							"featureType": "road",
							"elementType": "labels.text.stroke",
							"stylers": [
								{
									"color": "#1d2c4d"
								}
							]
						},
						{
							"featureType": "road.highway",
							"elementType": "geometry",
							"stylers": [
								{
									"color": "#2c6675"
								}
							]
						},
						{
							"featureType": "road.highway",
							"elementType": "geometry.stroke",
							"stylers": [
								{
									"color": "#255763"
								}
							]
						},
						{
							"featureType": "road.highway",
							"elementType": "labels.text.fill",
							"stylers": [
								{
									"color": "#b0d5ce"
								}
							]
						},
						{
							"featureType": "road.highway",
							"elementType": "labels.text.stroke",
							"stylers": [
								{
									"color": "#023e58"
								}
							]
						},
						{
							"featureType": "transit",
							"elementType": "labels.text.fill",
							"stylers": [
								{
									"color": "#98a5be"
								}
							]
						},
						{
							"featureType": "transit",
							"elementType": "labels.text.stroke",
							"stylers": [
								{
									"color": "#1d2c4d"
								}
							]
						},
						{
							"featureType": "transit.line",
							"elementType": "geometry.fill",
							"stylers": [
								{
									"color": "#283d6a"
								}
							]
						},
						{
							"featureType": "transit.station",
							"elementType": "geometry",
							"stylers": [
								{
									"color": "#3a4762"
								}
							]
						},
						{
							"featureType": "water",
							"elementType": "geometry",
							"stylers": [
								{
									"color": "#0e1626"
								}
							]
						},
						{
							"featureType": "water",
							"elementType": "labels.text.fill",
							"stylers": [
								{
									"color": "#4e6d70"
								}
							]
						}
					],
					{name: 'Dark'}
				);

				this.map = new google.maps.Map(document.getElementById('map'), {
					zoom: 5,
					center: {lat: points[0].location.lat(), lng: points[0].location.lng()},
					mapTypeId: 'roadmap',
					fullscreenControl: true,
					mapTypeControlOptions: {
						mapTypeIds: ['roadmap', 'satellite', 'hybrid', 'terrain',
							'styled_map']
					}
				});

				this.map.mapTypes.set('styled_map', this.styles);
				this.map.setMapTypeId('styled_map');

                /* Add drawing manager */
				this.createDrawingManager();

                /* Support adding new markers */
				this.map.addListener('click', function (e) {
					if (!vm.state.pinMode) {
						return;
					}
					vm.addManualMarker(e.latLng);
				});

				var heatmapPoints = points;
				this.heatmap = new google.maps.visualization.HeatmapLayer({
					data: heatmapPoints,
					map: vm.map,
					opacity: 1,
					maxIntensity: this.avgRevenue * 2
				});

				var gradient = [
					'rgba(0, 255, 255, 0)',
					'rgba(0, 255, 255, 1)',
					'rgba(0, 191, 255, 1)',
					'rgba(0, 127, 255, 1)',
					'rgba(0, 63, 255, 1)',
					'rgba(0, 0, 255, 1)',
					'rgba(0, 0, 223, 1)',
					'rgba(0, 0, 191, 1)',
					'rgba(0, 0, 159, 1)',
					'rgba(0, 0, 127, 1)',
					'rgba(63, 0, 91, 1)',
					'rgba(127, 0, 63, 1)',
					'rgba(191, 0, 31, 1)',
					'rgba(255, 0, 0, 1)'
				]
				this.heatmap.set('gradient', this.heatmap.get('gradient') ? null : gradient);
				console.timeEnd("createmap");
				this.state.loading = false;

				if (this.markers.length == 0 || this.propertyMarkers.length == 0 || redrawMarkers == true) {
					this.locationIndex = 0;
					setTimeout(function () {
						vm.drawMarkers(points);
					}, 1000);
				}

			},
			createDrawingManager() {
				var drawingManager = new google.maps.drawing.DrawingManager({
					drawingMode: null,
					drawingControl: true,
					drawingControlOptions: {
						position: google.maps.ControlPosition.TOP_CENTER,
						drawingModes: ['polygon']
					},
					polygonOptions: {
						fillColor: '#000000',
						strokeWeight: 0,
						fillOpacity: 0.45,
						editable: true
					},
					circleOptions: {
						fillColor: '#000000',
						fillOpacity: 1,
						strokeWeight: 5,
						clickable: false,
						editable: true,
						zIndex: 1
					}
				});
				drawingManager.setMap(this.map);

                /* Add event handlers for shape selections */
				var vm = this;
				google.maps.event.addListener(drawingManager, 'overlaycomplete', function (e) {
					drawingManager.setDrawingMode(null);
					if (e.type != google.maps.drawing.OverlayType.MARKER) {
						var newShape = e.overlay;
						newShape.type = e.type;
                        /* Add event handler for the shape */
						google.maps.event.addListener(newShape, 'click', function () {

							if (vm.selectedShape) {
								vm.selectedShape.set('fillColor', '#000000');
							}

							vm.selectedShape = newShape;
							vm.selectedShape.setEditable(true);

                            /* Highlight the polygon */
							vm.selectedShape.set('fillColor', '#E91E63');
						});

                        /* Set current shape to selected */
						if (vm.selectedShape) {
							vm.selectedShape.set('fillColor', '#000000');
						}

						vm.selectedShape = newShape;
						vm.selectedShape.setEditable(true);

                        /* Highlight the polygon */
						vm.selectedShape.set('fillColor', '#E91E63');
					}
				});

                /* Draw existing polygon if this is already a polygon based region */
				_.each(this.shared.locations, function (location) {
					if (_.isArray(location.polygon) && location.polygon.length > 1) {
						var regionCoords = [];
						_.each(location.polygon, function (point) {
							regionCoords.push(new google.maps.LatLng(point.lat, point.lng));
						});
						var newRegion = new google.maps.Polygon({
							paths: regionCoords,
							draggable: false,
							editable: false,
							strokeColor: '#FF0000',
							strokeOpacity: 0.8,
							strokeWeight: 2,
							fillColor: '#FF0000',
							fillOpacity: 0.1
						});
						newRegion.setMap(vm.map);
					}
				});

			},
			deleteShape() {
				if (!this.selectedShape) {
					return;
				}

				this.selectedShape.setMap(null);
				this.selectedShape = null;
			},
			saveRegion(confirmation) {

				if (confirmation != 'ok') {
					return;
				}

				if (!this.selectedShape) {
					this.$root.showNotification("You must select a region first.")
				}
				var coords = [];
				var len = this.selectedShape.getPath().getLength();
				for (var i = 0; i < len; i++) {
					coords.push(this.selectedShape.getPath().getAt(i).toUrlValue(5));
				}

				var newRegion = {
					'polygon': coords,
					'name': this.regionName
				}

				this.$http.post('/apiv1/locations/region/create', newRegion).then((response) => {

					this.$root.showNotification(response.body.message);

				}, (response) => {
					this.$root.showNotification(response.body.message);
					console.log(response);
				});
			},
			openRootDialog(ref) {
				this.$root.openDialog(ref);
			},
			loadRegionListings() {
				if (!this.selectedShape) {
					this.$root.showNotification("You must select a region first.")
				}
				var coords = [];
				var len = this.selectedShape.getPath().getLength();
				for (var i = 0; i < len; i++) {
					coords.push(this.selectedShape.getPath().getAt(i).toUrlValue(5));
				}

				var newRegion = {
					'polygon': coords,
					'name': this.regionName,
					'listings': this.getListingIds(),
					'multiSort': this.options.multiSort
				}

				var vm = this;
				this.$http.post('/apiv1/locations/region/listings', newRegion).then((response) => {

					vm.selectedListings = response.body.listings;
					this.$root.showNotification(response.body.message);


				}, (response) => {
					this.$root.showNotification(response.body.message);
					console.log(response);
				});
			},
			getPoints() {
				var vm = this;
				var points = [];
				var totalRevenue = 0;
				vm.state.filtering = true;
				_.each(this.mapPoints, function (point, key) {
					var curIndex = vm.shared.listingIndex[point.listing_id];
					var curListing = vm.shared.locations[curIndex.location_index].listings[curIndex.listing_index];

					if (!curListing || !curListing.stats) {
						return;
					}

					if (curListing.source == 'airbnb' && !vm.filters.airbnb) {
						console.log('skipping airbnb listings');
						return;
					}

					if (curListing.source == 'homeaway' && !vm.filters.homeaway) {
						console.log('skipping homeaway listings');
						return;
					}

					if (curListing.stats.percent_booked < vm.filters.occupancy.value[0] || curListing.stats.percent_booked > vm.filters.occupancy.value[1]) {
						return;
					}

					if (curListing.profit_score < vm.filters.score.value[0] || curListing.profit_score > vm.filters.score.value[1]) {
						return;
					}

					if (curListing.stats.projected_revenue < vm.filters.revenue.value[0] || curListing.stats.projected_revenue > vm.filters.revenue.value[1]) {
						return;
					}

					if (curListing.beds < vm.filters.beds.value[0] || curListing.beds > vm.filters.beds.value[1]) {
						return;
					}
					if (curListing.bedrooms < vm.filters.rooms.value[0] || curListing.bedrooms > vm.filters.rooms.value[1]) {
						return;
					}

					if (curListing.room_type == 'condo' && vm.filters.condos == false) {
						return;
					}

					if (curListing.room_type == 'home' && vm.filters.homes == false) {
						return;
					}

					if (_.find(points, _.matchesProperty('listing_id', point.listing_id))) {
						return;
					}

					if (vm.shared.reportOptions['heatmapWeight'] == 'profit_score') {
						totalRevenue += parseInt(point.weight);
					} else if (vm.shared.reportOptions['heatmapWeight'] == 'lease_score') {
						totalRevenue += parseInt(point.weight);
					} else {
						totalRevenue += parseInt(curListing.stats.projected_revenue);
					}

					points.push({
						location: new google.maps.LatLng(point.lat, point.lng),
						weight: parseFloat(point.weight),
						listing_id: point.listing_id,
						location_id: point.location_id
					});
				});

				vm.avgRevenue = totalRevenue / points.length;
				vm.locations = points;
				vm.state.filtering = false;
				return this.locations;
			},
			getListingIds() {
				var ids = [];
				_.each(this.shared.locations, function (location) {
					_.each(location.listings, function (listing) {
						ids.push(listing.id);
					});
				})
				return ids;
			},
			filterProperties(property) {
				var vm = this;
				if (!property) {
					return false;
				}

				/* If only apartments are selected */
				if (vm.filters.property_apartments && !vm.filters.property_homes && property.source != 'apartments') {
					return false;
                }

                /* If only homes are selected */
                if (vm.filters.property_homes && !vm.filters.property_apartments && property.source == 'apartments' ) {
					return false;
                }

				if (property.bedrooms < vm.filters.propbeds.value[0] || property.bedrooms > vm.filters.propbeds.value[1]) {
					return false;
				}

				if (property.bathrooms < vm.filters.proprooms.value[0] || property.bathrooms > vm.filters.proprooms.value[1]) {
					return false;
				}

				if (property.price < vm.filters.propprice.value[0] || property.price > vm.filters.propprice.value[1]) {
					return false;
				}

				if (property.sq_ft < vm.filters.propsqft.value[0] || property.beds > vm.filters.propsqft.value[1]) {
					return false;
				}

				return true;
			},
			openDialog(ref) {
				this.$refs[ref].open();
			}
		}
	}
</script>