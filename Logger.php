<?php
namespace sort;
/**
* Существует два режима работы логера. По умолчанию идет запись в файл
* режим dev - вывод в окно браузера
*/
Class Logger {

	protected $file = '';
	protected $env = '';

	public function __construct( $env,  $name = '') {
		$this->crFileName( $name );
		$this->env = $env;
	}
	/**
  * Создаем имя файла
  */
	protected function crFileName( $name )
	{

		$dir = "logs";
		if ( !file_exists($dir) )
		{
			mkdir($dir, 0777, true);
		}
		echo __FILE__;
		$this->file = $dir.'/sort_log ' . date("Y-m-d") . ' ' . $name;

	}
	/**
  * Пишем в файл
  */
	protected function writeFile( $text )
	{
		// открываем файл, если файл не существует,
		//делается попытка создать его
		$fp = fopen( date("Y-m-d") ." sorting_image.txt", "a" );
		// записываем в файл текст
		fwrite($fp, $text);
		// закрываем
		fclose($fp);
	}
	/**
  * Добавление сообщений в файл/переменную
  */
	public function mess( $header, $msg, $mode = '')
	{

		$mode = '' !== $mode ? 'info' : '';

		if( 'prod' === $this->env )
		{
			if( '' === $mode )
			{
				$line = "-----------------------------\n";
				$header = '[header]: ' . date("Y-m-d H:i:s") . ' ' . $header . "\n";
				$msg = is_array($msg) ? implode("\n", $msg) . "\n" : $msg . "\n";
				// строка, которую будем записывать
				$text = $line . $header . $msg;
				$this->writeFile($text);
			}
		} else
		{
			$line = "-----------------------------\n";
			$header = '[header]: ' . date("Y-m-d H:i:s") . ' ' . $header . "\n";
			$msg = is_array($msg) ? implode("\n", $msg) . "\n" : $msg . "\n";
			// строка, которую будем записывать
			$text = $line . $header . $msg;
			$this->writeFile($text);
		}

	}

}
