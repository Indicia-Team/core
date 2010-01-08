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
 * @package	Client
 * @subpackage PrebuiltForms
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Prebuilt Indicia data entry form that presents taxon search box, date control, map picker,
 * survey selector and comment entry controls.
 * 
 * @package	Client
 * @subpackage PrebuiltForms
 */
class iform_mnhnl_citizen_science_1 {
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {   
    return array(      
      array(
      	'name'=>'species_ctrl',
        'caption'=>'Species Control Type',
        'description'=>'The type of control that will be available to select a species.',
        'type'=>'select',
        'options' => array(
          'autocomplete' => 'Autocomplete',
          'select' => 'Select',
          'listbox' => 'List box',
          'radio_group' => 'Radio group',
          'treeview' => 'Treeview',
          'tree_browser' => 'Tree browser'
        )
      ),
      array(
      	'name'=>'website_id',
        'caption'=>'Website ID',
        'description'=>'The ID of the website that data will be posted into.',
        'type'=>'int'
      ),
      array(
      	'name'=>'password',
        'caption'=>'Website Password',
        'description'=>'The Password of the website that data will be posted into.',
        'type'=>'string'
      ),
      array(
      	'name'=>'list_id',
        'caption'=>'Species List ID',
        'description'=>'The Indicia ID for the species list that species can be selected from.',
        'type'=>'string'
      ),
	    array(
      	'name'=>'preferred',
        'caption'=>'Preferred species only?',
        'description'=>'Should the selection of species be limited to preferred names only?',
        'type'=>'boolean'
      ),
      array(
      	'name'=>'interface',
        'caption'=>'Interface Style Option',
        'description'=>'Choose the style of user interface, either dividing the form up onto separate tabs, '.
            'wizard pages or having all controls on a single page.',
        'type'=>'select',
        'options' => array(
          'tabs' => 'Tabs',
          'wizard' => 'Wizard',
          'one_page' => 'All One Page'
        )
      ),
      array(
        'name'=>'uid_attr_id',
        'caption'=>'User ID Attribute ID',      
        'description'=>'Indicia ID for the sample attribute that stores the CMS User ID.',
        'type'=>'string'
      ),
      array(      
        'name'=>'username_attr_id',
        'caption'=>'Username Attribute ID',      
        'description'=>'Indicia ID for the sample attribute that stores the user\'s username.',
        'type'=>'string'
      ),
      array(
        'name'=>'email_attr_id',
        'caption'=>'Email Attribute ID',      
        'description'=>'Indicia ID for the sample attribute that stores the user\'s email.',
        'type'=>'string'
      ),
      array(
        'name'=>'first_name_attr_id',
        'caption'=>'First Name Attribute ID',      
        'description'=>'Indicia ID for the sample attribute that stores the user\'s first name.',
        'type'=>'string'
      ),
      array(
        'name'=>'surname_attr_id',
        'caption'=>'Surname Attribute ID',      
        'description'=>'Indicia ID for the sample attribute that stores the user\'s surname.',
        'type'=>'string'
      ),
      array(
        'name'=>'phone_attr_id',
        'caption'=>'Phone Attribute ID',      
        'description'=>'Indicia ID for the sample attribute that stores the user\'s phone.',
        'type'=>'string'
      ),
      array(
        'name'=>'contact_attr_id',
        'caption'=>'Contactable Attribute ID',      
        'description'=>'Indicia ID for the sample attribute that if the user has opted in for being contacted regarding this record.',
        'type'=>'string'
      ),
      array(
        'name'=>'map_layers',
        'caption'=>'Available Map Layers',      
        'description'=>'List of available map background layers, comma separated. Options are '. 
            'openlayers_wms, nasa_mosaic, virtual_earth, multimap_default, multimap_landranger, google_physical, google_streets, google_hybrid or google_satellite.',
        'type'=>'string'
      ),
      array(
        'name'=>'spatial_systems',
        'caption'=>'Allowed Spatial Ref Systems',      
        'description'=>'List of allowable spatial reference systems, comma separated. Use the spatial ref system code (e.g. OSGB or the EPSG code).',
        'type'=>'string'
      )     
    );
  }
  
  /** 
   * Return the form title.
   * @return string The title of the form.
   */
  public static function get_title() {
    return 'MNHNL Citizen Science 1 - form designed for citizen science projects.';  
  }
  
  /**
   * Return the generated form output.
   * @return Form HTML.
   */
  public static function get_form($args) {
    global $user;
    $logged_in = $user->uid>0;
    
    $r = "<form method=\"post\" id=\"entry_form\">\n";
    // Get authorisation tokens to update and read from the Warehouse.
    $r .= data_entry_helper::get_auth($args['website_id'], $args['password']);
    $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
    $r .= "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"".$args['website_id']."\" />\n";
    $r .= "<input type=\"hidden\" id=\"record_status\" name=\"record_status\" value=\"C\" />\n";
    // request automatic JS validation
    data_entry_helper::init_validation('entry_form');

    if ($logged_in) {
      // If logged in, output some hidden data about the user
      $uid = $user->uid;
      $email = $user->mail;
      $username = $user->name;
      $uid_attr_id = $args['uid_attr_id'];      
      $email_attr_id = $args['email_attr_id'];
      $username_attr_id = $args['username_attr_id'];      
      $r .= "<input type=\"hidden\" name=\"smpAttr:$uid_attr_id\" value=\"$uid\" />\n";
      $r .= "<input type=\"hidden\" name=\"smpAttr:$email_attr_id\" value=\"$email\" />\n";
      $r .= "<input type=\"hidden\" name=\"smpAttr:$username_attr_id\" value=\"$username\" />\n";    
    }
    $r .= "<div id=\"controls\">\n";
    
    if ($args['interface']!='one_page') {    	
      $r .= "<ul>\n";
      if (!$logged_in) {
        $r .= '  <li><a href="#about_you"><span>'.lang::get('about you')."</span></a></li>\n";      
      }
      $r .= '  <li><a href="#species"><span>'.lang::get('what did you see')."</span></a></li>\n";      
      $r .= '  <li><a href="#place"><span>'.lang::get('where was it')."</span></a></li>\n";
      $r .= '  <li><a href="#other"><span>'.lang::get('other information')."</span></a></li>\n";
      $r .= "</ul>\n";      
      data_entry_helper::enable_tabs(array(
          'divId'=>'controls',
          'style'=>$args['interface']
      ));
    }   
    if ($user->uid==0) {
      $r .= "<div id=\"about_you\">\n";
      $r .= data_entry_helper::text_input(array(
        'label'=>lang::get('first name'),
        'fieldname'=>'smpAttr:'.$args['first_name_attr_id'],
        'class'=>'control-width-4'
      ));  
      $r .= data_entry_helper::text_input(array(
        'label'=>lang::get('surname'),
        'fieldname'=>'smpAttr:'.$args['surname_attr_id'],
        'class'=>'control-width-4'
      ));  
      $r .= data_entry_helper::text_input(array(
        'label'=>lang::get('email'),
        'fieldname'=>'smpAttr:'.$args['email_attr_id'],
        'class'=>'control-width-4'
      )); 
      $r .= data_entry_helper::text_input(array(
        'label'=>lang::get('phone number'),
        'fieldname'=>'smpAttr:'.$args['phone_attr_id'],
        'class'=>'control-width-4'
      ));     
      if ($args['interface']=='wizard') {
        $r .= data_entry_helper::tab_next_button(array(
          'divId'=>'controls'
        ));
      }
      if ($args['interface']=='wizard') {
        $r .= data_entry_helper::wizard_buttons(array(
          'divId'=>'controls',
          'page'=>'first'
        ));      
      }
      $r .= "</div>\n";      
    }
    $r .= "<div id=\"species\">\n";
    $r .= '<p class="page-notice ui-widget-header ui-corner-all">'.lang::get('species tab instructions')."</p>";
	  $extraParams = $readAuth + array('taxon_list_id' => $args['list_id']);
	  if ($args['preferred']) {
	    $extraParams += array('preferred' => 't');
	  }
    $species_list_args=array(
        'label'=>lang::get('occurrence:taxa_taxon_list_id'),
        'fieldname'=>'occurrence:taxa_taxon_list_id',
        'table'=>'taxa_taxon_list',
        'captionField'=>'taxon',
        'valueField'=>'id',
        'columns'=>2,
        'view'=>'detail',
        'parentField'=>'parent_id',
        'extraParams'=>$extraParams
    );
    if ($args['species_ctrl']=='tree_browser') {
      // change the node template to include images
      global $indicia_templates;
    	$indicia_templates['tree_browser_node']='<div>'.
    	    '<img src="'.data_entry_helper::$base_url.'/upload/{image_path}" alt="Image of {caption}" /></div>'.
    	    '<span>{caption}</span>';
    }
    // Dynamically generate the species selection control required.        
    $r .= call_user_func(array('data_entry_helper', $args['species_ctrl']), $species_list_args);
    if ($args['interface']=='wizard') {
      $r .= data_entry_helper::wizard_buttons(array(
        'divId'=>'controls',
        'page'=>($user->id==0) ? 'first' : 'middle'        
      ));
    }    
    $r .= "</div>\n";
    $r .= "<div id=\"place\">\n";
    $r .= '<p class="page-notice ui-widget-header ui-corner-all">'.lang::get('place tab instructions')."</p>";
    // Build the array of spatial reference systems into a format Indicia can use.
    $systems=array();
    $list = explode(',', str_replace(' ', '', $args['spatial_systems']));
    foreach($list as $system) {
      $systems[$system] = lang::get($system);
    }    
    $r .= data_entry_helper::sref_and_system(array(
      'label' => lang::get('sample:entered_sref'),
      'systems' => $systems
    ));
    $r .= data_entry_helper::map_panel(array(
      'presetLayers'=>explode(',', str_replace(' ', '', $args['map_layers'])),
      'width'=>760
    ));
    if ($args['interface']=='wizard') {
      $r .= data_entry_helper::wizard_buttons(array(
        'divId'=>'controls'
      ));      
    }
    $r .= "</div>\n";    
    $r .= "<div id=\"other\">\n";
    $r .= data_entry_helper::date_picker(array(
        'label'=>'Date',
        'fieldname'=>'sample:date'
    ));    
    $r .= data_entry_helper::textarea(array(
        'label'=>'Comment',
        'fieldname'=>'sample:comment',
        'class'=>'wide',
    ));
    $r .= '<div class="footer">'.data_entry_helper::checkbox(array(
        'label'=>lang::get('happy for contact'),
        'labelClass'=>'auto',
        'fieldname'=>'smpAttr:'.$args['contact_attr_id']
    )).'</div>';
    if ($args['interface']=='wizard') {
      $r .= data_entry_helper::wizard_buttons(array(
        'divId'=>'controls',
        'page'=>'last'
      ));
    } else { 
      $r .= "<input type=\"submit\" class=\"ui-state-default ui-corner-all\" value=\"Save\" />\n";
    }
    $r .= "</div>\n";        
    $r .= "</div>\n";    
    $r .= "</form>";
        
    return $r;
  }
  
  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    return data_entry_helper::build_sample_occurrence_submission($values);     
  }

  /**
   * Retrieves a list of the css files that this form requires in addition to the standard
   * Drupal, theme or Indicia ones.
   * 
   * @return array List of css files to include for this form.
   */
  public static function get_css() {
    return array('mnhnl_citizen_science_1.css');
  }  
  
}