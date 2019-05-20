<?php
declare(strict_types=1);
namespace Fab\Vidi\Controller;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Main script class for saving query
 */
class QueryBuilderController
{
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     * @throws \InvalidArgumentException
     */
    public function ajaxSaveQuery(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface
    {
        $result = new \stdClass();
        $result->status = 'ok';

        $requestParams = $request->getQueryParams();

        $uid = (int)$requestParams['uid'];
        
        if ((int)$requestParams['override'] === 1 && $uid > 0) {
            $result->uid = $uid;

            $clause = '1 = 1';
            $clause .= ' AND uid=' . $uid;
            $clause .= ' AND user=' . (int)$GLOBALS['BE_USER']->user['uid'];

            $data = [
                'where_parts' => $requestParams['query'],
                'queryname' => $requestParams['queryName']
            ];

            $this->getDatabaseConnection()->exec_UPDATEquery('tx_vidi_querybuilder', $clause, $data);
        } else {
            $data = [
                'where_parts' => $requestParams['query'],
                'user' => (int)$GLOBALS['BE_USER']->user['uid'],
                'affected_table' => $requestParams['table'],
                'queryname' => $requestParams['queryName'],
            ];

            $this->getDatabaseConnection()->exec_INSERTquery('tx_vidi_querybuilder', $data);
            $uid = $this->getDatabaseConnection()->sql_insert_id();
            $result->uid = $uid;
        }

        $response->getBody()->write(json_encode($result));

        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     * @throws \InvalidArgumentException
     */
    public function ajaxDeleteQuery(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface
    {
        $result = new \stdClass();
        $result->status = 'ok';

        $requestParams = $request->getQueryParams();

        $uid = (int)$requestParams['uid'];

        $clause = '1 = 1';
        $clause .= ' AND uid=' . $uid;
        $clause .= ' AND user=' . (int)$GLOBALS['BE_USER']->user['uid'];

        $record = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('uid', 'tx_vidi_querybuilder', $clause);

        if ($record) {
            $this->getDatabaseConnection()->exec_DELETEquery('tx_vidi_querybuilder', 'uid = ' . (int)$record['uid']);
        } else {
            $result->status = 'fail';
            $result->error = 'Object does not exist, or You are ot owner of this object';
        }

        $response->getBody()->write(json_encode($result));

        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     * @throws \InvalidArgumentException
     */
    public function ajaxGetRecentQueries(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface
    {
        $requestParams = $request->getQueryParams();
        
        $clause = '1 = 1';
        $clause .= ' AND affected_table=' . $this->getDatabaseConnection()->fullQuoteStr($requestParams['table'], 'tx_vidi_querybuilder');
        $clause .= ' AND user=' . (int)$GLOBALS['BE_USER']->user['uid'];
       
        $results = $this->getDatabaseConnection()->exec_SELECTgetRows('uid,queryname,where_parts', 'tx_vidi_querybuilder', $clause);

        $response->getBody()->write(json_encode($results));

        return $response;
    }

    /**
     * Fetches address coordinates through Google Geocoder
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function ajaxGetAddressCoordinates(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface
    {
        $requestParams = $request->getQueryParams();
        $address = $requestParams['address'];
        $coordinates = false;

        if ($address) {
            // define hook that should be used to provide class/function that will be responsible for
            // address processing and returning coordinates
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['vidi']['geocodeAddressForCoordinates'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['vidi']['geocodeAddressForCoordinates'] as $classRef) {
                    $hookObj = GeneralUtility::getUserObj($classRef);
                    if (method_exists($hookObj, 'getCoordinatesFromAddress')) {
                        $coordinates = $hookObj->getCoordinatesFromAddress($address);
                    }
                }
            }
        }

        if ($coordinates === false) {
            $response->getBody()->write(json_encode(['status' => 'fail']));
        } else {
            $response->getBody()->write(json_encode($coordinates + ['status' => 'ok']));
        }

        return $response;
    }

    /**
     * Returns a pointer to the database.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}

