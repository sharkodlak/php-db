<?php

declare(strict_types=1);
namespace Sharkodlak\Db\Adapter;

interface Di {
	public function getLogger(): \Psr\Log\LoggerInterface;
}
