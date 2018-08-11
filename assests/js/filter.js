<!-- Filter table -->
<script type="text/javascript" charset="utf-8">
var $rowsRequest = $('#id_of_table tr');
$('#filter_requests').keyup(function() {
    var val = $.trim($(this).val()).replace(/ +/g, ' ').toLowerCase();

    $rowsRequest.show().filter(function() {
        var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
        return !~text.indexOf(val);
    }).hide();
});
</script>

