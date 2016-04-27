<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-12
 * Time: 13:55
 */
namespace Gimay;
/**
 * 模型加载器
 * 产生一个模型的接口对象
 */
class ModelLoader
{
    protected $gimay = null;
    protected $_models = array();
    protected $_tables = array();

    function __construct(\Gimay $gimay)
    {
        $this->gimay = $gimay;
    }

    /**
     * 仅获取master
     * @param $model_name
     * @return mixed
     * @throws Error
     */
    function __get($model_name)
    {
        return $this->loadModel($model_name, 'master');
    }

    /**
     * 多DB实例
     * @param $model_name
     * @param $params
     * @return mixed
     * @throws Error
     */
    function __call($model_name, $params)
    {
        $db_key = count($params) < 1 ? 'master' : $params[0];
        return $this->loadModel($model_name, $db_key);
    }

    /**
     * 加载Model
     * @param $model_name
     * @param $db_key
     * @return mixed
     * @throws Error
     */
    public function loadModel($model_name, $db_key = 'master')
    {
        if (isset($this->_models[$db_key][$model_name])) {
            return $this->_models[$db_key][$model_name];
        } else {
            $model_file = \Gimay::$app_path . '/models/' . $model_name . '.php';
            if (!is_file($model_file)) {
                throw new Error("实体模型 '$model_name' 不存在.");
            }
            $model_class = '\\App\\Model\\' . $model_name;
            require_once $model_file;
            $this->_models[$db_key][$model_name] = new $model_class($this->gimay, $db_key);
            return $this->_models[$db_key][$model_name];
        }
    }

    /**
     * 加载表
     * @param $table_name
     * @param $db_key
     * @return Model
     */
    public function loadTable($table_name, $db_key = 'master')
    {
        if (isset($this->_tables[$db_key][$table_name])) {
            return $this->_tables[$db_key][$table_name];
        } else {
            $model = new Model($this->gimay, $db_key);
            $model->table = $table_name;
            $this->_tables[$db_key][$table_name] = $model;
            return $model;
        }
    }
}
