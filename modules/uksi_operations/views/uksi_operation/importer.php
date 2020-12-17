<?php 

warehouse::loadHelpers(['import_helper']);
$auth = import_helper::get_read_write_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));

$fieldMappings = <<<TXT
uksi_operation:parent_organism_key=parent_orgkey
uksi_operation:output_group=taxon_type
uksi_operation:organism_key=org_key
uksi_operation:taxon_version_key=new_tv_key
uksi_operation:batch_processed_on=processed_date
TXT;

echo import_helper::importer(array(
  'model' => 'uksi_operation',
  'auth' => $auth,
  'fieldMap' => [
    [
      'fields' => $fieldMappings,
    ],
  ],
));

echo import_helper::dump_javascript();