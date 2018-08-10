<?php

/**
 * @file
 * Contains the model class for the groups table.
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
 * @link http://code.google.com/p/indicia/
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Model class for the groups table.
 *
 * @link http://code.google.com/p/indicia/wiki/DataModel
 */
class Group_Model extends ORM {

  protected $has_one = array('filter');

  protected $has_and_belongs_to_many = array('users', 'locations');

  protected $has_many = array('group_invitations', 'group_pages');

  /**
   * Updating release status flag.
   *
   * Flag indicating if the group's private records status is changing,
   * indicating we need to update the release status of records.
   *
   * @var bool
   */
  protected $wantToUpdateReleaseStatus = FALSE;

  /**
   * ORM validate override.
   */
  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('title', 'required');
    $array->add_rules('group_type_id', 'required');
    $array->add_rules('website_id', 'required');
    $this->unvalidatedFields = [
      'code',
      'description',
      'from_date',
      'to_date',
      'private_records',
      'filter_id',
      'joining_method',
      'deleted',
      'implicit_record_inclusion',
      'view_full_precision',
      'logo_path',
      'licence_id',
    ];
    // Has the private records flag changed?
    $this->wantToUpdateReleaseStatus = isset($this->submission['fields']['private_records']) &&
        $this->submission['fields']['private_records'] !== $this->private_records;
    return parent::validate($array, $save);
  }

  /**
   * Post submission handler.
   *
   * If changing the private records setting, then must update the group's
   * records release_status.
   */
  public function postSubmit($isInsert) {
    if (!$isInsert && $this->wantToUpdateReleaseStatus) {
      $status = $this->private_records === '1' ? 'U' : 'R';
      $sql = "update #table# o
set release_status='$status'
from samples s
where s.deleted=false and s.id=o.sample_id and s.group_id=$this->id";
      $this->db->query(str_replace('#table#', 'occurrences', $sql));
      $this->db->query(str_replace('#table#', 'cache_occurrences_functional', $sql));
    }
    $this->processIndexGroupsLocations();
    $this->processIndexGroupsTaxonGroups();
    return TRUE;
  }

  /**
   * Popuate index_groups_locations.
   *
   * Method to populate the indexed locations that this group intersects with.
   * Makes it easy to do things like suggest groups based on geographic region.
   */
  private function processIndexGroupsLocations() {
    $filter = json_decode($this->filter->definition, TRUE);
    $exist = $this->db->select('id', 'location_id')
      ->from('index_groups_locations')
      ->where('group_id', $this->id)
      ->get();

    $location_ids = array();
    // Backwards compatibility checks.
    if (!empty($filter['indexed_location_id']) && empty($filter['indexed_location_list'])) {
      $filter['indexed_location_list'] = $filter['location_id'];
    }
    if (!empty($filter['location_id']) && empty($filter['location_list'])) {
      $filter['location_list'] = $filter['location_id'];
    }

    if (!empty($filter['indexed_location_list'])) {
      // Got an indexed location as the filter boundary definition, so we can
      // use that as it is.
      $location_ids = explode(',', $filter['indexed_location_list']);
    }
    elseif (!empty($filter['location_list']) || !empty($filter['searchArea'])) {
      // Got either an unindexed location, or a freehand boundary, so need to
      // intersect to find the indexed locations.
      // Without a configuration for the indexed location type layers we can't
      // go any further.
      $config = kohana::config_load('spatial_index_builder', FALSE);
      if (array_key_exists('location_types', $config)) {
        $types = "'" . implode("','", $config['location_types']) . "'";
        if (!empty($filter['location_list'])) {
          $sql = <<<SQL
SELECT l.id FROM locations l
JOIN locations search ON (
    st_intersects(search.boundary_geom, l.boundary_geom)
    AND NOT st_touches(search.boundary_geom, l.boundary_geom)
  OR (
    search.boundary_geom IS NULL
    AND st_intersects(search.centroid_geom, l.boundary_geom)
    AND NOT st_touches(search.centroid_geom, l.boundary_geom)
  )
  OR (
    l.boundary_geom IS NULL
    AND st_intersects(search.boundary_geom, l.centroid_geom)
    AND NOT st_touches(search.boundary_geom, l.centroid_geom)
  )
  OR (
    l.boundary_geom IS NULL AND search.boundary_geom IS NULL
    AND st_intersects(search.centroid_geom, l.centroid_geom)
    AND NOT st_touches(search.centroid_geom, l.centroid_geom)
  )
)
JOIN cache_termlists_terms t ON t.id=l.location_type_id AND t.preferred_term IN ($types)
WHERE search.id IN ($filter[location_list])
SQL;
          $rows = $this->db->query($sql)->result();
        }
        else {
          $srid = kohana::config('sref_notations.internal_srid');
          // Note that splitting WHERE clause to combine hits on boundary or
          // centroid (if no boundary) is much faster than a single coalesce
          // filter, since it allows geom indexes to be used.
          $sql = <<<SQL
SELECT DISTINCT l.id
FROM locations l
JOIN cache_termlists_terms t ON t.id=l.location_type_id AND t.term IN ($types)
WHERE (
  st_intersects(st_geomfromtext('$filter[searchArea]', $srid), l.boundary_geom)
  AND NOT st_touches(st_geomfromtext('$filter[searchArea]', $srid), l.boundary_geom)
) OR (
  l.boundary_geom IS NULL
  AND st_intersects(st_geomfromtext('$filter[searchArea]', $srid), l.centroid_geom)
  AND NOT st_touches(st_geomfromtext('$filter[searchArea]', $srid), l.centroid_geom)
);
SQL;
          $rows = $this->db->query($sql)->result();
        }
        foreach ($rows as $row) {
          $location_ids[] = $row->id;
        }
      }
    }
    $foundExistingLocationIds = array();
    // Go through the existing index entries for this group. Remove any that
    // are not needed now.
    foreach ($exist as $record) {
      if (in_array($record->location_id, $location_ids)) {
        // Got a correct one already. Remove the location ID from the list we
        // want to add later.
        $key = array_search($record->location_id, $location_ids);
        if ($key !== FALSE) {
          unset($location_ids[$key]);
        }
        if (in_array($record->location_id, $foundExistingLocationIds)) {
          // This one must exist twice in the index so clean it up.
          $this->db->delete('index_groups_locations', array('id' => $record->id));
        }
        else {
          $foundExistingLocationIds[] = $record->location_id;
        }
      }
      else {
        // Got one we didn't actually want.
        $this->db->delete('index_groups_locations', array('id' => $record->id));
      }
    }
    // Any remaining in our list now need to be added.
    foreach ($location_ids as $location_id) {
      $this->db->insert('index_groups_locations', array(
        'group_id' => $this->id,
        'location_id' => $location_id,
      ));
    }
  }

  /**
   * Method to populate the indexed taxon groups that this group intersects with. Makes it easy to do things like
   * suggest groups based on species being recorded.
   */
  private function processIndexGroupsTaxonGroups() {
    $filter = json_decode($this->filter->definition, TRUE);
    $exist = $this->db->select('id', 'taxon_group_id')
      ->from('index_groups_taxon_groups')
      ->where('group_id', $this->id)
      ->get();

    $taxon_group_ids = array();

    if (!empty($filter['taxon_group_list'])) {
      // Got a list of taxon groups linked to the group's filter, so these can
      // be used to define the context of the group.
      $taxon_group_ids = explode(',', $filter['taxon_group_list']);
    }
    elseif (!empty($filter['taxa_taxon_list_list']) || !empty($filter['higher_taxa_taxon_list_list']) || !empty($filter['taxon_meaning_list'])) {
      // Handle other types of species based filter, e.g. higher or lower
      // taxa taxon_list id.
      $groups = $this->db->select('DISTINCT taxon_group_id')
        ->from('cache_taxa_taxon_lists');
      if (!empty($filter['taxa_taxon_list_list'])) {
        $groups->in('id', explode(',', $filter['taxa_taxon_list_list']));
      }
      if (!empty($filter['higher_taxa_taxon_list_list'])) {
        $groups->in('id', explode(',', $filter['higher_taxa_taxon_list_list']));
      }
      if (!empty($filter['taxon_meaning_list'])) {
        $groups->in('taxon_meaning_id', explode(',', $filter['taxon_meaning_list']));
      }
      $groups = $groups->get();
      foreach ($groups as $record) {
        $taxon_group_ids[] = $record->taxon_group_id;
      }
    }
    // Go through the existing index entries for this group. Remove any that
    // are not needed now.
    foreach ($exist as $record) {
      if (in_array($record->taxon_group_id, $taxon_group_ids)) {
        // Got a correct one already. Remove the location ID from the list we
        // want to add later.
        unset($taxon_group_ids[$record->taxon_group_id]);
      } else {
        // Got one we didn't actually want.
        $this->db->delete('index_groups_taxon_groups', array('id' => $record->id));
      }
    }
    // Any remaining in our list now need to be added.
    foreach ($taxon_group_ids as $taxon_group_id) {
      $this->db->insert('index_groups_taxon_groups', array(
        'group_id' => $this->id,
        'taxon_group_id' => $taxon_group_id,
      ));
    }
  }

}
