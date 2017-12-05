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

 defined('SYSPATH') or die('No direct script access.');

/**
 * Controller class for the home page.
 */
class Home_Controller extends Indicia_Controller {

  public function index() {
    $view = new View('home');
    $this->template->title = 'Welcome to the Indicia Warehouse!';
    $system = new System_Model;
    $view->db_version = $system->getVersion();
    $view->app_version = kohana::config('version.version');
    // only get notifications if the database is up to date for v0.4. Otherwise you can't get to the upgrade page!
    // checking a table exists, we need a schema prefix on the table name to work.
    $dbConfig = kohana::config('database.default');
    $prefix = (isset($dbConfig['schema']) && !empty($dbConfig['schema'])) ? $dbConfig['schema'] . '.' : '';
    if ($this->db->table_exists($prefix . 'notifications')) {
      $view->notifications = $this->db
        ->select('source, source_type, data')
        ->from('notifications')
        ->where(array(
          'user_id' => $_SESSION['auth_user']->id,
          'acknowledged' => 'f'
          ))
        ->get()->as_array();
    }
    else {
      $view->notifications = array();
    }
    $this->set_website_access('admin');
    $view->configProblems = config_test::check_config(TRUE, TRUE);
    $view->gettingStartedTips = gettingStarted::getTips($this->db, $this->auth_filter);
    $this->template->content = $view;
  }

  /**
   * Action called when an formal upgrade is required.
   */
  public function upgrade()
  {
    $upgrader = new Upgrade_Model();
    try {
      $view = new View('upgrade');
      $this->template->title='Indicia Upgrade';
      $upgrader->run();
    } catch (Exception $e) {
      $view->error = $e->getMessage();
    }
    $system = new System_Model;
    $view->db_version=$system->getVersion();
    $view->app_version=kohana::config('version.version');
    $view->pgUserScriptsToBeApplied = $upgrader->pgUserScriptsToBeApplied;
    $view->slowScriptsToBeApplied = $upgrader->slowScriptsToBeApplied;
    $this->template->content=$view;
  }

}