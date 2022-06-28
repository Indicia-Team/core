<?php

/**
 * @file 
 * Queue worker to delete a user.
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

 defined('SYSPATH') or die('No direct script access.');

/**
 * Handle when a user deletes their account on a website.
 *
 * Anonymises a user's account by pointing their data at an anonymous account
 * when they remove themselves from a website (an app can also trigger this).
 * Also sends an email about other websites the user might be a member of if
 * they still have websites on their account.
 */
class task_indicia_svc_security_delete_user_account {

  const BATCH_SIZE = 1;

  /**
   * Perform the processing for a task batch found in the queue.
   *
   * @param object $db
   *   Database connection object.
   * @param object $taskType
   *   Object read from the database for the task batch. Contains the task
   *   name, entity, priority, created_on of the first record in the batch
   *   count (total number of queued tasks of this type).
   * @param string $procId
   *   Unique identifier of this work queue processing run. Allows filtering
   *   against the work_queue table's claimed_by field to determine which
   *   tasks to perform.
   */
  public static function process($db, $taskType, $procId) {
    $anonymousUserId = self::getAnonymousUserId($db, $procId);
    $jobs = $db
      ->select("record_id as user_id, (params::json->>'website_id')::integer as website_id")
      ->from('work_queue')
      ->where([
        'entity' => 'user',
        'task' => 'task_indicia_svc_security_delete_user_account',
        'claimed_by' => $procId,
      ])
      ->get()->result();
    foreach ($jobs as $job) {
      self::replaceUserIdWithAnonId($db, $procId, $job->user_id, $job->website_id, $anonymousUserId);
      self::sendWebsitesListEmail($db, $job->user_id, $job->website_id);
    }
  }

  /**
   * Repoint user's data to anonymous account.
   *
   * Repoint the user's data to anonymous account, noting that some of it is only
   * suitable for repointing if the user has no websites left
   * @param object $db
   *   Database connection object.
   * @param string $procId
   *   Unique identifier of this work queue processing run. Allows filtering
   *   against the work_queue table's claimed_by field to determine which
   *   tasks to perform.
   * @param int $userId
   *   User ID being deleted.
   * @param int $websiteId
   *   Website ID they are being deleted from.
   * @param int $anonymousUserId
   *   User ID of the special anonymous user.
   */
  public static function replaceUserIdWithAnonId($db, $procId, $userId, $websiteId, $anonymousUserId) {
    $sql = <<<SQL
    do $$
    BEGIN 
    -- Need to track updated rows so they can be added to the work_queue
    CREATE TEMP TABLE IF NOT EXISTS updated_occurrences (idx serial PRIMARY KEY, changed_record_id int);
    CREATE TEMP TABLE IF NOT EXISTS updated_samples (idx serial PRIMARY KEY, changed_record_id int);
    CREATE TEMP TABLE IF NOT EXISTS updated_termlists_terms (idx serial PRIMARY KEY, changed_record_id int);

    DELETE FROM updated_occurrences;
    DELETE FROM updated_samples;
    DELETE FROM updated_termlists_terms;

    DELETE FROM users_websites
    WHERE website_id = $websiteId AND user_id = $userId;
    -- Only repoint some items if no are websites left for the user
    IF (NOT EXISTS (
      select uw.id 
      FROM users_websites uw
      WHERE uw.user_id = $userId
    )) THEN 

      UPDATE location_media lm
      SET created_by_id = $anonymousUserId
      FROM locations l, locations_websites lw
      WHERE lm.created_by_id = $userId
      AND lm.location_id = l.id 
      AND lw.location_id = l.id
      AND lw.website_id = $websiteId;

      UPDATE location_media lm
      SET updated_by_id = $anonymousUserId
      FROM locations l, locations_websites lw
      WHERE lm.updated_by_id = $userId
      AND lm.location_id = l.id 
      AND lw.location_id = l.id
      AND lw.website_id = $websiteId;

      UPDATE location_attribute_values lav
      SET created_by_id = $anonymousUserId 
      FROM locations l, locations_websites lw
      WHERE lav.created_by_id = $userId 
      AND lav.location_id = l.id 
      AND lw.location_id = l.id
      AND lw.website_id = $websiteId;

      UPDATE location_attribute_values lav
      SET updated_by_id = $anonymousUserId 
      FROM locations l, locations_websites lw
      WHERE lav.updated_by_id = $userId 
      AND lav.location_id = l.id 
      AND lw.location_id = l.id
      AND lw.website_id = $websiteId;

      UPDATE locations l
      SET created_by_id = $anonymousUserId
      FROM locations_websites lw
      WHERE l.created_by_id = $userId
      AND lw.location_id = l.id
      AND lw.website_id = $websiteId;

      UPDATE locations l
      SET updated_by_id = $anonymousUserId
      FROM locations_websites lw
      WHERE l.updated_by_id = $userId
      AND lw.location_id = l.id
      AND lw.website_id = $websiteId;

      -- For notifications there are 2 statements. 
      -- This one repoints all notifications once user has no websites left
      UPDATE notifications n
      SET user_id = $anonymousUserId
      WHERE n.user_id = $userId;

      UPDATE people p
      SET email_address = 'deleted' || p.id || '@anonymous.anonymous'
      FROM users u
      WHERE p.id = u.person_id AND u.id = $userId;
      
    ELSE
    END IF;

    WITH updated AS (
      UPDATE terms t
      SET created_by_id = $anonymousUserId
      FROM termlists_terms tt, termlists tl
      WHERE t.created_by_id = $userId
      AND t.id = tt.term_id
      AND tt.termlist_id = tl.id
      AND tl.website_id = $websiteId
      RETURNING tt.id
    )
    INSERT INTO updated_termlists_terms (changed_record_id) SELECT id FROM updated;

    WITH updated AS (
      UPDATE terms t
      SET updated_by_id = $anonymousUserId
      FROM termlists_terms tt, termlists tl
      WHERE t.updated_by_id = $userId 
      AND t.id = tt.term_id
      AND tt.termlist_id = tl.id
      AND tl.website_id = $websiteId
      RETURNING tt.id
    )
    INSERT INTO updated_termlists_terms (changed_record_id) SELECT id FROM updated;

    WITH updated AS (
      UPDATE termlists_terms tt
      SET created_by_id = $anonymousUserId
      FROM termlists tl
      WHERE tt.created_by_id = $userId 
      AND tt.termlist_id = tl.id
      AND tl.website_id = $websiteId
      RETURNING tt.id
    )
    INSERT INTO updated_termlists_terms (changed_record_id) SELECT id FROM updated;

    WITH updated AS (
      UPDATE termlists_terms tt
      SET updated_by_id = $anonymousUserId
      FROM termlists tl
      WHERE tt.updated_by_id = $userId
      AND tt.termlist_id = tl.id
      AND tl.website_id = $websiteId
      RETURNING tt.id
    )
    INSERT INTO updated_termlists_terms (changed_record_id) SELECT id FROM updated;

    WITH updated AS (
      UPDATE occurrence_media om
      SET created_by_id = $anonymousUserId
      FROM occurrences o
      WHERE om.created_by_id = $userId
      AND om.occurrence_id = o.id 
      AND o.website_id = $websiteId
      RETURNING om.occurrence_id
    )
    INSERT INTO updated_occurrences (changed_record_id) SELECT occurrence_id FROM updated;

    WITH updated AS (
      UPDATE occurrence_media om
      SET updated_by_id = $anonymousUserId 
      FROM occurrences o
      WHERE om.updated_by_id = $userId
      AND om.occurrence_id = o.id 
      AND o.website_id = $websiteId
      RETURNING om.occurrence_id
    )
    INSERT INTO updated_occurrences (changed_record_id) SELECT occurrence_id FROM updated;

    WITH updated AS (
      UPDATE occurrence_attribute_values oav
      SET created_by_id = $anonymousUserId
      FROM occurrences o
      WHERE oav.created_by_id = $userId
      AND oav.occurrence_id = o.id 
      AND o.website_id = $websiteId
      RETURNING oav.occurrence_id
    )
    INSERT INTO updated_occurrences (changed_record_id) SELECT occurrence_id FROM updated;

    WITH updated AS (
      UPDATE occurrence_attribute_values oav
      SET updated_by_id = $anonymousUserId
      FROM occurrences o
      WHERE oav.updated_by_id = $userId
      AND oav.occurrence_id = o.id 
      AND o.website_id = $websiteId
      RETURNING oav.occurrence_id
    )
    INSERT INTO updated_occurrences (changed_record_id) SELECT occurrence_id FROM updated;

    WITH updated AS (
      UPDATE occurrences o
      SET created_by_id = $anonymousUserId
      WHERE o.created_by_id = $userId
      AND o.website_id = $websiteId
      RETURNING o.id
    )
    INSERT INTO updated_occurrences (changed_record_id) SELECT id FROM updated;

    WITH updated AS (
      UPDATE occurrences o
      SET updated_by_id = $anonymousUserId
      WHERE o.updated_by_id = $userId
      AND o.website_id = $websiteId
      RETURNING o.id
    )
    INSERT INTO updated_occurrences (changed_record_id) SELECT id FROM updated;
    
    WITH updated AS (
      UPDATE sample_media sm
      SET created_by_id = $anonymousUserId 
      FROM samples s, surveys surv
      WHERE sm.created_by_id = $userId
      AND sm.sample_id = s.id 
      AND surv.id = s.survey_id
      AND surv.website_id = $websiteId
      RETURNING sm.sample_id
    )
    INSERT INTO updated_samples (changed_record_id) SELECT sample_id FROM updated;

    WITH updated AS (
      UPDATE sample_media sm
      SET updated_by_id = $anonymousUserId
      FROM samples s, surveys surv
      WHERE sm.updated_by_id = $userId
      AND sm.sample_id = s.id 
      AND surv.id = s.survey_id
      AND surv.website_id = $websiteId
      RETURNING sm.sample_id
    )
    INSERT INTO updated_samples (changed_record_id) SELECT sample_id FROM updated;
    
    WITH updated AS (
      UPDATE sample_attribute_values sav
      SET created_by_id = $anonymousUserId 
      FROM samples s, surveys surv
      WHERE sav.created_by_id = $userId
      AND sav.sample_id = s.id 
      AND surv.id = s.survey_id
      AND surv.website_id = $websiteId
      RETURNING sav.sample_id
    )
    INSERT INTO updated_samples (changed_record_id) SELECT sample_id FROM updated;

    WITH updated AS (
      UPDATE sample_attribute_values sav
      SET updated_by_id = $anonymousUserId
      FROM samples s, surveys surv
      WHERE sav.updated_by_id = $userId
      AND sav.sample_id = s.id 
      AND surv.id = s.survey_id
      AND surv.website_id = $websiteId
      RETURNING sav.sample_id
    )
    INSERT INTO updated_samples (changed_record_id) SELECT sample_id FROM updated;

    WITH updated AS (
      UPDATE samples s
      SET created_by_id = $anonymousUserId 
      FROM surveys surv
      WHERE s.created_by_id = $userId
      AND surv.id = s.survey_id
      AND surv.website_id = $websiteId
      RETURNING s.id
    )
    INSERT INTO updated_samples (changed_record_id) SELECT id FROM updated;

    WITH updated AS (
      UPDATE samples s
      SET updated_by_id = $anonymousUserId
      FROM surveys surv
      WHERE s.updated_by_id = $userId
      AND surv.id = s.survey_id
      AND surv.website_id = $websiteId
      RETURNING s.id
    )
    INSERT INTO updated_samples (changed_record_id) SELECT id FROM updated;

    UPDATE filters_users fu
    SET created_by_id = $anonymousUserId
    FROM filters f
    WHERE fu.created_by_id = $userId
    AND fu.filter_id = f.id
    AND f.website_id = $websiteId;

    UPDATE filters_users fu
    SET user_id = $anonymousUserId
    FROM filters f
    WHERE fu.user_id = $userId
    AND fu.filter_id = f.id
    AND f.website_id = $websiteId;

    UPDATE filters f
    SET created_by_id = $anonymousUserId
    WHERE f.created_by_id = $userId
    AND f.website_id = $websiteId;

    UPDATE filters f
    SET updated_by_id = $anonymousUserId
    WHERE f.updated_by_id = $userId
    AND f.website_id = $websiteId;

    UPDATE group_pages gp
    SET created_by_id = $anonymousUserId 
    FROM groups g
    WHERE gp.created_by_id = $userId
    AND gp.group_id = g.id
    AND g.website_id = $websiteId;

    UPDATE group_pages gp
    SET updated_by_id = $anonymousUserId
    FROM groups g
    WHERE gp.updated_by_id = $userId
    AND gp.group_id = g.id
    AND g.website_id = $websiteId;

    UPDATE groups_users gu
    SET created_by_id = $anonymousUserId 
    FROM groups g
    WHERE gu.created_by_id = $userId
    AND gu.group_id = g.id
    AND g.website_id = $websiteId;

    UPDATE groups_users gu
    SET updated_by_id = $anonymousUserId
    FROM groups g
    WHERE gu.updated_by_id = $userId
    AND gu.group_id = g.id
    AND g.website_id = $websiteId;

    UPDATE groups_users gu
    SET user_id = $anonymousUserId
    FROM groups g
    WHERE gu.user_id = $userId
    AND gu.group_id = g.id
    AND g.website_id = $websiteId;

    UPDATE groups g
    SET created_by_id = $anonymousUserId 
    WHERE g.created_by_id = $userId
    AND g.website_id = $websiteId;

    UPDATE groups g
    SET updated_by_id = $anonymousUserId
    WHERE g.updated_by_id = $userId
    AND g.website_id = $websiteId;

    -- For notifications there are 2 statements. 
    -- This one repoints notifications associated with occurrences when they leave a website
    UPDATE notifications n
    SET user_id = $anonymousUserId
    FROM occurrences o
    WHERE n.user_id = $userId
    AND n.linked_id = o.id 
    AND o.website_id = $websiteId;

    DELETE FROM
      updated_samples a USING updated_samples b
    WHERE
      a.idx < b.idx AND a.changed_record_id = b.changed_record_id;

    DELETE FROM
      updated_occurrences c USING updated_occurrences d
    WHERE
      c.idx < d.idx AND c.changed_record_id = d.changed_record_id;

    DELETE FROM
      updated_termlists_terms e USING updated_termlists_terms f
    WHERE
      e.idx < f.idx AND e.changed_record_id = f.changed_record_id;

    INSERT INTO work_queue(task, entity, record_id, cost_estimate, priority, created_on)
    SELECT 'task_cache_builder_update', 'occurrence', changed_record_id, 100, 2, now()
    FROM updated_occurrences 
    WHERE changed_record_id NOT IN (
      SELECT record_id
      FROM work_queue
      WHERE task = 'task_cache_builder_update' AND entity = 'occurrence'
    );

    INSERT INTO work_queue(task, entity, record_id, cost_estimate, priority, created_on)
    SELECT 'task_cache_builder_update', 'sample', changed_record_id, 100, 2, now()
    FROM updated_samples 
    WHERE changed_record_id NOT IN (
      SELECT record_id
      FROM work_queue
      WHERE task = 'task_cache_builder_update' AND entity = 'sample'
    );

    INSERT INTO work_queue(task, entity, record_id, cost_estimate, priority, created_on)
    SELECT 'task_cache_builder_update', 'termlists_term', changed_record_id, 100, 2, now()
    FROM updated_termlists_terms
    WHERE changed_record_id NOT IN (
      SELECT record_id
      FROM work_queue
      WHERE task = 'task_cache_builder_update' AND entity = 'termlists_term'
    );
    
    END
    $$

    SQL;
    $db->query($sql);
  }

  /**
   * Get the user ID of the special anonymous user.
   *
   * @param object $db
   *   Database connection object.
   *
   * @return integer
   *   User ID of the special anonymous user.
   */
  public static function getAnonymousUserId($db) {
    $anonymousUserId = $db
      ->select("users.id as anonymous_user_id")
      ->from('users')
      ->join('people', 'people.id', 'users.person_id')
      ->where([
        'users.deleted' => 'false',
        'people.external_key' => 'indicia:anonymous',
        'people.deleted' => 'false',

      ])
      ->limit(1)
      ->get()->result_array();
    return $anonymousUserId[0]->anonymous_user_id;
  }

  /**
   * Send test email to test user account.
   *
   * Send email to user containing details of the websites they are still a
   * member of (after they have left their current website).
   * Email currently only sent to test account defined by
   * the deletion_user_test_id variable.
   *
   * @param object $db
   *   Database connection object.
   * @param int $accountDeletionUserId
   *   User ID being deleted.
   * @param int $websiteId
   *   Website ID they are being deleted from.
   */
  private static function sendWebsitesListEmail($db, $accountDeletionUserId, $websiteId) {
    // Get name of website user is leaving
    $websiteRemovalName = self::getWebsiteRemovalName($db, $websiteId);
    // List of websites user is still member of
    $websiteListUserIsStillMemberOf = self::getUserWebsitesList($db, $accountDeletionUserId);
    // Only send email if they are still a member of some websites
    if (!empty($websiteListUserIsStillMemberOf)) {
      $peopleResults = $db
        ->select('people.email_address')
        ->from('people')
        ->join('users', 'users.person_id', 'people.id')
        ->where('users.id', $accountDeletionUserId)
        ->limit(1)
        ->get()->result_array();
      $swift = email::connect();
      $emailSenderAddress = self::setupEmailSenderAddress();
      $emailSubject = self::setupEmailSubject($websiteRemovalName);
      $emailBody = self::setupEmailBody($db, $accountDeletionUserId, $websiteId, $websiteRemovalName,$websiteListUserIsStillMemberOf);
      $recipients = self::setupEmailRecipients($peopleResults[0]->email_address);
      $message = new Swift_Message($emailSubject, "<html>$emailBody</html>", 'text/html');
      $swift->send($message, $recipients, $emailSenderAddress);
      kohana::log('info', 'Website membership email sent to ' . $peopleResults[0]->email_address);
    }
  }

  /**
   * Collect address of email sender from configuration.
   *
   * @return string
   *   String containing the sender address.
   */
  private static function setupEmailSenderAddress() {
    // Try and get from configuration file if possible.
    try {
      $emailSenderAddress = kohana::config('indicia_svc_security.email_sender_address');
    }
    catch (Exception $e) {
      throw new Exception('Could not send the website membership information email, because the email sender address configuration was not specified.');
    }
    return $emailSenderAddress;
  }

  /**
   * Collect the subject line from configuration.
   * 
   * @param string $websiteRemovalName
   *   Name of the website the user is deleting themselves from.
   * @return string
   *   String containing the subject line.
   */
  private static function setupEmailSubject($websiteRemovalName) {
    try {
      $emailSubject = str_replace('{website_name}', $websiteRemovalName ,kohana::config('indicia_svc_security.email_subject'));

    }
    // Handle config file not present.
    catch (Exception $e) {
      throw new Exception('Could not send the website membership information email, because the email subject configuration was not specified.');
    }
    return $emailSubject;
  }

  /**
   * Collect the email body.
   *
   * @param object $db
   *   Database connection object.
   * @param int $accountDeletionUserId
   *   ID of the user whose account is being cancelled.
   * @param int $websiteId
   *   ID of the website the user is being removed from.
   * @param string $websiteRemovalName
   *   Name of the website the user is deleting themselves from.
   * @param array $websiteListUserIsStillMemberOf
   *   Array of websites the user is still a member of.
   *
   * @return string
   *   String containing the email's body.
   */
  private static function setupEmailBody($db, $accountDeletionUserId, $websiteId, $websiteRemovalName, $websiteListUserIsStillMemberOf) {
    try {
      // Get separator for the list of websites the user is still a member of e.g. a line break, or comma separated
      $websiteListImplosionSeparator = kohana::config('indicia_svc_security.website_list_implosion_separator');
      // Insert the website name into the body
      $emailBodyWithWebsiteName = str_replace('{website_name}', $websiteRemovalName ,kohana::config('indicia_svc_security.email_body'));
      $websitesListHtmlString = implode($websiteListImplosionSeparator, $websiteListUserIsStillMemberOf);
      // Insert the websites list into the body
      $finishedEmailBody = "<div>" . str_replace('{websites_list}', $websitesListHtmlString, $emailBodyWithWebsiteName) . "</div>";
    }
    catch (Exception $e) {
      throw new Exception('Could not send the website membership information email, because the email body creation failed.');
    }
    return $finishedEmailBody;
  }

  /**
   * Collect the email body that contains the details of the user's websites.
   *
   * @return string
   *   String containing the email address of the recipient.
   */
  private static function setupEmailRecipients($emailAddress) {
    $recipients = new Swift_RecipientList();
    $recipients->addTo($emailAddress);
    return $recipients;
  }

  /**
   * Collect the name of the website the user is being removed from.
   *
   * @param object $db
   *   Database connection object.
   * @param int $websiteId
   *   Website Id the user is being removed from.
   *
   * @return string
   *   Name of the website the user is being removed from.
   */
  private static function getWebsiteRemovalName($db, $websiteId) {
    $websitesResults = $db
      ->select('websites.title')
      ->from('websites')
      ->where(['websites.id' => $websiteId])
      ->get()->result_array();
    return $websitesResults[0]->title;
  }

  /**
   * Collect a list of websites user is still a member of to put in email.
   *
   * @param object $db
   *   Database connection object.
   * @param int $warehouseUserId
   *   Warehouse ID of user we are deleting.
   *
   * @return array
   *   Array of websites the user is still a member of.
   */
  private static function getUserWebsitesList($db, $warehouseUserId) {
    $usersWebsitesResults = $db
      ->select('websites.title')
      ->from('users_websites')
      ->join('websites', 'websites.id', 'users_websites.website_id')
      ->where(['users_websites.user_id' => $warehouseUserId])
      ->get()->result_array();
    // Convert result into a one dimensional array.
    $streamlinedUsersWebsitesResults = [];
    foreach ($usersWebsitesResults as $usersWebsitesResult) {
      array_push($streamlinedUsersWebsitesResults, $usersWebsitesResult->title);
    }
    return $streamlinedUsersWebsitesResults;
  }

}
