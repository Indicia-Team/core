<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<?php
include '../../../client_helpers/data_entry_helper.php';
include '../data_entry_config.php';
?>
<title>Occurrence Data Entry</title>
<link rel="stylesheet" href="demo.css" type="text/css" media="screen">
<link rel="stylesheet" href="../../../media/css/default_site.css" type="text/css" media="screen">
</head>
<body>
<div id="wrap">
<h1>Occurrence Data Entry</h1>
<?php
// Catch a submission to the form and send it to Indicia
if ($_POST)
{
  $submission = data_entry_helper::build_sample_occurrence_submission($_POST);
  $response = data_entry_helper::forward_post_to(
    'save', $submission
  );
  data_entry_helper::dump_errors($response);
}

?>
<form method="post" >
<fieldset>
<?php
// Get authentication information
echo data_entry_helper::get_auth($config['website_id'], $config['password']);
$readAuth = data_entry_helper::get_read_auth($config['website_id'], $config['password']);
?>
<input type='hidden' id='website_id' name='website_id' value='<?php echo $config['website_id']; ?>' />
<input type='hidden' id='survey_id' name='survey_id' value='<?php echo $config['survey_id']; ?>' />
<input type='hidden' id='record_status' name='occurrence:record_status' value='C' />
<label for='occurrence:taxa_taxon_list_id:taxon'>Taxon:</label>
<?php echo data_entry_helper::autocomplete('occurrence:taxa_taxon_list_id', 'taxa_taxon_list', 'taxon', 'id', $readAuth); ?>
<br/>
<label for="date">Date:</label>
<?php echo data_entry_helper::date_picker('sample:date'); ?>
<br />
<?php echo data_entry_helper::map('map', array('virtual_earth'), true, false, null, true); ?>
<br />
<input type="submit" value="Save" />
</fieldset>

</form>
</div>
</body>
<?php echo data_entry_helper::dump_javascript(); ?>
</html>
