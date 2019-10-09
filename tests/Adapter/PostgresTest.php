<?php

namespace Sharkodlak\Db\Adapter;

class PostgresTest extends \PHPUnit\Framework\TestCase {
	static private $di;
	static private $pdo;
	private $pdoMock;

	public static function getDi(): Di {
		if (!isset(self::$di)) {
			self::$di = new class implements Di {
				public function getLogger(): \Psr\Log\LoggerInterface {
					$logger = new class extends \Psr\Log\AbstractLogger {
						public function log($level, $message, array $context = []) {
							fputs(STDERR, $message . "\n");
						}
					};
					return $logger;
				}
			};
		}
		return self::$di;
	}

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
			'mocked PDO' => [new Postgres($pdoMock, self::getDi()), $fields],
			'PDO connected to real DB' => [new Postgres($pdo, self::getDi()), $fields],
		];
	}

	/**
	 * @dataProvider pdoProviderInsertIgnore
	 */
	public function testInsertIgnore(Interfaces\InsertIgnore $dbAdapter, array $fields) {
		$dbAdapter->pdo->exec('TRUNCATE TABLE "testTable"');
		$result = $dbAdapter->insertIgnore(['first'], 'testDatabase.public.testTable', $fields);
		$this->assertEquals(['first' => 1], $result);
	}

	public function testInsertIgnoreNested() {
		$pdo = self::getPdo();
		$pdo->exec('TRUNCATE TABLE nato');
		$dbAdapter = new Postgres($pdo, self::getDi());
		$query = 'SELECT id FROM second_table WHERE charlie = :charlie AND third_id = (
				SELECT id FROM third_table WHERE delta = :delta
			)';
		$nestedQuery = new \Sharkodlak\Db\Queries\Query($query);
		$nestedQuery->setParams(['charlie' => "\u03B3", 'delta' => "\u03B4"]);
		$fields = [
			'alpha' => "\u03B1",
			'bravo' => "\u03B2",
			'second_id' => $nestedQuery,
		];
		$result = $dbAdapter->insertIgnore(['alpha', 'bravo', 'second_id'], 'nato', $fields);
		$expected = [
			'alpha' => "\u03B1",
			'bravo' => "\u03B2",
			'second_id' => 1,
		];
		$this->assertEquals($expected, $result);
	}

	public function pdoProviderInsertSelect() {
		$query = [
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
				[$this->equalTo($fields[0])],
				[$this->equalTo($fields[1])],
				[$this->equalTo(['first' => 1])]
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
				[$this->equalTo($query[0])],
				[$this->equalTo($query[1])]
			)->willReturn($statementMock);
		$pdo = self::getPdo();
		return [
			'mocked PDO' => [new Postgres($pdoMock, self::getDi()), $fields, $returns],
			'PDO connected to real DB' => [new Postgres($pdo, self::getDi()), $fields, $returns],
		];
	}

	/**
	 * @dataProvider pdoProviderInsertSelect
	 */
	public function testInsertSelect(Interfaces\InsertOrSelect $dbAdapter, array $fields, array $returns) {
		$dbAdapter->pdo->exec('TRUNCATE TABLE "testTable"');
		$result = $dbAdapter->insertOrSelect(['second'], 'testDatabase.public.testTable', $fields[0], ['first']);
		$this->assertEquals($returns[0], $result);
		$result = $dbAdapter->insertOrSelect(['second'], 'testDatabase.public.testTable', $fields[1], ['first']);
		$this->assertEquals($returns[1], $result);
	}

	public function testResetQueryCounter() {
		$pdoMock = $this->createMock(\PDO::class);
		$statementMock = $this->createMock(\PDOStatement::class);
		$statementMock->method('execute')->will($this->onConsecutiveCalls(true, true, true, false, true));
		$id = ['id' => 123];
		$statementMock->method('fetch')->will($this->onConsecutiveCalls($id, $id, $id, null, $id));
		$pdoMock->method('prepare')->willReturn($statementMock);
		$dbAdapter = new Postgres($pdoMock, self::getDi());
		$fields = ['first' => 1, 'second' => 2.72];
		$dbAdapter->insertIgnore(['id'], 'testTable', $fields);
		$this->assertEquals(['insert' => 1], $dbAdapter->getQueryCounter());
		$dbAdapter->insertIgnore(['id'], 'testTable', $fields);
		$this->assertEquals(['insert' => 2], $dbAdapter->resetQueryCounter());
		$dbAdapter->insertIgnore(['id'], 'testTable', $fields);
		$this->assertEquals(['insert' => 1], $dbAdapter->getQueryCounter());
		$dbAdapter->insertOrSelect(['id'], 'testTable', $fields, ['first']);
		$this->assertEquals(['insert' => 1, 'select' => 1], $dbAdapter->resetQueryCounter(['update' => 0]));
		$this->assertEquals(['update' => 0], $dbAdapter->getQueryCounter());
	}

	public function testUpsert() {
		$pdo = self::getPdo();
		$pdo->exec('TRUNCATE TABLE "testTable"');
		$dbAdapter = new Postgres($pdo, self::getDi());
		$fields = ['first' => 1, 'second' => 2.72];
		$result = $dbAdapter->upsert(['second'], 'testDatabase.public.testTable', $fields, ['second'], ['first']);
		$this->assertEquals(['second' => 2.72], $result);
		$fields = ['first' => 1, 'second' => 3.14];
		$result = $dbAdapter->upsert(['second'], 'testDatabase.public.testTable', $fields, ['second'], ['first']);
		$this->assertEquals(['second' => 3.14], $result);
	}
}
