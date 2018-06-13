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
use TYPO3\CMS\Core\Database\ConnectionPool;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Fab\Vidi\QueryBuilder\Parser\QueryParser;

/**
 * Main script class for saving query
 */
class QuerybuilderController
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_vidi_querybuilder');
        $uid = (int)$requestParams['uid'];
        if ((int)$requestParams['override'] === 1 && $uid > 0) {
            $result->uid = $uid;
            $queryBuilder->update('tx_vidi_querybuilder')
                ->set('where_parts', $requestParams['query'])
                ->set('queryname', $requestParams['queryName'])
                ->where($queryBuilder->expr()->eq('uid', $uid))
                ->andWhere($queryBuilder->expr()->eq('user', (int)$GLOBALS['BE_USER']->user['uid']))
                ->execute();
        } else {
            $data = [
                'where_parts' => $requestParams['query'],
                'user' => (int)$GLOBALS['BE_USER']->user['uid'],
                'affected_table' => $requestParams['table'],
                'queryname' => $requestParams['queryName'],
            ];
            $queryBuilder->insert('tx_vidi_querybuilder')
                ->values($data)
                ->execute();
            $uid = $queryBuilder->getConnection()->lastInsertId('tx_vidi_querybuilder');
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
    public function ajaxGetRecentQueries(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface
    {
        $requestParams = $request->getQueryParams();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_vidi_querybuilder');

        $results = $queryBuilder
            ->select('uid','queryname', 'where_parts')
            ->from('tx_vidi_querybuilder')
            ->where(
                $queryBuilder->expr()->eq('affected_table', $queryBuilder->createNamedParameter($requestParams['table'])),
                $queryBuilder->expr()->eq('user', (int)$GLOBALS['BE_USER']->user['uid'])
            )
            ->execute()
            ->fetchAll();

        $response->getBody()->write(json_encode($results));

        return $response;
    }

    /**
     * Returns parsed query
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function ajaxParseQuery(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface
    {
        $requestParams = $request->getQueryParams();
        $table = $requestParams['table'];
        $query = json_decode($requestParams['query']);

        if ($query && $table) {
            $parsedQuery = GeneralUtility::makeInstance(QueryParser::class)->parse($query, $table);
        } else {
            $parsedQuery = null;
        }

        $response->getBody()->write(json_encode($parsedQuery));
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
}

