<?php
namespace TYPO3\CMS\Vidi\Tca;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Fabien Udriot <fabien.udriot@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * A class to handle TCA ctrl.
 */
class TcaServiceFactory implements \TYPO3\CMS\Core\SingletonInterface,
	\TYPO3\CMS\Vidi\Tca\TcaServiceInterface {

	/**
	 * @var array
	 */
	static protected $instanceStorage;

	/**
	 * Returns a class instance of a corresponding TCA service.
	 * If the class instance does not exist, create one.
	 *
	 * @throws \TYPO3\CMS\Vidi\Exception\NotExistingClassException
	 * @param string $tableName
	 * @param string $serviceType of the TCA. Typical values are: field, table, grid
	 * @return \TYPO3\CMS\Vidi\Tca\TcaServiceInterface
	 */
	static public function getService($tableName = '', $serviceType) {
		if (TYPO3_MODE == 'BE' && empty($tableName)) {
			/** @var \TYPO3\CMS\Vidi\ModuleLoader $moduleLoader */
			$moduleLoader = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Vidi\ModuleLoader');
			$tableName = $moduleLoader->getDataType();
		}

		if (empty(self::$instanceStorage[$tableName][$serviceType])) {
			$className = sprintf('TYPO3\CMS\Vidi\Tca\%sService', ucfirst($serviceType));

			if (! class_exists($className)) {
				throw new \TYPO3\CMS\Vidi\Exception\NotExistingClassException('Class does not exit: ' . $className, 1357060937);

			}
			$instance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className, $tableName, $serviceType);
			self::$instanceStorage[$tableName][$serviceType] = $instance;
		}
		return self::$instanceStorage[$tableName][$serviceType];
	}

	/**
	 * Returns a class instance of a corresponding TCA service.
	 * This is a shorthand method for "field" (AKA "columns").
	 *
	 * @param string $tableName
	 * @return \TYPO3\CMS\Vidi\Tca\FieldService
	 */
	static public function getFieldService($tableName = '') {
		return \TYPO3\CMS\Vidi\Tca\TcaServiceFactory::getService($tableName, self::TYPE_FIELD);
	}

	/**
	 * Returns a class instance of a corresponding TCA service.
	 * This is a shorthand method for "grid".
	 *
	 * @param string $tableName
	 * @return \TYPO3\CMS\Vidi\Tca\GridService
	 */
	static public function getGridService($tableName = '') {
		return \TYPO3\CMS\Vidi\Tca\TcaServiceFactory::getService($tableName, self::TYPE_GRID);
	}

	/**
	 * Returns a class instance of a corresponding TCA service.
	 * This is a shorthand method for "table" (AKA "ctrl").
	 *
	 * @param string $tableName
	 * @return \TYPO3\CMS\Vidi\Tca\TableService
	 */
	static public function getTableService($tableName = '') {
		return \TYPO3\CMS\Vidi\Tca\TcaServiceFactory::getService($tableName, self::TYPE_TABLE);
	}

	/**
	 * @return array
	 */
	public static function getInstanceStorage() {
		return self::$instanceStorage;
	}
}
?>