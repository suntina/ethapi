<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/5/21
 * Time: 11:04
 */

namespace Ethereum;


use IntSwitch\IntSwitch;

class Ethereum
{
    protected $host;//主机地址
    protected $port;//端口
    protected $version;//版本
    protected $id=0;//计数器
    public  function __construct($host,$port,$version='2.0')
    {
        $this->host=$host;
        $this->port=$port;
        $this->version=$version;
    }
    /*
     *  请求方法
     *  @params
     *   method  方法名
     *   params  参数
     *   returnArray 是否返回数组
     * */
    public function request($method , $params=[] , $returnArray=false){

        $data = array();
        $data['jsonrpc'] = $this->version;
        $data['id'] = $this->id++;
        $data['method'] = $method;
        $data['params'] = $params;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->host);
        curl_setopt($ch, CURLOPT_PORT, $this->port);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $err=curl_error($ch);
        $ret = curl_exec($ch);

        if($returnArray){
            return @json_decode($ret,true);
        }else{
            return @json_decode($ret);
        }
    }
    //去0
    public function showNum($num)
    {
        $rnum=bcmul($num,1,10).'';
        $rnum = trim($rnum, '0');
        if (substr($rnum, 0, 1) == '.') $rnum = '0' . $rnum;
        if (substr($rnum, -1, 1) == '.') $rnum = substr($rnum, 0, -1);
        return $rnum;
    }
    //转换真实金额
    public function real_banlance($input)
    {
        if ($input > 1) {
            return $input / 1000000000000000000;
        }
        return 0;
    }
    //bc库处理真实金额
    public function to_real_value($input)
    {
        if ($input > 0) {
            return bcmul((string) $input, '1000000000000000000');
        }
        return 0;
    }
    //16进制转10进制
    public function decode_hex($input)
    {
        if (preg_match('/[a-f0-9]+/', $input))
            return hexdec($input);

        return $input;
    }
    //10进制转16进制
    public  function dec_to_hex($dec)
    {
        $sign = "";
        if ($dec < 0) {
            $sign = "-";
            $dec = abs($dec);
        }
        $hex = Array(
            0 => 0,
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5,
            6 => 6,
            7 => 7,
            8 => 8,
            9 => 9,
            10 => 'a',
            11 => 'b',
            12 => 'c',
            13 => 'd',
            14 => 'e',
            15 => 'f'
        );
        $h='';
        do {
            $h = $hex[($dec % 16)] . $h;
            $dec /= 16;
        } while ($dec >= 1);
        return $sign . $h;
    }

    //10进制转16进制
    public function encode_dec($input)
    {
        if (preg_match('/[0-9]+/', $input)){

            $intSwitch = new IntSwitch();
            return '0x' . $intSwitch->changeBigInt($input, 16);
        }
        return $input;
    }
    //解锁账号
    public function personal_unlockAccount($address, $password, $time = 60)
    {
        return $this->request(__FUNCTION__, array(
            $address,
            $password,
            $time
        ));
    }
    //锁定账号
    public function personal_lockAccount($address)
    {
        return $this->request(__FUNCTION__, array(
            $address
        ));
    }

    //协议版本
    public function eth_protocolVersion()
    {
        return $this->request(__FUNCTION__);
    }

    //所有账号
    public function eth_accounts()
    {
        return $this->request(__FUNCTION__);
    }

    //区块高度
    public function eth_blockNumber($decode_hex = FALSE)
    {
        $block = $this->request(__FUNCTION__);
        if ($decode_hex)
            $block = $this->decode_hex($block);
        return $block;
    }

    //获取指定地址余额
    public function eth_getBalance($address, $block = 'latest')
    {
        if(empty($address))return -1;
        $balance = $this->request(__FUNCTION__, array(
            $address,
            $block
        ));
        $balance = $this->decode_hex($balance);
        return $this->real_banlance($balance);
    }
    //交易总数
    public  function eth_getTransactionCount($address,$block='latest',$decode_hex = FALSE)
    {
        $count = $this->request(__FUNCTION__, array(
            $address,
            $block
        ));
        if ($decode_hex)
            $count = $this->decode_hex($count);
        return $count;
    }
    //签名
    public function eth_sign($address, $input)
    {
        return $this->request(__FUNCTION__, array(
            $address,
            $input
        ));
    }
    //发送交易
    public function eth_sendTransaction($address, $password, $transaction)
    {
        //解锁账号
        $re = $this->personal_unlockAccount($address, $password);
        if (!$re){
            return false;
        }
        //发送交易
        $result = $this->request(__FUNCTION__, $transaction);
        //重新锁定账号
        $this->personal_lockAccount($address);
        return $result;
    }

    /*
     * 获取代币余额
     * @params
     * send_account 需要查询的钱包地址
     * contractAddr 代币合约地址
     * decimal      小数位
     * */
    public function eth_getTokenBalance($send_account,$contractAddr,$decimal=18)
    {
        if(empty($send_account)||empty($contractAddr)||empty($decimal))return -1;
        $data='0x'.str_pad('70a08231',32,'0',STR_PAD_RIGHT).str_replace('0x','',$send_account);
        $params = [['from'=>$send_account, 'to'  =>$contractAddr,'value'=>'0x0','data'=>$data],'latest'];
        $result= $this->request('eth_call',$params,true);

        $amount=-1;
        if(!empty($result['result'])){
            $amount =$this->showNum(bcdiv(hexdec(str_replace('0x','',$result['result'])), pow(10, $decimal), $decimal));   //金额
        }
        return $amount;
    }
    //获取油费
    public function eth_gasPrice()
    {
        return $this->request(__FUNCTION__);
    }

    //根据交易hash获取当时区块高度
    public function eth_getBlockByNumber($block = 'latest', $full_tx = TRUE)
    {
        return $this->request(__FUNCTION__, array(
            $block,
            $full_tx
        ));
    }
    //根据交易hash获取交易信息
    public function eth_getTransactionByHash($hash)
    {
        return $this->request(__FUNCTION__, array(
            $hash
        ));
    }

    //生成新钱包地址
    public function personal_newAccount($pass)
    {
        return $this->request(__FUNCTION__, array(
            $pass
        ));
    }

}