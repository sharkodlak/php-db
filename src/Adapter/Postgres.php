<?php

declare(strict_types=1);
namespace Sharkodlak\Db\Adapter;

class Postgres extends Base implements
	Interfaces\InsertIgnore,
	Interfaces\InsertOrSelect,
	Interfaces\Select,
	Interfaces\QueryCounter
{
	protected function escapeIdentifierWord(string $identifier): string {
		return '"' . $identifier . '"';
	}

	public function insertIgnore(string $table, array $fields, array $returnFieldNames): ?array {
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

	public function insertOrSelectComplex(string $table, array $insertFields, array $returnFieldNames, array $whereFields): array {
		$result = $this->insertIgnore($table, $insertFields, $returnFieldNames);
		if ($result === null) {
			$result = $this->select($table, $returnFieldNames, $whereFields);
		}
		return $result;
	}

	public function insertOrSelect(string $table, array $insertFields, array $returnFieldNames, array $whereFieldNames): array {
		$whereFields = \array_intersect_key($insertFields, \array_flip($whereFieldNames));
		return $this->insertOrSelectComplex($table, $insertFields, $returnFieldNames, $whereFields);
	}

	public function select(string $table, array $returnFieldNames, array $whereFields): array {
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
		return $result;
	}
}
