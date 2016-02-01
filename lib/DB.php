<?php

class DB{
	private $config;
	public $dbHand;

	public function __construct($dbConfig){
		$this->config = $dbConfig;
		try{
			$this->dbHand = new PDO("mysql:dbname={$this->config['database']};host={$this->config['host']};port={$this->config['port']}",$this->config['username'],$this->config['password']);
		}catch(PDOException $e){
			echo "数据库连接失败：".$e->getMessage();
			exit;
		}
	}
	
	public function select($sql){
		$statement = $this->dbHand->prepare($sql);
		$statement->execute();
		$result = $statement->fetchAll(PDO::FETCH_ASSOC);
		return $result;
	}

	public function execute($sql){
		$result = $this->dbHand->exec($sql); 
		return $result;
	}

	public function __destruct(){
		$this->dbHand = null;
	}
}
