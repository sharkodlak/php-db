<?php

namespace Sharkodlak\Db\Adapter;

class PostgresTest extends \PHPUnit\Framework\TestCase {
	static private $pdo;
	private $pdoMock;

	public static function getPdo(): \PDO {
		if (!isset(self::$pdo)) {
			self::$pdo = new \PDO('pgsql:host=localhost;dbname=testDatabase;user=test;password=test');
			self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		}
		return self::$pdo;
	}

	public static function tearDownAfterClass(): void {
		self::$pdo = null;
	}

	public function setUp(): void {
	}

	public function pdoProviderInsertIgnore() {
		$query = 'INSERT INTO "testDatabase"."public"."testTable" ("first", "second") VALUES (:first, :second) ON CONFLICT DO NOTHING RETURNING "first"';
		$fields = [
			'first' => 1,
			'second' => 2.0,
		];
		$pdoMock = $this->createMock(\PDO::class);
		$statementMock = $this->createMock(\PDOStatement::class);
		$statementMock->method('execute')->with($this->equalTo($fields))->willReturn(true);
		$statementMock->method('fetch')->willReturn(array_slice($fields, 0, 1, true));
		$pdoMock->method('prepare')->with($this->equalTo($query))->willReturn($statementMock);
		$pdo = self::getPdo();
		return [
			'mocked PDO' => [new Postgres($pdoMock), $fields],
			'PDO connected to real DB' => [new Postgres($pdo), $fields],
		];
	}

	/**
	 * @dataProvider pdoProviderInsertIgnore
	 */
	public function testInsertIgnore(Interfaces\InsertIgnore $dbAdapter, array $fields) {
		$dbAdapter->pdo->exec('TRUNCATE TABLE "testTable"');
		$result = $dbAdapter->insertIgnore('testDatabase.public.testTable', $fields, ['first']);
		$this->assertEquals(['first' => 1], $result);
	}

	public function pdoProviderInsertSelect() {
		$query = [
			'INSERT INTO "testDatabase"."public"."testTable" ("first", "second") VALUES (:first, :second) ON CONFLICT DO NOTHING RETURNING "second"',
			'INSERT INTO "testDatabase"."public"."testTable" ("first", "second") VALUES (:first, :second) ON CONFLICT DO NOTHING RETURNING "second"',
			'SELECT "second" FROM "testDatabase"."public"."testTable" WHERE "first" = :first',
		];
		$fields = [
			[
				'first' => 1,
				'second' => 2.0,
			],
			[
				'first' => 1,
				'second' => 3.14,
			],
		];
		$returns[] = ['second' => $fields[0]['second']];
		$returns[] = ['second' => $fields[0]['second']];
		$pdoMock = $this->createMock(\PDO::class);
		$statementMock = $this->createMock(\PDOStatement::class);
		$statementMock->method('execute')
			->withConsecutive(
				$this->equalTo($fields[0]),
				$this->equalTo($fields[0]),
				$this->equalTo($fields[1])
			)->willReturn(true);
		$statementMock->method('fetch')->will(
			$this->onConsecutiveCalls(
				$returns[0],
				false,
				$returns[1]
			)
		);
		$pdoMock->expects($this->exactly(3))
			->method('prepare')
			->withConsecutive(
				[$this->equalTo($query[0])],
				[$this->equalTo($query[1])],
				[$this->equalTo($query[2])]
			)->willReturn($statementMock);
		$pdo = self::getPdo();
		return [
			'mocked PDO' => [new Postgres($pdoMock), $fields, $returns],
			'PDO connected to real DB' => [new Postgres($pdo), $fields, $returns],
		];
	}

	/**
	 * @dataProvider pdoProviderInsertSelect
	 */
	public function testInsertSelect(Interfaces\InsertOrSelect $dbAdapter, array $fields, array $returns) {
		//$this->markTestIncomplete();
		$dbAdapter->pdo->exec('TRUNCATE TABLE "testTable"');
		$result = $dbAdapter->insertOrSelect('testDatabase.public.testTable', $fields[0], ['second'], ['first']);
		$this->assertEquals($returns[0], $result);
		$result = $dbAdapter->insertOrSelect('testDatabase.public.testTable', $fields[1], ['second'], ['first']);
		$this->assertEquals($returns[1], $result);
	}
}
