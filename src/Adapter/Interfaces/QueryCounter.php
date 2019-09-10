<?php

declare(strict_types=1);
namespace Sharkodlak\Db\Adapter\Interfaces;

interface QueryCounter {
	public function getQueryCounter(): array;
	public function resetQueryCounter(array $counter = []): array;
}
