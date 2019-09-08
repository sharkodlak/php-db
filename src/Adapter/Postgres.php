<?php

declare(strict_types=1);
namespace Sharkodlak\Db\Adapter;

class Postgres extends Base implements Interfaces\InsertIgnore, Interfaces\InsertOrSelect {
	protected function escapeIdentifierWord(string $identifier): string {
		return '"' . $identifier . '"';
	}

	public function insertIgnore(string $table, array $fields, array $returnFieldNames): ?array {
		$escapedTable = $this->escapeIdentifier($table);
		$escapedIdentifiers = $this->escapeIdentifiers(array_keys($fields));
		$placeholders = $this->getPlaceholders(array_keys($fields));
		$escapedReturnIdentifiers = $this->escapeIdentifiers($returnFieldNames);
		$query = sprintf(
			'INSERT INTO %s (%s) VALUES (%s) ON CONFLICT DO NOTHING RETURNING %s',
			$escapedTable,
			implode(', ', $escapedIdentifiers),
			implode(', ', $placeholders),
			implode(', ', $escapedReturnIdentifiers)
		);
		$statement = $this->pdo->prepare($query);
		$success = $statement->execute($fields);
		return $statement->fetch(\PDO::FETCH_ASSOC) ?: null;
	}

	public function insertOrSelect(string $table, array $insertFields, array $returnFieldNames, array $whereFieldNames): array {
		$result = $this->insertIgnore($table, $insertFields, $returnFieldNames);
		if ($result === null) {
			$escapedIdentifiers = $this->escapeIdentifiers($returnFieldNames);
			$escapedTable = $this->escapeIdentifier($table);
			$escapedWhere = $this->escapeWhere($whereFieldNames);
			$query = sprintf(
				'SELECT %s FROM %s WHERE %s',
				implode(', ', $escapedIdentifiers),
				$escapedTable,
				$escapedWhere
			);
			$statement = $this->pdo->prepare($query);
			$whereFields = \array_intersect_key($insertFields, \array_flip($whereFieldNames));
			$success = $statement->execute($whereFields);
			$result = $statement->fetch(\PDO::FETCH_ASSOC);
		}
		return $result;
	}
}
