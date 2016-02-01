<?php
/**
 * Created by VIM
 * User: xuguoliang 1044748759@qq.com
 * Date: 2015-08-28
 */
class Process{
	private $task;
	private $jobName;
	
	/**
	 *创建一个子任务进程
	 *@param $task
	 */
	public function createProcess($task){
		$this->task	   = $task;
		$this->jobName = $task['name'];
		Crontab::$processList[$this->jobName]	= new swoole_process([$this,'run']); 
		$pid		= Crontab::$processList[$this->jobName]->start();
		if(!$pid){
			Main::log('子任务创建失败');
		}else{
			Crontab::$taskList[$pid] = [
				'start'=>time(),
				'task'=>$task
			];
			Crontab::$tasks[$this->jobName]['pid']=$pid;
		}
	}
	
	/**
	 *子进程执行函数
	 *@param $worker
	 */
	public function run($worker){
        $id = $this->task['id'];
        $last_exec_time = $this->task['next_exec_time'];
        $db = new DB(Crontab::$config['stat']);
		//修改数据库状态正在执行中，修改最后一次执行时间
        $result = $db->execute("UPDATE task SET status=1,last_exec_time='{$last_exec_time}',pid={$worker->pid} WHERE id = $id");
		if(!$result){
			$worker->exit(0);
		}
		//定义子进程的名称
		$worker->name('timer job ' . $this->task['name'] . ' time:' . $this->task['next_exec_time']);
		//任务执行类
		$className = $this->task['name'];
		$this->autoload($className);
		(new $className)->run();
		//修改数据状态 status=0, 修改下一次执行时间
		$next_exec_time = date('Y-m-d H:i:s',strtotime($this->task['next_exec_time']) + $this->task['separate_time']);
        $updateSql = "UPDATE task SET status=0,next_exec_time='$next_exec_time',pid=0 WHERE id=$id";
        $res = $db->execute($updateSql);
		$worker->exit(0);
	}
	
	/**
	 *自动加载类 
	 */
	private function autoload($className){
		$jobFile = ROOT_PATH.'job'.DS.$className.'.php';
		if(file_exists($jobFile)){
			include($jobFile);
		}else{
			Main::log('工作类'.$className.'不存在');
		}
	}
}
