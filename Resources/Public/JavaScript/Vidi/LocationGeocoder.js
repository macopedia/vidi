/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Module: Fab/Vidi/Vidi/LocationGeocoder
 */
define(['jquery',
    'TYPO3/CMS/Backend/Notification',
    'Fab/Vidi/Vidi/Session',
    'Fab/Vidi/Vidi/QueryBuilder',
], function ($, Notification) {
    'use strict';

    var LocationGeocoder = {
        selectorLocationGeocoderContainer: '.t3js-location-geocoder-container',
        selectorLocationGeocoder: '.t3js-location-geocoder',
        template: '<div class="t3js-location-geocoder">' +
            '<div class="location-geocoder__headline">' +
                (TYPO3.lang['location.geocoder.headline'] || 'Find objects within a radius of X kilometers from the location') +
            '</div>' +
            '<div class="row">' +
                '<div class="col-xs-12 col-sm-8 col-md-4 col-lg-3 location-geocoder__address">'+
                    '<input name="address" class="form-control" placeholder="' + (TYPO3.lang['location.geocoder.placeholder.address'] || 'address') + '">' +
                    '<i class="fa fa-check-circle-o location-indicator location-indicator--found" aria-hidden="true"></i>' +
                '</div>' +
                '<div class="col-xs-12 col-sm-2 col-md-2 col-lg-1 location-geocoder__radius">' +
                    '<div class="input-group">' +
                        '<input name="radius" value="1" type="number" step="1" min="1" max="100" class="form-control" placeholder="' + (TYPO3.lang['location.geocoder.placeholder.radius'] || 'distance') + '"><span class="input-group-addon">km</span>' +
                    '</div>' +
                '</div>' +
                '<div class="col-xs-12 col-md-6 col-lg-8 location-geocoder__buttons">' +
                    '<button data-action="find" class="btn btn-default btn__find" disabled>' +
                        '<span class="state--normal">' + (TYPO3.lang['location.geocoder.button.findLocation'] || 'Find location') + '</span>' +
                        '<span class="state--searching">' + (TYPO3.lang['location.geocoder.button.searching'] || 'Searching') + ' <i class="fa fa-spinner fa-spin"></i></span>' +
                    '</button>' +
                    '<button data-action="clear" class="btn btn-link" disabled>' + (TYPO3.lang['location.geocoder.button.clear'] || 'Clear') + '</button>' +
                '</div>' +
            '</div>' +
            '</div>',
        locationData: {
            address: null,
            radius: 1,
            lat: null,
            lng: null
        }
    };

    /**
     * Initialize method
     */
    LocationGeocoder.initialize = function () {
        $(LocationGeocoder.template).appendTo(LocationGeocoder.selectorLocationGeocoderContainer);
        LocationGeocoder.initializeFilters();
    };

    /**
     * Initialize filters
     */
    LocationGeocoder.initializeFilters = function () {
        var $locationGeocoder = $(LocationGeocoder.selectorLocationGeocoder);
        var $inputAddress = $($locationGeocoder).find('input[name="address"]');
        var $inputRadius = $($locationGeocoder).find('input[name="radius"]');
        var $buttonFind = $($locationGeocoder).find('button[data-action="find"]');
        var $buttonClear = $($locationGeocoder).find('button[data-action="clear"]');
        var $locationIndicator = $($locationGeocoder).find('.location-indicator');

        $inputAddress.on('change paste keyup', function () {
            var addressInputValue = $(this).val();
            var shouldButtonBeDisabled = (!addressInputValue || /^\s*$/.test(addressInputValue));
            $buttonFind.prop('disabled', shouldButtonBeDisabled);
        });

        $inputRadius.on('change blur', function () {
            var radiusInputValue = $(this).val();
            var coordinates = JSON.parse(Vidi.Session.get('coordinates'));

            if (coordinates !== null) {
             coordinates.radius = parseInt(radiusInputValue);
                Vidi.Session.set('coordinates', JSON.stringify(coordinates));
            }
        });

        $locationGeocoder.find('button').click(function () {
            var $button = $(this);
            var action = $button.data('action');

            switch (action) {
                case 'find':
                    var address = $inputAddress.val();

                    if (address !== undefined && address.length) {
                        $buttonFind.prop('disabled', true).addClass('btn--state');
                        $.ajax({
                            url: TYPO3.settings.ajaxUrls.querybuilder_get_location_coordinates,
                            cache: false,
                            data: {
                                address: address
                            },
                            success: function(data) {
                                if (data.status === 'ok') {
                                    // this data will be used by QueryBuilder in apply action
                                    LocationGeocoder.setLocationData({
                                        address: data.address,
                                        radius: parseInt($inputRadius.val()),
                                        lat: data.lat,
                                        lng: data.lng
                                    });

                                    $buttonClear.prop('disabled', false);
                                    $locationIndicator.show();
                                } else {
                                    LocationGeocoder.setLocationData({});
                                    Notification.warning(TYPO3.lang['location.geocoder.notification.geocodingCantFindAddress']
                                        || 'Geocoding service is ubale to find this address.');
                                }
                                $buttonFind.prop('disabled', false).removeClass('btn--state');
                            },
                            error: function(data) {
                                $buttonFind.prop('disabled', false).removeClass('btn--state');
                                console.log(data);
                            }
                        });
                    } else {
                    }
                    break;
                case 'clear':
                    LocationGeocoder.setLocationData({});
                    break;
            }
        });
    }

    /**
     * Sets location data in form fields
     * @param data
     */
    LocationGeocoder.setLocationData = function (data) {
        var $locationGeocoder = $(LocationGeocoder.selectorLocationGeocoder);

        var address = data.hasOwnProperty('address') ? data.address:'';
        var radius = data.hasOwnProperty('radius') ? parseInt(data.radius):1;
        var lat = data.hasOwnProperty('lat') ? parseFloat(data.lat):null;
        var lng = data.hasOwnProperty('lng') ? parseFloat(data.lng):null;

        $locationGeocoder.find('input[name="address"]').val(address);
        $locationGeocoder.find('input[name="radius"]').val(radius);

        LocationGeocoder.locationData = {
            address: address,
            radius: radius,
            lat: lat,
            lng: lng
        };

        Vidi.Session.set('coordinates', JSON.stringify(LocationGeocoder.locationData));

        // show indicator if we have locations's lat anf lng set
        if (LocationGeocoder.locationData.lat !== null || LocationGeocoder.locationData.lng !== null) {
            $locationGeocoder.find('.location-indicator').show();
            $locationGeocoder.find('button[data-action="find"]').prop('disabled', false);
            $locationGeocoder.find('button[data-action="clear"]').prop('disabled', false);
        } else {
            $locationGeocoder.find('.location-indicator').hide();
            $locationGeocoder.find('button[data-action="find"]').prop('disabled', true);
            $locationGeocoder.find('button[data-action="clear"]').prop('disabled', true);
        }
    }

    // Expose in Vidi object for compatibility reason.
    Vidi.LocationGeocoder = LocationGeocoder;
    return LocationGeocoder;
});
