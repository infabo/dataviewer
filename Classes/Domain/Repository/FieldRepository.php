<?php
namespace MageDeveloper\Dataviewer\Domain\Repository;

use TYPO3\CMS\Core\Database\DatabaseConnection;

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
class FieldRepository extends AbstractRepository
{
	/**
	 * Returns the database connection
	 *
	 * @return DatabaseConnection
	 */
	protected function getDatabaseConnection()
	{
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Finds entries for an field value
	 * Executes an simple select query
	 *
	 * @param \MageDeveloper\Dataviewer\Domain\Model\FieldValue $fieldValue
	 * @return array
	 */
	public function findEntriesForFieldValue(\MageDeveloper\Dataviewer\Domain\Model\FieldValue $fieldValue)
	{
		$tablename 		= $fieldValue->getTableContent();
		$columnname 	= $fieldValue->getColumnName();
		$whereClause	= $fieldValue->getWhereClause();
		
		$query = $this->createQuery();
		$this->getDatabaseConnection()->debugOutput = false;
		$statement = "SELECT {$columnname} FROM {$tablename} {$whereClause}";
		$query->statement($statement);
		$result = $query->execute(true);
		return $result;
	}

	/**
	 * Executes an raw query
	 * 
	 * @param array $fields
	 * @param string $table
	 * @param string $where
	 * @return array
	 */
	public function rawQuery($fields, $table, $where)
	{
		$query = $this->createQuery();
		$fields = implode(",", $fields);
		$statement = "SELECT {$fields} FROM {$table} WHERE {$where}";
		
		$query->statement($statement);
		$result = $query->execute(true);
		
		return $result;
	}

	/**
	 * FindAll Override
	 *
	 * @param bool $respectStoragePage
	 * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	public function findAll($respectStoragePage = true)
	{
		$query = $this->createQueryWithSettings(true, false, $respectStoragePage);
		$querySettings = $query->getQuerySettings();

		$this->setDefaultQuerySettings($querySettings);

		return parent::findAll();
	}

	/**
	 * Finds all records on a given storage page id
	 *
	 * @param int $storagePid
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	public function findAllOnPid($storagePid)
	{
		$query = $this->createQueryWithSettings(true, false, true, [$storagePid]);
		$querySettings = $query->getQuerySettings();

		$this->setDefaultQuerySettings($querySettings);

		return parent::findAll();
	}
}