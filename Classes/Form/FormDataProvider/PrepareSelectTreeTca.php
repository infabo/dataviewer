<?php

namespace MageDeveloper\Dataviewer\Form\FormDataProvider;

use MageDeveloper\Dataviewer\Domain\Model\Datatype;
use MageDeveloper\Dataviewer\Domain\Model\Record;
use MageDeveloper\Dataviewer\Domain\Model\Field as Field;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Tree\TableConfiguration\ExtJsArrayTreeRenderer;
use TYPO3\CMS\Core\Tree\TableConfiguration\TableConfigurationTree;
use TYPO3\CMS\Core\Tree\TableConfiguration\TreeDataProviderFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * MageDeveloper Dataviewer Extension
 * -----------------------------------
 *
 * @category    TYPO3 Extension
 * @package     MageDeveloper\Dataviewer
 * @author		Bastian Zagar
 * @copyright   Magento Developers / magedeveloper.de <kontakt@magedeveloper.de>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class PrepareSelectTreeTca extends \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems
{
	/**
	 * Object Manager
	 *
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager;

	/**
	 * Constructor
	 *
	 * @return PrepareSelectTreeTca
	 */
	public function __construct()
	{
		$this->objectManager 			= GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
	}
	
	/**
	 * Resolve select items
	 *
	 * @param array $result
	 * @return array
	 * @throws \UnexpectedValueException
	 */
	public function addData(array $result)
	{
		$table = $result['tableName'];

		foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
			if (empty($fieldConfig['config']['type']) || $fieldConfig['config']['type'] !== 'select') {
				continue;
			}

			// Make sure we are only processing supported renderTypes
			if (!$this->isTargetRenderType($fieldConfig)) {
				continue;
			}

			$fieldConfig['config']['items'] = $this->sanitizeItemArray($fieldConfig['config']['items'], $table, $fieldName);
			$fieldConfig['config']['maxitems'] = $this->sanitizeMaxItems($fieldConfig['config']['maxitems']);

			$fieldConfig['config']['items'] = $this->addItemsFromSpecial($result, $fieldName, $fieldConfig['config']['items']);
			$fieldConfig['config']['items'] = $this->addItemsFromFolder($result, $fieldName, $fieldConfig['config']['items']);
			$staticItems = $fieldConfig['config']['items'];

			$fieldConfig['config']['items'] = $this->addItemsFromForeignTable($result, $fieldName, $fieldConfig['config']['items']);
			$dynamicItems = array_diff_key($fieldConfig['config']['items'], $staticItems);

			$fieldConfig['config']['items'] = $this->removeItemsByKeepItemsPageTsConfig($result, $fieldName, $fieldConfig['config']['items']);
			$fieldConfig['config']['items'] = $this->addItemsFromPageTsConfig($result, $fieldName, $fieldConfig['config']['items']);
			$fieldConfig['config']['items'] = $this->removeItemsByRemoveItemsPageTsConfig($result, $fieldName, $fieldConfig['config']['items']);

			$fieldConfig['config']['items'] = $this->removeItemsByUserLanguageFieldRestriction($result, $fieldName, $fieldConfig['config']['items']);
			$fieldConfig['config']['items'] = $this->removeItemsByUserAuthMode($result, $fieldName, $fieldConfig['config']['items']);
			$fieldConfig['config']['items'] = $this->removeItemsByDoktypeUserRestriction($result, $fieldName, $fieldConfig['config']['items']);

			// Resolve "itemsProcFunc"
			if (!empty($fieldConfig['config']['itemsProcFunc'])) {
				$fieldConfig['config']['items'] = $this->resolveItemProcessorFunction($result, $fieldName, $fieldConfig['config']['items']);
				// itemsProcFunc must not be used anymore
				unset($fieldConfig['config']['itemsProcFunc']);
			}

			// Translate labels
			$fieldConfig['config']['items'] = $this->translateLabels($result, $fieldConfig['config']['items'], $table, $fieldName);

			$staticValues = $this->getStaticValues($fieldConfig['config']['items'], $dynamicItems);
			$result['databaseRow'][$fieldName] = $this->processDatabaseFieldValue($result['databaseRow'], $fieldName);
			// Dataviewer Edit for dataviewer fields that are numeric
			if(!is_numeric($fieldName))
				$result['databaseRow'][$fieldName] = $this->processSelectFieldValue($result, $fieldName, $staticValues);


			// Keys may contain table names, so a numeric array is created
			$fieldConfig['config']['items'] = array_values($fieldConfig['config']['items']);

			// A couple of tree specific config parameters can be overwritten via page TS.
			// Pick those that influence the data fetching and write them into the config
			// given to the tree data provider
			if (isset($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['config.']['treeConfig.'])) {
				$pageTsConfig = $result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['config.']['treeConfig.'];
				// If rootUid is set in pageTsConfig, use it
				if (isset($pageTsConfig['rootUid'])) {
					$fieldConfig['config']['treeConfig']['rootUid'] = (int)$pageTsConfig['rootUid'];
				}
				if (isset($pageTsConfig['appearance.']['expandAll'])) {
					$fieldConfig['config']['treeConfig']['appearance']['expandAll'] = (bool)$pageTsConfig['appearance.']['expandAll'];
				}
				if (isset($pageTsConfig['appearance.']['maxLevels'])) {
					$fieldConfig['config']['treeConfig']['appearance']['maxLevels'] = (int)$pageTsConfig['appearance.']['maxLevels'];
				}
				if (isset($pageTsConfig['appearance.']['nonSelectableLevels'])) {
					$fieldConfig['config']['treeConfig']['appearance']['nonSelectableLevels'] = $pageTsConfig['appearance.']['nonSelectableLevels'];
				}
			}
			
			$fieldConfig['config']['treeData'] = $this->renderTree($result, $fieldConfig, $fieldName, $staticItems);

			$result['processedTca']['columns'][$fieldName] = $fieldConfig;
		}
		
		return $result;
	}
}
