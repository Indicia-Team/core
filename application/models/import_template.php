<?php defined('SYSPATH') or die('No direct script access.');

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

/**
 * Model class for the import_templates table.
 */
class Import_template_Model extends ORM {

  protected $belongs_to = [
    'group_id' => 'group',
  ];

  public function validate(Validation $array, $save = FALSE) {
    // Cleanup leading/trailing whitespace.
    $array->pre_filter('trim');

    // Field validation.
    $array->add_rules('title', 'required');
    $array->add_rules('entity', 'required');
    $array->add_rules('mappings', 'required');
    $array->add_rules('global_values', 'required');
    $array->add_rules('group_id', 'integer');

    $this->unvalidatedFields = [];
    return parent::validate($array, $save);
  }

}
