<?php
/**
 * Created by VIM
 * User: xuguoliang 1044748759@qq.com
 * Date: 2015-10-14
 */
class JobBase{
    public $stat;
    public $ib;

    public function __construct(){
        $this->stat = new DB(Crontab::$config['stat']);
        $this->ib   = new DB(Crontab::$config['ib']);
    }
    
    public function buildSql($data){
        $k = [];
        $v = [];
        foreach ($data as $key => $value) {
            $k[] = $key;
            $v[] = $value;
        }
        $ret = [];
        $ret[0] = join(',', $k);
        $ret[1] = '"' . join('","', $v) . '"';
        return $ret;
    }
}
