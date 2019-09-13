<?php

declare(strict_types=1);
namespace Sharkodlak\Db\Adapter;

abstract class Base {
	const PDO_PLACEHOLDER = ':';
	public $pdo;
	protected $queryCounter = [];

	public function __construct(\PDO $pdo) {
		$this->pdo = $pdo;
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

	protected function escapeWhere(array $whereFieldNames): string {
		$whereParts = [];
		foreach ($whereFieldNames as $key => $fieldName) {
			$whereParts[$key] = sprintf(
				'%s = %s',
				$this->escapeIdentifier($fieldName),
				self::PDO_PLACEHOLDER . $fieldName
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
			$placeholders[] = $value instanceof \Sharkodlak\Db\Queries\Query ? "($value)" : self::PDO_PLACEHOLDER . $fieldName;
		}
		return $placeholders;
	}

	public function getQueryCounter(): array {
		return $this->queryCounter;
	}

	public function resetQueryCounter(array $counter = []): array {
		$queryCounter = $this->queryCounter;
		$this->queryCounter = $counter;
		return $queryCounter;
	}
}
