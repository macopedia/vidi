<?php

/**
 * Definitions for routes provided by EXT:querybuilder
 * Contains all AJAX-based routes for entry points
 *
 * Currently the "access" property is only used so no token creation + validation is made
 * but will be extended further.
 */
return [
    // Save query
    'querybuilder_save_query' => [
        'path' => '/querybuilder/query/save',
        'target' => Fab\Vidi\Controller\QueryBuilderController::class . '::ajaxSaveQuery'
    ],
    // Delete query
    'querybuilder_delete_query' => [
        'path' => '/querybuilder/query/delete',
        'target' => Fab\Vidi\Controller\QueryBuilderController::class . '::ajaxDeleteQuery'
    ],
    // Get recent queries
    'querybuilder_get_recent_queries' => [
        'path' => '/querybuilder/query/get',
        'target' => Fab\Vidi\Controller\QueryBuilderController::class . '::ajaxGetRecentQueries'
    ],
    // Get parsed query
    'querybuilder_get_location_coordinates' => [
        'path' => '/querybuilder/location/get/coordinates',
        'target' => Fab\Vidi\Controller\QueryBuilderController::class . '::ajaxGetAddressCoordinates'
    ],
];
