<?php
require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'init.inc.php';

class Pix_Cache_TestCassandra extends PHPUnit_Framework_TestCase
{
    private static $cache;
    private static $pdo;

    public function setUp()
    {
        if (!extension_loaded('pdo_cassandra')) {
            $this->markTestSkipped('pdo_cassandra not available, skipping Pix_Cache_TestCassandra tests');
        }
    }

    public static function setUpBeforeClass()
    {
        if (!extension_loaded('pdo_cassandra')) {
            return;
        }

        foreach (explode(',', PIXCORE_TEST_CASSANDRAHOST) as $host) {
            $port = PIXCORE_TEST_CASSANDRAPORT;
            $hosts[] = array('host' => $host, 'port' => $port);
            $dsn_hosts[] = "host=$host;port=$port";
        }
        $dsn = 'cassandra:' . implode(',', $dsn_hosts);

        self::$pdo = $pdo = new PDO($dsn, null, null, array(PDO::ATTR_TIMEOUT => 3000, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        try {
            $pdo->exec("DROP KEYSPACE " . PIXCORE_TEST_CASSANDRAKEYSPACE);
        } catch (PDOException $e) {
            // ignore
        }
        $pdo->exec("CREATE KEYSPACE " . PIXCORE_TEST_CASSANDRAKEYSPACE . " WITH strategy_class = 'SimpleStrategy'" .
            " AND strategy_options:replication_factor = 1");
        $pdo->exec("USE " . PIXCORE_TEST_CASSANDRAKEYSPACE);
        $pdo->exec("CREATE COLUMNFAMILY Test (KEY text PRIMARY KEY) WITH comparator = LongType AND read_repair_chance = 0");

        Pix_Cache::addServer('Pix_Cache_Core_Cassandra', array(
            'hosts' => $hosts,
            'keyspace' => PIXCORE_TEST_CASSANDRAKEYSPACE,
            'column_family' => 'Test',
            'auto_history' => 5
        ), 'test_cassandra');
	$cache = new Pix_Cache('test_cassandra');
        self::$cache = $cache;
    }

    public static function tearDownAfterClass()
    {
        try {
            self::$pdo->exec("DROP KEYSPACE " . PIXCORE_TEST_CASSANDRAKEYSPACE);
        } catch (PDOException $e) {
            // ignore
        }
    }

    public function testSetWithTTL()
    {
        $cache = self::$cache;
        $this->assertTrue($cache->set('key1', 'value1', 1));
        $this->assertEquals('value1', $cache->get('key1'));
        sleep(2);
        $this->assertFalse($cache->get('key1'));
    }

    public function testSetWithoutTTL()
    {
        $cache = self::$cache;
        $this->assertTrue($cache->set('key2', 'value2'));
        $this->assertEquals('value2', $cache->get('key2'));
    }

    public function testAddWithKeyExist()
    {
        $cache = self::$cache;
        $this->assertTrue($cache->set('key3', 'value3'));
        $this->assertFalse($cache->add('key3', 'addvalue3'));
        $this->assertEquals('value3', $cache->get('key3'));
    }

    public function testAddWithoutKeyExist()
    {
        $cache = self::$cache;
        $this->assertTrue($cache->add('key4', 'value4'));
        $this->assertEquals('value4', $cache->get('key4'));
    }

    public function testDeleteAfterSet()
    {
        $cache = self::$cache;
        $this->assertTrue($cache->set('key5', 'value5'));
        $this->assertTrue($cache->remove('key5'));
        $this->assertFalse($cache->get('key5'));
    }

    public function testReplaceWithoutKeyExist()
    {
        $cache = self::$cache;
        $this->assertFalse($cache->replace('key7', 'replace7'));
        $this->assertFalse($cache->get('key7'));
    }

    public function testReplaceWithKeyExist()
    {
        $cache = self::$cache;
        $this->assertTrue($cache->set('key8', 'value8'));
        $this->assertTrue($cache->replace('key8', 'replace8'));
        $this->assertEquals('replace8', $cache->get('key8'));
    }

    public function testReplaceWithKeyExistAndTTL()
    {
        $cache = self::$cache;
        $this->assertTrue($cache->set('key9', 'value9'));
        $this->assertTrue($cache->replace('key9', 'replace9', 1));
        $this->assertEquals('replace9', $cache->get('key9'));
        sleep(2);
        $this->assertFalse($cache->get('key9'));
    }

    /**
     * @expectedException Pix_Exception
     */
    public function testIncr()
    {
        $cache = self::$cache;
        $cache->inc('key10');
    }

    /**
     * @expectedException Pix_Exception
     */
    public function testDecr()
    {
        $cache = self::$cache;
        $cache->dec('key11');
    }

    public function testSetWithAsciiKey()
    {
        $cache = self::$cache;
        for ($ascii = 32; $ascii < 126; $ascii++) {
            $key = 'k' . chr($ascii) . 'e' . chr($ascii) . 'y';
            $value = 'value' . $ascii;
            $this->assertTrue($cache->set($key, $value));
            $this->assertEquals($value, $cache->get($key));
        }
    }

    public function testValueWithSingleQuote()
    {
        $cache = self::$cache;
        $this->assertTrue($cache->set('foo', "'"));
        $this->assertEquals($cache->get('foo'), "'");
    }

    public function testKeyWithSingleQuote()
    {
        $cache = self::$cache;
        $this->assertTrue($cache->set("'", 'foo'));
        $this->assertEquals($cache->get("'"), 'foo');
        $this->assertTrue($cache->remove("'"));
        $this->assertFalse($cache->get("'"));
    }

    public function testSetMultiKeys()
    {
        $cache = self::$cache;
        foreach (range(12, 20) as $number) {
            $key = 'key' . $number;
            $keys[] = $key;
            $value = $number . 'value';
            $set_data[$key] = $value;
        }

        $cache->sets($set_data);

        $keys[] = 'key21';
        $keys[] = 'key22';

        $get_data = $cache->gets($keys);
        foreach ($get_data as $key => $value) {
            $this->assertEquals($set_data[$key], $value);
            unset($set_data[$key]);
        }

        $this->assertEquals(count($set_data), 0);
    }

    public function testGetMultiKeys()
    {
        $cache = self::$cache;
        $data = array();
        for ($ascii = 32; $ascii < 126; $ascii++) {
            $key = 'k' . chr($ascii) . 'e' . chr($ascii) . 'y';
            $value = 'value' . $ascii;
            $data[$key] = $value;
            $this->assertTrue($cache->set($key, $value));
            $this->assertEquals($value, $cache->get($key));
        }

        foreach ($cache->gets(array_keys($data)) as $key => $value) {
            $this->assertEquals($data[$key], $value);
            unset($data[$key]);
        }
        $this->assertEquals(count($data), 0);
    }

    public function testAutoHistory()
    {
        $count = 3;
        $cache = self::$cache;

        foreach (range(0, $count - 1) as $i) {
            $time = time();
            $times[] = $time;
            $this->assertTrue($cache->set('key23', $time));
            sleep(1);
        }

        $stmt = self::$pdo->query("SELECT REVERSED * FROM Test WHERE KEY = 'key23'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        unset($row['KEY']);
        $this->assertEquals(count($row), $count + 1);
        $this->assertEquals($times[$count - 1], $row[0]);
        unset($row[0]);
        foreach ($row as $column => $value) {
            $column = intval($column / 1000);
            $this->assertEquals($column, $value);
        }
        sleep(6);

        $stmt = self::$pdo->query("SELECT REVERSED * FROM Test WHERE KEY = 'key23'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        unset($row['KEY']);
        $this->assertEquals(count($row), 1);
        $this->assertEquals($times[$count - 1], $row[0]);
    }
}
