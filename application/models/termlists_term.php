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
 * Model class for the Termlists_Terms table.
 */
class Termlists_term_Model extends Base_Name_Model {

  public $search_field = 'term';

  protected $lookup_against = 'lookup_term';

  protected $list_id_field = 'termlist_id';

  protected $belongs_to = [
    'term', 'termlist', 'meaning',
    'created_by' => 'user',
    'updated_by' => 'user',
  ];

  // Declare that this model has child attributes, and the name of the node in the submission which contains them
  protected $has_attributes = TRUE;
  protected $attrs_submission_name = 'trmAttributes';
  public $attrs_field_prefix = 'trmAttr';

  protected $ORM_Tree_children = 'termlists_terms';

  public $import_duplicate_check_combinations = array(
      array(
        'description' => 'Termlist And Term',
        'fields' => array(array('fieldName' => 'termlists_term:termlist_id'),
              array('fieldName' => 'termlists_term:term_id', 'notInMappings' => TRUE),
        )
      ),
      array(
        'description' => 'Termlist, Parent Term And Term',
        'fields' => array(array('fieldName' => 'termlists_term:termlist_id'),
              array('fieldName' => 'termlists_term:term_id', 'notInMappings' => TRUE),
              array('fieldName' => 'termlists_term:parent_id')
        )
      ),
  );

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('term_id', 'required');
    $array->add_rules('termlist_id', 'required');
    $array->add_rules('meaning_id', 'required');
    $array->add_rules('sort_order', 'integer');
    // $array->add_callbacks('deleted', array($this, '__dependents'));

    // Explicitly add those fields for which we don't do validation.
    $this->unvalidatedFields = array(
      'parent_id',
      'preferred',
      'deleted',
      'source_id',
      'image_path',
    );
    return parent::validate($array, $save);
  }

  /**
   * If we want to delete the record, we need to check that no dependents exist.
   */
  public function __dependents(Validation $array, $field) {
    if ($array['deleted'] == 'true') {
      $record = ORM::factory('termlists_term', $array['id']);
      if (count($record->children) !== 0) {
        $array->add_error($field, 'has_children');
      }
    }
  }

  /**
   * Overrides the post submit function to add in synonomies
   */
  protected function postSubmit($isInsert) {
    $success = TRUE;
    if ($this->submission['fields']['preferred']['value'] === 't') {
      try {
        if (isset($this->submission['metaFields']) && array_key_exists('synonyms', $this->submission['metaFields'])) {
          $arrSyn = $this->parseRelatedNames(
            $this->submission['metaFields']['synonyms']['value'],
            'set_synonym_sub_array'
          );
        }
        else {
          $arrSyn = array();
        }
        $meaning_id = $this->submission['fields']['meaning_id']['value'];
        $existingSyn = $this->getSynonomy('meaning_id', $meaning_id);

        // Iterate through existing synonomies, discarding those that have
        // been deleted and removing existing ones from the list to add
        // Not sure this is correct way of doing it as it would appear that you can only have one synonym per language....
        foreach ($existingSyn as $syn) {
          // Is the term from the db in the list of synonyms?
          if (array_key_exists($syn->term->language->iso, $arrSyn) &&
              $arrSyn[$syn->term->language->iso] == $syn->term->term) {
            // This one already in db, so can remove from our array
            $arrSyn = array_diff_key($arrSyn, array($syn->term->language->iso => ''));
          }
          else {
            // Synonym has been deleted - remove it from the db.
            $syn->deleted = 't';
            $syn->save();
          }
        }

        // $arraySyn should now be left only with those synonyms
        // we wish to add to the database

        Kohana::log("info", "Synonyms remaining to add: " . count($arrSyn));
        $sm = ORM::factory('termlists_term');
        foreach ($arrSyn as $lang => $term) {
          $sm->clear();
          $syn = array();
          // Wrap a new submission
          Kohana::log("debug", "Wrapping submission for synonym " . $term);
          $lang_id = ORM::factory('language')->where(array('iso' => $lang))->find()->id;
          // If language not found, use english as the default. Future versions may wish this to be
          // user definable.
          $lang_id = $lang_id ? $lang_id : ORM::factory('language')->where(array('iso' => 'eng'))->find()->id;
          // copy the original post array to pick up the common things, first the taxa_taxon_list data
          foreach (array('parent', 'sort_order', 'termlist_id') as $field) {
            if (isset($this->submission['fields'][$field])) {
              $syn["termlists_term:$field"]=is_array($this->submission['fields'][$field]) ? $this->submission['fields'][$field]['value'] : $this->submission['fields'][$field];
            }
          }
          // unlike the taxa there are no term based shared data.
          // Now update the record with specifics for this synonym
          $syn['term:id'] = NULL;
          $syn['term:term'] = $term;
          $syn['term:language_id'] = $lang_id;
          $syn['termlists_term:id'] = '';
          $syn['termlists_term:preferred'] = 'f';
          // meaning Id cannot be copied from the submission, since for new data it is generated when saved
          $syn['termlists_term:meaning_id'] = $meaning_id;
          // Prevent a recursion by not posting synonyms with a synonym
          $syn['metaFields:synonyms']='';
          $sub = $this->wrap($syn);
          // Don't resubmit the meaning record, again we can't rely on the order of the supermodels in the list
          foreach ($sub['superModels'] as $idx => $supermodel) {
            if ($supermodel['model']['id']=='meaning') {
              unset($sub['superModels'][$idx]);
              break;
            }
          }
          $sm->submission = $sub;
          if (!$sm->submit()) {
            $success = false;
            foreach($sm->errors as $key => $value) {
              $this->errors[$sm->object_name . ':' . $key] = $value;
            }
          }
        }
        if (!$isInsert) {
          $this->enqueueCustomAttributeJsonUpdate($meaning_id);
        }
      }
      catch (Exception $e) {
        $this->errors['general'] = '<strong>An error occurred</strong><br/>' . $e->getMessage();
        error_logger::log_error('Exception during postSubmit in termlists_term model.', $e);
        $success = FALSE;
      }
    }
    return $success;
  }

  /**
   * Work queue task additions after a term change.
   *
   * Term changes may need to be reflected in the cache table attrs_json fields
   * for both occurrences and samples, so create the work queue entries
   * required to perform the updates in the background.
   *
   * @param int $meaning_id
   *   Term meaning ID being changed.
   */
  private function enqueueCustomAttributeJsonUpdate($meaning_id) {
    // The comments at the top of these insert statements prevent the kohana
    // DB layer from treating these as inserts and looking for lastval(),
    // which causes errors when the work_queue task already exists.
    $sql = <<<SQL
-- insert if not already exists
INSERT INTO work_queue (task, entity, record_id, priority, cost_estimate, created_on)
SELECT DISTINCT 'task_cache_builder_attrs_occurrences', 'occurrences', o.id, 2, 60, now()
FROM occurrences o
JOIN occurrence_attribute_values av ON av.occurrence_id=o.id AND av.deleted=false
JOIN occurrence_attributes a ON a.id=av.occurrence_attribute_id AND a.deleted=false AND a.data_type='L'
JOIN cache_termlists_terms t on t.id=av.int_value AND t.meaning_id=$meaning_id
LEFT JOIN work_queue q ON q.task='task_cache_builder_attrs_occurrences'
  AND q.entity='occurrences' AND q.record_id=o.id AND q.params IS NULL
WHERE o.deleted=false
AND q.id IS NULL;
SQL;
    $this->db->query($sql);
    $sql = <<<SQL
-- insert if not already exists
INSERT INTO work_queue (task, entity, record_id, priority, cost_estimate, created_on)
SELECT DISTINCT 'task_cache_builder_attrs_samples', 'samples', s.id, 2, 60, now()
FROM samples s
JOIN sample_attribute_values av ON av.sample_id=s.id AND av.deleted=false
JOIN sample_attributes a ON a.id=av.sample_attribute_id AND a.deleted=false AND a.data_type='L'
JOIN cache_termlists_terms t on t.id=av.int_value AND t.meaning_id=$meaning_id
LEFT JOIN work_queue q ON q.task='task_cache_builder_attrs_samples'
  AND q.entity='samples' AND q.record_id=s.id AND q.params IS NULL
WHERE s.deleted=false
AND q.id IS NULL;
SQL;
    $this->db->query($sql);
  }

  /**
   * Build the array that stores the language attached to synonyms being submitted.
   */
  protected function set_synonym_sub_array($tokens, &$array) {
    if (count($tokens) >= 2) {
      $array[trim($tokens[1])] = trim($tokens[0]);
    }
    else {
      $array[kohana::config('indicia.default_lang')] = trim($tokens[0]);
    }
  }

  /**
   * Return a displayable caption for the item.
   */
  public function caption() {
    if ($this->id) {
      return ($this->term_id != NULL ? $this->term->term : '');
    }
    else {
      return 'Term in List';
    }
  }

  /**
   * Return the submission structure, which includes defining term and meaning as the parent
   * (super) models, and the synonyms as metaFields which are specially handled.
   *
   * @return array Submission structure for a termlists_term entry.
   */
  public function get_submission_structure() {
    return array(
      'model'=>$this->object_name,
      'superModels'=>array(
        'meaning'=>array('fk' => 'meaning_id'),
        'term'=>array('fk' => 'term_id')
      ),
      'metaFields'=>array('synonyms')
    );
  }

  /**
   * Set default values for a new entry.
   */
  public function getDefaults() {
    return array(
      'preferred'=>'t'
    );
  }

  /**
   * Define a form that is used to capture a set of predetermined values that apply to every record during an import.
   */
  public function fixed_values_form() {
    return array(
        'termlists_term:termlist_id' => array(
            'display' => 'Termlist',
            'description' => 'Select the Termlist for all terms in this import file. Note, if you have a file with a mix of location type then you need a ' .
                'column in the import file which is mapped to the Termlist field.',
            'datatype' => 'lookup',
            'population_call'=>'direct:termlist:id:title'
        ),
        'term:language_id' => array(
            'display'=>'Language',
            'description'=>'Select the language to import preferred terms for.',
            'datatype'=>'lookup',
            'population_call'=>'direct:language:id:language'
        )
    );
  }

}
