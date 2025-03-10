<?php

/**
 * @file
 * Example configuration file for REST API.
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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

/**
 * Define the database ID used to identify this system in the network.
 */
$config['user_id'] = 'BRC';

/**
 * Dataset name attribute ID.
 *
 * Which sample attribute will we use to store the dataset name for records
 * which came from remote systems?
 */
$config['dataset_name_attr_id'] = 99;

/**
 * Authentication methods allowed.
 *
 * Default options exclude direct passing of id and password which should be
 * enabled on development servers only.
 * * jwtUser - fora authenticating warehouse user accounts to access their
 *   own records via a JWT access token.
 * * hmacClient - authorise a client in the list below using HMAC in the http
 *   header
 * * hmacWebsite - authorise as a website registered on the warehouse using
 *   HMAC in the http header
 * * directUser - allow the user ID and password to be passed directly.
 * * directClient - allow the client system ID and shared secret to be passed
 *   directly.
 * * directWebsite - allow the website ID and password to be passed directly.
 *
 * Note that hmacUser is not supported as the password is hashed on the server
 * so a hmac cannot be generated. Each key points to an array of options:
 * * allow_http - this must be set if access over http (rather than https) is
 *   going to be enabled. Use with caution in production environments.
 * * resource_options - pass the name of a resource (e.g. reports) and an array
 *   of flags to pass to the resource. Flags depend on the resource.
 */
$config['authentication_methods'] = [
  'hmacClient' => [
    // HMAC is a bit safer over https as the authentication secrets are never
    // shared. There are still implications for the data itself though.
    'allow_http' => TRUE,
    'resource_options' => [
      // Grants full access to all reports. Client configs can override this.
      'reports' => [],
    ],
  ],
  'hmacWebsite' => [
    'allow_http' => TRUE,
    'resource_options' => [
      // Featured reports with cached summary data only - highly restricted.
      'reports' => ['featured' => TRUE, 'summary' => TRUE, 'cached' => TRUE],
    ],
  ],
  'directClient' => [
    'resource_options' => [
      // Grants full access to all reports. Client configs can override this.
      'reports' => [],
      // Grant access to elasticsearch via the listed endpoints. Either a
      // simple array of endpoint names, or a associative array keyed by name
      // containing config in the values. Set config option limit_to_website
      // to TRUE to limit to data accessible to this website. Set
      // limit_to_own_data to TRUE to restrict to the user's own data. Each
      // endpoint needs to be added to the 'elasticsearch' configuration entry
      // to define how it maps to Elasticsearch. If allowing directClient
      // authentication, either configure the clients which can access each
      // index in the clients config entry in /modules/rest_api/config/rest.php,
      // or in the rest_api_clients and rest_api_client_connections tables
      // using the warehouse UI.
      'elasticsearch' => ['es'],
    ],
  ],
  'jwtClient' => [
    // TRUE to allow CORS from any domain, or provide an array of domain regexes.
    // OPTIONS requests always return CORS header unless this is FALSE which
    // completely disables the headers (assuming CORS is not allowed, or is
    // handled at the web-server level).
    'allow_cors' => TRUE,
    'resource_options' => [
      'reports' => [
        'authorise' => [
          // In this example, restricted reports are authorised, other
          // non-restricted reports are still available.
          'library/occurrences/list_for_elastic_all.xml',
          'library/occurrences/list_for_elastic_sensitve_all.xml',
        ],
      ],
      // Grant access to elasticsearch via the listed endpoints. Either a
      // simple array of endpoint names, or a associative array keyed by name
      // containing config in the values. Set config option limit_to_website
      // to TRUE to limit to data accessible to this website. Set
      // limit_to_own_data to TRUE to restrict to the user's own data. Each
      // endpoint needs to be added to the 'elasticsearch' configuration entry
      // to define how it maps to Elasticsearch. If allowing jwtClient
      // authentication, either configure the clients which can access each
      // index in the clients config entry in /modules/rest_api/config/rest.php,
      // or in the rest_api_clients and rest_api_client_connections tables
      // using the warehouse UI.
      'elasticsearch' => [
        'es' => [],
      ],
    ],
  ],
  'jwtUser' => [
    // TRUE to allow CORS from any domain, or provide an array of domain regexes.
    // OPTIONS requests always return CORS header unless this is FALSE which
    // completely disables the headers (assuming CORS is not allowed, or is
    // handled at the web-server level).
    'allow_cors' => TRUE,
    'resource_options' => [
      // Grants access to featured reports for own data. Client configs can
      // override this.
      'reports' => ['featured' => TRUE, 'limit_to_own_data' => TRUE],
      // Grant access to Elasticsearch but in this case, apply website and user
      // ID filters. Limit to own data can be bypassed by providing a token
      // without a http://indicia.org.uk/user:id claim only if the
      // allow_anonymous option is set to TRUE.
      // Best practice is to set both limit options to TRUE, then in the
      // Indicia settings enable the option to allow users to access all data
      // if appropriate for the website.
      'elasticsearch' => [
        'es' => [
          'limit_to_website' => TRUE,
          'limit_to_own_data' => TRUE,
          // Optional allow_anonymous setting - default is FALSE.
          'allow_anonymous' => FALSE,
        ],
      ],
    ],
  ],
];

/*
 * Should authorisation tokens be allowed in the query parameters rather than the
 * authorisation header? Recommended for development servers only.
 */
$config['allow_auth_tokens_in_url'] = FALSE;

/*
 * Specify Elasticsearch version. Important for version 6 as some code has to
 * be adapted.
 */
$config['elasticsearch_version'] = '8.6';

/*
 * If this warehouse is configured to work with an Elasticsearch instance then
 * the REST API can act as a proxy to avoid having to expose all the public
 * APIs. The proxy can point to index aliases to limit the search filter.
 */
$config['elasticsearch'] = [
  // Name of the end-point, e.g. /index.php/services/rest/es.
  'es' => [
    // Set open = TRUE if this end-point is available without authentication.
    'open' => FALSE,
    // Can be set to FALSE if this is a pre-filtered alias so proxy filtering
    // (website ID, blur etc) does not need to be applied. Default TRUE if not
    // specified.
    'apply_filters' => TRUE,
    // Optional type, either occurrence or sample. Default is occurrence if not
    // specified.
    'type' => 'occurrence',
    // Name of the elasticsearch index or alias this end-point points to.
    'index' => 'occurrence',
    // URL of the Elasticsearch index.
    'url' => 'http://my.elastic.url:9200',
    // If specified, limit the access to the following operations. List of
    // HTTP request types (e.g. get, post, put, delete) each containing a
    // list of regular expressions for allowed requests, along with the
    // description of what that allows.
    // So, this example allows the following call:
    // http://mywarehouse.com/index.php/services/rest/es/_search?q=taxon.name:quercus
    // which proxies to
    // http://my.elastic.url:9200/occurrence/_search?q=taxon.name:quercus
    'allowed' => [
      'get' => [
        '/^_search/' => 'GET requests to the search API (/_search?...)',
        '/^_mapping/' => 'GET requests to the mappings API (/_mapping?...)',
      ],
      'post' => [
        '/^_search/' => 'POST requests to the search API (/_search?...)',
        '/^doc\/.*\/_update/' => 'POSTed document updates',
        '/^_update_by_query/' => 'POSTed multi-document updates',
      ],
    ],
  ],
];

// The following configuration defines a list of clients for the REST API
// (other than the intrinsic website registrations and warehouse user clients).
// Each client has access to a number of projects which provide filtered access
// to the records of a given website registration.
// @todo Move this configuration into a database table.
$config['clients'] = [
  // Client list keyed by client ID.
  'BTO' => [
    'shared_secret' => 'password',
    'projects' => [
      // List of available projects keyed by project ID.
      'BTOSYNC' => [
        'id' => 'BTOSYNC',
        'website_id' => 2,
        'title' => 'iRecord avian records to BTO',
        'resources' => ['sync-taxon-observations', 'sync-annotations'],
        'description' => 'Bird records entered onto the BRC warehouse made available for verification on iRecord.',
        // Other paraneters available here will depend on the requested
        // resource. Some resources may support filter_id for example.
        'id_prefix' => 'iBRC',
        'dataset_id_attr_id' => 22,
        'blur' => 'F',
        // Define a filter for the observations available to this
        // project.
        'filter_id' => 123,
        // Define a filter for the annotations data. This should match the
        // location that the other server's observations are synced to using
        // the rest_api_sync module.
        'annotations_filter' => [
          'survey_id' => 10,
        ],
      ],
    ],
    'elasticsearch' => ['es'],
  ],
];
