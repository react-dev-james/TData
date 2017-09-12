const { mix } = require('laravel-mix');

mix.js('resources/assets/js/app.js', 'public/js')
    .sass('resources/assets/sass/app.scss', 'public/css')
    .copy('resources/assets/js/plugins', 'public/js/plugins', false)
    //.combine(['./node_modules/vue-material/dist/vue-material.css'], 'public/css/vendor.css');
    .combine(['./node_modules/vue-material/dist/vue-material.css', './node_modules/keen-ui/dist/keen-ui.css'], 'public/css/vendor.css');
