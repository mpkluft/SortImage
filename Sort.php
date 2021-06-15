<?php
namespace sort;

Class Sort {

	private static $instances = [];
	private $folder = [];

	/**
  * Конструктор скрыт
  */
	protected function __construct() {}

	/**
  * Запрет на клонирование
  */
	protected function __clone() {}

	/**
  * Sort не должен быть восстанавливаемым из строк.
  */
  public function __wakeup()
  {
    throw new \Exception("Cannot unserialize a сlass Sort");
  }

  public function showPath()
 	{
 		print_r($this->folder); 
 	}

 	public function getFolder()
 	{
 		return ($this->folder); 
 	}

	/**
  * Позволяется создавать не более одного объекта каждого подксласса
  */
	public static function getInstance(string $pathF): Sort
	{
		$cls = static::class;
		if (!isset(self::$instances[$cls]))
		{
			self::$instances[$cls] = new static();
			echo '<br> создали объект <br>';
		}
		//Нужно предусмотреть, чтобы отсутствовали дубли
		self::$instances[$cls]->folder[] = $pathF;

		return self::$instances[$cls];
	}
}
