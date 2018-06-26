<?php

namespace Fab\Vidi\Hooks;

use InvalidArgumentException;
use Fab\Vidi\QueryBuilder\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use UnexpectedValueException;

/**
 * Class PageRenderer
 *
 */
class PageRenderer
{
    /**
     * @param array $params
     *
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function renderPreProcess(array $params)
    {
        //@todo find some better way to determine if we are inside Vidi module context
        if (strpos(GeneralUtility::_GP('M'), 'content_Vidi') !== false) {
            $moduleLoader = GeneralUtility::makeInstance('Fab\Vidi\Module\ModuleLoader');
            $table = $moduleLoader->getDataType();
            $pageRenderer = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);

            $languageLabelFilePath = 'EXT:vidi/Resources/Private/Language/' . $GLOBALS['BE_USER']->uc['lang'] . '.querybuilder-js.xlf';
            if (file_exists(GeneralUtility::getFileAbsFileName($languageLabelFilePath))) {
                $pageRenderer->addInlineLanguageLabelFile('EXT:vidi/Resources/Private/Language/pl.querybuilder-js.xlf');
            } else {
                $pageRenderer->addInlineLanguageLabelFile('EXT:vidi/Resources/Private/Language/querybuilder-js.xlf');
            }
            //$pageRenderer->addCssFile('EXT:vidi/Resources/Public/Css/query-builder.default.css');
            //$pageRenderer->addCssFile('EXT:vidi/Resources/Public/Css/custom-query-builder.css');

           $pageRenderer->addRequireJsConfiguration([
                'paths' => [
                    'query-builder' => PathUtility::getAbsoluteWebPath('../typo3conf/ext/vidi/Resources/Public/JavaScript/QueryBuilder/query-builder.standalone'),
                    'query-builder/lang' => PathUtility::getAbsoluteWebPath('../typo3conf/ext/vidi/Resources/Public/JavaScript/QueryBuilder/Language'),
                ],
            ]);

            $languageModule = 'query-builder/lang/query-builder.en';
            $languageFile = 'EXT:vidi/Resources/Public/JavaScript/QueryBuilder/Language/query-builder.' . $GLOBALS['BE_USER']->uc['lang'] . '.js';
            if (file_exists(GeneralUtility::getFileAbsFileName($languageFile))) {
                $languageModule = 'query-builder/lang/query-builder.' . $GLOBALS['BE_USER']->uc['lang'];
            }

            //@todo this is not needed/used anymore as we do not pass query with url (view is reloaded with ajax call)
            $query = GeneralUtility::_GP('query');
            $query = json_decode($query);
            $pageRenderer->addJsInlineCode('tx_querybuilder_query', 'var tx_querybuilder_query = ' . json_encode($query) . ';');

            $queryBuilder = GeneralUtility::makeInstance(QueryBuilder::class);
            $filter = $queryBuilder->buildFilterFromTca($table);
            $pageRenderer->addJsInlineCode('tx_querybuilder_filter', 'var tx_querybuilder_filter = ' . json_encode($filter) . ';');

            $pageRenderer->loadRequireJsModule($languageModule);
            $pageRenderer->loadRequireJsModule('Fab/Vidi/Vidi/QueryBuilder', 'function(QueryBuilder) {
                QueryBuilder.initialize(tx_querybuilder_query, tx_querybuilder_filter);
            }');
        }
    }
}
