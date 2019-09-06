<?php

namespace Sharkodlak\Db\Adapter;

class PostgresTest extends \PHPUnit\Framework\TestCase {
	private $dbAdapter;

	public function setUp(): void {
		$pdo = new \PDO('pgsql:host=localhost;dbname=testDatabase;user=test;password=test');
		$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

		$pdo = $this->createMock(\PDO::class);
		$this->dbAdapter = new Postgres($pdo);
	}

	public function testInsertIgnore() {
		$query = 'INSERT INTO "testDatabase"."public"."testTable" ("first", "second") VALUES (:first, :second) ON CONFLICT DO NOTHING';
		$fields = [
			'first' => 1,
			'second' => 2.0,
		];
		$mockStatement = $this->createMock(\PDOStatement::class);
		$mockStatement->method('execute')->with($this->equalTo($fields))->willReturn(true);
		$this->dbAdapter->pdo->method('prepare')->with($this->equalTo($query))->willReturn($mockStatement);
		$result = $this->dbAdapter->insertIgnore('testDatabase.public.testTable', $fields);
		$this->assertTrue($result);
	}
}
