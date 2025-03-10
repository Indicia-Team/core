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

 /**
 * Extension to the ORM library which includes handling for attribute value validation.
 * Subclasses should call attribute_validation in their validate() method. They can also implement a
 * protected method called get_survey_specific_rules which returns a Kohana result object for a query
 * to get the validation_rules field if there are any specific to the survey.
 */
abstract class Attribute_Value_ORM extends ORM {

  /**
   * Should metadata fields be updated?
   *
   * Default behaviour on save is to update metadata. If we detect no changes
   * we can skip this.
   *
   * @var bool
   */
  public $wantToUpdateMetadata = TRUE;

  /**
   * Set up attribute value record validation.
   *
   * Function that applies the validation rules for any attribute value
   * (sample, location or occurrence).
   *
   * @param Validation $array
   *   The validation object to check.
   * @param string $type
   *   Specify the attribute type, either sample, occurrence or location.
   */
  protected function attribute_validation(Validation $array, $type) {
    // Uses PHP trim() to remove whitespace from beginning and end of all
    // fields before validation.
    $array->pre_filter('trim');
    $array->add_rules($type . '_attribute_id', 'required');
    $array->add_rules($type . '_id', 'required');
    $values = $array->as_array();
    // We apply the validation rules specified in the sample attribute
    // table to the value given.
    if (array_key_exists($type . '_attribute_id', $array->as_array())) {
      $attr = self::loadAttrDef($type, $values[$type . '_attribute_id']);
      switch ($attr->data_type) {
        case 'T':
          $vf = 'text_value';
          break;

        case 'I':
          $vf = 'int_value';
          $array->add_rules('int_value', 'integer');
          if ($attr->allow_ranges === 't') {
            $array->add_rules('upper_value', 'integer');
          }
          break;

        case 'F':
          $vf = 'float_value';
          $array->add_rules('float_value', 'numeric');
          if ($attr->allow_ranges === 't') {
            $array->add_rules('upper_value', 'numeric');
          }
          break;

        case 'D':
          $vf = 'date_start_value';
          break;

        case 'V':
          // Vague date - presumably already validated?
          $vf = 'date_start_value';
          break;

        case 'B':
          // Boolean
          $array->add_rules('int_value', 'integer');
          $array->add_rules('int_value', 'minimum[0]');
          $array->add_rules('int_value', 'maximum[1]');
          $vf = 'int_value';
          break;

        case 'G':
          $vf = 'geom_value';
          break;

        default:
          $vf = 'int_value';
      }
      // Now get the global custom attribute validation rules for the attribute
      if ($attr->validation_rules != '') {
        $rules = explode("\n", $attr->validation_rules);
        foreach ($rules as $a) {
          $a = trim($a);
          if ($vf === 'float_value' && substr($a, 0, 7) === 'decimal') {
            // When performing the decimal rule (correct number of digits before
            // and after decimal point) on a float, use its string value as
            // trailing zeroes will be missing on the float value.
            if ($attr->allow_ranges === 't') {
              // Substitute a decimal rule that allows ranges.
              $a = str_replace('decimal', 'decimal_range', $a);
            }
            $array->add_rules('text_value', $a);
          }
          else {
            $array->add_rules($vf, $a);
            if ($attr->allow_ranges === 't' && $a !== 'required') {
              $array->add_rules('upper_value', $a);
            }
          }
        }
      }
      else {
        $this->unvalidatedFields[] = $vf;
      }
      // Now get the survey specific custom attribute validation rules for the attribute
      // @todo: Are there opportunities to cache this information as this is called for each
      // attribute value saved and causes a query to be issued to the db.
      if (method_exists($this, 'get_survey_specific_rules')) {
        $aw = $this->get_survey_specific_rules($values);
        if (count($aw) > 0) {
          $aw = $aw[0];
          if ($aw->validation_rules != '') {
            $rules = explode("\n", $aw->validation_rules);
            foreach ($rules as $a) {
              $a = trim($a);
              // Same comments as for global rules above.
              if ($vf === 'float_value' && substr($a, 0, 7) === 'decimal') {
                if ($aw->allow_ranges === 't') {
                  $a = str_replace('decimal', 'decimal_range', $a);
                }
                $array->add_rules('text_value', $a);
              }
              else {
                $array->add_rules($vf, $a);
                if ($aw->allow_ranges === 't' && $a !== 'required') {
                  $array->add_rules('upper_value', $a);
                }
              }
            }
          }
        }
      }

    }
  }

  public function save() {
    if ($this->delete_if_empty()) {
      return $this;
    }
    else {
      return parent::save();
    }
  }

  /**
   * Detect if the attribute value is empty. If so, either delete and save it, or if it does not exist just
   * skip saving it.
   */
  protected function delete_if_empty() {
    $arr = $this->as_array();
    foreach ($arr as $field => $content) {
      if (substr($field, -6)=='_value') {
        if ($content!=="" && $content!==null)
          // not empty, so can exit
          return false;
        else
          // empty values should be null, especially if we empty something other than a string.
          $this->$field=null;
      }
    }
    // delete if it exists
    if ($this->id!==0) {
      $this->deleted='t';
      parent::save();
    }

    return true;
  }

  /**
  * Override set handler to translate WKT to PostGIS internal spatial data.
  */
  public function __set($key, $value) {
    if ($key === 'geom_value') {
      if ($value) {
        $row = $this->db->query("SELECT ST_GeomFromText(?, ?) AS geom", [
          $value,
          kohana::config('sref_notations.internal_srid'),
        ])->current();
        $value = $row->geom;
      }
    }
    parent::__set($key, $value);
  }

  /**
  * Override get handler to translate PostGIS internal spatial data to WKT.
  */
  public function __get($column) {
    $value = parent::__get($column);

    if ($column === 'geom_value' && !empty($value)) {
      $row = $this->db->query("SELECT ST_asText(?) AS wkt", [$value])->current();
      $value = $row->wkt;
    }
    return $value;
  }

  /**
   * Post submit of a single attribute value.
   *
   * Occurs only when an attribute value submitted in isolation. Updates the
   * parent record updated metadata to reflect the change in the attribute
   * value, making it easier to detect when anything about a record has been
   * changed.
   *
   * @param bool $isInsert
   *   True if the operation is an insert, false for an update.
   */
  protected function postSubmit($isInsert) {
    if ($this->wantToUpdateMetadata) {
      // Value has changed, so trigger metadata update on parent.
      $parentEntity = str_replace('_attribute_value','',$this->object_name);
      $parentTable = pg_escape_identifier($this->db->getLink(), inflector::plural($parentEntity));
      $parentTableFkField = $parentEntity . '_id';
      $parentTableId = $this->$parentTableFkField;
      $this->db->query("UPDATE $parentTable SET updated_on=now(), updated_by_id=? WHERE id=?", [
        $this->updated_by_id,
        $parentTableId,
      ]);
    }
    return parent::postSubmit($isInsert);
  }

}
