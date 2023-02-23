<?php

class customVerificationRules {

  /**
   * Returns help information.
   */
  public static function helpBlock() {
    return <<<TXT
      <pre>

        Format for rulesets:
          Title *
          Description
          Fail icon *
          Fail message *
          Skip ruleset if life stage not one of ... (comma separated)
          Skip ruleset if latitude not greater than ...
          Skip ruleset if latitude not less than ...
          Skip ruleset if longitude not greater than ...
          Skip ruleset if longitude not less than ...
          Skip ruleset if location not one of ... (indexed)

        Data import format for rules:
          Ruleset ID *
          Taxon *
          Fail icon
          Fail message
          Skip rule if life stage not one of ... (comma separated)
          Skip rule if latitude not greater than ...
          Skip rule if latitude not less than ...
          Skip rule if longitude not greater than ...
          Skip rule if longitude not less than ...
          Skip rule if location not one of ... (indexed)
          Rule type *, one of the following:
            abundance - checks for records of a species which have an exact count given for their abundance and the count is greater than a certain value.
            geography - checks for records of a species that are outside an area which you define, e.g. a bounding box, grid reference, or administrative location. Can also find records north or south of a latitude line, or east or west of a longitude line.
            period - checks for records before or after a given year, e.g. can highlight records before the year of arrival of a newly arrived species.
            phenology - checks for records that don't fall in a defined time of year.
            species_recorded - checks for any records of a species, e.g. can be used to build a rarity list ruleset.
          Reverse rule - if set, then the outcome of the rule checks are reversed, e.g. geography rules define a region inside which records will be flagged, or an abundance check flag is raised for records with a count less than the defined value.

          abundance:
            max_individual_count
          geography:
            min_lat
            max_lat
            min_lng
            max_lng
            grid_refs
              (with grid_refs_system)
            higher_geography_ids
          phenology:
            start_day_of_year
            end_day_of_year
            or:
            start_month
            end_month
          period:
            start_year
            end_year
          species_recorded:
            # no parameters for this rule.

      </pre>
TXT;
  }

  public static function exampleBlock() {
    return <<<TXT
    <pre>
      <strong>Example</strong>

      # A verification rule in YAML format.
      # Note the higher geography info and taxon name info will be mapped back to the relevant key when importing the rule data.
      title: Southwest abundance
      description: Ladybird abundance checks for South Devon and Dorset.
      fail_icon: count
      fail_message: An unusually high count of individuals which warrants further checking.
      limit_to_geography:
        locations:
          - Vice County|Dorset
          - Vice County|South Devon

      rule:
        type: abundance
        taxon_id: NBNSYS0012345678
        max_individual_count: 10

      rule:
        type: abundance
        taxon_name: '2 spot ladybird'
        fail_message: It's very rare that you'll find more than 20 2-spot Ladybirds in a single location.
        max_individual_count: 20

    </pre>

    <pre>

      <strong>Example</strong>

      # A verification rule in YAML format.
      title: Various checks
      fail_icon: warning
      fail_message: A local verification rule check has failed.
      limit_to_geography:
        min_lat: 52.5

      rule:
        type: phenology
        taxon_id: NBNSYS0012345678
        limit_to_stages:
          - adult
        limit_to_geography:
          grid_ref:
            SY99
        fail_message: This is a late spring and summer species. Record is outside the expected time of year so should be checked.
        fail_icon: calendar
        start_month: 4
        end_month: 8

      rule:
        type: geography
        taxon_name: '13-spot ladybird'
        fail_message: 13-spot ladybirds are highly localised.
        geography:
          min_lat: 52.313
          max_lat: 52.562
          min_lng: -2.552
          max_lng: -2.212

      rule:
        type: species_recorded
        taxon_id: NBNSYS0012345678

    </pre>
TXT;
  }

  public static function mappingHelpBlock() {
    return <<<TXT
      <pre>

        # Add a mapping to hold the custom flags.
        PUT occurrence_v1/_mapping
        {
          "properties": {
            "identification.custom_verification_rule_flags": {
              "type": "nested",
              "properties": {
                "custom_verification_ruleset_id": { "type": "integer" },
                "custom_verification_rule_id": { "type": "integer" },
                "created_by_id": { "type": "integer" },
                "result": { "type": "keyword" },
                "icon": { "type": "keyword" },
                "message": { "type": "text" },
                "check_date_time": {
                  "type": "date",
                  "format": "8yyyy-MM-dd HH:mm:ss"
                }
              }
            }
          }
        }

      </pre>
TXT;
  }

  public static function buildCustomRuleRequest($rulesetId) {
    // @todo Make user ID dynamic.
    $userID = 5;

    $db = new Database();
    $datetime = new DateTime();
    $timestamp = $datetime->format('Y-m-d H:i:s');

    $ruleset = $db->select('*')->from('custom_verification_rulesets')->where('id', $rulesetId)->get()->current();
    if (empty($ruleset)) {
      throw new exception('Ruleset not found');
    }
    // Get the filters that limit the set of records this ruleset can be
    // applied to.
    $rulesetFilterText = json_encode(self::getRulesetFilters($db, $ruleset));

    $rules = $db->select('*')
      ->from('custom_verification_rules')
      ->where([
        'custom_verification_ruleset_id' => $ruleset->id,
        'deleted' => 'f',
      ])
      ->orderby('id')
      ->get();
    $ruleScripts = [];

    foreach ($rules as $rule) {
      $ruleScripts[] = self::getRuleScript($ruleset, $rule);
    }

    $allRuleScripts = implode("\n", $ruleScripts);

    $requestBody = <<<TXT
    POST occurrence_v1/_update_by_query
    {
      "script": {
        "source": """

          // Function to build the info to store for a rule fail.
          HashMap errorInfo(int ruleId, String icon, String message) {
            return [
              'custom_verification_ruleset_id': $ruleset->id,
              'custom_verification_rules_id': ruleId,
              'created_by_id': $userID,
              'result': 'fail',
              'icon': icon,
              'message': message,
              'check_date_time': '$timestamp'
            ];
          }

          // Function to check record higher geography list against list of IDs in a rule.
          boolean higherGeoIntersection(def higherGeoList, ArrayList list) {
            ArrayList recordGeoIds = new ArrayList();
            if (higherGeoList !== null) {
              for (id in higherGeoList) {
                recordGeoIds.add(id);
              }
            }
            recordGeoIds.retainAll(list);
            return recordGeoIds.size() > 0;
          }

          if (ctx._source.identification.custom_verification_rule_flags == null) {
            ctx._source.identification.custom_verification_rule_flags = new ArrayList();
          }
          ArrayList flags = ctx._source.identification.custom_verification_rule_flags;
          flags.removeIf(a -> a.custom_verification_ruleset_id === $ruleset->id);
          // Prep some data to make the tests simpler.
          ArrayList geoIds = new ArrayList();
          if (ctx._source.location.higher_geography !== null) {
            for (item in ctx._source.location.higher_geography) {
              geoIds.add(Integer.parseInt(item.id));
            }
          }
          def latLng = ctx._source.location.point.splitOnToken(',');
          def lat = Float.parseFloat(latLng[0]);
          def lng = Float.parseFloat(latLng[1]);
$allRuleScripts
        """,
        "lang": "painless"
      },
      "query": {
        "bool": {
          "must":
            $rulesetFilterText
        }
      }
    }
TXT;
    return $requestBody;
  }

  /**
   * Retrieve filters that limit the records this ruleset can be applied to.
   *
   * @param Database $db
   *   Database connection.
   * @param obj $ruleset
   *   Ruleset metadata read from the database.
   *
   * @return array
   *   List of filter definitions, e.g. life stage or geographic limits.
   */
  private static function getRulesetFilters($db, $ruleset) {
    // The outer filter will be restricted to the taxa in the rules within the
    // set.
    $allTaxaKeys = $db->query("SELECT string_agg(DISTINCT taxon_external_key, ',') as keylist FROM custom_verification_rules WHERE deleted=false AND custom_verification_ruleset_id=$ruleset->id")->current()->keylist;
    if (empty($allTaxaKeys)) {
      throw new exception('No rules in this ruleset');
    }
    $rulesetFilters = [
      ['terms' => ['taxon.taxon_id' => explode(',', $allTaxaKeys)]],
    ];
    // Also limit to stages if the ruleset has limit_to_stages set.
    if (!empty($ruleset->limit_to_stages)) {
      $stages = str_getcsv(substr($ruleset->limit_to_stages, 1, strlen($ruleset->limit_to_stages) - 2));
      $rulesetFilters[] = ['terms' => ['occurrence.life_stage' => $stages]];
    }
    // Finally, limit geography if specified in the ruleset.
    if (!empty($ruleset->limit_to_geography)) {
      $geoLimits = json_decode($ruleset->limit_to_geography);
      // Limit on a bounding box.
      if (!empty($geoLimits->min_lat) || !empty($geoLimits->max_lat) || !empty($geoLimits->min_lng) || !empty($geoLimits->max_lng)) {
        $rulesetFilters[] = [
          'geo_bounding_box' => [
            'location.point' => [
              'top' => empty($geoLimits->max_lat) ? 90 : $geoLimits->max_lat,
              'left' => empty($geoLimits->min_lng) ? -180 : $geoLimits->min_lng,
              'bottom' => empty($geoLimits->min_lat) ? -90 : $geoLimits->min_lat,
              'right' => empty($geoLimits->max_lng) ? 180 : $geoLimits->max_lng,
            ],
          ],
        ];
      }
      // Limit on higher geography (indexed location) IDs.
      if (!empty($geoLimits->higher_geography_ids)) {
        $rulesetFilters[] = [
          'nested' => [
            'path' => 'location.higher_geography',
            'query' => [
              'terms' => [
                'location.higher_geography.id' => $geoLimits->higher_geography_ids,
              ],
            ],
          ],
        ];
      }
      // @todo Grid ref.
    }
    return $rulesetFilters;
  }

  /**
   * Return the script required to test a record against a single rule.
   *
   * @param obj $ruleset
   *   Ruleset metadata read from the database.
   * @param obj $rule
   *   Rule metadata read from the database.
   *
   * @return string
   *   Painless script to test the current document against a rule.
   */
  private static function getRuleScript($ruleset, $rule) {
    // Message and icon are set in the ruleset but can be overridden by a rule.
    $message = $rule->fail_message ?? $ruleset->fail_message;
    $icon = $rule->fail_icon ?? $ruleset->fail_icon;
    $ruleParams = json_decode($rule->definition);
    $checks = [];
    $ruleIsToBeAppliedChecks = implode(' && ', self::getApplicabilityChecksForRule($rule));

    switch ($rule->rule_type) {
      case 'abundance':
        self::getAbundanceChecks($rule, $ruleParams, $checks);
        break;

      case 'geography':
        self::getGeographyChecks($rule, $ruleParams, $checks);
        break;

      case 'period':
        self::getPeriodChecks($rule, $ruleParams, $checks);
        break;

      case 'phenology':
        self::getPhenologyChecks($rule, $ruleParams, $checks);
        break;

      case 'species_recorded':
        // Just a presence check so no additional checks required to fire the rule.
        break;

      default:
        throw new exception("Unrecognised rule type $rule->type");
    }

    $testForFail = implode(' || ', $checks);
    return <<<TXT
          // Rule ID $rule->id.
          if ($ruleIsToBeAppliedChecks) {
            if ($testForFail) {
              flags.add(errorInfo($rule->id, '$icon', '$message'));
            }
          }
TXT;
  }

  /**
   * Define applicability limits for each specific rule.
   *
   * A rule is always limited to a specific taxon, but a rule can also be
   * limited to only certain life stages or by geography.
   *
   * @param obj $rule
   *   Rule data from the database.
   *
   * @return array
   *   List of filter clauses in Painless script language.
   */
  private static function getApplicabilityChecksForRule($rule) {
    // Start with a filter on the taxon ID.
    $applicabilityCheckList = [
      "ctx._source.taxon.taxon_id == '$rule->taxon_external_key'",
    ];
    // Rule may be only applicable to certain stages.
    if (!empty($rule->limit_to_stages)) {
      $stages = str_getcsv(substr($rule->limit_to_stages, 1, strlen($rule->limit_to_stages) - 2));
      $stages = array_map(function ($stage) {
        return "'" . str_replace(["'", '\"'], ["\\'", '"'], $stage) . "'";
      }, $stages);
      $stageTxt = implode(', ', $stages);
      $applicabilityCheckList[] = "ctx._source.occurrence.life_stage != null";
      $applicabilityCheckList[] = "[$stageTxt].indexOf(ctx._source.occurrence.life_stage.toLowerCase()) >= 0";
    }
    // Rule may be only applicable to a geographic area. Note this is a test
    // for which records to include, not a test for which records to fail, so
    // operators are reverse direction to the rule test.
    if (!empty($rule->limit_to_geography)) {
      $geoLimits = json_decode($rule->limit_to_geography);
      self::geographyToPainlessChecks($geoLimits, TRUE, TRUE, $applicabilityCheckList);
    }
    return $applicabilityCheckList;
  }

  /**
   * Convert a geography definition to Painless script check clauses.
   *
   * Take the geography defined for a ruleset limit, rule limit, or a geography
   * rule check and convert it to filter clauses in Painless script format.
   *
   * @param object $geoParams
   *   Geography definition parameters, supports the following:
   *   * min_lat
   *   * max_lat
   *   * min_lng
   *   * max_lng
   *   * higher_geography_ids.
   *   * @todo grid_refs
   * @param bool $includeIfTouches
   *   Should the result include records where the point lies on the line, or
   *   only those which are completely over the line.
   * @param bool $includeRecordsWhichPassChecks
   *   Should the result include the records which pass the defined checks, or
   *   fail? Default for a limit is to include records which pass, default for
   *   a rule check is to include records which fail unless reverse_rule is
   *   true.
   * @param array &$checks
   *   List of Painless checks that additional checks will be appended to.
   *
   * @todo Consider whether we need to occurrences as grid squares instead of
   *   just points.
   */
  private static function geographyToPainlessChecks($geoParams, $includeIfTouches, $includeRecordsWhichPassChecks, array &$checks) {
    // Prepare operators according to the reverse rule setting.
    $eq = $includeIfTouches ? '=' : '';
    $opMin = ($includeRecordsWhichPassChecks ? '>' : '<') . $eq;
    $opMax = ($includeRecordsWhichPassChecks ? '<' : '>') . $eq;
    // Latitude longitude range checks.
    if (!empty($geoParams->min_lat)) {
      $checks[] = "lat $opMin $geoParams->min_lat";
    }
    if (!empty($geoParams->max_lat)) {
      $checks[] = "lat $opMax $geoParams->max_lat";
    }
    if (!empty($geoParams->min_lng)) {
      $checks[] = "lng $opMin $geoParams->min_lng";
    }
    if (!empty($geoParams->max_lng)) {
      $checks[] = "lng $opMax $geoParams->max_lng";
    }
    // Checks against indexed locations (higher geogprahy IDs) such as Vice
    // Counties.
    if (!empty($geoParams->higher_geography_ids)) {
      $checkInOrOut = $includeRecordsWhichPassChecks ? '' : '!';
      $checks[] = $checkInOrOut . 'higherGeoIntersection([' . implode(',', $geoParams->higher_geography_ids) . '], geoIds)';
    }
    if (!empty($geoParams->grid_refs)) {
      if (empty($geoParams->grid_ref_system) || !spatial_ref::is_valid_system($geoParams->grid_ref_system)) {
        throw new exception('Grid references specified without a valid grid ref system.');
      }
      // A list of checks to determine if record inside this square.
      $inSquareCheckCode = [];
      foreach ($geoParams->grid_refs as $gridRef) {
        $webMercatorWkt = spatial_ref::sref_to_internal_wkt($gridRef, $geoParams->grid_ref_system);
        if (strpos($webMercatorWkt, 'POLYGON((') === FALSE) {
          throw new exception('Grid reference given is not actually a grid square.');
        }
        $latLngWkt = spatial_ref::internal_wkt_to_wkt($webMercatorWkt, 4326);
        $coordString = preg_replace(['/^POLYGON\(\(/', '/\)\)$/'], ['', ''], $latLngWkt);
        $coordList = explode(',', $coordString);
        $minLat = NULL;
        $maxLat = NULL;
        $minLng = NULL;
        $maxLng = NULL;
        foreach ($coordList as $coordPair) {
          [$x, $y] = explode(' ', $coordPair);
          $minLat = $minLat === NULL ? $y : min($minLat, $y);
          $maxLat = $maxLat === NULL ? $y : max($maxLat, $y);
          $minLng = $minLng === NULL ? $x : min($minLng, $y);
          $maxLng = $maxLng === NULL ? $x : max($maxLng, $y);
        }
        $inSquareCheckCode[] = "(lat >$eq $minLat && lat <$eq $maxLat && lng >$eq $minLng && lng <$eq $maxLng)";
      }
      // Either check if record in any of the squares, or check that record in
      // none of the squares.
      $checks[] = $includeRecordsWhichPassChecks ? '(' . implode(' || ', $inSquareCheckCode) . ')' : '(!' . implode(' && !', $inSquareCheckCode) . ')';
    }
  }

  /**
   * Return the code clause required to test a record against abundance rules.
   *
   * @param object $rule
   *   Rule metadata read from the database.
   * @param object $ruleParams
   *   Params and values defined for the rule.
   * @param array $checks
   *   List of checks which will be added to. Checks will be later combined
   *   with an OR operation.
   */
  private static function getAbundanceChecks($rule, $ruleParams, array &$checks) {
    // Prepare operators according to the reverse_rule setting.
    $opEnd = $rule->reverse_rule === 't' ? '<' : '>';
    $checks[] = "ctx._source.occurrence.individual_count != null && Integer.parseInt(ctx._source.occurrence.individual_count) $opEnd $ruleParams->max_individual_count";
  }

  /**
   * Return the code clause required to test a record against a geography rule.
   *
   * @param object $rule
   *   Rule metadata read from the database.
   * @param object $ruleParams
   *   Params and values defined for the rule.
   * @param array $checks
   *   List of checks which will be added to. Checks will be later combined
   *   with an OR operation.
   *
   * @return string
   *   Painless script to test the current document against a rule.
   */
  private static function getGeographyChecks($rule, $ruleParams, array &$checks) {
    return self::geographyToPainlessChecks($ruleParams, FALSE, $rule->reverse_rule === 't', $checks);
  }

  /**
   * Return the code clause required to test a record against a period rule.
   *
   * @param object $rule
   *   Rule metadata read from the database.
   * @param object $ruleParams
   *   Params and values defined for the rule.
   * @param array $checks
   *   List of checks which will be added to. Checks will be later combined
   *   with an OR operation.
   */
  private static function getPeriodChecks($rule, $ruleParams, array &$checks) {
    // Prepare operators according to the reverse_rule setting.
    $opStart = $rule->reverse_rule === 't' ? '>' : '<';
    $opEnd = $rule->reverse_rule === 't' ? '<' : '>';
    if (!empty($ruleParams->start_year)) {
      $checks[] = "Integer.parseInt(ctx._source.event.year) $opStart $ruleParams->start_year";
    }
    if (!empty($ruleParams->end_year)) {
      $checks[] = "Integer.parseInt(ctx._source.event.year) $opEnd $ruleParams->end_year";
    }
  }

  /**
   * Return the code clause required to test a record against a phenology rule.
   *
   * @param object $rule
   *   Rule metadata read from the database.
   * @param object $ruleParams
   *   Params and values defined for the rule.
   * @param array $checks
   *   List of checks which will be added to. Checks will be later combined
   *   with an OR operation.
   */
  private static function getPhenologyChecks($rule, $ruleParams, array &$checks) {
    // Prepare operators according to the reverse_rule setting.
    $opStart = $rule->reverse_rule === 't' ? '>' : '<';
    $opEnd = $rule->reverse_rule === 't' ? '<' : '>';
    // Checks based on day in the year 1 to 365.
    if (!empty($ruleParams->start_day_of_year)) {
      $checks[] = "Integer.parseInt(ctx._source.event.day_of_year) $opStart $ruleParams->start_day_of_year";
    }
    if (!empty($ruleParams->end_day_of_year)) {
      $checks[] = "Integer.parseInt(ctx._source.event.day_of_year) $opEnd $ruleParams->end_day_of_year";
    }
    // Checks based on month 1-12.
    if (!empty($ruleParams->start_month)) {
      $checks[] = "Integer.parseInt(ctx._source.event.month) $opStart $ruleParams->start_month";
    }
    if (!empty($ruleParams->end_month)) {
      $checks[] = "Integer.parseInt(ctx._source.event.month) $opEnd $ruleParams->end_month";
    }
  }

}
