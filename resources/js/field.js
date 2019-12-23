Nova.booting((Vue, router, store) => {
  Vue.component('index-price-maker', require('./components/IndexField'));
  Vue.component('detail-price-maker', require('./components/DetailField'));
  Vue.component('form-price-maker', require('./components/FormField'));
});
