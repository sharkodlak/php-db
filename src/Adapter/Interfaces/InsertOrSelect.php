<?php

declare(strict_types=1);
namespace Sharkodlak\Db\Adapter\Interfaces;

interface InsertOrSelect {
	public function insertOrSelect(array $returnFieldNames, string $table, array $insertFields, array $whereFieldNames): array;
}
