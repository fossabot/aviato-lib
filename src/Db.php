<?php
/*
 * License
 *
 * @author Aviato Soft
 * @copyright 2014-present Aviato Soft. All Rights Reserved.
 * @license GNUv3
 * @version 01.23.07
 * @since  2023-02-21 17:09:18
 *
 */
declare(strict_types = 1);
namespace Avi;

use Avi\Log as AviLog;
use Avi\Tools as AviTools;

class Db
{

	protected $oc;

	private $log;

	private $connection = [];

	private $debug = [
		'con' => 0,
		'sql' => []
	];


	public function __construct(?array $options = [])
	{
		$this->log = new \Avi\Log();
		$this->connection = [
			'server' => $options['server'] ?? AVI_DB_SERVER,
			'user' => $options['user'] ?? AVI_DB_USER,
			'password' => $options['password'] ?? \Avi\Tools::dec(AVI_DB_PASSWORD),
			'database' => $options['database'] ?? AVI_DB_NAME,
			'port' => $options['port'] ?? defined('AVI_DB_PORT')? AVI_DB_PORT : 3306,
			'charset' => defined('AVI_DB_CHARSET') ? AVI_DB_CHARSET : 'utf8'
		];
		// set error reporting
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

		$this->connect();

		return $this;
	}


	public function __destruct()
	{
		if ($this->isOpen()) {
			$this->oc->close();
		}
	}


	public function getDebug()
	{
		return $this->debug;
	}


	public function isOpen()
	{
		if ($this->oc === null) {
			return false;
		}

		if (is_object($this->oc)) {
			return true;
		}

		return false;
	}


	/**
	 * Connection to database server
	 */
	private function connect(): bool
	{
		// check if connection exists
		if ($this->isOpen()) {
			return true;
		}

		// connect
		try {
			$this->oc = new \mysqli($this->connection['server'], $this->connection['user'],
				$this->connection['password'], $this->connection['database'], $this->connection['port']);
		} catch (\mysqli_sql_exception $e) {
			$this->log->trace($e, LOG_ERR);
			return false;
		}

		// set charset
		$this->oc->set_charset($this->connection['charset']);

		// debug counter
		$this->debug['con'] ++;

		return true;
	}


	/**
	 *
	 * Execute a select query
	 *
	 * @param array $query
	 * @param array $vars
	 * @return boolean|array
	 */
	public function select(array $query, array $vars = [])
	{
		return $this->exec($query, 'select', $vars);
	}


	/**
	 * Execute an insert query
	 *
	 * @param array $query
	 * @param array $vars
	 * @return boolean
	 */
	public function insert(array $query, array $vars = [])
	{
		return $this->exec($query, 'insert', $vars);
	}


	/**
	 * Execute an UPDATE query
	 *
	 * @param array $query
	 * @param array $vars
	 * @return boolean
	 */
	public function update(array $query, array $vars = [])
	{
		return $this->exec($query, 'update', $vars);
	}


	/**
	 * Execite am DELETE query
	 *
	 * @param array $query
	 * @param array $vars
	 * @return boolean|number
	 */
	public function delete(array $query, array $vars = [])
	{
		return $this->exec($query, 'delete', $vars);
	}


	/**
	 * Alias of select
	 *
	 * @param array $query
	 * @param array $vars
	 * @return boolean|array
	 */
	public function get(array $query, array $vars = [])
	{
		return $this->select($query, $vars);
	}


	/**
	 * Return only one row (1st one) - usefull when search something specific
	 *
	 * @param array $query
	 * @param array $vars
	 * @return boolean
	 */
	public function getOneRow(array $query, array $vars = [])
	{
		$result = $this->select($query, $vars);
		$result = ($result === false) ? false : $result[0];
		return $result;
	}


	/**
	 * Alias of insert
	 *
	 * @param array $query
	 * @param array $vars
	 * @return boolean
	 */
	public function add(array $query, array $vars = [])
	{
		return $this->insert($query, $vars);
	}


	/**
	 * Alias of update
	 *
	 * @param array $query
	 * @param array $vars
	 * @return boolean
	 */
	public function set(array $query, array $vars = [])
	{
		return $this->update($query, $vars);
	}


	/**
	 * Alias of del query
	 *
	 * @param array $query
	 * @param array $vars
	 * @return bool
	 */
	public function del(array $query, array $vars = []): bool
	{
		return $this->delete($query, $vars);
	}


	/**
	 * Parsing the query from array to string
	 *
	 * @param array|string $query the query as array or string
	 * @param string $pattern the conversion patter for query
	 * @param array $vars those vars will be replaced with their values on final query sting
	 * @return string the query in string format
	 */
	public function parse($query, string $pattern = 'auto', array $vars = []): string
	{
		if(strtolower($pattern) === 'auto') {
			$pattern = $this->getPatternFromQuery($query);
		}

		// parsing the query array to string
		switch (strtolower($pattern)) {
			case 'get':
			case 'select':
				$result = $this->parseSelect($query);
				break;

			case 'add':
			case 'insert':
				$result = $this->parseInsert($query);
				break;

			case 'set':
			case 'update':
				$result = $this->parseUpdate($query);
				break;

			case 'delete':
				$result = $this->parseDelete($query);
				break;

			case 'sql':
			default:
				$result = $query;
				break;
		}

		// parsing the vars argument
		if (count($vars) > 0) {
			$search = [];
			$replace = [];
			foreach ($vars as $k => $v) {
				$search[] = '{'.$k.'}';
				$replace[] = $v;
			}
			$result = str_replace($search, $replace, $result);
		}

		return $result;
	}


	private function getPatternFromQuery($query)
	{
		$queryKeys = array_keys(array_change_key_case($query, CASE_LOWER));
		$patterns = [
			'select',
			'insert',
			'update',
			'delete'
		];

		foreach($patterns as $v) {
			if (in_array($v, $queryKeys, true)) {
				return $v;
			}
		}

		//if there is nothing there assume that a select * is needed
		return 'select';
	}

	/**
	 * Parse delete query
	 *
	 * @param array $query
	 * @return string
	 */
	private function parseDelete(array $query): string
	{
		// empty values => nothing to insert
		if (! isset($query['delete'])) {
			return '';
		}
		$table = $this->encloseInBacktick($query['delete']);

		$where = $query['where'] ?? false;
		if (is_array($where)) {
			$where = implode(' AND ', $where);
		}

		$sql = ($where === false) ?
			sprintf("TRUNCATE TABLE %s", $table) :
			sprintf("DELETE FROM %s WHERE %s", $table, $where);

		return $sql;
	}


	/**
	 * Parse an insert query
	 *
	 * @param array $query
	 * @return string
	 */
	private function parseInsert(array $query): string
	{
		$columns = $query['columns'] ?? [];
		$types = $query['types'] ?? [];
		$values = $query['values'] ?? [];

		// empty values => nothing to insert
		if (! isset($query['insert']) || $values === []) {
			return '';
		}

		if ($columns === []) {
			if (is_array($values)) {
				if (isset($values[0])) {
					$columns = array_keys($values[0]);
				} else {
					$columns = array_keys($values);
				}
			} else {
				// the columns must be specific for values formated as string
				return '';
			}
		}
		if (! is_array($columns)) {
			$columns = explode(',', $columns);
		}

		if (is_array($values)) {
			if (isset($values[0])) {
				if (is_array($values[0])) {
					$val = [];
					foreach ($values as $row) {
						$val[] = sprintf('(%s)', implode(',', $this->parseVars($row, $types)));
					}
					$values = implode(',', $val);
					$values = substr($values, 1, - 1);
				} else {
					$values = implode(',', $this->parseVars($values, $types));
				}
			} else {
				$val = [];
				foreach ($columns as $col) {
					$val[] = $this->parseVar($values[$col], $types[$col] ?? null);
				}
				$values = implode(',', $val);
			}
		}

		$columns = $this->encloseInBacktick(implode(',', $columns));

		$table = $this->encloseInBacktick($query['insert']);

		$sql = sprintf("INSERT INTO %s(%s) VALUES(%s)", $table, $columns, $values);

		return $sql;
	}


	/**
	 * Parsing a query with select pattern
	 *
	 * @param array $query
	 * @return string formated query as string
	 */
	private function parseSelect(array $query): string
	{
		$select = $query['select'] ?? '*';
		if (is_array($select)) {
			$select = implode(',', $select);
		}
		$select = $this->encloseInBacktick($select);

		$from = $query['from'] ?? false;
		if (is_array($from)) {
			$from = implode(' ', $from);
		}

		$where = $query['where'] ?? false;
		if (is_array($where)) {
			$where = implode(' AND ', $where);
		}

		$group = $query['group'] ?? false;
		if (is_array($group)) {
			$group = implode(',', $group);
		}

		$having = $query['having'] ?? false;
		if (is_array($having)) {
			$having = implode(' AND ', $having);
		}

		$order = $query['order'] ?? false;
		if (is_array($order)) {
			$order = implode(',', $order);
		}

		$limit = $query['limit'] ?? false;
		if (is_array($limit)) {
			$limit = '('.$limit[0].','.$limit[1].')';
		}

		$sql = sprintf('SELECT %s', $this->encloseInBacktick($select));
		$sql .= $from !== false ? sprintf(' FROM %s', $this->encloseInBacktick($from)) : '';
		$sql .= $where !== false ? sprintf(' WHERE (%s)', $where) : '';
		$sql .= $group !== false ? sprintf(' GROUP BY %s', $this->encloseInBacktick($group)) : '';
		$sql .= $having !== false ? sprintf(' HAVING %s', $having) : '';
		$sql .= $order !== false ? sprintf(' ORDER BY %s', $order) : '';
		$sql .= $limit !== false ? sprintf(' LIMIT %s', $limit) : '';

		return $sql;
	}


	/**
	 * Parsing update query
	 *
	 * @param array $query
	 * @return string
	 */
	private function parseUpdate(array $query): string
	{
		if (! isset($query['update'])) {
			return '';
		}

		$update = $query['update'];
		if (is_array($update)) {
			$update = implode(' ', $update);
		}

		if (isset($query['values']) && is_array($query['values'])) {
			$set = [];
			foreach ($query['values'] as $k => $v) {
				$set[] = $k.'='.$v;
			}
		} else {
			$set = $query['set'];
		}
		if (is_array($set)) {
			$set = implode(',', $set);
		}

		$where = $query['where'] ?? false;
		if (is_array($where)) {
			$where = implode(' AND ', $where);
		}

		$sql = sprintf("UPDATE %s SET %s", $update, $set);
		$sql .= $where === false ? '' : sprintf(' WHERE (%s)', $where);

		return $sql;
	}


	/**
	 * Parsing a variable value to be included in the querystring
	 *
	 * @param $var
	 * @param string $type
	 * @return
	 */
	public function parseVar($var, ?string $type = null)
	{
		$type = $type ?? '?str';
		$type = strtolower($type);

		// Checking for allowed NULL value in specified types:
		if ($type[0] === '?') {
			if (is_null($var)) {
				return 'NULL';
			}

			if(empty($var)) {
				//exceptions:
				if (!(in_array($type, ['?int', '?num'], true) && is_numeric($var))) {
					return 'NULL';
				}
			}

			$type = ltrim($type, '?');
		}

		// string can be specified the length of the string using any char sample: str#10 or str?25
		if (substr($type, 0, 3) === 'str') {
			if (is_null($var) || empty($var)) {
				return '';
			}

			$result = (strlen($type) > 3) ? substr($var, 0, intval(substr($type, 4))) : $var;
			$this->connect();
			$result = $this->oc->real_escape_string($result);
			return sprintf("'%s'", $result);
		}

		if ($type === 'bool') {
			return intval(boolval($var));
		}

		if ($type === 'dtm') {
			// for timestamp values use unix format:
			$var = (is_numeric($var)) ? sprintf('@%s', $var) : $var;

			$date = new \DateTimeImmutable($var);
			return sprintf("'%s'", $date->format('c'));
		}

		if ($type === 'int') {
			return sprintf('%d', intval($var));
		}

		if ($type === 'num') {
			return sprintf('%F', floatval($var));
		}

		if($type === 'ip') {
			if (filter_var($var, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
				return 'NULL';
			}
			return (sprintf('INET_ATON(%s)', $this->parseVar($var, 'str')));
		}

		if ($type === 'json') {
			return $this->parseVar(json_encode($var), 'str');
		}

		// for invalid type the result is not parsed
		return $var;
	}


	/**
	 * Similar with parse var but used to Parse multiple variables
	 *
	 * @param array $vars
	 * @param array $types
	 * @return array
	 */
	public function parseVars(array $vars, array $types = []): array
	{
		$fnParseVar = function ($value, $type) {
			return $this->parseVar($value, $type);
		};

		return array_map($fnParseVar, $vars, $types);
	}


	/**
	 * Execute query
	 */
	public function exec(array $query, $type, array $vars = [])
	{
		$sql = $this->parse($query, $type, $vars);

		if ($sql === '') {
			return false;
		}

		// debug part start
		$time = microtime(true);
		$debug = [
			$sql,
			$time
		];

		// execute query
		$this->connect();
		try {
			$result = $this->oc->query($sql);
		} catch (\mysqli_sql_exception $e) {
			$this->debug['sql'][] = array_merge($debug, [
				'KO',
				(microtime(true) - $time)
			]);
			$this->log->trace('MySQL Error on Db/'.$type.PHP_EOL, LOG_ERR);
			$this->log->trace($e->getMessage().PHP_EOL, LOG_ERR);
			return false;
		}

		// debug part end
		$this->debug['sql'][] = array_merge($debug, [
			'OK',
			(microtime(true) - $time)
		]);

		if ($type === 'select') {
			$rows = $result->fetch_all(MYSQLI_ASSOC);
			$result->free();
			return $rows;
		}

		if ($type === 'insert') {
			return $this->oc->insert_id;
		}

		// for update / delete:
		return true;
	}


	/**
	 * Return the last inserted id from table
	 */
	public function getLastId($table, $pk = 'id')
	{
		$query = [
			'select' => sprintf("MAX(`%s`) AS `lastId`", $pk),
			'from' => $table
		];

		$result = $this->getOneRow($query);

		return (int) $result['lastId'];
	}


	/**
	 * Parse element and include it in Backtick
	 *
	 * @param string $element
	 * @return string
	 */
	private function encloseInBacktick(string $element): string
	{
		// is the element start or end with backtick:
		if (substr($element, 0, 1) === '`' || substr($element, - 1, 1) === '`') {
			return $element;
		}

		// in case of aggregated functions ommit the enclosing
		if (preg_match('/[\'^£$%&*()}{@#~?><>|=+¬-]/', $element) || strpos($element, ' ') !== false) {
			return $element;
		}

		// is the element name contain dot:
		if (strpos($element, '.') !== false) {
			$elements = explode('.', $element);
			$element = implode('`.`', $elements);
		}

		// is the element name contain comma:
		if (strpos($element, ',') !== false) {
			$elements = explode(',', $element);
			$element = implode('`,`', $elements);
		}

		return sprintf('`%s`', $element);
	}
}
