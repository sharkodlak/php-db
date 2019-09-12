<?php

declare(strict_types=1);
namespace Sharkodlak\Db;

interface Di {
	public function getQuery(...$args): Queries\Query;
}
