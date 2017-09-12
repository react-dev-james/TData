<template>
    <div>
        <div :class="selectDivClasses">
            <md-input-container class="margin-bottom-5">
                <label for="report_type">Select a Report</label>
                <md-select name="report_type" id="report_type" v-model="reportType">
                    <md-option v-if="itemType == 'locations'" value="locationRevenueHeatmap">Revenue Heatmap Report</md-option>
                    <md-option v-if="itemType == 'locations'" value="pricePerBed">Price Per Bed Report</md-option>
                    <md-option v-if="itemType == 'locations'" value="revenuePerBed">Revenue Per Bed Report</md-option>
                    <md-option value="monthlyRevenueOccupancy">Monthly Revenue & Occupancy Report</md-option>
                    <md-option v-if="itemType == 'locations'" value="homeValues">Home Values Report</md-option>
                    <md-option v-if="itemType == 'locations'" value="homeValuesProfitScore">Annual Revenue & Home Values</md-option>
                    <md-option v-if="itemType == 'listings'" value="nightlyRates">Nightly Rates Report</md-option>
                    <md-option value="default" disabled>More Later...</md-option>
                </md-select>
            </md-input-container>
            <div v-if="showOptions && (currentReport != 'nightlyRates' && reportType != 'monthlyRevenueOccupancy' && reportType != 'homeValues')">
                <ui-datepicker ref='startDate' v-model='options.startDate' :date-filter="datePickerFilter" :floatingLabel="true" icon="date_range" label="Start Date" placeholder="Select a Date">
                </ui-datepicker>

                <ui-datepicker ref='endDate' v-model='options.endDate' :date-filter="datePickerFilter" :floatingLabel="true" icon="date_range" label="End Date" placeholder="Select a Date"></ui-datepicker>
                <div v-if="reportType != 'nightlyRates' && reportType != 'homeValues'" class="min-width-300">
                    <md-input-container class="margin-bottom-5">
                        <label for="report_source">Select a Source</label>
                        <md-select name="report_source" id="report_source" v-model="shared.reportOptions.source">
                            <md-option value="airbnb">AirBnB</md-option>
                            <md-option value="homeaway">HomeAway</md-option>
                            <md-option value="combined">Combined</md-option>
                        </md-select>
                    </md-input-container>
                </div>
                <div class="margin-top-25">
                    <md-switch v-model="options.includeOutliers" class="md-primary"
                               @change="options.includeOutliers = !options.includeOutliers">
                        Outliers
                    </md-switch>
                    <md-switch v-model="options.includePotentialOutliers" class="md-primary"
                               @change="options.includePotentialOutliers = !options.includePotentialOutliers">Potential Outliers
                    </md-switch>
                    <md-switch v-model="options.includeCondos" class="md-primary" @change="options.includeCondos = !options.includeCondos">
                        Condos
                    </md-switch>
                    <md-switch v-if="(reportType == 'pricePerBed' || reportType == 'revenuePerBed') && shared.reportOptions.source == 'combined'"
                               v-model="shared.reportOptions.compare"
                               class="md-primary"
                               @change="shared.reportOptions.compare = !shared.reportOptions.compare">
                        Compare Sources
                    </md-switch>

                    <div v-if="reportType == 'locationRevenueHeatmap'">
                        <md-radio v-model="options.heatmapWeight" id='rw1' name='radio-weight' md-value="profit_score" class="md-primary">
                            Use Profit Score
                        </md-radio>
                        <md-radio v-model="options.heatmapWeight" id='rw1' name='radio-weight' md-value="lease_score" class="md-primary">
                            Use Lease Score
                        </md-radio>
                        <md-radio v-model="options.heatmapWeight" id='rw2' name='radio-weight' md-value="projected_revenue" class="md-primary">
                            Use Monthly Revenue
                        </md-radio>
                    </div>
                </div>
                <div class="clearfix"></div>

                <md-button v-if="options.startDate || options.endDate"
                           class="font-size-12"
                           @click.native="options.startDate = null; options.endDate = null;">
                    <md-icon>close</md-icon>
                    Clear Dates
                </md-button>
                <br/>
            </div>

            <div v-if="reportType == 'homeValues' || reportType == 'homeValuesProfitScore' " class="min-width-300">
                <md-input-container class="margin-bottom-5">
                    <label>Select Property Type</label>
                    <md-select name="report_source" id="report_source" v-model="shared.reportOptions.property_source">
                        <md-option value="for_sale">Homes for Sale</md-option>
                        <md-option value="leases">Leases</md-option>
                    </md-select>
                </md-input-container>
            </div>

            <a v-if="!showOptions && (currentReport != 'nightlyRates' && reportType != 'monthlyRevenueOccupancy' && reportType != 'homeValues')"
               href=""
               class="text-muted"
               @click.prevent="showOptions = true;">
                Advanced Options
            </a>

            <a v-if="showOptions" href="" class="text-muted"
               @click.prevent="showOptions = false;">
                Hide Advanced Options
            </a>

            <a v-if="currentReport" href="" class="text-muted pull-right" @click.prevent="openSaveReport()">
                Save This Report
            </a>

        </div>
        <div :class="buttonDivClasses">
            <md-button :class="buttonClasses" @click.native="runReport">Run Report ({{ reportItems.length}} Selected)</md-button>
        </div>

        <md-dialog-prompt
                md-title="Save Current Report"
                md-ok-text="Save Report"
                md-cancel-text="Cancel"
                md-input-placeholder="Enter a name for this report"
                md-input-maxlength="255"
                v-model="savedReportName"
                @close="saveReport"
                ref="saveReportDialog">
        </md-dialog-prompt>

    </div>
</template>

<script>
    export default {
        mounted() {
            console.log(' Report select component ready.')
            this.reportType = this.currentReport;
            if (this.shared.reportOptions.startDate && this.shared.reportOptions.endDate
                && this.shared.reportOptions.startDate != null
                && this.shared.reportOptions.endDate != null) {
                this.options.startDate = new Date(this.shared.reportOptions.startDate + " 00:00:000");
                this.options.endDate = new Date(this.shared.reportOptions.endDate + " 00:00:000");
            }

            if (!_.isEmpty(this.shared.reportOptions)) {
                this.options.includeOutliers = this.shared.reportOptions.includeOutliers;
                this.options.includeCondos = this.shared.reportOptions.includeCondos;
                this.options.includePotentialOutliers = this.shared.reportOptions.includePotentialOutliers;
                this.options.heatmapWeight = this.shared.reportOptions.heatmapWeight;
                this.itemType = this.shared.reportOptions.itemType;
            } else {
                this.shared.reportOptions.source = 'combined';
                this.shared.reportOptions.compare = false;
            }

        },
        props: {
            reportItems: [Array, Object],
            currentReport: String,
            itemType: String,
            stacked: {
                type: Boolean,
                default: false
            }
        },
        data: () => ({
            _: window._,
            shared: window.appShared,
            reportType: null,
            savedReportName: '',
            showOptions: false,
            options: {
                startDate : null,
                endDate: null,
                includeOutliers: true,
                includeCondos: true,
                includePotentialOutliers: true,
                heatmapWeight: 'profit_score',
                compare: false,
            }
        }),
        computed: {
          selectDivClasses: function() {
              if (this.stacked) {
                  return "margin-bottom-5";
              }

              return "col-lg-9 col-xs-12 margin-top-10";
          },
          buttonDivClasses: function() {
              if (this.stacked) {
                  return 'text-right';
              }

              return 'col-lg-3 col-xs-12 margin-top-10 no-margin-top-sm no-margin-top-xs';
          },
           buttonClasses: function () {
                if (this.stacked) {
                    return 'md-primary md-raised';
                }

                return 'md-primary md-raised margin-top-20 no-margin-top-xs no-margin-top-sm';
            }
        },
        methods: {
            datePickerFilter(date) {
                var endDay = _moment(date).clone().endOf('month').date();
                var startDay = _moment(date).clone().startOf('month').date();

                if (date.getDate() == endDay || date.getDate() == startDay) {
                    return true;
                }
            },
            removeReportItem(index) {
              this.reportItems.splice(index, 1);
            },
            openSaveReport() {
                if (!_.isEmpty(this.shared.activeReport)) {
                    this.savedReportName = this.shared.activeReport.name;
                }
                this.$refs['saveReportDialog'].open();
            },
            saveReport(confirmation) {
                if (confirmation != 'ok') {
                    return;
                }
                if (_.isEmpty(this.savedReportName)) {
                    this.$root.showNotification("You must enter a name to save this report.");
                    return;
                }

                var itemIds = _.map(this.reportItems, function(item) {
                    return item.id;
                });

                /* Normalize saved markers before saving */
                var customMarkers = [];
                _.each(this.shared.customMarkers, function(marker) {
                    if (_.isNull(marker.map)) {
                        return;
                    }
                   customMarkers.push({
                       position: {lat: marker.position.lat(), lng: marker.position.lng()},
                       label: marker.info,
                       draggable: marker.draggable
                   });
                });

                this.shared.reportOptions.customMarkers = customMarkers;
                console.log(customMarkers);

                var savedReport = {
                    'type' : this.currentReport,
                    'item_type' : this.shared.reportOptions.itemType ? this.shared.reportOptions.itemType : this.itemType,
                    'items' : itemIds,
                    'options' : this.shared.reportOptions,
                    'name' : this.savedReportName
                }

                this.$http.post('/apiv1/reports/save/', savedReport).then((response) => {

                    this.$root.showNotification(response.body.message);

                }, (response) => {
                    this.$root.showNotification(response.body.message);
                    console.log(response);
                });

            },
            runReport() {
                if (!this.reportType) {
                    this.$root.showNotification("Please select a report type first.");
                    return;
                }

                if (this.reportType == 'revenuePerBed' && this.reportItems.length > 2) {
                    this.$root.showNotification("The revenue per bed report only supports up to 2 locations.");
                    return;
                }

                if (this.reportItems.length == 0) {
                    this.$root.showNotification("Please select one or more items for the report first.");
                    return;
                }

                /*
                if (this.reportItems.length > this.maxItems) {
                    this.$root.showNotification("Please select a maximum of " + this.maxItems + " items.");
                    return;
                }*/

                /* Make sure end date is after start and not the same as start date */
                if (!_.isNull(this.options.startDate)
                    && !_.isNull(this.options.endDate)
                    && _moment(this.options.endDate).startOf('day').isSameOrBefore(_moment(this.options.startDate).startOf('day'))
                    )
                {
                    this.$root.showNotification("Please select a valid date range.");
                    return;
                }


                /* Construct report URL */
                var reportUrl = "/admin/reports/" + this.reportType + "?"
                var vm = this;
                _.each(this.reportItems, function (item, key) {
                    reportUrl += "i[]=" + item.id + "&";
                });
                _.each(this.options, function (value, key) {
                    if (_.isDate(value)) {
                        value = _moment(value).startOf('day').unix();
                    }

                    if ((key == 'startDate' || key == 'endDate') && _.isNaN(value) ) {
                        value = 'null';
                    }

                    reportUrl += key + "=" + value + "&";
                });

                reportUrl += "itemType=" + this.itemType;

                if (this.shared.reportOptions && this.shared.reportOptions.source) {
                    reportUrl += "&source=" + this.shared.reportOptions.source;
                }

				if (this.shared.reportOptions && this.shared.reportOptions.property_source) {
					reportUrl += "&property_source=" + this.shared.reportOptions.property_source;
				}

                if (this.shared.reportOptions && this.shared.reportOptions.compare) {
                    reportUrl += "&compare=" + this.shared.reportOptions.compare;
                }

                window.location = reportUrl;

            },
            openRootDialog(ref) {
                this.$root.openDialog(ref);
            },
            openDialog(ref) {
                this.$refs[ref].open();
            }

        }
    }
</script>