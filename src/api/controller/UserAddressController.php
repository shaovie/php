<?php
/**
 * @Author shaowei
 * @Date   2015-12-26
 */

namespace src\api\controller;

use \src\user\model\UserAddressModel;
use \src\common\Check;

class UserAddressController extends ApiController
{
    public function getAll()
    {
        $addrList = UserAddressModel::getAddrList($this->userId());

        if (empty($addrList)) {
            $this->ajaxReturn(0, '', '', array());
            return ;
        }

        $retList = array();
        $sysCityCodeBook = include(CONFIG_PATH . '/city_code_book.php');
        foreach ($addrList as $addr) {
            $v['id'] = $addr['id'];
            $v['reName'] = $addr['re_name'];
            $v['rePhone'] = $addr['re_phone'];
            $v['provinceId'] = $addr['province_id'];
            $v['provinceName'] = '';
            if (isset($sysCityCodeBook[$addr['province_id']])) {
                $v['provinceName'] = $sysCityCodeBook[$addr['province_id']];
            }
            $v['cityId'] = $addr['city_id'];
            $v['cityName'] = '';
            if (isset($sysCityCodeBook[$addr['city_id']])) {
                $v['cityName'] = $sysCityCodeBook[$addr['city_id']];
            }
            $v['districtId'] = $addr['district_id'];
            $v['districtName'] = '';
            if (isset($sysCityCodeBook[$addr['district_id']])) {
                $v['districtName'] = $sysCityCodeBook[$addr['district_id']];
            }
            $v['detail'] = $addr['detail'];
            $v['isDefault'] = $addr['is_default'];
            $retList[] = $v;
        }
        $this->ajaxReturn(0, '', '', $retList);
    }

    public function add()
    {
        $reName = $this->postParam('name', ''); // 收件人
        $rePhone = $this->postParam('phone', '');
        $provinceId = (int)$this->postParam('provinceId', 0);
        $cityId = (int)$this->postParam('cityId', 0);
        $districtId = (int)$this->postParam('districtId', 0);
        $detail = $this->postParam('detail', '');
        $reIdCard = $this->postParam('reIdCard', '');
        $isDefault = (int)$this->postParam('isDefault', 0);

        $sysCityCodeBook = include(CONFIG_PATH . '/city_code_book.php');
        $reName = preg_replace('/\s|　/', '', $reName);
        if (!Check::isName($reName)
            || !Check::isPhone($rePhone)
            || !isset($sysCityCodeBook[$provinceId])
            || !isset($sysCityCodeBook[$cityId])
            || !isset($sysCityCodeBook[$districtId])
            || empty($detail) || strlen($detail) > 255) {
            $this->ajaxReturn(ERR_PARAMS_ERROR, '输入不合法，请重新输入');
            return ;
        }

        if ($isDefault == 1) {
            UserAddressModel::clearDefaultAddr($this->userId());
        }
        $ret = UserAddressModel::newOne(
            $this->userId(),
            $reName,
            $rePhone,
            UserAddressModel::ADDR_TYPE_UNKNOW,
            $provinceId,
            $cityId,
            $districtId,
            $detail,
            $reIdCard,
            $isDefault
        );
        if ($ret === false) {
            $this->ajaxReturn(ERR_SYSTEM_ERROR, '系统异常，保存地址失败');
            return ;
        }
        $this->ajaxReturn(0, '');
    }

    public function setDefault()
    {
        $addrId = $this->getParam('addrId', 0);
        if (empty($addrId)) {
            $this->ajaxReturn(ERR_PARAMS_ERROR, '参数错误');
            return ;
        }
        $ret = UserAddressModel::setDefaultAddr($this->userId(), $addrId, 1);
        if ($ret === false) {
            $this->ajaxReturn(ERR_SYSTEM_ERROR, '设置默认地址失败');
            return ;
        }
        $this->ajaxReturn(0, '');
    }

    public function edit()
    {
        $reName = $this->postParam('name', ''); // 收件人
        $rePhone = $this->postParam('phone', '');
        $provinceId = (int)$this->postParam('provinceId', 0);
        $cityId = (int)$this->postParam('cityId', 0);
        $districtId = (int)$this->postParam('districtId', 0);
        $detail = $this->postParam('detail', '');
        $reIdCard = $this->postParam('reIdCard', '');
        $isDefault = (int)$this->postParam('isDefault', 0);

        $sysCityCodeBook = include(CONFIG_PATH . '/city_code_book.php');
        $reName = preg_replace('/\s|　/', '', $reName);
        if (!Check::isName($reName)
            || !Check::isPhone($rePhone)
            || !isset($sysCityCodeBook[$provinceId])
            || !isset($sysCityCodeBook[$cityId])
            || !isset($sysCityCodeBook[$districtId])
            || empty($detail) || strlen($detail) > 255) {
            $this->ajaxReturn(ERR_PARAMS_ERROR, '输入不合法，请重新输入');
            return ;
        }

        if ($isDefault == 1) {
            UserAddressModel::clearDefaultAddr($this->userId());
        }

        $ret = UserAddressModel::update(
            $this->userId(),
            $addrId,
            array(
                're_name' => $reName,
                're_phone' => $rePhone,
                'province_id' => $provinceId,
                'city_id' => $cityId,
                'district_id' => $districtId,
                'detail' => $detail,
                're_id_card' => $reIdCard,
                'is_default' => $isDefault
            )
        );
        if ($ret === false) {
            $this->ajaxReturn(ERR_SYSTEM_ERROR, '系统异常，更新地址失败');
            return ;
        }
        $this->ajaxReturn(0, '');
    }

    public function del()
    {
        $addrId = $this->getParam('id', 0);
        if (empty($addrId)) {
            $this->ajaxReturn(ERR_PARAMS_ERROR, '参数错误');
            return ;
        }
        $ret = UserAddressModel::delOne($this->userId(), $addrId);
        if ($ret === false) {
            $this->ajaxReturn(ERR_SYSTEM_ERROR, '删除地址失败');
            return ;
        }
        $this->ajaxReturn(0, '');
    }
}

