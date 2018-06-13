<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['vidi']);

if (false === isset($configuration['autoload_typoscript']) || true === (bool)$configuration['autoload_typoscript']) {

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        'vidi',
        'constants',
        '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:vidi/Configuration/TypoScript/constants.txt">'
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        'vidi',
        'setup',
        '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:vidi/Configuration/TypoScript/setup.txt">'
    );
}

// Configure commands that can be run from the cli_dispatch.phpsh script.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'Fab\Vidi\Command\VidiCommandController';

// Initialize generic Vidi modules after the TCA is loaded.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['extTablesInclusion-PostProcessing'][] = 'Fab\Vidi\Configuration\VidiModulesAspect';

// Initialize generic grid TCA for all data types
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['extTablesInclusion-PostProcessing'][] = 'Fab\Vidi\Configuration\TcaGridAspect';

// cache configuration, see https://docs.typo3.org/typo3cms/CoreApiReference/ApiOverview/CachingFramework/Configuration/Index.html#cache-configurations
$TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['vidi']['frontend'] = \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class;
$TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['vidi']['groups'] = array('all', 'vidi');
$TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['vidi']['options']['defaultLifetime'] = 2592000;

// QueryBuilder related configuration

// PageRenderer Hook to add QueryBuilder CSS and JS modules
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'][] =
    \Fab\Vidi\Hooks\PageRenderer::class . '->renderPreProcess';


// Create DataProviderGroup
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaOnly'] = array(
    \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\InlineOverrideChildTca::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\ParentPageTca::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\InlineOverrideChildTca::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordOverrideValues::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class,
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordOverrideValues::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseLanguageRows::class,
            // As the ctrl.type can hold a nested key we need to resolve all relations
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfigMerged::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class,
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsOverrides::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsOverrides::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessCommon::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessRecordTitle::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessCommon::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessPlaceholders::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessRecordTitle::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessShowitem::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class,
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessPlaceholders::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessCommon::class,
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessRecordTitle::class,
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessPlaceholders::class,
            \TYPO3\CMS\Backend\Form\FormDataProvider\InlineOverrideChildTca::class,
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessShowitem::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaTypesShowitem::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue::class,
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows::class,
            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldLabels::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaTypesShowitem::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
            \TYPO3\CMS\Backend\Form\FormDataProvider\UserTsConfig::class,
            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfigMerged::class,
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class,
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldLabels::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCheckboxItems::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageRootline::class,
            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfigMerged::class,
            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaTypesShowitem::class,
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class,
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCheckboxItems::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInline::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInputPlaceholders::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineIsOnSymmetricSide::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInputPlaceholders::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRecordTitle::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInline::class,
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineIsOnSymmetricSide::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordOverrideValues::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class,
        ),
    ),
    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class => array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class,
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDateTimeFields::class,
        ),
    ),
);