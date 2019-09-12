<?php

namespace Sharkodlak\Db\Queries;

class QueryTest extends \PHPUnit\Framework\TestCase {
	public function setUp(): void {
	}

	public function testSimpleQuery() {
		$expected = 'SELECT * FROM the_table WHERE id % 2 = 1 LIMIT 10';
		$query = 'SELECT * FROM the_table WHERE id %% 2 = 1 LIMIT 10';
		$query = new Query($query);
		$this->assertEquals($expected, (string) $query);
	}

	/**
	 * @depends testSimpleQuery
	 */
	public function testQueryWithScalarArgs() {
		$expected = 'SELECT first_field, second_field, third_field';
		$query = 'SELECT %s';
		$query = new Query($query, 'first_field, second_field, third_field');
		$this->assertEquals($expected, (string) $query);
		$expected = 'SELECT first_field, second_field, third_field FROM the_table WHERE first_field = 1 AND second_field = 2 LIMIT 3';
		$query = 'SELECT %s FROM %s WHERE %s LIMIT %d';
		$query = new Query($query, 'first_field, second_field, third_field', 'the_table', 'first_field = 1 AND second_field = 2', 3);
		$this->assertEquals($expected, (string) $query);
	}

	public function testParams() {
		$expected = $params = ['first_field' => 1, 'second_field' => 2];
		$query = new Query('SELECT * FROM the_table WHERE first_field = :first_field AND second_field = :second_field');
		$query->setParams($params);
		$this->assertEquals($expected, $query->getParams());
	}

	/**
	 * @depends testQueryWithScalarArgs
	 */
	public function testQueryWithComplexArgs() {
		$this->markTestIncomplete('Not yet ready, needs Query Parts.');
		$dbAdapter = $this->createMock(\Sharkodlak\Db\Adapter\Base::class);
		$expected = 'SELECT first_field, second_field, third_field';
		$query = 'SELECT %s';
		$names = new Parts\Names($dbAdapter, ['first_field', 'second_field', 'third_field']);
		$query = new Query($query, $names);
		$this->assertEquals($expected, (string) $query);
	}
}
