<?php

declare(strict_types=1);
namespace Sharkodlak\Db\Adapter\Interfaces;

interface InsertIgnore {
	public function insertIgnore(string $table, array $fields, array $returnFields): ?array;
}
