import Vue from 'vue'
import Vuex from 'vuex'

Vue.use(Vuex);

let osmAttr = '&copy; <a target="_blank" href="http://osm.org/copyright">OpenStreetMap</a> contributors';

export default new Vuex.Store({
   state: {
      user: '',
      alreadyLoggedIn: false,
      //api : 'https://ncbs.res.in/hippo/api',
      api : 'http://ghevar.ncbs.res.in/hippo/api',
      key : '',
      tobook: null,
      OSM: {
         tileProviders: [ 
            {
               name: 'OpenStreetMap',
               visible: true,
               attribution: osmAttr,
               url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'
            },
            {
               name: 'OpenCycle',
               visible: false,
               url: 'http://tile.thunderforest.com/cycle/{z}/{x}/{y}.png?apikey=635faabc9da4464585a4b72ddb4c0917',
               attribution: '&copy; OpenCycleMap, ' + 'Map data ' + osmAttr
            },
            {
               name: 'OpenTopoMap',
               visible: false,
               url: 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
               attribution: 'Map data: &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)'
            },
         ],
         toolTipOpts: {permanent:false, interactive:true, direction:'top'},
         center: L.latLng(13.071081, 77.58025),
         url:'http://{s}.tile.osm.org/{z}/{x}/{y}.png',
         attribution:'&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors',
         mapStyle: 'width:100%; height:100%',
         venueIcon: L.divIcon( {className: 'fa fa-map-marker fa-2x' }),
      },
   },
   actions: {
      userLogged ({commit}, user) {
         commit('USER_LOGGED', user)
      },
      keyLogger ( {commit}, key ) {
         commit('HIPPO_API_KEY', key)
      },
      addBookingData( {commit}, data){
         commit('ADD_BOOKING_DATA', data)
      },
   },
   mutations: {
      USER_LOGGED (state, user) {
         state.user = user;
         state.alreadyLoggedIn = true;
      },
      HIPPO_API_KEY (state, key) {
         state.key = key;
      },
      ADD_BOOKING_DATA(state, data) {
         state.tobook = data;
      }
   },
});
