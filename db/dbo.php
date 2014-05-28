<?php
include_once 'config/config.php';


define('DBO_CORE','core');

define('_DBO_STANDARD_SERVER',$siteconfig['db_server']);
define('_DBO_STANDARD_DB',$siteconfig['db_name']);
define('_DBO_STANDARD_USER',$siteconfig['db_user']);
define('_DBO_STANDARD_PASS',$siteconfig['db_pass']);

define('DBO_STANDARD_KEY','core');

define('DATE_MYSQL','Y-m-d H:i:s');

class dbo  
{
    private static $_someConnection;
    public static $_queries = array();
    public static $counter = 0;
    public static $instrument = false;
    private static $_cache = array();
    
    public static $logall = false;
    
    const TABLE_PREFIX = 'led_';
    
    public static $_databases = array(
        DBO_CORE=>array('cache'=>null, 
            'server'=>_DBO_STANDARD_SERVER,
            'database'=>_DBO_STANDARD_DB,
            'user'=>_DBO_STANDARD_USER,
            'pass'=>_DBO_STANDARD_PASS)
        );

    public static function getConnection($connectionKey)
    {
        if ($connectionKey == NULL) $connectionKey= DBO_STANDARD_KEY;
        if (isset(self::$_databases[$connectionKey]['cache']))
        {
            $cnx = self::$_databases[$connectionKey]['cache'];
        }
        else
        {
            if (array_key_exists($connectionKey, self::$_databases))
            {
                $pDb = self::$_databases[$connectionKey];
            }
            else
            {
                $pDb = self::$_databases[DBO_STANDARD_KEY];
            }
            $cnx = mysqli_connect($pDb['server'],$pDb['user'],$pDb['pass'],$pDb['database']);
            self::$_databases[$connectionKey]['cache'] = $cnx;
        }
        return $cnx;
    }
    
    private static function _escape(array $args, $cnx)
    {
        for ($i = 0; $i < sizeof($args); $i++)
        {
            if (is_array($args[$i]))
            {
                foreach ($args[$i] as $key=>$arg)
                {
                    if (is_string($arg))
                    {
                        $args[$i][$key] = mysqli_escape_string($cnx,$arg);
                    }
                }
                $args[$i] = '\''.implode('\',\'',$args[$i]).'\'';
            }
            elseif (!is_numeric($args[$i]))
            {
                $args[$i] = mysqli_escape_string($cnx,$args[$i]);
            }
        }
        return $args;
    }    

    
    public static function lastId($datakey = DBO_STANDARD_KEY)
    {
        $cnx = self::getConnection($datakey);
        return mysqli_insert_id($cnx);    
    }
    public static function lastErr($dataKey = DBO_STANDARD_KEY)
    {
        $cnx = self::getConnection($dataKey);
        return mysqli_error($cnx);
    }

    public static function checkInstrument()
    {
        return true;
        if (empty(self::$instrument))
        {
            self::$instrument = true;
        }
        return self::$instrument;
    }
    
    public static function ClearCacheKey($key)
    {
        unset(self::$_cache[$key]);
    }
    public static function GetCacheKey($key)
    {
        if (array_key_exists($key, self::$_cache))     return self::$_cache[$key];
        return null;
    }
    public static function SetCacheKey($key, $value)
    {
        self::$_cache[$key] = $value;
    }
    
    public static function prefixTable($tableName)
    {
        return self::TABLE_PREFIX.$tableName;        
    }
    
    public static function logError($message, $sql)
    {
        error_log($message.' :: '.$sql);
    }
    public static function renderSql($sql, $params, $datakey = DBO_STANDARD_KEY)
    {
        $cnx = self::getConnection($datakey);
        if (!$cnx) throw new Exception('Database Error, could not connection to '.$datakey);
        
        $params = self::_escape($params, $cnx);
        $cleanedSql = vsprintf($sql, $params);
        return $cleanedSql;        
    }
    public static function query($sql, $params = array(), $datakey = DBO_STANDARD_KEY, $debug=false)
    {

        if (self::checkInstrument())
        {
            $start = microtime(true);
        }
        if (!is_array($params))
        {
            $params = array($params);
        }

        $cnx = self::getConnection($datakey);
        if (!$cnx) throw new Exception('Database Error, could not connection to '.$datakey);
        
        $params = self::_escape($params, $cnx);
        $cleanedSql = vsprintf($sql, $params);
        
        if (self::$logall)
        {
            error_log($cleanedSql);
        }
        
        if ($debug) echo '<br />DEBUG!!<br />'.$cleanedSql.'<br/>';
        if (array_key_exists('showsql', $_REQUEST))
        {
            echo '<br />DEBUG!!<br />'.$cleanedSql.'<br/>';
        }
        $ret = mysqli_query($cnx, $cleanedSql);
        if (!$ret)
        {
            self::logError('INVALID SQL', str_replace(array("    ","\n"), ' ', $cleanedSql));
            self::logError('INFO', self::lastErr() );
        }
        if (self::checkInstrument())
        {
            $end = microtime(true);
            array_push(self::$_queries, array('sql'=>$cleanedSql, 'start'=>$start, 'end'=>$end,'diff'=>$end-$start));
        }
        
        return $ret;
    }
    
    
}

