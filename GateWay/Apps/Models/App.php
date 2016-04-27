<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-13
 * Time: 16:26
 * APP应用模型
 */
namespace App\Model;
use Gimay;
class App extends Gimay\Model
{
    /**
     * 按ID查询
     * @param $id
     * @return $value
     */
    function findByID($id)
    {
        return $this->find($id);
    }

    /**
     * 按AppID查询
     * @param $appid
     * @return $value
     */
    function findByAppID($appid)
    {
        return $this->get($appid, 'appid')->get();
    }
}