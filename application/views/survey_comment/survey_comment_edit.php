<?php

/**
 * @file
 * View template for the survey dataset comment edit page.
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

$id = html::initial_value($values, 'survey_comment:id');
warehouse::loadHelpers(['data_entry_helper']);
?>
<p>This page allows you to specify the details of a survey comment.</p>
<form action="<?php echo url::site() . 'survey_comment/save'; ?>" method="post" enctype="multipart/form-data">
  <?php echo $metadata; ?>
  <fieldset>
    <legend>Survey Comment</legend>
    <input type="hidden" name="survey_comment:id" value="<?php echo $id ?>" />
    <input type="hidden" name="survey_comment:survey_id" value="<?php echo html::initial_value($values, 'survey_comment:survey_id'); ?>" />
    <?php
    echo data_entry_helper::textarea(array(
      'label' => 'Comment',
      'fieldname' => 'survey_comment:comment',
      'default' => html::initial_value($values, 'survey_comment:comment')
    ));
    ?>
  </fieldset>
  <?php echo html::form_buttons($id != NULL, FALSE, FALSE); ?>
</form>
