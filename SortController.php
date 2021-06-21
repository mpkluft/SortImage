<?php

class FileSortControllerNas {

	private static $instances = [];
	private static $dirNotSort;
	private static $dirSort;
	private static $dirLogger;
	private static $CI;

	const ENV = '';

	public $result = [];
  public $struct = '/([\s\S][^\/]+)\/([\s\S][^\/]+)\/([\s\S][^\/]+)/i';

  protected $weight;
	protected $height;
	protected $file;
	protected $orig;
	protected $type;
	protected $coord;
	public $text = false;

  function __construct() {
  	//For Codeigniter 3
		//parent::__construct(); 
		//self::$CI = &get_instance();
		//$this->setFolders();
		$this->setFoldersLocalServer();
  }

	function sorting()
	{
		echo 'Hello World';    
		$this->recursScan( $this->getDirNotSort() );
    $this->result = array_unique($this->result);

    echo '<pre>';
    print_r($this->result);
    echo '</pre>';
	}

  function index() {
  	echo 'Hello World';
    /*
    | recursScan() - рекурсивный обход каталога с несорт. файлами
    | $this->result - результат работы метода
    */
    $this->recursScan( $this->getDirNotSort() );
    $this->result = array_unique($this->result);

    echo '<pre>';
    print_r($this->result);
    echo '</pre>';

    foreach ($this->result as $path) 
    {
    	
    	$pathTmp = str_replace( $this->getDirNotSort() . '/', '', $path );

      if( preg_match( $this->struct, $pathTmp, $match ) )
      {
        /*
        | $doctype - тип документа, участвует в создании иерархии каталогов
        | $user - временно не используется
        */
      	$doctype = preg_replace( '/[^А-Яа-пр-яЁёa-z0-9]+/ui', '-', $match[1] );
        $user = preg_replace( '/[^А-Яа-пр-яЁёa-z0-9]+/ui', '-', $match[2] );


      	echo '</br>' . $doctype . '</br>';
      	echo '</br>' . $user . '</br>';
      	/*
        | getAllImg() - Отбираются все файлы, типа image/png || jpeg || jpg из каталога $path
        */
        $imgs = $this->getAllImg( $path );
      	/*
        | chekImage() - Возвращается массив из правильных наборов файлов - 
        | [QR начала документа - изображения - QR конца документа]
        */        
				$docs = $this->chekImage($imgs, $path);

        foreach ($docs as $key => $doc) {
          /*
          | $params - расшифрованный текст из QR.
          */
          $params = explode(',', $key);
          /*
          | На данный момент заложено 4 параметра
          */
          if( count($params) != 4 )
          {
            $this->logger('Файлы не перемещны. Невалидный QR. Пример текста для QR: id объекта,Установка,Трубопроводы,позиция' , $doc);
            continue;
          }

          $ust = $params[1];
          $class = $params[2]; 
          $index = $params[3];
          /*
          | $newPath - новый путь расположения изображений
          */
          $newPath = $this->getDirSort() . '/' . $ust . '/' . $doctype . '/' . $class . '/' . $index;
          /*
          | $movingImg - перемещение изображений в новый каталог
          */
          $this->movingImg( $doc, $newPath, $path);
          $this->logger('Файлы перемещены' , $doc);

        } // foreach $docs
      } // if preg_match( $this->struct, $pathTmp, $match )
    } // foreach $this->result
  } // end index()
	/*
	|--------------------------------------------------------------------------
	| Методы для сканироавния изображения
	|--------------------------------------------------------------------------
	|	scan() - поиск и декодирование QR кода вовращает резульата декодировани текст(str) || false
	|	getImgParam() - Определяет параметры изобаржения
	|	getCoordinate() - Возвращает массив с координатами для разрезания изображения (пока не используется)
	|--------------------------------------------------------------------------
	*/
  protected function scan( $file )
	{

		$this->getImgParam( $file );

		$this->getCoordinate();
		//Добавить разрезание фото

		$qrcode = new \Zxing\QrReader( $file );
		echo file_exists($file) . ' Существует ли файл <br>';
		//$qrcode = new \QrReader( $file );
    return $qrcode->text();
	}

	protected function getImgParam( $file )
	{

		$info = getimagesize( $file );
    $this->weight = $info[0];
    $this->height = $info[1];

		$mime = mime_content_type( $file );

		switch ($mime) {

			case 'image/png':
				$orig = imagecreatefrompng( $file );
				$type = '.png';
			break;

			case 'image/jpeg':
				$orig = imagecreatefromjpeg( $file );
				$type = '.jpeg';
			break;

			default:
			# code...
			break;
		}

		$this->orig = $orig;
		$this->type = $type;

	}	
	protected function getCoordinate( $w = '', $h = '', $cnt = 1, $x_cord = 0, $y_cord = 0, &$resArr = [], $x_min = 1000, $y_min = 1000)
	{

		$w = $this->weight;
		$h = $this->height;

		if( $w <= $x_min && $h <= $y_min )
		{
			return [ $w, $h, $x_cord, $y_cord ];
		}

		//Схема деления изображения
		$shema = [[$w/2, $h], [$w, $h/2], [$h/2, $w/2]];

		foreach ($shema as $key => $value) {

			$x_canv = $value[0]; // размер холста по X
			$y_canv = $value[1]; // размер холста по Y

			if( $key === 0 )
			{

				$resArr[] = [ $x_canv, $y_canv, $x_cord, $y_cord ];
				$resArr[] = [ $x_canv, $y_canv, $x_cord + $x_canv, $y_cord ];
				$resArr[] = [ $x_canv, $y_canv, $x_cord + $x_canv/2, $y_cord ];

			} else if( $key === 1 )
			{

				$resArr[] = [ $x_canv, $y_canv, $x_cord, $y_cord ];
				$resArr[] = [ $x_canv, $y_canv, $x_cord, $y_cord + $y_canv];
				$resArr[] = [ $x_canv, $y_canv, $x_cord, $y_cord  + $y_canv/2];

			} else if( $key === 2 )
			{

				$resArr[] = [ $x_canv, $y_canv, $x_cord, $y_cord ];
				$resArr[] = [ $x_canv, $y_canv, $x_cord + $x_canv, $y_cord];
				$resArr[] = [ $x_canv, $y_canv, $x_cord, $y_cord + $y_canv];
				$resArr[] = [ $x_canv, $y_canv, $x_cord + $x_canv, $y_cord + $y_canv];

			}
		}

		$this->coord = $resArr;
	}
	/*
	|--------------------------------------------------------------------------
	| Проверка и создание необходимых каталогов
	|--------------------------------------------------------------------------
	|	getDirNotSort() - возвращает путь до каталога DirNotSort
	|	getDirSort() - возвращает путь до каталога DirSort
	|	setFolders() - Загружает из config.php пути к необходимым каиалогам для работы
	|--------------------------------------------------------------------------
	*/
  public function getDirNotSort()
 	{
 		return (self::$dirNotSort); 
 	}

	public function getDirSort()
 	{
 		return (self::$dirSort); 
 	}
 	protected  function setFoldersLocalServer()
 	{
  	self::$dirNotSort = 'NotSort';
 		self::$dirSort = 'SortDocs';
 		self::$dirLogger = 'logs';		
 	}
 	protected  function setFolders()
 	{
 		self::$CI->config->load('config');

 		self::$dirNotSort = self::$CI->config->item('dirNotSort');
 		self::$dirSort = self::$CI->config->item('dirSort');
 		self::$dirLogger = self::$CI->config->item('logger');

 		if( !is_dir( $this->getDirNotSort() ) )
 		{
 			$this->logger('Файлы не отсортированы', 'В файле config.php не указан путь до каталога dirNotSort');
 			exit(1);
 		}

 		if( !is_dir( $this->getDirSort() ) )
 		{
 			$this->logger('Файлы не отсортированы', 'В файле config.php не указан путь до каталога dirSort');
 			exit(1);
 		}

 		if( !is_dir(self::$dirLogger) )
 			mkdir(self::$dirLogger, 0777, true);
 	}
	/*
	|--------------------------------------------------------------------------
	| Методы для сортировки и перемещения файлов
	|--------------------------------------------------------------------------
	|	recursScan() - Рекурсивный обход каталогов и формирование массива с существующими путями
	|	getAllImg() - Получение всех изображений из указанной директории
	| chekImage() - Проверка и сканирование изображений, возвращает наборы файлов, которые нужно переместить
	| movingImg() - Перемещение файлов в новый каталог и удаление из старого
	|--------------------------------------------------------------------------
	*/

  protected function recursScan( $dir )
  {
    if(is_dir($dir))
    {
      $files = scandir($dir);
      foreach ($files as $file) 
      {
        if( $file == '.' || $file == '..' )
          continue;

        $file = $dir.'/'.$file;
        $this->recursScan( $file );
      } 
    }else 
    {
      $offset = strripos($dir, '/');

      $dir = substr($dir, 0, $offset);

      return $this->result[] = $dir;
    }
  }

  protected function getAllImg( $dir )
  {
  	$result = [];

  	$imgs = scandir($dir);

	  foreach ($imgs as $key => $img) {

	  	//Убираем элементы '.' и '..' в массиве
	  	if( $img === '.' || $img === '..' )
	  		continue;

	  	//В финальный массив должны быть добавены только изображения png или jpeg|jpg
	  	$mime = mime_content_type($dir . '/' . $img);

	  	if( preg_match('/image\/png|image\/jpeg|image\/jpg/i', $mime) !== 1 ) 
	  	{
	  		$notSort[] = $dir.'/'.$img;
	  		continue;
	  	}

	  	$result[] = $img;

	  }

    $this->logger( 'В каталоге ' . $dir . ' находятся следующие файлы' , $imgs, 'info');

	  // Нужно сфофрировать запись в логе о том, что файлы ... не отсортированы по причине того, что не являются jpeg png
	  if( isset($notSort) ) 
	  	$this->logger( 'Файлы не соответсвуют типу png|jpeg.', $notSort);

    $this->logger( 'Файлы прошли проверку и будут сортироваться' , $result, 'info');      

  	return $result;
  }

  protected function chekImage( $files, $folder )
  {
    $result = [];
  	$notSort = [];
  	$Sort = [];
  	$QRfirst = '';

  	foreach ($files as $key => $file) 
  	{

  		//Поиск QR и декодирование
  		$text = $this->scan( $folder . '/' . $file );

      if(!$text)
        echo 'текст не прочитан';

      echo $text;

			if( $text && $QRfirst === '' ){
				if( count($notSort) > 0 ){

					//Есть неотсортированные файлы
					$this->logger('У данного списка файлов отсутствует QR начала паспорта' , $notSort);
					$notSort = [];

				}

				//Определяем как начало паспорта
				$QRfirst = $text;
				$Sort[] = $file;

			} elseif( !$text && $QRfirst !== '' ) 
			{

				$Sort[] = $file;

			} elseif( $text && $QRfirst !== '' )
			{
				if( $QRfirst === $text )
				{
					//Определяем как конец паспорта
          
					$Sort[] = $file;
          
          $result[$QRfirst] = $Sort;
					//Обнуляем массив Sort
          $QRfirst = '';
					$Sort = [];

				} else {

					//QR код от другого паспорта. Все вышеперечисленные файлы вносим в notSort
					//полученное знаение QR определяем как начало нового паспорта
					$QRfirst = $text;

					$this->logger('У данного списка файлов отсутствует QR конца паспорта' , $Sort);

					$Sort = [];
				}

			} elseif( !$text && $QRfirst === '' )
			{

				//Отсутствует начальный QR помещаем данное изображение в массив notSort
				$notSort[] = $file;

			}
  	}
    if( count($notSort) > 0 ){

      //Есть неотсортированные файлы
      $this->logger('У данного списка файлов отсутствует QR начала паспорта' , $notSort);
      $notSort = [];

    }

    return $result;
  }

  protected function movingImg( $files, $newPath, $path )
  {
    if ( !file_exists($newPath) )
    {
      mkdir($newPath, 0777, true);
    }

    foreach ($files as $file) {
      rename($path . '/' . $file, $newPath . '/' . $file);
    }

  }
	/*
	|--------------------------------------------------------------------------
	| Логирование событий в файл
	|--------------------------------------------------------------------------
	| logger() - формирование сообщения
	| writeFile() -	запись в файл		
	|--------------------------------------------------------------------------
	*/
	public function logger( $header, $msg, $mode = '')
	{

		$mode = '' !== $mode ? 'info' : '';
		$dir = self::$dirLogger;

		if( 'prod' === self::ENV )
		{
			if( '' === $mode )
			{
				$line = "-----------------------------\n";
				$header = '[header]: ' . date("Y-m-d H:i:s") . ' ' . $header . "\n";
				$msg = is_array($msg) ? implode("\n", $msg) . "\n" : $msg . "\n";
				// строка, которую будем записывать
				$text = $line . $header . $msg;
				$this->writeFile($text, $dir);
			}
		} else
		{
			$line = "-----------------------------\n";
			$header = '[header]: ' . date("Y-m-d H:i:s") . ' ' . $header . "\n";
			$msg = is_array($msg) ? implode("\n", $msg) . "\n" : $msg . "\n";
			// строка, которую будем записывать
			$text = $line . $header . $msg;
			$this->writeFile($text, $dir);
		}
	}

	protected function writeFile( $text, $dir )
	{
		// открываем файл, если файл не существует,
		//делается попытка создать его
		$fp = fopen( $dir . '/' .date("Y-m-d") ." sorting_image.txt", "a" );
		// записываем в файл текст
		fwrite($fp, $text);
		// закрываем
		fclose($fp);
	}

}