<?php

/**
 * @file
 * A standard database fixture definition.
 *
 * The core fixture sets up the Indicia database with a consistent set of
 * test data in the core tables.
 * Id values in tables having sequences are never supplied so that, if a test
 * adds a record to a table, the sequence will supply it the next valid id.
 */

$core_fixture = [
  "websites" => [
    [
      "title" => "Test website",
      "description" => "Website for unit testing",
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
      "url" => "http:,//www.indicia.org.uk",
      "password" => "password",
      "verification_checks_enabled" => 'true',
    ],
  ],
  "users_websites" => [
    [
      "user_id" => 1,
      "website_id" => 1,
      "site_role_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
  ],
  "surveys" => [
    [
      "title" => "Test survey",
      "description" => "Survey for unit testing",
      "website_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
    [
      "title" => "Test survey 2",
      "description" => "Additional survey for unit testing",
      "website_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
  ],
  "taxon_meanings" => [
    [
    // No support for INSERT INTO table DEFAULT VALUES.
    // Use high id values to avoid conflict with any values created by sequence
    // during testing.
      "id" => 10000,
    ],
    [
      "id" => 10001,
    ],
  ],
  "taxon_groups" => [
    [
      "title" => "Test taxon group",
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
  ],
  "taxon_ranks" => [
    [
      "rank" => "Genus",
      "short_name" => "Genus",
      "italicise_taxon" => "false",
      "sort_order" => 290,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
    [
      "rank" => "Species",
      "short_name" => "Species",
      "italicise_taxon" => "true",
      "sort_order" => 300,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
  ],
  "taxon_lists" => [
    [
      "title" => "Test taxon list",
      "description" => "Taxon list for unit testing",
      "website_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
  ],
  "taxa" => [
    [
      "taxon" => "Test taxon",
      "taxon_group_id" => 1,
      "language_id" => 2,
      "external_key" => "TESTKEY",
      "taxon_rank_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
    [
      "taxon" => "Test taxon 2",
      "taxon_group_id" => 1,
      "language_id" => 2,
      "external_key" => "TESTKEY2",
      "taxon_rank_id" => 2,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
  ],
  "taxa_taxon_lists" => [
    [
      "taxon_list_id" => 1,
      "taxon_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "taxon_meaning_id" => 10000,
      "taxonomic_sort_order" => 1,
      "preferred" => "true",
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
    [
      "taxon_list_id" => 1,
      "taxon_id" => 2,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "taxon_meaning_id" => 10001,
      "taxonomic_sort_order" => 1,
      "preferred" => "true",
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
  ],
  "cache_taxa_taxon_lists" => [
    [
      "id" => 1,
      "preferred" => TRUE,
      "taxon_list_id" => 1,
      "taxon_list_title" => "Test taxa taxon list",
      "website_id" => 1,
      "preferred_taxa_taxon_list_id" => 1,
      "taxonomic_sort_order" => 1,
      "taxon" => "Test taxon",
      "language_iso" => "lat",
      "language" => "Latin",
      "preferred_taxon" => "Test taxon",
      "preferred_language_iso" => "lat",
      "preferred_language" => "Latin",
      "external_key" => "TESTKEY",
      "taxon_rank" => "Genus",
      "taxon_rank_sort_order" => "290",
      "taxon_meaning_id" => 10000,
      "taxon_group_id" => 1,
      "taxon_group" => "Test taxon group",
      "cache_created_on" => "2016-07-22 16:00:00",
      "cache_updated_on" => "2016-07-22 16:00:00",
    ],
    [
      "id" => 2,
      "preferred" => TRUE,
      "taxon_list_id" => 1,
      "taxon_list_title" => "Test taxa taxon list 2",
      "website_id" => 1,
      "preferred_taxa_taxon_list_id" => 2,
      "taxonomic_sort_order" => 2,
      "taxon" => "Test taxon 2",
      "language_iso" => "lat",
      "language" => "Latin",
      "preferred_taxon" => "Test taxon",
      "preferred_language_iso" => "lat",
      "preferred_language" => "Latin",
      "external_key" => "TESTKEY2",
      "taxon_rank" => "Species",
      "taxon_rank_sort_order" => "300",
      "taxon_meaning_id" => 10001,
      "taxon_group_id" => 1,
      "taxon_group" => "Test taxon group",
      "cache_created_on" => "2016-07-22 16:00:00",
      "cache_updated_on" => "2016-07-22 16:00:00",
    ],
  ],
  "cache_taxon_searchterms" => [
    [
      "id" => 1,
      "taxa_taxon_list_id" => 1,
      "taxon_list_id" => 1,
      "searchterm" => "testtaxon",
      "original" => "Test taxon",
      "taxon_group" => "Test taxon group",
      "taxon_meaning_id" => 10000,
      "preferred_taxon" => "Test taxon",
      "default_common_name" => "Test taxon",
      "language_iso" => "lat",
      "name_type" => "L",
      "simplified" => "t",
      "taxon_group_id" => 1,
      "preferred" => "t",
      "searchterm_length" => 9,
      "preferred_taxa_taxon_list_id" => 1,
      "external_key" => "TESTKEY",
      "taxon_rank_sort_order" => "290",
    ],
    [
      "id" => 2,
      "taxa_taxon_list_id" => 1,
      "taxon_list_id" => 1,
      "searchterm" => "Test taxon",
      "original" => "Test taxon",
      "taxon_group" => "Test taxon group",
      "taxon_meaning_id" => 10000,
      "preferred_taxon" => "Test taxon",
      "default_common_name" => "Test taxon",
      "language_iso" => "lat",
      "name_type" => "L",
      "simplified" => "f",
      "taxon_group_id" => 1,
      "preferred" => "t",
      "searchterm_length" => 10,
      "preferred_taxa_taxon_list_id" => 1,
      "external_key" => "TESTKEY",
      "taxon_rank_sort_order" => "290",
    ],
    [
      "id" => 3,
      "taxa_taxon_list_id" => 2,
      "taxon_list_id" => 1,
      "searchterm" => "testtaxon2",
      "original" => "Test taxon 2",
      "taxon_group" => "Test taxon group",
      "taxon_meaning_id" => 10001,
      "preferred_taxon" => "Test taxon 2",
      "default_common_name" => "Test taxon 2",
      "language_iso" => "lat",
      "name_type" => "L",
      "simplified" => "t",
      "taxon_group_id" => 1,
      "preferred" => "t",
      "searchterm_length" => 10,
      "preferred_taxa_taxon_list_id" => 1,
      "external_key" => "TESTKEY2",
      "taxon_rank_sort_order" => "300",
    ],
    [
      "id" => 4,
      "taxa_taxon_list_id" => 2,
      "taxon_list_id" => 1,
      "searchterm" => "Test taxon 2",
      "original" => "Test taxon 2",
      "taxon_group" => "Test taxon group",
      "taxon_meaning_id" => 10001,
      "preferred_taxon" => "Test taxon 2",
      "default_common_name" => "Test taxon 2",
      "language_iso" => "lat",
      "name_type" => "L",
      "simplified" => "f",
      "taxon_group_id" => 1,
      "preferred" => "t",
      "searchterm_length" => 12,
      "preferred_taxa_taxon_list_id" => 1,
      "external_key" => "TESTKEY2",
      "taxon_rank_sort_order" => "300",
    ],
  ],
  "meanings" => [
    // No support for INSERT INTO table DEFAULT VALUES.
    // Use high id values to avoid conflict with any values created by sequence
    // during testing.
    [
      "id" => 10000,
    ],
    [
      "id" => 10001,
    ],
    [
      "id" => 10002,
    ],
    [
      "id" => 10003,
    ],
    [
      "id" => 10004,
    ],
    [
      "id" => 10005,
    ],
    [
      "id" => 10006,
    ],
    [
      "id" => 10007,
    ],
  ],
  "termlists" => [
    [
      "title" => "Test term list",
      "description" => "Term list list for unit testing",
      "website_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
      "external_key" => "TESTKEY",
    ],
    [
      "title" => "Location types",
      "description" => "Term list for location types",
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
      "external_key" => "indicia:location_types",
    ],
    [
      "title" => "Sample methods",
      "description" => "Term list for sample methods",
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
      "external_key" => "indicia:sample_methods",
    ],
    [
      "title" => "User identifier types",
      "description" => "Term list for user identifier types",
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
      "external_key" => "indicia:user_identifier_types",
    ],
    [
      "title" => "Group types",
      "description" => "Term list for group types",
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
      "external_key" => "indicia:group_types",
    ],
    [
      "title" => "Media types",
      "description" => "Term list for media types",
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
      "external_key" => "indicia:media_types",
    ],
    [
      "title" => "Media classifiers",
      "description" => "List of media/image classification services used to identify photos.",
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
      "external_key" => "indicia:classifiers",
    ],
  ],

  "terms" => [
    [
      "term" => "Test term",
      "language_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
    [
      "term" => "Test location type",
      "language_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
    [
      "term" => "Test sample method",
      "language_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
    [
      "term" => "email",
      "language_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
    [
      "term" => "twitter",
      "language_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
    [
      "term" => "Test group type",
      "language_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
    [
      "term" => "Image:Local",
      "language_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
    [
      "term" => "Unknown",
      "language_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
  ],
  "termlists_terms" => [
    [
      // Test term list.
      "termlist_id" => 1,
      // Test term.
      "term_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
      "meaning_id" => 10000,
      "preferred" => "true",
      "sort_order" => 1,
    ],
    [
      // Location types.
      "termlist_id" => 2,
      // Test location type.
      "term_id" => 2,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
      "meaning_id" => 10001,
      "preferred" => "true",
      "sort_order" => 1,
    ],
    [
      // Sample methods.
      "termlist_id" => 3,
      // Test sample method.
      "term_id" => 3,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
      "meaning_id" => 10002,
      "preferred" => "true",
      "sort_order" => 1,
    ],
    [
      // User identifier types.
      "termlist_id" => 4,
      // Email.
      "term_id" => 4,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
      "meaning_id" => 10003,
      "preferred" => "true",
      "sort_order" => 1,
    ],
    [
      // User identifier types.
      "termlist_id" => 4,
      // Twitter.
      "term_id" => 5,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
      "meaning_id" => 10004,
      "preferred" => "true",
      "sort_order" => 2,
    ],
    [
      // Group types.
      "termlist_id" => 5,
      // Test group type.
      "term_id" => 6,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
      "meaning_id" => 10005,
      "preferred" => "true",
      "sort_order" => 2,
    ],
    [
      // Media types.
      "termlist_id" => 6,
      // Image:Local.
      "term_id" => 7,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
      "meaning_id" => 10005,
      "preferred" => "true",
      "sort_order" => 1,
    ],
    [
      // Classifiers.
      "termlist_id" => 7,
      // Unknown.
      "term_id" => 8,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
      "meaning_id" => 10007,
      "preferred" => "true",
      "sort_order" => 1,
    ],
  ],
  "cache_termlists_terms" => [
    [
      "id" => 1,
      "preferred" => "true",
      "termlist_id" => 1,
      "termlist_title" => "Test term list",
      "website_id" => 1,
      "preferred_termlists_term_id" => 1,
      "sort_order" => 1,
      "term" => "Test term",
      "language_iso" => "eng",
      "language" => "English",
      "preferred_term" => "Test term",
      "preferred_language_iso" => "eng",
      "preferred_language" => "English",
      "meaning_id" => 10000,
      "cache_created_on" => "2016-07-22 16:00:00",
      "cache_updated_on" => "2016-07-22 16:00:00",
    ],
    [
      "id" => 2,
      "preferred" => "true",
      "termlist_id" => 2,
      "termlist_title" => "Location types",
      "website_id" => 1,
      "preferred_termlists_term_id" => 2,
      "sort_order" => 1,
      "term" => "Test location type",
      "language_iso" => "eng",
      "language" => "English",
      "preferred_term" => "Test location type",
      "preferred_language_iso" => "eng",
      "preferred_language" => "English",
      "meaning_id" => 10001,
      "cache_created_on" => "2016-07-22 16:00:00",
      "cache_updated_on" => "2016-07-22 16:00:00",
    ],
    [
      "id" => 3,
      "preferred" => "true",
      "termlist_id" => 3,
      "termlist_title" => "Sample methods",
      "website_id" => 1,
      "preferred_termlists_term_id" => 3,
      "sort_order" => 1,
      "term" => "Test sample method",
      "language_iso" => "eng",
      "language" => "English",
      "preferred_term" => "Test term",
      "preferred_language_iso" => "eng",
      "preferred_language" => "English",
      "meaning_id" => 10002,
      "cache_created_on" => "2016-07-22 16:00:00",
      "cache_updated_on" => "2016-07-22 16:00:00",
    ],
    [
      "id" => 4,
      "preferred" => "true",
      "termlist_id" => 4,
      "termlist_title" => "User identifier types",
      "website_id" => 1,
      "preferred_termlists_term_id" => 4,
      "sort_order" => 1,
      "term" => "email",
      "language_iso" => "eng",
      "language" => "English",
      "preferred_term" => "email",
      "preferred_language_iso" => "eng",
      "preferred_language" => "English",
      "meaning_id" => 10003,
      "cache_created_on" => "2016-07-22 16:00:00",
      "cache_updated_on" => "2016-07-22 16:00:00",
    ],
    [
      "id" => 5,
      "preferred" => "true",
      "termlist_id" => 4,
      "termlist_title" => "User identifier types",
      "website_id" => 1,
      "preferred_termlists_term_id" => 5,
      "sort_order" => 2,
      "term" => "twitter",
      "language_iso" => "eng",
      "language" => "English",
      "preferred_term" => "twitter",
      "preferred_language_iso" => "eng",
      "preferred_language" => "English",
      "meaning_id" => 10004,
      "cache_created_on" => "2016-07-22 16:00:00",
      "cache_updated_on" => "2016-07-22 16:00:00",
    ],
    [
      "id" => 6,
      "preferred" => "true",
      "termlist_id" => 5,
      "termlist_title" => "Group types",
      "website_id" => 1,
      "preferred_termlists_term_id" => 6,
      "sort_order" => 1,
      "term" => "Test group type",
      "language_iso" => "eng",
      "language" => "English",
      "preferred_term" => "Test group type",
      "preferred_language_iso" => "eng",
      "preferred_language" => "English",
      "meaning_id" => 10005,
      "cache_created_on" => "2016-07-22 16:00:00",
      "cache_updated_on" => "2016-07-22 16:00:00",
    ],
    [
      "id" => 7,
      "preferred" => "true",
      "termlist_id" => 6,
      "termlist_title" => "Media types",
      "website_id" => 1,
      "preferred_termlists_term_id" => 7,
      "sort_order" => 1,
      "term" => "Image:Local",
      "language_iso" => "eng",
      "language" => "English",
      "preferred_term" => "Image:Local",
      "preferred_language_iso" => "eng",
      "preferred_language" => "English",
      "meaning_id" => 10006,
      "cache_created_on" => "2016-07-22 16:00:00",
      "cache_updated_on" => "2016-07-22 16:00:00",
    ],
  ],
  "samples" => [
    [
      "survey_id" => 1,
      "date_start" => "2016-07-22",
      "date_end" => "2016-07-22",
      "date_type" => "D",
      "entered_sref" => "SU01",
      "entered_sref_system" => "OSGB",
      "comment" => "Sample for unit testing with a \" double quote",
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
      "recorder_names" => "PHPUnit",
      "record_status" => "C",
    ],
    [
      "survey_id" => 1,
      "date_start" => "2016-07-22",
      "date_end" => "2016-07-22",
      "date_type" => "D",
      "entered_sref" => "SU01",
      "entered_sref_system" => "OSGB",
      "comment" => "Sample for unit testing with a \nline break",
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
      "recorder_names" => "PHPUnit",
      "record_status" => "C",
    ],
  ],
  "map_squares" => [
    [
      "geom" => "010300002031BF0D0001000000050000004E9C282E3C320BC18FC3A8120A2F59412CCEAACFA94309C1E75E7B57062F5941A7DE4D3BB84209C11FD1A351893E5941BBB729FE3E320BC1D8E4B8118D3E59414E9C282E3C320BC18FC3A8120A2F5941",
      "x" => -214871,
      "y" => 6609705,
      "size" => 10000,
    ],
  ],
  "occurrences" => [
    [
      "sample_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
      "website_id" => 1,
      "comment" => "Occurrence for unit testing",
      "taxa_taxon_list_id" => 1,
      "record_status" => "C",
      "release_status" => "R",
      "confidential" => "f",
    ],
    [
      "sample_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
      "website_id" => 1,
      "comment" => "Confidential occurrence for unit testing",
      "taxa_taxon_list_id" => 1,
      "record_status" => "C",
      "release_status" => "R",
      "confidential" => "t",
    ],
  ],
  "cache_occurrences_functional" => [
    [
      "id" => 1,
      "sample_id" => 1,
      "website_id" => 1,
      "survey_id" => 1,
      "date_start" => "2016-07-22",
      "date_end" => "2016-07-22",
      "date_type" => "D",
      "created_on" => "2016-07-22 16:00:00",
      "updated_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "taxa_taxon_list_id" => 1,
      "preferred_taxa_taxon_list_id" => 1,
      "taxon_meaning_id" => 10000,
      "taxa_taxon_list_external_key" => "TESTKEY",
      "taxon_group_id" => 1,
      "record_status" => "C",
      "release_status" => "R",
      "zero_abundance" => "f",
      "confidential" => "f",
      "map_sq_1km_id" => 1,
      "map_sq_2km_id" => 1,
      "map_sq_10km_id" => 1,
      "verification_checks_enabled" => "true",
    ],
    [
      "id" => 2,
      "sample_id" => 1,
      "website_id" => 1,
      "survey_id" => 1,
      "date_start" => "2016-07-22",
      "date_end" => "2016-07-22",
      "date_type" => "D",
      "created_on" => "2016-07-22 16:00:00",
      "updated_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "taxa_taxon_list_id" => 1,
      "preferred_taxa_taxon_list_id" => 1,
      "taxon_meaning_id" => 10000,
      "taxa_taxon_list_external_key" => "TESTKEY",
      "taxon_group_id" => 1,
      "record_status" => "C",
      "release_status" => "R",
      "zero_abundance" => "f",
      "confidential" => "t",
      "map_sq_1km_id" => 1,
      "map_sq_2km_id" => 1,
      "map_sq_10km_id" => 1,
      "verification_checks_enabled" => "true",
    ],
  ],
  "cache_occurrences_nonfunctional" => [
    [
      "id" => 1,
    ],
    [
      "id" => 2,
    ],
  ],
  "cache_samples_functional" => [
    [
      "id" => 1,
      "website_id" => 1,
      "survey_id" => 1,
      "date_start" => "2016-07-22",
      "date_end" => "2016-07-22",
      "date_type" => "D",
      "created_on" => "2016-07-22 16:00:00",
      "updated_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "record_status" => "C",
      "map_sq_1km_id" => 1,
      "map_sq_2km_id" => 1,
      "map_sq_10km_id" => 1,
    ],
  ],
  "cache_samples_nonfunctional" => [
    [
      "id" => 1,
      "website_title" => "Test website",
      "survey_title" => "Test survey",
      "public_entered_sref" => "SU01",
      "entered_sref_system" => "OSGB",
      "recorders" => "PHPUnit",
    ],
  ],
  "sample_attributes" => [
    [
      "caption" => "Altitude",
      "data_type" => "I",
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
      "public" => "false",
    ],
  ],
  "sample_attributes_websites" => [
    [
      "website_id" => 1,
      "sample_attribute_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "restrict_to_survey_id" => 1,
    ],
  ],
  "occurrence_attributes" => [
    [
      "caption" => "Identified_by",
      "data_type" => "T",
      "public" => "false",
      "system_function" => "det_full_name",
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
  ],
  "occurrence_attributes_websites" => [
    [
      "website_id" => 1,
      "occurrence_attribute_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "restrict_to_survey_id" => 1,
    ],
  ],
  "locations" => [
    [
      "name" => "Test location",
      "centroid_sref" => "SU01",
      "centroid_sref_system" => "OSGB",
      "location_type_id" => "2",
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
      "public" => "true",
    ],
  ],
  "locations_websites" => [
    [
      "location_id" => 1,
      "website_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
  ],
  "location_attributes" => [
    [
      "caption" => "Test text",
      "data_type" => "T",
      "public" => "false",
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
    [
      "caption" => "Test lookup",
      "data_type" => "L",
      "termlist_id" => 1,
      "public" => "false",
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
    [
      "caption" => "Test integer",
      "data_type" => "I",
      "termlist_id" => 1,
      "public" => "false",
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
  ],
  "location_attributes_websites" => [
    [
      // Test website.
      "website_id" => 1,
      // Test text.
      "location_attribute_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
    ],
    [
      // Test website.
      "website_id" => 1,
      // Test lookup.
      "location_attribute_id" => 2,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
    ],
    [
      // Test website.
      "website_id" => 1,
      // Test integer.
      "location_attribute_id" => 3,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
    ],
  ],
  "location_attribute_values" => [
    [
      // Test location.
      "location_id" => 1,
      // Test lookup.
      "location_attribute_id" => 2,
      // Test term.
      "int_value" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
  ],
  "groups" => [
    [
      "title" => "public group 1",
      "website_id" => 1,
      "group_type_id" => 6,
      "joining_method" => "P",
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
    [
      "title" => "private group 1",
      "website_id" => 1,
      "group_type_id" => 6,
      "joining_method" => "A",
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
  ],
  "groups_locations" => [
    [
      "group_id" => 1,
      "location_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
    ],
  ],
  "group_pages" => [
    [
      "group_id" => 1,
      "caption" => "Enter a list of records",
      "path" => "record/list",
      "administrator" => NULL,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
  ],
];
