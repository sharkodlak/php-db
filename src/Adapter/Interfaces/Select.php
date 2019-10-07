<?php

declare(strict_types=1);
namespace Sharkodlak\Db\Adapter\Interfaces;

interface Select {
	public function select(array $returnFieldNames, string $table, array $whereFieldNames): ?array;
}
