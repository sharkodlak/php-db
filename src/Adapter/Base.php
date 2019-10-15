<?php

declare(strict_types=1);
namespace Sharkodlak\Db\Adapter;

abstract class Base {
	const PDO_PLACEHOLDER = ':';
	protected $pdo;
	protected $di;
	protected $queryCounter = [];

	public function __construct(\PDO $pdo, Di $di) {
		$this->pdo = $pdo;
		$this->di = $di;
	}

	public function __get(string $name) {
		return $this->$name;
	}

	abstract protected function escapeIdentifierWord(string $identifier): string;

	private function escapeIdentifierCallback(array $matches): string {
		return $this->escapeIdentifierWord($matches[0]);
	}

	protected function escapeIdentifier(string $identifier): string {
		return \preg_replace_callback('~[^.]+~', [$this, 'escapeIdentifierCallback'], $identifier);
	}

	protected function escapeIdentifiers(array $identifiers): array {
		return \array_map([$this, 'escapeIdentifier'], $identifiers);
	}

	protected function escapePlaceholder($fieldName, $value): string {
		return $value instanceof \Sharkodlak\Db\Queries\Query ? "($value)" : self::PDO_PLACEHOLDER . $fieldName;
	}

	protected function escapeUpdateSet(array $updateFieldNames): array {
		$updateSetParts = [];
		foreach ($updateFieldNames as $fieldName) {
			$escapedIdentifier = $this->escapeIdentifier($fieldName);
			$updateSetParts[] = \sprintf(
				'%s = %s',
				$escapedIdentifier,
				$this->excludedValuesIdentifier($escapedIdentifier)
			);
		}
		return $updateSetParts;
	}

	abstract protected function excludedValuesIdentifier($escapedFieldName): string;

	protected function escapeWhere(array $whereFields): string {
		$whereParts = [];
		foreach ($whereFields as $fieldName => $value) {
			$whereParts[$fieldName] = \sprintf(
				'%s = %s',
				$this->escapeIdentifier($fieldName),
				$this->escapePlaceholder($fieldName, $value)
			);
		}
		return \implode(' AND ', $whereParts);
	}

	protected function getFieldsParams(array $fields): array {
		$params = [];
		foreach ($fields as $fieldName => $value) {
			if ($value instanceof \Sharkodlak\Db\Queries\Query) {
				$params += $value->getParams();
			} else {
				$params[$fieldName] = $value;
			}
		}
		return $params;
	}

	protected function getPlaceholders(array $fields): array {
		$placeholders = [];
		foreach ($fields as $fieldName => $value) {
			$placeholders[] = $this->escapePlaceholder($fieldName, $value);
		}
		return $placeholders;
	}

	public function getQueryCounter(): array {
		return $this->queryCounter;
	}

	protected function query(string $query, array $params, string $queryType) {
		$statement = $this->pdo->prepare($query);
		$msg = sprintf("Query: %s\nParams: %s\n",
			$query,
			\var_export($params, true)
		);
		$this->di->getLogger()->debug($msg, $params);
		$success = $statement->execute($params);
		$result = $statement->fetch(\PDO::FETCH_ASSOC) ?: null;
		$msg = sprintf("Query result %ssuccessful: %s\n",
			$success ? '' : 'un',
			\var_export($result, true)
		);
		$this->di->getLogger()->debug($msg, $params);
		if ($success && $result !== null) {
			$this->queryCounter[$queryType] = ($this->queryCounter[$queryType] ?? 0) + 1;
		}
		return $result;
	}

	public function resetQueryCounter(array $counter = []): array {
		$queryCounter = $this->queryCounter;
		$this->queryCounter = $counter;
		return $queryCounter;
	}
}
