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
 * @package Core
 * @subpackage Controllers
 * @author	Indicia Team
 * @link http://code.google.com/p/indicia/
 * @license http://www.gnu.org/licenses/gpl.html GPL
 */

/**
 * Controller for the trigger page.
 *
 * @package Core
 * @subpackage Controllers
 */
class Trigger_Controller extends Gridview_Base_Controller {

  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('trigger', 'trigger', 'trigger/index');
    $this->columns = array(
      'name'=>'',
      'description'=>'');
    $this->pagetitle = "Triggers";
    $this->model = ORM::factory('trigger');
  }  

  public function record_authorised($id)
  {
    return $this->auth->logged_in('CoreAdmin');
  }
}
?>
