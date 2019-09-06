<?php

declare(strict_types=1);
namespace Sharkodlak\Db\Adapter;

class Postgres extends Base implements Interfaces\InsertIgnore {
	protected function escapeIdentifierWord(string $identifier): string {
		return '"' . $identifier . '"';
	}

	public function insertIgnore(string $table, array $fields): bool {
		$escapedIdentifiers = $this->escapeIdentifiers(array_keys($fields));
		$placeholders = $this->getPlaceholders(array_keys($fields));
		$query = sprintf(
			'INSERT INTO %s (%s) VALUES (%s) ON CONFLICT DO NOTHING',
			$this->escapeIdentifier($table),
			implode(', ', $escapedIdentifiers),
			implode(', ', $placeholders)
		);
		$statement = $this->pdo->prepare($query);
		return $statement->execute($fields);
	}
}
