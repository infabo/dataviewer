<?php
namespace MageDeveloper\Dataviewer\UserFunc;

use MageDeveloper\Dataviewer\Utility\DebugUtility;
use MageDeveloper\Dataviewer\Utility\LocalizationUtility as Locale;
use MageDeveloper\Dataviewer\Utility\IconUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
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
class Database
{
	/**
	 * Object Manager
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager;

	/**
	 * Field Repository
	 * 
	 * @var \MageDeveloper\Dataviewer\Domain\Repository\FieldRepository
	 * @inject
	 */
	protected $fieldRepository;

	/**
	 * FieldValue Repository
	 *
	 * @var \MageDeveloper\Dataviewer\Domain\Repository\FieldValueRepository
	 * @inject
	 */
	protected $fieldValueRepository;

	/**
	 * Constructor
	 *
	 * @return Database
	 */
	public function __construct()
	{
		$this->objectManager 		= \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
		$this->fieldRepository		= $this->objectManager->get(\MageDeveloper\Dataviewer\Domain\Repository\FieldRepository::class);
		$this->fieldValueRepository	= $this->objectManager->get(\MageDeveloper\Dataviewer\Domain\Repository\FieldValueRepository::class);
	}
	
	/**
	 * Populate flexform tables
	 *
	 * @param array $config Configuration Array
	 * @param array $parentObject Parent Object
	 * @return array
	 */
	public function populateTablesAction(array &$config, &$parentObject)
	{
		$options = [];

		$label = Locale::translate("flexform.please_select", \MageDeveloper\Dataviewer\Configuration\ExtensionConfiguration::EXTENSION_KEY);
		$options[] = ["label" => $label, 0 => $label, 1 => ""];

		/* @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
		$query = GeneralUtility::makeInstance(ConnectionPool::class)
			->getConnectionForTable("tt_content");

		$tables = $query->fetchAll("SHOW TABLES");
		
		foreach($tables as $_table)
		{
			$tableName = reset($_table);
			$options[] = ["label" => $tableName,
						  0 => $tableName,
						  1 => $tableName];
		}
		
		$config["items"] = $options;

		return $config;
	}

	/**
	 * Populate flexform tables
	 *
	 * @param array $config Configuration Array
	 * @param array $parentObject Parent Object
	 * @return array
	 */
	public function populateColumnsAction(array &$config, &$parentObject)
	{
		$tableName = reset($config["row"]["table_content"]);

		$options = [];

		if ($tableName)
		{
			/* @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
			$query = GeneralUtility::makeInstance(ConnectionPool::class)
				->getConnectionForTable("tt_content");

			$columns = $query->fetchAll("SHOW COLUMNS FROM {$tableName}");
			
			foreach($columns as $_column)
			{
				$field = $_column["Field"];
				$options[] = ["label" => $field,
							  0 => $field,
							  1 => $field];
			}
			
		}

		$config["items"] = $options;

		return $config;
	}

	/**
	 * Displays the result of the selected table/column
	 *
	 * @param array $config
	 * @param array $parentObject
	 * @return string
	 */
	public function displayTableContentResult(array &$config, &$parentObject)
	{
		$this->populateColumnsAction($config, $parentObject);
		unset($config["items"][0]);

		$html = "";

		$options = [];

		if (isset($config["row"]))
		{
			$fieldValueUid = $config["row"]["uid"];
			$fieldValue = $this->fieldValueRepository->findByUid($fieldValueUid);

			if ($fieldValue instanceof \MageDeveloper\Dataviewer\Domain\Model\FieldValue)
			{
				$statement = "SELECT * FROM {$fieldValue->getTableContent()} {$fieldValue->getWhereClause()}";
				

                if (($fieldValue->getType() == \MageDeveloper\Dataviewer\Domain\Model\FieldValue::TYPE_DATABASE)
                    &&
                    $fieldValue->getTableContent() &&
                    $fieldValue->getColumnName() &&
                    strpos($statement, "{") === false
                ) {
                    try {
                        $result = $this->fieldRepository->findEntriesForFieldValue($fieldValue);

                        $html .= "<h4>".Locale::translate("items", [count($result)])."</h4>";
                        $html .= DebugUtility::debugVariable($result);

                    } catch (\Exception $e) {
                        $html = "<div class=\"alert alert-danger\">{$e->getMessage()}<br /><br /><strong>Statement:</strong><br /><pre>{$statement}</pre></div>";
                    }
                }
                else
                {
			        $html = "<strong>Statement</strong><br /><pre>{$statement}</pre>";		

				}
			}

		}
		
		return $html;
	}
}
