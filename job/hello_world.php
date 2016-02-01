<?php
/**
 * Created by VIM
 * User: xuguoliang 1044748759@qq.com
 * Date: 2016-02-01
 * Note: Hello World
 */
class hello_world extends JobBase implements JobBaseInterface{

    /**
     *任务执行
     */
    public function run(){
        echo "hello world";
    }
     
}
