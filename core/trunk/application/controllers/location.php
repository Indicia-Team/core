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
 * @package	Core
 * @subpackage Controllers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

 defined('SYSPATH') or die('No direct script access.');

/**
 * Controller providing CRUD access to the locations data.
 *
 * @package	Core
 * @subpackage Controllers
 */
class Location_Controller extends Gridview_Base_Controller {

  public function __construct()
  {
    parent::__construct('location', 'location', 'location/index');
    $this->columns = array(
        'name'=>'',
        'code'=>'',
        'centroid_sref'=>'');
    $this->pagetitle = "Locations";

    // Get the list of locations the user is allowed to see.
    // @todo Is this a performance bottleneck with large lists of locations?
    if(!is_null($this->gen_auth_filter)){
      $locations=ORM::factory('locations_website')->in('website_id', $this->gen_auth_filter['values'])->find_all();
      $location_id_values = array();
      foreach($locations as $location)
        $location_id_values[] = $location->location_id;
      $this->auth_filter = array('field' => 'id', 'values' => $location_id_values);
    }
  }

  /**
   * Get the list of terms ready for the location types list. 
   */
  protected function prepareOtherViewData($values)
  {    
    return array(
      'type_terms' => $this->get_termlist_terms('indicia:location_types')    
    );   
  }

  protected function record_authorised ($id)
  {
    if (!is_null($id) AND !is_null($this->auth_filter))
    {
      return (in_array($id, $this->auth_filter['values']));
    }
    return true;
  }
  
  protected function getModelValues() {
    $r = parent::getModelValues();
    $this->loadAttributes($r);
    return $r;      
  }

  /**
   * Adds the upload csv form to the view (which should then insert it at the bottom of the grid).
   */
  protected function add_upload_shp_form() {
    $this->upload_shp_form = new View('templates/upload_shp');
    $this->upload_shp_form->staticFields = null;
    $this->upload_shp_form->controllerpath = $this->controllerpath;
    $this->view->upload_shp_form = $this->upload_shp_form;
  }

  /**
   * This is the main controller action method for the index page of the grid.
   */
  public function page($page_no, $filter=null) {
    if ($this->page_authorised() == false) {
      $this->access_denied();
      return;
    }
    parent::page($page_no, $filter);
    $this->add_upload_shp_form();
  }

  /**
   * Controller action to build the page that allows the user to choose which field is to be
   * used for the location name, and optionally for the name of the parent location.
   * A lot of this is stolen from the csv upload code.
   */
  public function upload_shp() {    
    $sizelimit = '4M';
    $_FILES = Validation::factory($_FILES)->add_rules(
        'zip_upload', 'upload::valid', 'upload::required', 'upload::type[zip]', "upload::size[$sizelimit]"
    );
    
    if ($_FILES->validate()) {
      // move the file to the standard upload directory
      $zipTempFile = upload::save('zip_upload');
      $_SESSION['uploaded_zip'] = $zipTempFile;

      // Following helps for files from Macs
      ini_set('auto_detect_line_endings',1);
      $view = new View('location/upload_shp');
      $zip = new ZipArchive;
      $res = $zip->open($zipTempFile);
      if ($res != TRUE) {
        $this->setError('Upload file problem', 'Could not open Zip archive file - possible invalid format.');
        return;
      }
      $directory = Kohana::config('upload.zip_extract_directory', TRUE);
      // Make sure the directory ends with a slash
      $directory = rtrim($directory, '/').'/';
      if ( ! is_dir($directory) AND Kohana::config('upload.create_directories') === TRUE) {
          // Create the extraction directory
          mkdir($directory, 0777, TRUE);
      }
      if ( ! is_dir($directory) ) {
        $this->setError('Upload file problem', 'Zip extraction directory '.$directory.' does not exist. Please create, or set Indicia upload.create_directories configuration item to true.');
        return;
      }
      if ( ! is_writable($directory)) {
        $this->setError('Upload file problem', 'Zip extraction directory '.$directory.' is not writable.');
        return;
      }
      if ( ! $zip->extractTo($directory)) {
        $this->setError('Upload file problem', 'Could not extract Zip archive file contents.');
        return;
      }
      $entry = '';
      $dbf = 0;
      $shp = 0;
      for($i = 0; $i < $zip->numFiles; $i++) {
        if(basename($zip->getNameIndex($i)) != basename($zip->getNameIndex($i), '.dbf')) {
          $entry = $zip->getNameIndex($i);
          $dbf++;
        }
        if(basename($zip->getNameIndex($i)) != basename($zip->getNameIndex($i), '.shp')) {
          $shpentry = $zip->getNameIndex($i);
          $shp++;
        }
      }
      if($shp == 0) {
        $this->setError('Upload file problem', 'Zip archive file does not contain a file with a .shp extension.');
        return;
      }
      if($shp > 1) {
        $this->setError('Upload file problem', 'Zip archive file contains more than one file with a .shp extension.');
        return;
      }
      if($dbf == 0) {
        $this->setError('Upload file problem', 'Zip archive file does not contain a file with a .dbf extension.');
        return;
      }
      if($dbf > 1) {
        $this->setError('Upload file problem', 'Zip archive file contains more than one file with a .dbf extension.');
        return;
      }
      if(basename($entry, '.dbf') != basename($shpentry, '.shp')) {
        $this->setError('Upload file problem', '.dbf and .shp files in Zip archive have different names.');
        return;
      }
      $_SESSION['extracted_basefile'] = $directory.'/'.basename($entry, '.dbf');
      $zip->close();
      $this->template->title = "Choose details in ".$entry." for ".$this->pagetitle;
      $dbasedb = dbase_open($directory.'/'.$entry, 0);
      if ($dbasedb) {
          // read some data ..
          $view->columns = dbase_get_header_info($dbasedb);
          dbase_close($dbasedb);
      } else  {
        $this->setError('Upload file problem', 'Could not open '.$entry.' from Zip archive.');
        return;
      }
      $view->onCompletePage = 'test.php';
      $view->model = $this->model;
      $view->controllerpath = $this->controllerpath;
      $this->template->content = $view;
      // Setup a breadcrumb
      $this->page_breadcrumbs[] = html::anchor($this->model->object_name, $this->pagetitle);
      $this->page_breadcrumbs[] = 'Setup SHP File upload';
    } else {
      $errors = $_FILES->errors();
      $error = '';
      foreach ($errors as $key => $val) {
        switch ($val) {
          case 'required': 
            $error .= 'You must specify a Zip Archive file to upload, containing the .shp and .dbf files.<br/>';
            break;
          case 'valid': 
            $error .= 'The uploaded file is not valid.<br/>';
            break;
          case 'type': 
            $error .= 'The uploaded file is not a zip file. The Shapefile should be uploaded in a Zip Archive file, which should also contain the .dbf file containing the data for each record.<br/>';
            break;
          case 'size': 
            $error .= 'The upload file is greater than the limit of '.$sizelimit.'b.<br/>';
            break;
          default : $error .= 'An unknown error occurred when checking the upload file.<br/>';
        }
      }
      // TODO: error message needs a back button.
      $this->setError('Upload file problem', $error);
    }
  }

  /**
   * TODO create an AJAX call to give details of what will be done before it is done:
   * ie how many will be created, how many will be updated.
   * /
  public function upload_shp_check() {
  }
  /**
   * Controller action that performs the import of data in an uploaded Shapefile.
   * TODO Sort out how large geometries are displayed in the locations indicia page, and also other non
   * WGS84/OSGB srids.
   * TODO Add identification of record by external code as alternative to name.
   */

  public function upload_shp2() {
  	$zipTempFile = $_POST['uploaded_zip'];
    $basefile = $_POST['extracted_basefile'];
    // at this point do I need to extract the zipfile again? will assume at the moment that it is
    // already extracted: TODO make sure the extracted files still exist
      ini_set('auto_detect_line_endings',1);
      $view = new View('location/upload_shp2');
      $view->update = Array();
      $view->create = Array();
      // create the file pointer, plus one for errors
      $count=0;
      $this->template->title = "Confirm Shapefile upload for ".$this->pagetitle;
      $dbasedb = dbase_open($basefile.'.dbf', 0);
      if(!array_key_exists('name', $_POST))  {
        $this->setError('Upload problem', 'Name column in .dbf file must be specified.');
        return;
      }
      if(array_key_exists('use_parent', $_POST) && !array_key_exists('parent', $_POST))  {
        $this->setError('Upload problem', 'Parent column in .dbf file must be specified.');
        return;
      }
      if ($dbasedb) {
        // read some data ..
        $record_numbers = dbase_numrecords($dbasedb);
        $handle = fopen($basefile.'.shp', "rb");
        //Don't care about file header: jump direct to records.
        fseek($handle, 100, SEEK_SET);
        
        for ($i = 1; $i <= $record_numbers; $i++) {
          $row = dbase_get_record_with_names($dbasedb, $i);
          $this->loadFromFile($handle);
          if(kohana::config('sref_notations.internal_srid') != $_POST['srid']) {
            $result = $this->db->query("SELECT ST_asText(ST_Transform(ST_GeomFromText('".$this->wkt."',".$_POST['srid']."),".
            kohana::config('sref_notations.internal_srid').")) AS wkt;")->current();
            $this->wkt = $result->wkt;
          }
          if(array_key_exists('use_parent', $_POST)) {
            $parent_locations=ORM::factory('location')->where('name', trim($row[$_POST['parent']]))->where('deleted', 'false')->find_all();
            if(count($parent_locations) == 0) {
              $this->setError('Upload problem', 'Could not find non deleted parent where name = '.trim($row[$_POST['parent']]));
              return;
            }
            if(count($parent_locations) > 1) {
              $this->setError('Upload problem', 'Found more than one non deleted parent where name = '.trim($row[$_POST['parent']]));
              return;
            }
            $my_locations=ORM::factory('location')->where('name', $_POST['prepend'].trim($row[$_POST['name']]))->where('parent_id', $parent_locations[0]->id)->where('deleted', 'false')->find_all();
            if(count($my_locations) > 1) {
              $this->setError('Upload problem', 'Found '.count($my_locations).' non deleted children where name = '.$_POST['prepend'].trim($row[$_POST['name']]).' and parent name = '.trim($row[$_POST['parent']]));
              return;
            }
            $myLocation = ORM::factory('location', array('name' => $_POST['prepend'].trim($row[$_POST['name']]), 'parent_id' => $parent_locations[0]->id, 'deleted' => 'false'));
          } else {            $my_locations = $this->db                ->select('locations.id')                ->from('locations')                ->join('locations_websites', 'locations_websites.location_id', 'locations.id')                ->where('locations.deleted', 'false')                ->where('locations_websites.deleted', 'false')                ->where('locations.name', $_POST['prepend'].trim($row[$_POST['name']]))                ->where('locations_websites.website_id', $_POST['website_id'])                ->get();            if(count($my_locations) > 1) {
              $this->setError('Upload problem', 'Found more than one location where name = '.$_POST['prepend'].trim($row[$_POST['name']]));
              return;
            }            if (count($my_locations===1)) {              $r=$my_locations[0];
              $myLocation = ORM::factory('location', $r->id);            } else {              $myLocation = ORM::factory('location');            }
          }
          if ($myLocation->loaded){
            // existing record
            $myLocation->__set((array_key_exists('boundary', $_POST) ? 'boundary_geom' : 'centroid_geom'), $this->wkt);
            $myLocation->__set('centroid_sref', $this->firstPoint);
            $myLocation->save();
            $view->update[] = $_POST['prepend'].trim($row[$_POST['name']]).(array_key_exists('use_parent', $_POST) ? ' - parent '.trim($row[$_POST['parent']]) : '');
          } else {
            // create a new record
            $fields = array('name' => array('value' => $_POST['prepend'].trim($row[$_POST['name']]))
                          ,'deleted' => array('value' => 'f')
                          ,'centroid_sref' => array('value' => $this->firstPoint)
                          ,'centroid_sref_system' => array('value' => $_POST['srid'])
                          ,(array_key_exists('boundary', $_POST) ? 'boundary_geom' : 'centroid_geom') => array('value' => $this->wkt));
            if(array_key_exists('use_parent', $_POST))
              $fields['parent_id'] = array('value' => $parent_locations[0]->id);
            
            $save_array = array(
                'id' => $myLocation->object_name
                ,'fields' => $fields
                ,'fkFields' => array()
                ,'superModels' => array());
            $myLocation->submission = $save_array;
            $myLocation->submit();
            $joinModel = ORM::factory('locations_website');
            $joinModel->validate(new Validation(array('location_id' => $myLocation->id, 'website_id' => $_POST['website_id'])), true);
            $view->create[] = $_POST['prepend'].trim($row[$_POST['name']]).(array_key_exists('use_parent', $_POST) ? ' - parent '.trim($row[$_POST['parent']]) : '');
          }
        }
        fclose($handle);
        dbase_close($dbasedb);
      }      kohana::log('debug', 'locations import done');
      $view->onCompletePage = 'test.php';
      $view->model = $this->model;
      $view->controllerpath = $this->controllerpath;
      $this->template->content = $view;
      $this->page_breadcrumbs[] = html::anchor($this->model->object_name, $this->pagetitle);
      $this->page_breadcrumbs[] = 'Setup SHP File upload';
      
  }
  
    function loadData($type, $data)
    {
      if (!$data) return $data;
      $tmp = unpack($type, $data);
      return current($tmp);
    }

    function loadStoreHeaders($handle)
    {
        $this->recordNumber = $this->loadData("N", fread($this->SHPFile, 4));
        $this->recordLength = $this->loadData("N", fread($this->SHPFile, 4)); //We read the length of the record: NB this ignores the header
        $this->recordStart =  ftell($this->SHPFile);        $this->shapeType = $this->loadData("V", fread($this->SHPFile, 4));
    }

    private function loadFromFile($handle)
    {
        $this->SHPFile = $handle;
        $this->loadStoreHeaders($handle);
        $this->firstPoint = "";
        switch ($this->shapeType) {
            case 0:
                $this->loadFromFile($handle);
                break;
            case 1:
                $this->loadPointRecord();
                break;
            case 3:
                $this->loadPolyLineRecord('MULTILINESTRING');
                break;
            case 5:
                $this->loadPolyLineRecord('POLYGON');
                break;
            case 15:
                $this->loadPolyLineZRecord('POLYGON'); // we discard the Z data.
                break;
            default:
                break;
        }
    }

    function loadPoint()
    {
        $x1 = $this->loadData("d", fread($this->SHPFile, 8));
        $y1 = $this->loadData("d", fread($this->SHPFile, 8));
        $data = "$x1 $y1";
        if($this->firstPoint == "") $this->firstPoint = "$x1".Kohana::lang('misc.x_y_separator')." $y1";
        return $data;
    }

    function loadPointRecord()
    {
        $data = $this->loadPoint();
        $this->wkt = 'POINT('.$data.')';
    }

    function loadPolyLineRecord($title)
    {
        $this->SHPData = array();
        $this->loadData("d", fread($this->SHPFile, 8)); // xmin
        $this->loadData("d", fread($this->SHPFile, 8)); // ymin
        $this->loadData("d", fread($this->SHPFile, 8)); // xmax
        $this->loadData("d", fread($this->SHPFile, 8)); // ymax

        $this->SHPData["numparts"] = $this->loadData("V", fread($this->SHPFile, 4));
        $this->SHPData["numpoints"] = $this->loadData("V", fread($this->SHPFile, 4));

        for ($i = 0; $i < $this->SHPData["numparts"]; $i++) {
            $this->SHPData["parts"][$i] = $this->loadData("V", fread($this->SHPFile, 4));
        }

        $this->wkt = $title.'(';
        $firstIndex = ftell($this->SHPFile);
        $readPoints = 0;
        while (list($partIndex, $partData) = each($this->SHPData["parts"])) {
            if (!isset($this->SHPData["parts"][$partIndex]["pointString"]) || !is_array($this->SHPData["parts"][$partIndex]["pointString"])) {
                $this->SHPData["parts"][$partIndex] = array();
                $this->SHPData["parts"][$partIndex]["pointString"] = "";
            }
            while (!in_array($readPoints, $this->SHPData["parts"]) && ($readPoints < ($this->SHPData["numpoints"])) && !feof($this->SHPFile)) {
                $data = $this->loadPoint();
                $this->SHPData["parts"][$partIndex]["pointString"] .= ($this->SHPData["parts"][$partIndex]["pointString"] == "" ? "" : ', ').$data;
                $readPoints++;
            }
            $this->wkt .= ($partIndex == 0 ? "" : ",").'('.$this->SHPData["parts"][$partIndex]["pointString"].')';
        }

        $this->wkt .= ')';
        // Seek to the exact end of this record        
      fseek($this->SHPFile, $this->recordStart + ($this->recordLength * 2));    }
    
    /**
     * Read a PolyLineZ record. This is the same as a PolyLine for our purposes since we do not hold Z data.
     */
    private function loadPolyLineZRecord($title)
    {
      $this->loadPolyLineRecord($title);
      // According to the spec there are 2 sets of minima and maxima, plus 2 arrays of values * numpoints, that we skip, but since each       // record's length is read and used to find the next record, this does not matter.
    }
    
}

?>
