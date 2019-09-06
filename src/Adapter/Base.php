<?php

declare(strict_types=1);
namespace Sharkodlak\Db\Adapter;

abstract class Base {
	const PDO_PLACEHOLDER = ':';
	public $pdo;

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

	protected function getPlaceholders(array $fieldNames): array {
		return \array_map(
			function($fieldName) {
				return self::PDO_PLACEHOLDER . $fieldName;
			},
			$fieldNames
		);
	}
}
