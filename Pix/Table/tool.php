#!/usr/local/bin/php
<?php

function readtext($Prompt, $silent=False)
{
    $Options="-er";
    if ($silent == true) {
	$Options = $Options . " -s";
    }
    echo $Prompt;
    $Returned = popen("bash -c 'read -s; echo \$REPLY'","r");
    $TextEntered = fgets($Returned,100);
    pclose($Returned);
    $TextEntered = trim($TextEntered);
    if ($silent == True) {
	Print "\n";
	@ob_flush();
	flush();
    }
    return $TextEntered;
}

if (count($_SERVER['argv']) == 1) {
    echo "usage: php tool.php [-c] [-h DBHOST | -u DBUSER | -p | -pDBPASS] DB TABLE1 [TABLE2 TABLE3 ...]\n";
    echo "-c 表示有 cluster ， 不加表示沒有 cluster\n";
    exit(0);
}

$isCluster = false;
for ($index = 0; isset($_SERVER['argv'][$index]); $index ++ ) {
    if ($index == 0) continue;
    $argv = $_SERVER['argv'][$index];

    if ($argv == '-h') {
	$host = $_SERVER['argv'][++$index];
    } elseif ($argv == '-c') {
	$isCluster = true;
    } elseif ($argv == '-u') {
	$user = $_SERVER['argv'][++$index];
    } elseif (preg_match('#^-p(.*)$#', $argv, $ret)) {
	if (trim($ret[1])) {
	    $pass = trim($ret[1]);
	} else {
	    $pass = readtext('Password: ', true);
	}
    } else {
	$db = $argv;
	$index ++;
	break;
    }
}

$tables = array();

for (; isset($_SERVER['argv'][$index]); $index ++) {
    $tables[] = trim($_SERVER['argv'][$index]);
}


$link = new mysqli;
$link->connect($host, $user, $pass);
$link->select_db($db);

$now = date(DATE_RFC2822, time());

echo "<?php\n\n/* Auto-generating in $now */\n";
foreach ($tables as $table) {
    $primary = array();
    $fields = array();
    $res = $link->query("DESCRIBE `$table`"); 

    while ($row = $res->fetch_assoc()) {
	$name= $row['Field'];

	$type = $row['Type'];
	if (preg_match('#^([a-z]+)\((\d+)\)\s?(unsigned)?$#', $type, $ret)) {
	    $fields[$name]['type'] = $ret[1];
	    $fields[$name]['size'] = $ret[2];
	    if ($ret[3] == 'unsigned') {
		$fields[$name]['unsigned'] = true; 
	    }
	} elseif (preg_match('#^([a-z]+)$#', $type, $ret)) {
	    $fields[$name]['type'] = $ret[1];
	} elseif (preg_match('#^enum\((.*)\)$#', $type, $ret)) {
	    $fields[$name]['type'] = 'enum';
	    $fields[$name]['list'] = $ret[1];
	} elseif (preg_match('#^set\((.*)\)$#', $type, $ret)) {
	    $fields[$name]['type'] = 'set';
	    $fields[$name]['list'] = $ret[1];
	} else {
	    echo "Unknown: $type\n";
	    // TODO: Enum
	}

	if ($row['Key'] == 'PRI') {
	    $primary[] = $name;
	}

	if ($row['Default'] != 'NULL') { 
	    $fields[$name]['default'] = $row['Default'];
	}
	if ($row['Extra'] == 'auto_increment') { 
	    $fields[$name]['auto_increment'] = true;;
	}
    }

    $res = $link->query("SHOW INDEX FROM `$table`");

    $indexes = array();
    while ($row = $res->fetch_assoc()) {
	if ($row['Key_name'] == 'PRIMARY') continue;
	$indexes[$row['Key_name']][$row['Seq_in_index']] = $row['Column_name'];
    }
?>
class <?= ucfirst($table) ?> extends Pix_Table_Cluster
{
    public $_name = '<?= $table; ?>';

<?php if (!$isCluster) { ?>
    public function getLink($type)
    {
	return DbLib::getSingleLink('foo-db', $type);
    }	
<?php } else { ?>
    public function getMappingLink($mapping, $type)
    {
        return DbLib::getSingleLink('foo-db', $type);
    }

    public function getClusterLink($mapping, $type, $idx)
    {
	return DbLib::getSingleLink('foo-db', $type, $idx);
    }
<?php } ?>

    public function __construct()
    {
	<?php
	    if (count($primary) == 1) { 
		echo "\$this->_primary = '$primary[0]';";
	    } else {
		echo "\$this->_primary = array('".implode("', '", $primary) ."');";
	    }
	?>


<?php
foreach ($fields as $name => $field) {
    $params = array();
    $params[] = "'type' => '".$field['type']."'";
    if ($field['list']) {
	$params[] = "'list' => array(".$field['list'].")";
    }
    if ($field['size']) {
	$params[] = "'size' => '".$field['size']."'";
    }
    if ($field['unsigned']) {
	$params[] = "'unsigned' => true";
    }
    if ($field['default']) {
	$params[] = "'default' => '".$field['default']."'";
    }
    echo "\t\$this->_columns['$name'] = array(";
    echo implode(', ', $params);
    echo ");\n";
}
echo "\n";
foreach ($indexes as $name => $index) {
    echo "\t\$this->_indexes['$name'] = array('";
    echo implode("', '", $index);
    echo "');\n";
}

foreach ($fields as $name => $field) {
    if (preg_match('#^(.+)_id$#', $name, $ret)) {
	if ($ret[1] == $table) continue;
	echo "        //\$this->_relations['$name'] = array('rel' => 'has_one', 'type' => '".ucfirst($ret[1])."');\n";


    }
}
?><?php if ($isCluster) { ?>
        // $this->_mappings['default'] = array('belong' => '<?= $table ?>'); // belong 後面是 relation name 不是 class name, 所以一定要存在 $_relations['<?= $table ?>']
<?php } ?>
    }
}

<?
}

