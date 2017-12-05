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

?>
<form enctype="multipart/form-data" class="form-inline" method="post" action="<?php echo url::site().$controllerpath.'/importer/'.$returnPage; ?>">
<?php
if ($staticFields != NULL) {
  foreach ($staticFields as $a => $b) {
    print form::hidden($a, $b);
  }
}
?>
<label for="csv_upload">Upload a CSV file into this list:</label>
<input type="file" name="csv_upload" id="csv_upload" class="form-control" />
<input type="submit" value="Upload CSV File" class="btn btn-default" />
</form>
