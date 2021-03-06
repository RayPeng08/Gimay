<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-12
 * Time: 15:53
 * MySQL数据库封装类,支持异步操作
 */
namespace Gimay\Database;
use Gimay;
class MySQLi extends \mysqli implements Gimay\IDatabase
{
    const DEFAULT_PORT = 3306;

    public $debug = false;
    public $conn = null;
    public $config;

    function __construct($db_config)
    {
        if (empty($db_config['port']))
        {
            $db_config['port'] = self::DEFAULT_PORT;
        }
        $this->config = $db_config;
    }

    function lastInsertId()
    {
        return $this->insert_id;
    }

    /**
     * 参数为了兼容parent类，代码不会使用传入的参数作为配置
     * @param null $_host
     * @param null $user
     * @param null $password
     * @param null $database
     * @param null $port
     * @param null $socket
     * @return bool
     */
    function connect($_host = null, $user = null, $password = null, $database = null, $port = null, $socket = null)
    {
        $db_config = &$this->config;
        $host = $db_config['host'];
        if (!empty($db_config['persistent']))
        {
            $host = 'p:' . $host;
        }
        if (isset($db_config['passwd']))
        {
            $db_config['password'] = $db_config['passwd'];
        }
        if (isset($db_config['dbname']))
        {
            $db_config['name'] = $db_config['dbname'];
        }
        parent::connect(
            $host,
            $db_config['user'],
            $db_config['password'],
            $db_config['name'],
            $db_config['port']
        );
        if (mysqli_connect_errno())
        {
            trigger_error("Mysqli 连接失败: " . mysqli_connect_error());
            return false;
        }
        if (!empty($db_config['charset']))
        {
            $this->set_charset($db_config['charset']);
        }
        return true;
    }

    /**
     * 过滤特殊字符
     * @param $value
     * @return string
     */
    function quote($value)
    {
        return $this->tryReconnect(array($this, 'escape_string'), array($value));
    }

    /**
     * SQL错误信息
     * @param $sql
     * @return string
     */
    protected function errorMessage($sql)
    {
        $msg = $this->error . "<hr />$sql<hr />";
        $msg .= "Server: {$this->config['host']}:{$this->config['port']}. <br/>";
        $msg .= "Message: {$this->error} <br/>";
        $msg .= "Errno: {$this->errno}";
        return $msg;
    }

    protected function tryReconnect($call, $params)
    {
        $result = false;
        for ($i = 0; $i < 2; $i++)
        {
            $result = call_user_func_array($call, $params);
            if ($result === false)
            {
                if ($this->errno == 2013 or $this->errno == 2006)
                {
                    $r = $this->checkConnection();
                    if ($r === true)
                    {
                        continue;
                    }
                }
                else
                {
                    Gimay\Error::info(__CLASS__ . " SQL Error", $this->errorMessage($params[0]),701);
                    return false;
                }
            }
            break;
        }
        return $result;
    }

    /**
     * 执行一个SQL语句
     * @param string $sql 执行的SQL语句
     * @return MySQLiRecord | false
     */
    function query($sql)
    {
        $result = $this->tryReconnect(array('parent', 'query'), array($sql));
        if (!$result)
        {
            Gimay\Error::info(__CLASS__." SQL Error", $this->errorMessage($sql),703);
            return false;
        }
        return new MySQLiRecord($result);
    }

    /**
     * 异步SQL
     * @param $sql
     * @return bool|\mysqli_result
     */
    function queryAsync($sql)
    {
        $result = $this->tryReconnect(array('parent', 'query'), array($sql, MYSQLI_ASYNC));
        if (!$result)
        {
            Gimay\Error::info(__CLASS__." SQL Error", $this->errorMessage($sql),703);
            return false;
        }
        return $result;
    }

    /**
     * 检查数据库连接,是否有效，无效则重新建立
     */
    protected function checkConnection()
    {
        if (!@$this->ping())
        {
            $this->close();
            return $this->connect();
        }
        return true;
    }

    /**
     * 获取错误码
     * @return int
     */
    function errno()
    {
        return $this->errno;
    }

    /**
     * 获取受影响的行数
     * @return int
     */
    function getAffectedRows()
    {
        return $this->affected_rows;
    }

    /**
     * 返回上一个Insert语句的自增主键ID
     * @return int
     */
    function Insert_ID()
    {
        return $this->insert_id;
    }
}

class MySQLiRecord implements Gimay\IDbRecord
{
    /**
     * @var \mysqli_result
     */
    public $result;

    function __construct($result)
    {
        $this->result = $result;
    }

    function fetch()
    {
        return $this->result->fetch_assoc();
    }

    function fetchall()
    {
        $data = array();
        while ($record = $this->result->fetch_assoc())
        {
            $data[] = $record;
        }
        return $data;
    }

    function free()
    {
        $this->result->free_result();
    }

    function __get($key)
    {
        return $this->result->$key;
    }

    function __call($func, $params)
    {
        return call_user_func_array(array($this->result, $func), $params);
    }
}
