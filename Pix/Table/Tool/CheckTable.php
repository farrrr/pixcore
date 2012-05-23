<?php

/**
 * Pix_Table_Tool_CheckTable 檢查所有 model 是否與 db 上 schema 相同的工具
 * 
 * @copyright 2003-2010 PIXNET
 * @author Shang-Rung Wang <srwang@pixnet.tw> 
 */
class Pix_Table_Tool_CheckTable
{
    /**
     * _walkDir 爬所有的資料夾
     *
     * @param string $dir 要爬的資料夾
     * @param callback $callback 爬到的檔案所執行的 callback
     * @static
     * @access protected
     * @return void
     */
    static protected function _walkDir($dir, $callback)
    {
        $d = opendir($dir);
        while ($f = readdir($d)) {
            if ('.' == $f or '..' == $f) {
                continue;
            }

            if (is_dir($dir . '/' . $f)) {
                self::_walkDir($dir . '/' . $f, $callback);
            }

            if (preg_match('#\.php$#', $f)) {
                $callback($dir . '/' . $f);
            }
        }
    }

    /**
     * check 
     * 
     * @param array $files 資料夾或是檔案
     * @access public
     * @return void
     */
    public function check($files)
    {
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::_walkDir($file, function($f) { require_once($f); });
            } elseif (is_file($file)) {
                require_once($file);
            }
        }

        $ret = array();
        foreach (get_declared_classes() as $class) {
            if (!is_a($class, 'Pix_Table')) {
                continue;
            }
            $testClass = new ReflectionClass($class);
            if ($testClass->isAbstract()) {
                continue;
            }

            $table = Pix_Table::getTable($class);
            try {
                $ret = array_merge($ret, @$table->checkTable());
            } catch (Exception $e) {
                $ret[] = array(
                    '',
                    $table,
                    '',
                    $e->getMessage(),
                );
            }
        }

        return $ret;
    }

    /**
     * main
     *
     * @static
     * @access public
     * @return void
     */
    static public function main()
    {
        if ($_SERVER['argv'][1] == '--help') {

?>
php modelcheck.php [--options...] [directory or .php ...]
--default               檢查預設值(預設: false)
--no-default            不檢查預設值
--autoincrement         檢查 autoincrement(預設: true)
--no-autoincrement      不檢查 auto_increment
--unsigned              檢查 unsigned(預設: false)
--no-unsigned           不檢查 unsigned
--size                  檢查 size(預設: false)
--no-size               不檢查 size
--type                  檢查 type(預設: true)
--no-type               不檢查 type
<?php
            exit;
        }

        $argv = $_SERVER['argv'];
        array_shift($argv);

        $options = array('default' => 0, 'autoincrement' => 1, 'unsigned' => 0, 'size' => 0, 'type' => 1);
        $files = array();
        foreach ($argv as $option) {
            if (substr($option, 0, 2) != '--') {
                $files[] = $option;
                continue;
            }

            $option = substr($option, 2);
            if (substr($option, 0, 3) == 'no-') {
                $option = substr($option, 3);
                if (isset($options[$option])) {
                    $options[$option] = 0;
                    continue;
                }
            }
            if (isset($options[$option])) {
                $options[$option] = 1;
            }
        }

        /** 你可以在這下面開始下 Test Code **/
        $ret = self::check($files);

        foreach ($ret as $error) {
            $groups = explode('|', $error[0]);

            foreach ($groups as $group) {
                if (isset($options[$group]) and $options[$group] == 0) {
                    continue 2;
                }
            }
            echo "Table({$error[1]->getClass()}) {$error[3]}\n";
        }
    }
}
