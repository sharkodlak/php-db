<?php

declare(strict_types=1);
namespace Sharkodlak\Db\Adapter\Interfaces;

interface Upsert {
	public function upsert(array $returnFieldNames, string $table, array $insertFields, array $updateFieldNames, array $uniqueFieldNamesCastingUpdate): array;
}
