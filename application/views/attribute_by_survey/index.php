<?php

/**
 * @file
 * View template for the attributes by surveys index page.
 *
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

warehouse::loadHelpers(['data_entry_helper']);
?>
<form action="" method="get" class="form-inline">
<?php

echo data_entry_helper::select(array(
  'fieldname' => 'type',
  'label' => 'Display attributes for',
  'lookupValues' => array(
    'sample' => 'Samples',
    'occurrence' => 'Occurrences',
    'location' => 'Locations',
  ),
  'default' => $_GET['type'],
));
?>
<input type="submit" class="btn btn-default" id="change-type" value="Go" />
</form>
<div id="attribute-by-survey-index">
<ul id="top-blocks" class="block-list">
<?php
foreach ($top_blocks as $block) {
  echo <<<PNLTOP
<li class="block-drop"></li>
<li id="block-$block->id" class="panel panel-primary draggable-block">
  <div class="panel-heading clearfix">
    <span class="handle">&nbsp;</span>
    <span class="caption">$block->name</span>
    <a href="" class="block-delete btn btn-warning btn-xs pull-right">Delete</a>
    <a href="" class="block-rename btn btn-default btn-xs pull-right">Rename</a>
  </div>
  <ul id="child-blocks-$block->id" class="block-list">

PNLTOP;
  $child_blocks = ORM::factory('form_structure_block')
    ->where('parent_id', $block->id)
    ->where($filter)
    ->orderby('weight', 'ASC')
    ->find_all();
  foreach ($child_blocks as $child_block) {
    echo <<<PNLCHILD
    <li class="block-drop"></li>
    <li id="block-$child_block->id" class="panel panel-info draggable-block">
      <div class="panel-heading clearfix">
      <span class="handle">&nbsp;</span>
      <span class="caption">$child_block->name</span>
      <a href="" class="block-delete pull-right btn btn-warning btn-xs">Delete</a>
      <a href="" class="block-rename pull-right btn btn-default btn-xs">Rename</a>
    </div>
PNLCHILD;
    get_controls($child_block->id, $controlfilter, $this->db);
    echo "</li>\n";
  }
  echo '<li class="block-drop"></li>';
  echo "</ul>";
  get_controls($block->id, $controlfilter, $this->db);
  echo "</li>";
}
?><li class="block-drop"></li></ul>
<?php
get_controls(NULL, $controlfilter, $this->db);

/**
 * Echos the list of controls inside a block level.
 *
 * @param string $block_id
 *   ID of the block, or NULL for top level.
 * @param array
 *   Filter to apply, e.g. to the correct survey.
 * @param db
 *   Database object.
 */
function get_controls($block_id, array $controlfilter, $db) {
  global $indicia_templates;
  $masterTaxonListId = kohana::config('cache_builder_variables.master_list_id', FALSE, FALSE);
  $id = "controls";
  if ($block_id) {
    $id .= '-for-block-' . $block_id;
  }
  echo "<ul id=\"$id\" class=\"control-list\">\n";
  $attr = $_GET['type'] . '_attribute';
  $attrIdField = $attr . '_id';
  $query = $db
    ->select("aw.id, aw.$attrIdField, a.caption, aw.validation_rules as aw_validation_rules, a.validation_rules")
    ->from("$_GET[type]_attributes_websites as aw")
    ->join("$_GET[type]_attributes as a", 'a.id', "aw.$_GET[type]_attribute_id")
    ->where($controlfilter)
    ->where([
      'form_structure_block_id' => $block_id,
      'a.deleted' => 'f',
      'aw.deleted' => 'f',
    ])
    ->orderby('weight', 'ASC');
  if ($_GET['type'] === 'sample' || $_GET['type'] === 'occurrence') {
    if ($masterTaxonListId) {
      $db
        ->select('t.taxon as restrict_to_taxon, t.authority as restrict_to_taxon_authority')
        ->join('cache_taxa_taxon_lists as t', [
          't.taxon_meaning_id' => 'aw.restrict_to_taxon_meaning_id',
          't.preferred' => TRUE,
          't.taxon_list_id' => $masterTaxonListId,
        ], NULL, 'LEFT');
    }
    $db
      ->select('stage.term as restrict_to_stage')
      ->join('cache_termlists_terms as stage', [
        'stage.meaning_id' => 'aw.restrict_to_stage_term_meaning_id',
        'stage.preferred' => TRUE,
      ], NULL, 'LEFT');
    if ($_GET['type'] === 'sample') {
      $db
        ->select('method.term as restrict_to_sample_method')
        ->join('cache_termlists_terms as method', [
          'method.id' => 'aw.restrict_to_sample_method_id',
        ], NULL, 'LEFT');
    }
  }
  if ($_GET['type'] === 'location') {
    $db
      ->select('type.term as restrict_to_location_type')
      ->join('cache_termlists_terms as type', [
        'type.id' => 'aw.restrict_to_location_type_id',
      ], NULL, 'LEFT');
  }
  $childControls = $query->get();
  foreach ($childControls as $control) {
    echo '<li class="control-drop"></li>';
    // Prepare some dynamic property names.
    $attrId = $control->$attrIdField;
    $caption = $control->caption;
    $siteUrl = url::site();
    $restrictionList = [];
    if (!empty($control->restrict_to_taxon)) {
      $restrictionList[] = $control->restrict_to_taxon .
        (empty($control->restrict_to_taxon_authority) ? '' : " $control->restrict_to_taxon_authority");
    }
    if (!empty($control->restrict_to_stage)) {
      $restrictionList[] = $control->restrict_to_stage;
    }
    if (!empty($control->restrict_to_sample_method)) {
      $restrictionList[] = $control->restrict_to_sample_method;
    }
    if (!empty($control->restrict_to_location_type)) {
      $restrictionList[] = $control->restrict_to_location_type;
    }
    $restrictions = empty($restrictionList) ? '' : ', restricted to ' . implode(', ', $restrictionList);
    $required = strpos($control->aw_validation_rules, 'required') === FALSE
      && strpos($control->validation_rules, 'required') === FALSE ? '' : " $indicia_templates[requiredsuffix]";
    echo <<<HTML
<li id="control-$control->id" class="$attrId draggable-control panel panel-primary clearfix">
  <span class="handle">&nbsp;</span>
  <span class="caption"> $caption (ID {$attrId}{$restrictions})</span>
  <a class="control-delete pull-right btn btn-warning btn-xs">Delete</a>
  <a href="{$siteUrl}attribute_by_survey/edit/$control->id?type=$_GET[type]" class="pull-right btn btn-default btn-xs">Survey settings</a>
  <a href="$siteUrl$_GET[type]_attribute/edit/{$control->$attrIdField}" class="pull-right btn btn-default btn-xs">Global settings</a>
  $required
</li>
HTML;
  }
  // Extra item to allow drop at end of list.
  echo '<li class="control-drop"></li>';
  echo '</ul>Attributes marked ' . $indicia_templates['requiredsuffix'] . ' are required';
}

  ?>
</div>

<form style="display: none" id="layout-change-form" class="inline-form panel alert alert-info" action="<?php
    echo url::site() . 'attribute_by_survey/layout_update/' . $this->uri->last_segment() . '?type=' . $_GET['type'];
?>" method="post">
<input type="hidden" name="layout_updates" id="layout_updates"/>
<span>The layout changes you have made will not be saved until you click the Save button.</span>
<input type="submit" value="Save" id="layout-submit" class="btn btn-primary"/>
</form>
<form id="actions-new-block" class="form-inline">
  <div class="form-group">
    <label for="new-block">Block name:</label>
    <input type="text" name="new-block" id="new-block" class="form-control" />
  </div>
  <input type="submit" value="Create new block" id="submit-new-block" class="btn btn-default line-up" />
</form>
<form id="actions-add-existing" class="form-inline">
  <div class="form-group">
    <label for="existing-attribute">Existing attribute:</label>
    <select id="existing-attribute" name="existing-attribute" class="form-control">
<?php
foreach ($existingAttrs as $attr) {
  echo "      <option value=\"{$attr->id}\">{$attr->caption} (ID {$attr->id})</option>\n";
}
?>
    </select>
    <input type="submit" value="Add existing attribute" id="submit-existing-attribute" class="btn btn-default" />
  </div>
</form>
<?php
// The JavaScript needs a list of attribute captions.
$attrsData = [];
foreach ($existingAttrs as $attr) {
  $attrs["id$attr->id"] = $attr->caption;
}
data_entry_helper::$javascript .= "indiciaData.existingAttrs = " . json_encode($attrs) . ";\n";
echo data_entry_helper::dump_javascript();
