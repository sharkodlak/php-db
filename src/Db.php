<?php

declare(strict_types=1);
namespace Sharkodlak\Db;

class Db {
	private $adapter;

	public function __construct(Adapter\Base $adapter) {
		$this->adapter = $adapter;
	}
}
