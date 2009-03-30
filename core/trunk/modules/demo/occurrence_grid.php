<?php
include '../../client_helpers/data_entry_helper.php';
$readAuth = data_entry_helper::get_read_auth(1, 'password');
?>
<html>
<head>
<link rel='stylesheet' type='text/css' href='../../media/css/datagrid.css' />
<script type='text/javascript' src='../../media/js/jquery.js' ></script>
<script type='text/javascript' src='../../media/js/hasharray.js' ></script>
<script type='text/javascript' src='../../client_helpers/datagrid.js' ></script>
<script type='text/javascript'>
(function($) {
$(document).ready(function(){
$('div#grid').indiciaDataGrid('occurrence', {actionColumns: {view : "occurrence.php?id=�id�", edit : "data_entry/test_data_entry.php?id=�id�"}, auth : { nonce : "<?php echo $readAuth['nonce']; ?>", auth_token : "<?php echo $readAuth['auth_token']; ?>"}});
});
})(jQuery);
</script>
<title>Occurrence Grid Demo</title>
</head>
<body>
<div id='grid'>
</div>
</body>
</html>