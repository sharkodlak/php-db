<?php

declare(strict_types=1);
namespace Sharkodlak\Db;

class Db {
	private $adapter;
	private $di;

	public function __construct(Di $di, Adapter\Base $adapter) {
		$this->di = $di;
		$this->adapter = $adapter;
	}

	public function __get(string $name) {
		return $this->$name;
	}

	public function query(...$args): Queries\Query {
		return $this->di->getQuery(...$args);
	}
}
