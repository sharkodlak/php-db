<?php

declare(strict_types=1);
namespace Sharkodlak\Db\Adapter\Interfaces;

interface Select {
	public function select(string $table, array $returnFieldNames, array $whereFieldNames): array;
}
