<?php

declare(strict_types=1);
namespace Sharkodlak\Db\Adapter\Interfaces;

interface InsertOrSelect {
	public function insertOrSelect(string $table, array $insertFields, array $returnFieldNames, array $whereFieldNames): array;
}
