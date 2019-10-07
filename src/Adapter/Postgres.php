<?php

declare(strict_types=1);
namespace Sharkodlak\Db\Adapter;

class Postgres extends Base implements
	Interfaces\InsertIgnore,
	Interfaces\InsertOrSelect,
	Interfaces\Select,
	Interfaces\QueryCounter,
	Interfaces\Upsert
{
	protected function escapeIdentifierWord(string $identifier): string {
		return '"' . $identifier . '"';
	}

	protected function excludedValuesIdentifier($escapedFieldName): string {
		return 'EXCLUDED.' . $escapedFieldName;
	}

	public function insertIgnore(array $returnFieldNames, string $table, array $fields): ?array {
		$escapedTable = $this->escapeIdentifier($table);
		$escapedIdentifiers = $this->escapeIdentifiers(array_keys($fields));
		$placeholders = $this->getPlaceholders($fields);
		$escapedReturnIdentifiers = $this->escapeIdentifiers($returnFieldNames);
		$query = sprintf(
			'INSERT INTO %s (%s) VALUES (%s) ON CONFLICT DO NOTHING RETURNING %s',
			$escapedTable,
			implode(', ', $escapedIdentifiers),
			implode(', ', $placeholders),
			implode(', ', $escapedReturnIdentifiers)
		);
		$statement = $this->pdo->prepare($query);
		$success = $statement->execute($this->getFieldsParams($fields));
		$result = $statement->fetch(\PDO::FETCH_ASSOC) ?: null;
		if ($success && $result !== null) {
			$this->queryCounter['insert'] = ($this->queryCounter['insert'] ?? 0) + 1;
		}
		return $result;
	}

	public function insertOrSelectComplex(array $returnFieldNames, string $table, array $insertFields, array $whereFields): array {
		$result = $this->insertIgnore($returnFieldNames, $table, $insertFields);
		if ($result === null) {
			$result = $this->select($returnFieldNames, $table, $whereFields);
		}
		return $result;
	}

	public function insertOrSelect(array $returnFieldNames, string $table, array $insertFields, array $whereFieldNames): array {
		$whereFields = \array_intersect_key($insertFields, \array_flip($whereFieldNames));
		return $this->insertOrSelectComplex($returnFieldNames, $table, $insertFields, $whereFields);
	}

	public function select(array $returnFieldNames, string $table, array $whereFields): ?array {
		$escapedIdentifiers = $this->escapeIdentifiers($returnFieldNames);
		$escapedTable = $this->escapeIdentifier($table);
		$escapedWhere = $this->escapeWhere($whereFields);
		$query = sprintf(
			'SELECT %s FROM %s WHERE %s',
			implode(', ', $escapedIdentifiers),
			$escapedTable,
			$escapedWhere
		);
		$statement = $this->pdo->prepare($query);
		$success = $statement->execute($this->getFieldsParams($whereFields));
		$result = $statement->fetch(\PDO::FETCH_ASSOC);
		if ($success && $result !== null) {
			$this->queryCounter['select'] = ($this->queryCounter['select'] ?? 0) + 1;
		}
		return $result ?: null;
	}

	public function upsert(array $returnFieldNames, string $table, array $insertFields, array $updateFieldNames, array $uniqueFieldNamesCastingUpdate): array {
		$escapedTable = $this->escapeIdentifier($table);
		$escapedIdentifiers = $this->escapeIdentifiers(array_keys($insertFields));
		$escapedUniqueIdentifiers = $this->escapeIdentifiers($uniqueFieldNamesCastingUpdate);
		$placeholders = $this->getPlaceholders($insertFields);
		$escapedUpdateSet = $this->escapeUpdateSet($updateFieldNames);
		$escapedReturnIdentifiers = $this->escapeIdentifiers($returnFieldNames);
		$query = sprintf(
			'INSERT INTO %s (%s) VALUES (%s) ON CONFLICT (%s) DO UPDATE SET %s RETURNING %s',
			$escapedTable,
			implode(', ', $escapedIdentifiers),
			implode(', ', $placeholders),
			implode(', ', $escapedUniqueIdentifiers),
			implode(', ', $escapedUpdateSet),
			implode(', ', $escapedReturnIdentifiers)
		);
		$statement = $this->pdo->prepare($query);
		$success = $statement->execute($this->getFieldsParams($insertFields));
		$result = $statement->fetch(\PDO::FETCH_ASSOC) ?: null;
		if ($success && $result !== null) {
			$this->queryCounter['insert'] = ($this->queryCounter['insert'] ?? 0) + 1;
		}
		return $result;
	}
}
