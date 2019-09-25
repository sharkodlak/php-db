<?php

declare(strict_types=1);
namespace Sharkodlak\Db\Adapter\Interfaces;

interface InsertIgnore {
	public function insertIgnore(array $returnFields, string $table, array $fields): ?array;
}
