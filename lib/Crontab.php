<?php
/**
 * Created by VIM
 * User: xuguoliang 1044748759@qq.com
 * Date: 2015-08-28
 */
class Crontab{
	static public $daemon     = false;
	//当前任务数组
	static public $taskList   = array();
	//任务配置队列
	static public $tasks      = array();
	//当前时间戳
	static public $time;
	//退出主进程的信号
	static public $stopSignal = false;
    //config.php的配置
    static public $config;
	//任务进程池
	static public $processList = array();
    
	/**
	 *一些初始化任务，在这里初始化任务静态变量
	 */
	private static function init(){
	   	self::$config = include dirname(__FILE__).'/../config/config.php';
		$db = new DB(self::$config['stat']);
		$data = $db->select('SELECT * FROM task');
		foreach($data as $row){
			self::$tasks[$row['name']] = $row;
		}
	}

	/**
	 *启动
	 */
	static public function start(){
		if(file_exists(Main::$pidFile)){
			exit("pid文件已经存在！\n");
		}
		self::init();
		self::daemon();
		self::run();
		Main::log("服务启动成功");
	}
	
	/**
	 *关闭
	 */	
	static public function stop(){
		$pid = file_get_contents(Main::$pidFile);
		if($pid){
			if(@swoole_process::kill($pid,0)){        //检查$pid进程是否存在
				swoole_process::kill($pid);		
				unlink(Main::$pidFile);
				Main::log('进程'.$pid.'已经结束，服务关闭成功');
			}else{
				unlink(Main::$pidFile);
				Main::log('进程'.$pid.'不存在，删除pid文件');
			}
		}else{
			Main::log("服务没有启动");
		}
	}
	
	static public function reload(){
		$pid = file_get_contents(Main::$pidFile);
		if($pid){
			$res = swoole_process::kill($pid,SIGUSR1);
			if($res){
				Main::log("reload success");
			}
		}else{
			Main::log("进程不存在，reload失败");
		}
	}

	/**
	 *重置修改任务的状态未0
	 */
	static public function resetStatus(){
		$db = new DB(self::$config['stat']);
		$db->execute('UPDATE task SET status=0,pid=0');
	}
 	
	//查询是否还有正在执行的任务
	static public function checkStatus(){
		$db = new DB(self::$config['stat']);
		$data = $db->select('SELECT 1 FROM task WHERE status = 1');
		if($data){
			return true;
		}else{
			return false;
		}
	}

	/**
	 *执行
	 */
	static protected function run(){
		self::registerSignal();                 //注册监听的信号
		self::registerTimerTask();              //注册定时器
		self::writePidFile();
	}
	
	/**
	 *注册定时器
	 */	
	static private function registerTimerTask(){
		swoole_timer_tick(1000,function(){
			self::$time=time();
            try{
			    self::runJob();
            }catch(Exception $e){
                Main::log("抛出异常：".$e->getMessage());    
            }
		});
	}
	
	/**
	 *每秒执行一次这个函数,这里面做最少的工作
	 */	
	static private function runJob(){
		foreach(self::$tasks as $jobName => $job){
			//这里判断是否要执行，如果要执行，开启一个线程  条件：1、到达开始执行时间 2、status状态未执行0 3、到达下一次执行时间
			if(self::$time >= strtotime($job['start_time']) && $job['status']==0 && self::$time >= strtotime($job['next_exec_time'])){
				//条件成立，执行任务
				self::$tasks[$jobName]['status'] = 1;       //修改状态未正在执行当中
				(new Process())->createProcess($job);
			}else{
				continue;
			}
        }
	}
	
	/**
	 *更新任务列表
	 */
	static private function updateTasks($data){
		$task = $data['task'];	
		$id   = $task['id'];
		$db = new DB(self::$config['stat']);
		$config  = $db->select("SELECT * FROM task WHERE id = $id");
		if($config){
			echo "原来:".self::$tasks[$task['name']]['next_exec_time']." 更新为：".$config[0]['next_exec_time'];
			echo "更新。。。。。。。。。。。。";
			self::$tasks[$task['name']]['status']		  = $config[0]['status'];
			self::$tasks[$task['name']]['last_exec_time'] = $config[0]['last_exec_time'];
			self::$tasks[$task['name']]['next_exec_time'] = $config[0]['next_exec_time'];
			self::$tasks[$task['name']]['pid']		      = $config[0]['pid'];
		}
	}

	/**
	 *注册监听的信号
	 */
	static private function registerSignal(){
		swoole_process::signal(SIGCHLD,function($signo){     //SIGCHLD，子进程结束时，父进程会收到这个信号
			//这里可以做任务执行完后的事情，比如：改变任务状态，统计任务执行时间
			while($status =  swoole_process::wait(false)) {
					$task      = self::$taskList[$status['pid']]; 
					$startTime = $task['start'];
					self::updateTasks($task);
					$runTime   = time() - $startTime;
					Main::log($task['task']['name'] . "执行了".$runTime."秒");
					unset(self::$taskList[$status['pid']]);
			}
		});
			
		swoole_process::signal(SIGINT,function($signo){
			self::resetStatus();
			unlink(Main::$pidFile);
			exit;
		});
		
		swoole_process::signal(SIGUSR1,function(){
			self::init();
		});
	}

	/**
   	 *重启
	 */
	static public function restart(){
		self::stop();
		self::$daemon=true;
		self::start();
	}

	/**
	 *获取当前的进程pid
	 */
	static private function getPid(){
		return posix_getpid();
	}

	/**
	 *写入当前进程pid到pid文件
	 */
	static private function writePidFile(){
		file_put_contents(Main::$pidFile,self::getPid());
	}
	
	/**
	 *是否以守护进程的方式运行
	 */
	static private function daemon(){
		if(self::$daemon===true){
			swoole_process::daemon();
		}
	}
}

