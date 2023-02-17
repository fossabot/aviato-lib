<?php

declare(strict_types = 1);

require_once dirname(dirname(__FILE__)).'/vendor/autoload.php';
require_once dirname(dirname(__FILE__)).'/config/test.php';

use PHPUnit\Framework\TestCase;

use Avi\Db as AviDb;

final class xDb extends AviDb
{
	public function setOc($v)
	{
		$this->oc = $v;
	}
}

final class testAviatoDb extends TestCase
{

	public function testFn_Construct(): void
	{
		global $gdb;
		$gdb = new AviDb();
		$this->assertIsObject($gdb);
		$this->assertTrue($gdb->isOpen());

		//invalid connection:
		$db = new AviDb(['server'=>'db-avi']);
		$this->assertIsObject($db);
		$this->assertFalse($db->isOpen());

		$xDb = new xDb();
		$this->assertIsObject($xDb);
		$xDb->setOc('v');
		$this->assertFalse($xDb->isOpen());
	}


	public function testFn_Insert(): void
	{
		$db = new AviDb();
		$this->assertIsObject($db);
		$this->assertTrue($db->isOpen());

		//insert nothing
		$query = [
			'insert' => 'test',
			'values' => [
			]
		];
		$result = $db->add($query, []);
		$this->assertFalse($result);

		//insert one row
		$query['values'] = [
			'col_string' => 'Aviato Soft'
		];
		$query['types'] = [
			'col_string' => 'str_100',
			'col_float' => 'num',
			'col_decimal' => 'num',
			'col_bit' => 'bool',
			'col_json' => 'json',
			'col_datetime' => 'dtm'
		];
		$result = $db->insert($query, [], true);
		$test = $db->getLastId('test');
		$this->assertEquals($test, $result);


		//insert multiple rows
		$nr = random_int(2, 10);
		$test++; // only one insert
		$query['values'] = [];
		for ($i = 0; $i < $nr; $i++) {
			$decimal = rand() / 100;
			$int = random_int(10, 255);
			$query['values'][] = [
				'col_string' => 'Test '.\Avi\Tools::str_random($int),
				'col_float' => $decimal/10,
				'col_decimal' => $decimal,
				'col_bit' => (bool)($int > 130),
				'col_json' => [
					'str' => \Avi\Tools::str_random(10),
					'dec' => $decimal,
					'int' => $int
				],
				'col_datetime' => mt_rand(1378905255, time())
			];
		}
		$result = $db->insert($query, []);
//		echo 'COMPARISON:'.PHP_EOL.'test:'.$test.PHP_EOL.'result:'.$result.PHP_EOL.'rows:'.$nr; //=> uncomment this line for debug
		$this->assertEquals($test, $result);


		//insert select

	}


	public function testFn_Select()
	{
		$db = new AviDb();
		$this->assertIsObject($db);
		$this->assertTrue($db->isOpen());

		$nr = random_int(2, 10);

		//simple test
		$query = [
			'select' => [
				'id',
				'col_string',
				'col_float',
				'col_decimal',
				'col_bit',
				'col_json',
				'col_datetime',
				'created_at',
				'updated_at'
			],
			'from' => 'test',
			'where' => [
				"`id` >= 1",
				"`id` <= ".$db->parseVar($nr, 'int'),
			],
			'order' => "`id`",
		];
		$result1 = $db->get($query);

		//aggregated test
		$query['select'] = "SUM(`col_decimal`) AS `sumDecimal`";
		$result2 = $db->select($query);
		$test = array_sum(array_column($result1, 'col_decimal'));
		$this->assertEquals($test, $result2[0]['sumDecimal']);

		//error test
		$query = [
			'select' => 'NotExistingColumn',
			'from' => 'test',
			'limit' => 1
		];
		$result = $db->get($query);
		$this->assertFalse($result);

//		print_r($db->getDebug());
	}


	public function testFn_Update(): void
	{
		$db = new AviDb();
		$this->assertIsObject($db);
		$this->assertTrue($db->isOpen());

		$decimal = floatval(random_int(0, 255) / 100);

		$query = [];
		$result = $db->set($query);
		$test = '';
		$this->assertEquals($test, $result);

		$query=[
			'update' => ['test'],
			'values' => [
				'col_decimal' => $decimal
			]
		];
		$test = true;
		$result = $db->update($query);
		$this->assertEquals($test, $result);

		$query=[
			'update' => ['test'],
			'set' => "`col_decimal`=".$db->parseVar($decimal, 'num'),
			'where' => [
				"`id`=7"
			]
		];
		$test = true;
		$result = $db->update($query);
		$this->assertEquals($test, $result);
	}


	public function testFn_Delete(): void
	{
		$db = new AviDb();
		$this->assertIsObject($db);
		$this->assertTrue($db->isOpen());

		$nr = random_int(0, 9);

		$query = [];
		$result = $db->del($query);
		$test = '';
		$this->assertEquals($test, $result);

		$query = [
			'delete' => 'test'
		];
		$result = $db->parse($query, 'delete');
		$test = 'TRUNCATE TABLE `test`';
		$this->assertEquals($test, $result);

		$query = [
			'delete' => 'test',
			'where' => [
				"`id`>1"
			]
		];
		$test = true;
		$result = $db->delete($query);
		$this->assertEquals($test, $result);
	}


	public function testFn_Parse() : void
	{
		$db = new AviDb();
		$this->assertIsObject($db);
		$this->assertTrue($db->isOpen());

		$nr = random_int(0, 9);

		$result = $db->parseVar(null, 'str');
		$test = 'NULL';
		$this->assertEquals($test, $result);

		$result = $db->parseVar('', 'str');
		$test = 'NULL';
		$this->assertEquals($test, $result);

		$result = $db->parseVar('', 'str_0');
		$test = 'NULL';
		$this->assertEquals($test, $result);


		$result = $db->parseVar('abc', 'xyz');
		$test = 'abc';
		$this->assertEquals($test, $result);

		$query = [
			'insert' => 'table',
			'values' => '1,2,3'
		];
		$result = $db->parse($query, 'insert');
		$test = '';
		$this->assertEquals($test, $result);

		$query['columns'] = 'a,b,c';
		$result = $db->parse($query, 'insert');
		$test='INSERT INTO `table`(`a`,`b`,`c`) VALUES(1,2,3)';
		$this->assertEquals($test, $result);


		$query = [
			'insert' => 'test',
			'values' => [
				'abc' => '{x}'
			]
		];
		$result = $db->parse($query, 'insert', ['x' => $nr]);
		$test = sprintf("INSERT INTO `test`(`abc`) VALUES('%s')", $nr);
		$this->assertEquals($test, $result);

		$test = $test;
		$result = $db->parse($test, 'sql');
		$this->assertEquals($test, $result);

		$query=[
			'from' => [
				"`test`",
				"JOIN `join`"
			],
			'where' => [
				"`id`=0"
			],
			'group' => [
				'test.id',
				'join.name'
			],
			'having' => [
				'SUM(`id`)>0'
			],
			'order' => [
				'`id` DESC',
				'`name` ASC'
			],
			'limit' => [
				0,
				100
			]
		];
		$result = $db->parse($query, 'select');
		$test = 'SELECT * FROM `test` JOIN `join` WHERE (`id`=0) GROUP BY `test`.`id`,`join`.`name` HAVING SUM(`id`)>0 ORDER BY `id` DESC,`name` ASC LIMIT (0,100)';
		$this->assertEquals($test, $result);
	}


	public function testFn_Debug()
	{
		global $gdb;
		if ($gdb === null) {
			$gdb = new AviDb();
		}
		$this->assertIsObject($gdb);
		$this->assertTrue($gdb->isOpen());

		print_r($gdb->getDebug());
	}

}