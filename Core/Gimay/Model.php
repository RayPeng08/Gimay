<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-13
 * Time: 16:27
 * Model类，ORM基础类，提供对某个数据库表的接口
 */
namespace Gimay;
class Model
{
    /**
     * @var IDatabase
     */
    public $db;
    public $gimay;

    public $primary = "id";
    protected $autoinc = false;

    public $name = "";
    public $tablePrefix = '';
    public $config;
    protected $_table_before_shard;

    /**
     * 表切片参数,分表
     *
     * @var int
     */
    public $tablesize = 1000000;
    public $select = '*';

    // 最近错误信息
    public $error = '';
    // 字段信息
    public $fields = array();
    // 数据信息
    protected $data = array();
    // 是否自动检测数据表字段信息
    protected $autoCheckFields = true;
    //最后执行的sql数据
    public $lastsql = '';
    public $create_sql = '';
    //开启缓存
    public $auto_cache = false;
    public $cache_lifetime = 300;
    public $cache_prefix = 'gimay_model_';

    /**
     * 构造函数
     * @param \Gimay $Gimay
     * @param string $db_key 选择哪个数据库
     */
    function __construct(\Gimay $gimay, $db_key = 'master')
    {
        $this->db = $gimay->db($db_key);
        if (empty($this->name)) {
            $this->name = $this->getModelName();
        }
        $this->config = $this->db->config;
        if (!empty($this->config)) {
            $this->tablePrefix = $this->config['prefix'];
        }
        $this->name = $this->tablePrefix . $this->name;
        $this->gimay = $gimay;
        //$this->cache();//开启缓存
        if ($this->db->check_status()) {
            // 字段检测
            if (!empty($this->name) && $this->autoCheckFields) {
                $this->_checkTableInfo();
            }
        }
    }

    /**
     * 启用缓存
     * @param int $lifetime
     */
    function cache($lifetime = 300)
    {
        $this->auto_cache = true;
        $this->cache_lifetime = $lifetime;
    }

    /**
     * 得到当前的数据对象名称
     * @access public
     * @return string
     */
    public function getModelName()
    {
        if (empty($this->name)) {
            $name = get_class($this);
            if ($pos = strrpos($name, '\\')) {//有命名空间
                $this->name = substr($name, $pos + 1);
            } else {
                $this->name = $name;
            }
        }
        return $this->name;
    }

    /**
     * 自动检测数据表信息
     * @access protected
     * @return void
     */
    protected function _checkTableInfo()
    {
        if (empty($this->fields)) {
            // 设置缓存,如果设置则从缓存中读取
            if ($this->auto_cache) {
                $cache_key = $this->cache_prefix . '_' . $this->config['name'] . '_' . $this->name;
                $record = $this->gimay->cache->get($cache_key);
                if (empty($record)) {
                    $this->flush();
                    $record = $this->fields;
                    if (!empty($record)) {
                        $this->gimay->cache->set($cache_key, $record, $this->cache_lifetime);
                    }
                } else {
                    $this->fields = $record;
                }
            } else {
                $this->flush();
            }
        }
    }

    /**
     * 获取字段信息并缓存
     * @access public
     * @return void
     */
    protected function flush()
    {
        $fields = $this->getFields($this->name);
        if (!$fields) { // 无法获取字段信息
            return false;
        }
        $this->fields = array_keys($fields);
        unset($this->fields['_pk']);
        foreach ($fields as $key => $val) {
            // 记录字段类型
            $type[$key] = $val['type'];
            if ($val['primary']) {
                // 增加复合主键支持
                if (isset($this->fields['_pk']) && $this->fields['_pk'] != null) {
                    if (is_string($this->fields['_pk'])) {
                        $this->primary = array($this->fields['_pk']);
                        $this->fields['_pk'] = $this->primary;
                    }
                    $this->primary[] = $key;
                    $this->fields['_pk'][] = $key;
                } else {
                    $this->primary = $key;
                    $this->fields['_pk'] = $key;
                }
                if ($val['autoinc']) $this->autoinc = true;
            }
        }
        // 记录字段类型信息
        $this->fields['_type'] = $type;
    }

    /**
     * 取得数据表的字段信息
     * @access public
     */
    protected function getFields($tableName)
    {
        $sqlstr = 'select COLUMN_NAME,COLUMN_DEFAULT,IS_NULLABLE,DATA_TYPE,COLUMN_TYPE,COLUMN_KEY,EXTRA'
            . " from information_schema.columns"
            . " where TABLE_SCHEMA='" . $this->config['name'] . "' and TABLE_NAME='" . $tableName . "'"
            . " ORDER BY ORDINAL_POSITION";
        $result = $this->db->query($sqlstr)->fetchall();
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $info[trim($val['COLUMN_NAME'])] = array(
                    'name' => trim($val['COLUMN_NAME']),
                    'type' => trim($val['DATA_TYPE']),
                    'notnull' => (bool)($val['IS_NULLABLE'] == 'NO'), // NO表示不为Null
                    'default' => $val['COLUMN_DEFAULT'],
                    'primary' => (bool)($val['COLUMN_KEY'] == 'PRI'), // PRI表示主键
                    'autoinc' => (bool)($val['EXTRA'] == 'auto_increment'), // auto_increment表示自增
                );
            }
        }
        return $info;
    }

    /**
     * 设置数据对象的值
     * @access public
     * @param string $name 名称
     * @param mixed $value 值
     * @return void
     */
    public function __set($name, $value)
    {
        // 设置数据对象属性
        $this->data[$name] = $value;
    }

    /**
     * 获取数据对象的值
     * @access public
     * @param string $name 名称
     * @return mixed
     */
    public function __get($name)
    {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    /**
     * 检测数据对象的值
     * @access public
     * @param string $name 名称
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    /**
     * 销毁数据对象的值
     * @access public
     * @param string $name 名称
     * @return void
     */
    public function __unset($name)
    {
        unset($this->data[$name]);
    }

    /**
     * 利用__call方法实现一些特殊的Model方法
     * @access public
     * @param string $method 方法名称
     * @param array $args 调用参数
     * @return mixed
     */
    public function __call($method, $args = null)
    {
        $method = strtolower($method);
        if (in_array($method, array('from', 'field', 'distinct', 'where', 'orwhere', 'group', 'having', 'order', 'limit', 'pagesize', 'page', 'union', 'init', 'alias'), true)) {
            $this->db->db_apt->$method($args[0]);
            return $this;
        } else if (in_array($method, array('equal', 'like', 'notlike', 'in', 'notin', 'join', 'leftjoin', 'rightjoin'), true)) {
            $this->db->db_apt->$method($args[0], $args[1]);
            return $this;
        } else if (in_array($method, array('find', 'count', 'sum', 'min', 'max', 'avg'), true)) {
            if (empty($this->db->db_apt->table)) {
                $this->db->db_apt->table = $this->name;
            }
            $this->db->db_apt->is_execute = 0;
            return $this->db->db_apt->$method($args);
        } else if (in_array($method, array('select', 'getsql', 'paging'), true)) {
            if (empty($this->db->db_apt->table)) {
                $this->db->db_apt->table = $this->name;
            }
            $this->db->db_apt->is_execute = 0;
            return $this->db->db_apt->$method();
        }
    }

    /**
     * 得到完整的数据表名
     * @access public
     * @return string
     */
    public function getTableName()
    {
        return $this->config['name'] . '.' . $this->tablePrefix . $this->name;
    }

    /**
     * 按ID切分表
     *
     * @param $id
     * @return null
     */
    function shard_table($id)
    {
        if (empty($this->_table_before_shard)) {
            $this->_table_before_shard = $this->name;
        }
        $table_id = intval($id / $this->tablesize);
        $this->name = $this->_table_before_shard . '_' . $table_id;
    }

    /**
     * 获取主键$primary_key为$object_id的一条记录对象(Record Object)
     * 如果参数为空的话，则返回一条空白的Record，可以赋值，产生一条新的记录
     * @param $object_id
     * @param $where
     * @return Record Object
     */
    public final function get($object_id = 0, $where = '')
    {
        return new Record($object_id, $this->db, $this->name, $this->primary, $where, $this->select);
    }

    /**
     * 获取表的一段数据，查询的参数由$params指定
     * @param $params
     * @param $pager Pager
     * @throws \Exception
     * @return array
     */
    public final function gets($params, &$pager = null)
    {
        if (empty($params)) {
            throw new \Exception("参数不能为空!");
        }

        $selectdb = new SelectDB($this->db);
        $selectdb->from($this->name);
        $selectdb->primary = $this->primary;
        $selectdb->select($this->select);

        if (!isset($params['order'])) {
            $params['order'] = "`{$this->name}`.{$this->primary} desc";
        }
        $selectdb->put($params);

        if (isset($params['page'])) {
            $selectdb->paging();
            $pager = $selectdb->pager;
        }

        return $selectdb->getall();
    }

    /**
     * 过滤表字段
     * @param $field string 表字段值
     * @rerurn string
     */
    public final function filterfield($field)
    {
        $result = '';
        $fieldlist = explode(',', $field);
        if (!empty($this->fields)) {
            foreach ($fieldlist as $item) {
                if (in_array($item, $this->fields)) {
                    if ($result != '') {
                        $result .= ',';
                    }
                    $result .= $item;
                }
            }
        }
        return $result;
    }

    /**
     * 设置表数据
     * @param $postdata array 表字段值
     */
    public final function filldata($postdata = null, $isadd = true)
    {
        if (empty($postdata)) {
            $postdata = $this->data;
        }
        $data = array();
        /* 如果有设置字段则先按字段设置数据 */
        if (!empty($this->db->db_apt->field) && $this->db->db_apt->field != '*') {
            $fieldlist = explode(',', $this->db->db_apt->field);
        } else {
            $fieldlist = $this->fields;
        }
        if (!empty($fieldlist)) {
            foreach ($postdata as $key => $value) {
                if (in_array($key, $fieldlist)) {
                    /* 特别处理,自增列主键自动转化为查询条件/去除新增数据 */
                    if ($this->primary == $key && $this->autoinc) {
                        if (!$isadd) {
                            $where = $key . '=';
                            if (is_numeric($value)) {
                                $where .= strval($value);
                            } else {
                                $where .= "'" . $value . "'";
                            }
                            $this->db->db_apt->where($where);
                        }
                    } else {
                        $data[$key] = $value;
                    }
                }
            }
        }
        if (!empty($data)) {
            $this->data = $data;
        }
    }

    /**
     * 从POST数据中获取数据
     */
    public final function create()
    {
        $postdata = $this->gimay->request->post;
        $this->data($postdata);
    }

    /**
     * 设置数据
     * @param $data Array 必须是键值（表的字段对应值）对应
     */
    public final function data($data = null)
    {
        if (!empty($data)) {
            $this->data = $data;
        }
    }

    /**
     * 插入一条新的记录到表
     * @param $data Array 必须是键值（表的字段对应值）对应
     * @return int
     */
    public final function add($data = null)
    {
        return $this->put($data);
    }

    /**
     * 插入一条新的记录到表
     * @param $data Array 必须是键值（表的字段对应值）对应
     * @return int
     */
    public final function put($data = null)
    {
        if ((empty($data) || !is_array($data)) && empty($this->data)) {
            $this->error = '数据不能为空!';
            return false;
        }
        /* 数据以最后提交的为准 */
        if (!empty($data) && is_array($data)) {
            $this->data = $data;
        }
        /* 数据校验和填充 */
        $this->filldata();
        if ($this->db->insert($this->data, $this->name)) {
            $this->data = array();
            return $this->db->lastInsertId();
        } else {
            $this->error = '添加数据失败!';
            return false;
        }
    }

    /**
     * 更新记录
     * @param $data Array 必须是键值（表的字段对应值）对应
     * @return bool
     */
    public final function save($data = null)
    {
        return $this->set($data);
    }

    /**
     * 更新记录
     * @param $data Array 必须是键值（表的字段对应值）对应
     * @return bool
     */
    public final function set($data = null)
    {
        if ((empty($data) || !is_array($data)) && empty($this->data)) {
            $this->error = '数据不能为空!';
            return false;
        }

        /* 数据以最后提交的为准 */
        if (!empty($data) && is_array($data)) {
            $this->data = $data;
        }
        /* 数据校验和填充 */
        $this->filldata();
        $where = $this->db->db_apt->where;
        if (!empty($where)) {
            $data = $this->data;
            $this->data = array();
            return $this->db->update(0, $data, $this->name, $where);
        } else {
            $this->error = '查询条件不能为空!';
            return false;
        }
    }

    /**
     * 更新一组数据
     * @param array $data 更新的数据
     * @param array $params update的参数列表
     * @return bool
     * @throws \Exception
     */
    public final function sets($data, $params)
    {
        if (empty($params)) {
            $this->error = '参数列表不能为空!';
            return false;
        }
        $selectdb = new SelectDB($this->db);
        $selectdb->from($this->name);
        $selectdb->put($params);
        return $selectdb->update($data);
    }

    /**
     * 删除数据
     * @param $id ,为空时,条件参照where
     * @return true/false
     */
    public final function delete($id = null)
    {
        $where = '';
        if (!empty($id)) {
            $where = 'where ' . $this->primary . ' in(' . $id . ')';
        } else if (!empty($this->db->db_apt->where)) {
            $where = $this->db->db_apt->where;
        }
        if (!empty($this->db->db_apt->order)) {
            $where .= ' ' . $this->db->db_apt->order;
        }
        if (!empty($this->db->db_apt->limit)) {
            $where .= ' ' . $this->db->db_apt->limit;
        }
        if (!empty($where)) {
            return $this->db->delete($id, $this->name, $where);
        } else {
            $this->error = '查询条件不能为空!';
            return false;
        }
    }

    /**
     * 删除一条数据主键为$id的记录，
     * @param $id
     * @param $where 指定匹配字段，默认为主键
     * @return true/false
     */
    public final function del($id, $where = null)
    {
        if ($where == null) $where = $this->primary;
        return $this->db->delete($id, $this->name, $where);
    }

    /**
     * 删除一条数据包含多个参数
     * @param array $params
     * @return true/false
     */
    public final function dels($params)
    {
        if (empty($params)) {
            throw new \Exception("参数不能为空!");
            return false;
        }
        $selectdb = new SelectDB($this->db);
        $selectdb->from($this->name);
        $selectdb->put($params);
        $selectdb->delete();
        return true;
    }

    /**
     * 获取到所有表记录的接口，通过这个接口可以访问到数据库的记录
     * @return RecordSet Object (这是一个接口，不包含实际的数据)
     */
    public final function all()
    {
        return new RecordSet($this->db, $this->name, $this->primary, $this->select);
    }

    /**
     * 建立表，必须在Model类中，指定create_sql
     * @return bool
     */
    function createTable()
    {
        if ($this->create_sql) {
            return $this->db->query($this->create_sql);
        } else {
            return false;
        }
    }

    /**
     * 获取表状态
     * @return array 表的status，包含了自增ID，计数器等状态数据
     */
    public final function getStatus()
    {
        return $this->db->query("show table status from " . DBNAME . " where name='{$this->name}'")->fetch();
    }

    /*
     * 读取字段值
     * @param $field 字段名,支持多个,用逗号分割
     * @param $param 附属参数,默认空,字段多于三个,输入分隔符将按第一字段为key,其他字段以分割符拼接为value,数字将取出对应数量记录
     * @return array
     */
    public final function getField($field, $param = null)
    {
        if (!empty($field)) {
            if (empty($this->db->db_apt->table)) {
                $this->db->db_apt->table = $this->name;
            }
            $this->db->db_apt->is_execute = 0;
            $split = ':';
            if (!empty($param)) {
                if (is_numeric($param)) {
                    $this->db->db_apt->limit($param);
                } else {
                    $split = $param;
                }
            }
            $result = $this->db->db_apt->select();
            $data = array();
            if ($result) {
                $fieldlist = explode(',', $field);
                foreach ($result as $item) {
                    if (count($fieldlist) == 1) {
                        $data[] = $item[$fieldlist[0]];
                    } else {
                        $value = $item[$fieldlist[1]];
                        for ($i = 2; $i < count($fieldlist); $i++) {
                            $value .= $split . $item[$fieldlist[$i]];
                        }
                        $data[$item[$fieldlist[0]]] = $value;
                    }
                }
            }
            return $data;
        } else {
            $this->error = '查询字段不能为空!';
            return false;
        }
    }

    /**
     * 检测是否存在数据，实际可以用count代替，0为false，>0为true
     * @return bool
     */
    function exists()
    {
        if (empty($this->db->db_apt->table)) {
            $this->db->db_apt->table = $this->name;
        }
        $this->db->db_apt->is_execute = 0;
        $c = $this->db->db_apt->count();

        if ($c > 0) return true;
        else return false;
    }

    /**
     * 获取表的字段描述
     * @return array
     */
    function desc()
    {
        return $this->db->query('describe ' . $this->name)->fetchall();
    }
}

/**
 * Record类，表中的一条记录，通过对象的操作，映射到数据库表
 * 可以使用属性访问，也可以通过关联数组方式访问
 */
class Record implements \ArrayAccess
{
    protected $_data = array();
    protected $_update = array();
    protected $_change = 0;
    protected $_save = false;

    /**
     * @var \Gimay\Database
     */
    public $db;

    public $primary = "id";
    public $table = "";


    public $_current_id = 0;
    public $_currend_key;

    const STATE_EMPTY = 0;
    const STATE_INSERT = 1;
    const STATE_UPDATE = 2;

    const CACHE_KEY_PREFIX = 'gimay_record_';

    /**
     * @param        $id
     * @param        $db \Gimay\Database
     * @param        $table
     * @param        $primary
     * @param string $where
     * @param string $select
     */
    function __construct($id, $db, $table, $primary, $where = '', $select = '*')
    {
        $this->db = $db;
        $this->_current_id = $id;
        $this->table = $table;
        $this->primary = $primary;

        if (empty($where)) {
            $where = $primary;
        }

        if (!empty($this->_current_id)) {
            if ($this->db->check_status()) {
                $res = $this->db->query("select {$select} from {$this->table} where {$where} ='{$id}' limit 1")->fetch();
                if (!empty($res)) {
                    $this->_data = $res;
                    $this->_current_id = $this->_data[$this->primary];
                    $this->_change = self::STATE_INSERT;
                }
            }
        }
    }

    /**
     * 是否存在
     * @return bool
     */
    function exist()
    {
        return !empty($this->_data);
    }

    /**
     * 将关联数组压入object中，赋值给各个字段
     * @param $data
     * @return unknown_type
     */
    function put($data)
    {
        if ($this->_change == self::STATE_INSERT) {
            $this->_change = self::STATE_UPDATE;
            $this->_update = $data;
        } elseif ($this->_change == self::STATE_EMPTY) {
            $this->_change = self::STATE_INSERT;
            $this->_data = $data;
        }
    }

    /**
     * 获取数据数组
     * @return mixed
     */
    function get()
    {
        return $this->_data;
    }

    /**
     * 获取属性
     * @param $property
     *
     * @return null
     */
    function __get($property)
    {
        if (isset($this->_data[$property])) {
            return $this->_data[$property];
        } else {
            Error::pecho("Record对象找不到 '$property' 属性.");
            return null;
        }
    }

    function __set($property, $value)
    {
        if ($this->_change == self::STATE_INSERT or $this->_change == self::STATE_UPDATE) {
            $this->_change = self::STATE_UPDATE;
            $this->_update[$property] = $value;
            $this->_data[$property] = $value;
        } else {
            $this->_data[$property] = $value;
        }
        $this->_save = true;
    }

    /**
     * 保存对象数据到数据库
     * 如果是空白的记录，保存则会Insert到数据库
     * 如果是已存在的记录，保持则会update，修改过的值，如果没有任何值被修改，则不执行SQL
     * @return unknown_type
     */
    function save()
    {
        if ($this->_change == 0 or $this->_change == 1) {
            $ret = $this->db->insert($this->_data, $this->table);
            if ($ret === false) {
                return false;
            }
            //改变状态
            $this->_change = 1;
            $this->_current_id = $this->db->lastInsertId();
        } elseif ($this->_change == 2) {
            $update = $this->_update;
            unset($update[$this->primary]);
            return $this->db->update($this->_current_id, $update, $this->table, $this->primary);
        }
        return true;
    }

    function update()
    {
        $update = $this->_data;
        unset($update[$this->primary]);
        return $this->db->update($this->_current_id, $this->_update, $this->table, $this->primary);
    }

    function __destruct()
    {
        if ($this->_save) {
            $this->save();
        }
    }

    /**
     * 删除数据库中的此条记录
     * @return unknown_type
     */
    function delete()
    {
        $this->db->delete($this->_current_id, $this->table, $this->primary);
    }

    function offsetExists($key)
    {
        return isset($this->_data[$key]);
    }

    function offsetGet($key)
    {
        return $this->_data[$key];
    }

    function offsetSet($key, $value)
    {
        $this->_data[$key] = $value;
    }

    function offsetUnset($key)
    {
        unset($this->_data[$key]);
    }
}

/**
 * 数据结果集，由Record组成
 * 通过foreach遍历，可以产生单条的Record对象，对每条数据进行操作
 */
class RecordSet implements \Iterator
{
    protected $_list = array();
    protected $table = '';
    protected $db;
    /**
     * @var SelectDb
     */
    protected $db_select;

    public $primary = "";

    public $_current_id = 0;

    function __construct($db, $table, $primary, $select)
    {
        $this->table = $table;
        $this->primary = $primary;
        $this->db = $db;
        $this->db_select = new SelectDB($db);
        $this->db_select->from($table);
        $this->db_select->primary = $primary;
        $this->db_select->select($select);
        $this->db_select->order($this->primary . " desc");
    }

    /**
     * 获取得到的数据
     * @return array
     */
    function get()
    {
        return $this->_list;
    }

    /**
     * 制定查询的参数，再调用数据之前进行
     * 参数为SQL SelectDB的put语句
     * @param array $params
     * @return bool
     */
    function params($params)
    {
        return $this->db_select->put($params);
    }

    /**
     * 过滤器语法，参数为SQL SelectDB的where语句
     * @param array $params
     * @return null
     */
    function filter($where)
    {
        $this->db_select->where($where);
    }

    /**
     * 增加过滤条件，$field = $value
     * @return unknown_type
     */
    function eq($field, $value)
    {
        $this->db_select->equal($field, $value);
    }

    /**
     * 过滤器语法，参数为SQL SelectDB的orwhere语句
     * @param $params
     */
    function orfilter($where)
    {
        $this->db_select->orwhere($where);
    }

    /**
     * 获取一条数据
     * 参数可以制定返回的字段
     * @param $field
     */
    function fetch($field = '')
    {
        return $this->db_select->getone($field);
    }

    /**
     * 获取全部数据
     */
    function fetchall()
    {
        return $this->db_select->getall();
    }

    function __set($key, $v)
    {
        $this->db_select->$key = $v;
    }

    function __call($method, $argv)
    {
        return call_user_func_array(array($this->db_select, $method), $argv);
    }

    public function rewind()
    {
        if (empty($this->_list)) {
            $this->_list = $this->db_select->getall();
        }
        $this->_current_id = 0;
    }

    public function key()
    {
        return $this->_current_id;
    }

    public function current()
    {
        $record = new Record(0, $this->db, $this->table, $this->primary);
        $record->put($this->_list[$this->_current_id]);
        $record->_current_id = $this->_list[$this->_current_id][$this->primary];
        return $record;
    }

    public function next()
    {
        $this->_current_id++;
    }

    public function valid()
    {
        if (isset($this->_list[$this->_current_id])) {
            return true;
        } else {
            return false;
        }
    }
}
