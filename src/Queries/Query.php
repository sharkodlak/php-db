<?php

declare(strict_types=1);
namespace Sharkodlak\Db\Queries;

class Query {
	private $queryParts;
	private $params;

	public function __construct(...$queryParts) {
		$this->queryParts = $queryParts;
	}

	public function __toString() {
		return sprintf(...$this->queryParts);
	}

	public function setParams(array $params) {
		$this->params = $params;
	}

	public function getParams() {
		return $this->params;
	}
}
