<?php

/**
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */
warehouse::loadHelpers(['map_helper', 'data_entry_helper']);
$id = html::initial_value($values, 'sample:id');
$readAuth = data_entry_helper::get_read_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
$site = url::site();
?>
<form action="<?php echo url::site() . 'sample/save' ?>" method="post" id="sample-edit">
  <fieldset>
    <legend>Sample Details<?php echo $metadata; ?></legend>
    <input type="hidden" name="sample:id" value="<?php echo html::initial_value($values, 'sample:id'); ?>" />
    <input type="hidden" name="sample:survey_id" value="<?php echo html::initial_value($values, 'sample:survey_id'); ?>" />
    <input type="hidden" name="website_id" value="<?php echo html::initial_value($values, 'website_id'); ?>" />
    <?php
    echo data_entry_helper::text_input([
      'label' => 'Survey',
      'fieldname' => 'survey-label',
      'default' => $model->survey->title,
      'readonly' => TRUE,
    ]);
    $parent_id = html::initial_value($values, 'sample:parent_id');
    if (!empty($parent_id)) {
      echo "<h2>Child of: <a href=\"{$site}sample/edit/$parent_id\">Sample ID $parent_id</a></h2>";
    }
    echo data_entry_helper::date_picker([
      'label' => 'Date',
      'fieldname' => 'sample:date',
      'default' => html::initial_value($values, 'sample:date'),
      'validation' => ['required'],
    ]);
    echo data_entry_helper::sref_and_system([
      'label' => 'Spatial Ref',
      'fieldname' => 'sample:entered_sref',
      'geomFieldname' => 'sample:geom',
      'default' => html::initial_value($values, 'sample:entered_sref'),
      'defaultGeom' => html::initial_value($values, 'sample:geom'),
      'systems' => spatial_ref::system_list(),
      'defaultSystem' => html::initial_value($values, 'sample:entered_sref_system'),
    ]);
    ?>
    <p class="alert alert-info">Zoom the map in by double-clicking then single click on the sample's centre to set the
    spatial reference. The more you zoom in, the more accurate the reference will be.</p>
    <?php
    echo map_helper::map_panel(array(
        'readAuth' => $readAuth,
        'presetLayers' => array('osm'),
        'editLayer' => TRUE,
        'layers' => array(),
        'initial_lat' => 52,
        'initial_long' => -2,
        'initial_zoom' => 7,
        'width' => '100%',
        'height' => 400,
        'initialFeatureWkt' => html::initial_value($values, 'sample:geom'),
        'standardControls' => array('layerSwitcher', 'panZoom', 'fullscreen')
    ));
    echo data_entry_helper::text_input(array(
      'label' => 'Location Name',
      'fieldname' => 'sample:location_name',
      'default' => html::initial_value($values, 'sample:location_name')
    ));
    $location_id = html::initial_value($values, 'sample:location_id');
    if (!empty($location_id)) {
      echo "<h2>Associated with location record: <a href=\"{$site}location/edit/$location_id\" >" .
        ORM::factory("location", $location_id)->name . '</a></h2>';
    }
    echo data_entry_helper::autocomplete(array(
      'label' => 'Location',
      'fieldname' => 'sample:location_id',
      'table' => 'location',
      'captionField' => 'name',
      'valueField' => 'id',
      'extraParams' => $readAuth,
      'default' => $location_id,
      'defaultCaption' => (empty($location_id) ? NULL : html::specialchars($model->location->name)),
    ));
    echo data_entry_helper::textarea(array(
      'label' => 'Recorder Names',
      'helpText' => 'Enter the names of the recorders, one per line',
      'fieldname' => 'sample:recorder_names',
      'default' => html::initial_value($values, 'sample:recorder_names'),
    ));
    echo data_entry_helper::select(array(
      'label' => 'Sample Method',
      'fieldname' => 'sample:sample_method_id',
      'default' => html::initial_value($values, 'sample:sample_method_id'),
      'lookupValues' => $other_data['method_terms'],
      'blankText' => '<Please select>',
    ));
    echo data_entry_helper::textarea(array(
      'label' => 'Comment',
      'fieldname' => 'sample:comment',
      'default' => html::initial_value($values, 'sample:comment'),
    ));
    echo data_entry_helper::text_input(array(
      'label' => 'External Key',
      'fieldname' => 'sample:external_key',
      'default' => html::initial_value($values, 'sample:external_key'),
    ));
    echo data_entry_helper::select(array(
      'label' => 'Licence',
      'helpText' => 'Licence which applies to all records and media held within this sample.',
      'fieldname' => 'sample:licence_id',
      'default' => html::initial_value($values, 'sample:licence_id'),
      'table' => 'licence',
      'valueField' => 'id',
      'captionField' => 'title',
      'blankText' => '<Please select>',
      'extraParams' => $readAuth
    ));
   ?>
  </fieldset>
  <fieldset>
    <legend>Survey Specific Attributes</legend>
    <?php
    foreach ($values['attributes'] as $attr) {
      $name = "smpAttr:$attr[sample_attribute_id]";
      // If this is an existing attribute, tag it with the attribute value
      // record id so we can re-save it.
      if ($attr['id']) {
        $name .= ":$attr[id]";
      }
      switch ($attr['data_type']) {
        case 'D':
        case 'V':
          echo data_entry_helper::date_picker(array(
            'label' => $attr['caption'],
            'fieldname' => $name,
            'default' => $attr['value'],
          ));
          break;

        case 'L':
          echo data_entry_helper::select(array(
            'label' => $attr['caption'],
            'fieldname' => $name,
            'default' => $attr['raw_value'],
            'lookupValues' => $values["terms_$attr[termlist_id]"],
            'blankText' => '<Please select>',
          ));
          break;

        case 'B':
          echo data_entry_helper::checkbox(array(
            'label' => $attr['caption'],
            'fieldname' => $name,
            'default' => $attr['value'],
          ));
          break;

        default:
          echo data_entry_helper::text_input(array(
            'label' => $attr['caption'],
            'fieldname' => $name,
            'default' => $attr['value'],
          ));
      }
    }
    ?>
  </fieldset>
  <?php
  echo html::form_buttons($id !== NULL, FALSE, FALSE);
  data_entry_helper::enable_validation('sample-edit');
  echo data_entry_helper::dump_javascript();
  ?>
</form>
