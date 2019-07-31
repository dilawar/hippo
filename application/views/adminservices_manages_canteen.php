<?php
include_once FCPATH . 'system/autoload.php';
echo userHTML( );
?>

<!-- Vue2 app -->

<div id="app">
    <h1>Canteen </h1>
  {{ message }}
</div>

<script>
var app = new Vue({
  el: '#app',
  mixins: [mixin],
  data: {
    message: 'Hello Vue!'
  },
  mounted: function() {
    console.log( "Mounted " );
  },
})
</script>
