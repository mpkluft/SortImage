<?php
namespace sort;

Class Sort {

	private static $instances = [];
	private static $dirNotSort;
	private static $dirSort;

	protected function __construct() {}

	protected function __clone() {}

  public function __wakeup()
  {
    throw new \Exception("Cannot unserialize a сlass Sort");
  }

 	public function getDirNotSort()
 	{
 		return (self::$dirNotSort); 
 	}

 	public function getDirSort()
 	{
 		return (self::$dirSort); 
 	}

 	private static function setFolders()
 	{
 		//На боевой взять папку из конфига
 		self::$dirNotSort = 'photo';
 		self::$dirSort = 'root';
 	}

	public static function getInstance(): Sort
	{
		$cls = static::class;
		if (!isset(self::$instances[$cls]))
		{
			self::$instances[$cls] = new static();
			self::setFolders();
		}

		return self::$instances[$cls];
	}
}
