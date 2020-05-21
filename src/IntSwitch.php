<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/5/21
 * Time: 11:22
 */

namespace IntSwitch;


class IntSwitch
{
    public function __construct()
    {
        $this->key = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
        $this->carry = 1;
    }

    public function changeInt($raw, $ary, $len)
    {

        $result = "";
        $variable = 1;
        $residue = 1;
        $median = 1;
        $verify = $raw;
        if ($raw == 0)
            $result = substr($this->key, 0, $this->carry);
        while ($raw != 0) {
            $variable = intval($raw / $ary);
            $residue = $raw % $ary;
            $result = substr($this->key, $residue * $this->carry, $this->carry) . $result;
            $raw = $variable;
        }
        $median = strlen($result);
        if ($median < $len)
            $result = $this->fillPlace($len - $median) . $result;
        if ($this->revertInt($ary, $result) != $verify)
            return - 1;
        return $result;
    }

    public function revertInt($ary, $value)
    {

        $result = "";
        $median = intval(strlen($value) / $this->carry);
        $character = "";
        for ($i = 1; $i <= $median; $i ++) {
            if ($this->carry > 1) {
                $character = substr($value, $i * $this->carry - ($this->carry), $this->carry);
                $result += (intval(strpos($this->key, $character) / $this->carry)) * pow($ary, $median - $i);
            } else {
                $character = substr($value, $i * $this->carry - 1, $this->carry);
                $result += intval(strpos($this->key, $character)) * pow($ary, $median - $i);
            }
        }
        return $result;
    }

    public function changeBigInt($raw, $ary, $len=null)
    {

        bcscale(0);
        $result = "";
        $variable = 1;
        $residue = 1;
        $median = 1;
        $verify = $raw;
        if ($raw == "0")
            $result = substr($this->key, 0, $this->carry);
        while ($raw != "0") {
            $variable = bcdiv($raw, $ary);
            $residue = bcmod($raw, $ary);
            $result = substr($this->key, $residue * $this->carry, $this->carry) . $result;
            $raw = $variable;
        }
        $median = strlen($result);
        if ($median < $len)
            $result = $this->fillPlace($len - $median) . $result;
        if ($this->revertBigInt($ary, $result) != $verify)
            return - 1;
        return $result;
    }

    public function revertBigInt($ary, $value)
    {
        bcscale(0);
        $result = "";
        $median = bcdiv(strlen($value), $this->carry);
        $character = "";
        for ($i = 1; $i <= $median; $i ++) {
            if ($this->carry > 1) {
                $character = substr($value, $i * $this->carry - ($this->carry), $this->carry);
                $result = bcadd(bcmul(bcdiv(strpos($this->key, $character), $this->carry), bcpow($ary, $median - $i)), $result);
            } else {
                $character = substr($value, $i * $this->carry - 1, $this->carry);
                $result = bcadd(bcmul(strpos($this->key, $character), bcpow($ary, $median - $i)), $result);
            }
        }
        return $result;
    }

    public function fillPlace($number)
    {
        $character = substr($this->key, 0, $this->carry);
        $result = $character;
        for ($i = 1; $i <= $number - 1; $i ++)
            $result .= $character;
        return $result;
    }


    public function String2Hex($string){
        $hex='';
        for ($i=0; $i < strlen($string); $i++){
            $hex .= dechex(ord($string[$i]));
        }
        return $hex;
    }

    public function Hex2String($hex){
        $string='';
        for ($i=0; $i < strlen($hex)-1; $i+=2){
            $string .= chr(hexdec($hex[$i].$hex[$i+1]));
        }
        return $string;
    }

}