<template>
    <div class="margin-bottom-15">
        <label class="text-muted">Sort By Multiple Fields</label>
        <br/>
        <div class="col-lg-3">
        <md-input-container class="margin-bottom-5">
            <label>Sort By Field</label>
            <md-select  v-model="sortField">
                <md-option v-for="col in columns" :value="col.field" :key="col.field">{{ col.name }}</md-option>
            </md-select>
        </md-input-container>
        </div>

        <div class="col-lg-3">
            <md-input-container class="margin-bottom-5">
                <label>Sort Direction</label>
                <md-select v-model="sortDirection">
                    <md-option value="desc">Ascending</md-option>
                    <md-option value="asc">Descending</md-option>
                </md-select>
            </md-input-container>
        </div>
        <div class="col-lg-3">
            <md-button class="md-primary md-raised md-dense margin-top-20" @click.native="addSortOption">Add Sort</md-button>
            <md-button v-if="sortOptions.length > 0" class="md-warn md-raised md-dense margin-top-20" @click.native="clearSortOptions">Clear All</md-button>
        </div>

        <div class="clearfix"></div>
        <div class="col-lg-4 margin-top-5 margin-top-sm-15 margin-top-xs-15" v-for="(sort, rowIndex) in sortOptions" :key="rowIndex">
            <div class="padding-5 border-1 border-grey-100 bg-grey-100 border-radius-5">
                Sort by {{ sort.name }}, {{ sort.direction|directionFormat }}
                <span class="pull-right">
                    <md-icon @click.prevent.native="removeSortOption(rowIndex)" class="pull-right cursor-pointer">clear</md-icon>
                </span>
            </div>
        </div>
        <div class="clearfix"></div>
    </div>
</template>

<script>
	export default {
		mounted() {

		},
		props: {
			columns: [Array],
            type: [String]
		},
		data: () => ({
			_: window._,
			shared: window.appShared,
            sortField: 'created_at',
            sortDirection: 'asc',
            sortOptions: []

		}),
        filters: {
			directionFormat: function (value) {
				if (value == 'desc') {
					return 'Ascending';
                } else {
					return 'Descending';
                }
			}
        },
		computed: {

        },
		methods: {
            addSortOption() {
				var vm = this;
				var hasOption = false;

            	/* Only add sort if not already in sort list */
				this.sortOptions.forEach(function (col) {
					if (col.field == vm.sortField) {
						hasOption = true;
					}
				});

				if (hasOption) {
					return;
                }

            	/* Look up the label (name) for this sort field */
            	var sortName = '';
            	this.columns.forEach(function(col) {
            		if (col.field == vm.sortField) {
            			sortName = col.name;
                    }
                });

            	this.sortOptions.push({
            		field: this.sortField,
                    direction: this.sortDirection,
                    name: sortName
                });

            	this.$emit('multiSort', this.sortOptions);
            },
            clearSortOptions() {
				this.sortOptions = [];
				this.$emit('multiSort', this.sortOptions);
            },
			removeSortOption(index) {
            	var sortLength = this.sortOptions.length;
				this.sortOptions.splice(index, 1);
				if (this.sortOptions.length < sortLength) {
					this.$emit('multiSort', this.sortOptions);
                }
			},
        }
	}
</script>