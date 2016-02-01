<?php
/**
 * Created by VIM
 * User: xuguoliang 1044748759@qq.com
 * Date: 2015-08-27
 */
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', realpath(dirname(__FILE__)) . DS);
class Main{
	static private $options = 'hs:d';
	static private $longOptions = ['help','daemon'];
	static private $optionSList = ['start','stop','restart','reload'];
	//运行时log日志目录
	static private $logPath;
	//Monnolog对象
	static public $logger;
	//Monolog handler
	static public $logHandler;
	//主进程的进程id文件
	static public  $pidFile;
	//主进程名
	static private $processName;
	static private $help = <<<EOF
帮助信息:
   使用: /path/to/php main.php [options] -- [args...]

    -h [--help]        显示帮助信息
	-s start stop reload		   热加载配置

EOF;
    //初始化	
	public static function init(){
		self::$pidFile = ROOT_PATH . 'run/main.pid';
		self::$logPath = ROOT_PATH . 'log';
		self::$processName = 'inception timer calculate main process run...';
		self::registerMonolog();
	}

	public static function run(){
		self::spl_autoload_register();
		self::init();
		self::setProcessName();
		$opt = getopt(self::$options,self::$longOptions);
		self::options_h($opt);             //-h --help
		self::options_d($opt);             //-d 是否以守护进程的方式运行
		self::options_s($opt);             //-s start stop restart 
	}

	/**
	 *注册Monolog框架
	 */
	public static function registerMonolog()
	{
		self::$logger = new \Monolog\Logger('inc');
		\Monolog\ErrorHandler::register(self::$logger);
		self::$logHandler = new \Monolog\Handler\StreamHandler(self::$logPath . '/log_debug.log',\Monolog\Logger::DEBUG);
		self::$logger->pushHandler(self::$logHandler);
	}

	/**
	 *自动加载类
	 */
	public static function spl_autoload_register(){
		include ROOT_PATH . 'lib/monolog/autoload.php';  //monolog框架
		spl_autoload_register(function($className){
			$fileName = ROOT_PATH . 'lib/' . $className . '.php';
			include $fileName;
		});
	}
	
	/**
	 *是否以守护进程方式运行
	 *@param $opt
	 */
	static public function options_d($opt){
		if(isset($opt['d']) || isset($opt['daemon'])){
			Crontab::$daemon = true;
		}
	}

	/**
	 *解析帮助函数
	 *@param $opt
	 */
	static public function options_h($opt){
		if(empty($opt) || isset($opt['h']) || isset($opt['help'])){
			exit(self::$help);
		}
	}

	/**
	 *启动
	 *@param $opt
	 */
	static public function options_s($opt){
		if((isset($opt['s']) && !$opt['s']) || (isset($opt['s']) && !in_array($opt['s'],self::$optionSList))){
			exit("-s参数错误，-s start/stop/restart/reload\n");
		}
		if(isset($opt['s']) && in_array($opt['s'],self::$optionSList))
		{
			switch($opt['s']){
				case 'start':
					echo "正在启动服务中...\n";
					sleep(1);
					Crontab::start();
					break;
				case 'stop':
					echo "正在停止服务...\n";
					sleep(1);
					Crontab::stop();
					break;
				case 'restart':
					echo "正在重启服务...\n";
					sleep(1);
					Crontab::restart();
					break;
				case 'reload';
					Crontab::reload();
					break;
			}
		}
	}

	//获取当前进程的pid
	static public function getPid(){
		return posix_getpid();
	}
	
	//定义主进程名
	static private function setProcessName(){
		swoole_set_process_name(self::$processName);
	}

	/**
	 *log日志
	 */
	static public function log($log){
		$now = date('Y-m-d H:i:s',time());
		//守护进程方式运行时记log到log文件，否则打印到屏幕
		if(Crontab::$daemon === true) {
			$text = "[$now] : {$log}\n";
			$logPath = self::$logPath.'/log-'.date('Y-m-d').'.log'; 
			file_put_contents($logPath,$text,FILE_APPEND);
		}else{
			echo "[$now] : {$log}\n";
		}
	}
}

Main::run();
