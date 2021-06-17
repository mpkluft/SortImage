<?php
namespace sort;

Class SortImage extends Sort
{

  protected $env = '';

  public $logger = '';
  public $struct = '/([\s\S][^\/]+)\/([\s\S][^\/]+)\/([\s\S][^\/]+)/i';

  public $result = [];

	public function __construct() {
		parent::__construct();
    $this->logger = new Logger( $this->env );
	}

  public function sorting()
  {
    /**
    * Получаем каталог, где находятся файлы для сортировки
    */
  	$dir = $this->getDirNotSort();

    if ( !file_exists( $dir ) )
    {
      $this->logger->mess('Отсутствует корневой каталог', $this->getDirNotSort());
    } else
    {
      /**
      * Рекурсивно получаем список путей каталогов, в которых есть файлы
      */
      $this->recursScan( $dir );
    }
    /**
    * Удаляем повторяющиеся пути
    */
    $this->result = array_unique($this->result);
    /**
    * Проходим все каталоги в цикле
    */
    foreach ($this->result as $path) {

      if( preg_match( $this->struct, $path, $match) ){
        /**
        * $doctype - тип документа, участвует в создании иерархии каталогов
        * $user - временно не используется
        */
        $doctype = $match[2];
        $user = $match[3];
        /**
        * $imgs - Отбираются все файлы, типа image/png || jpeg || jpg из каталога
        */
        $imgs = $this->getAllImg($path);
        /**
        * $docs - Возвращается массив из правильных наборов изображений [QR начала документа - изображения - QR конца документа]
        */
        $docs = $this->chekImage($imgs, $path);

        foreach ($docs as $key => $doc) {
          /**
          * $params - расшифрованный текст из QR.
          */
          $params = explode(',', $key);
          /**
          * На данный момент заложено 4 параметра
          */
          if( count($params) != 4 )
          {
            $this->logger->mess('Файлы не перемещны. Невалидный QR. Пример текста для QR: id объекта,Установка,Трубопроводы,позиция' , $doc);
            continue;
          }

          $ust = $params[1];
          $class = $params[2]; 
          $index = $params[3];
          /**
          * $newPath - новый путь расположения изображений
          */
          $newPath = $this->getDirSort() . '/' . $ust . '/' . $doctype . '/' . $class . '/' . $index;
          /**
          * $movingImg - перемещение изображений в новый каталог
          */
          $this->movingImg( $doc, $newPath, $path);
          $this->logger->mess('Файлы перемещены' , $doc);

        }
      }
    }

  }
  /**
  * Перемещение файлов
  */
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

  /**
  * Рекурсивно совершает обход каталогов относительно каталога в аргументе $dir
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
 	/**
  * Проверка и сканирование изображений, возвращает наборы файлов, которые нужно переместить
  */
  protected function chekImage( $files, $folder )
  {
    $result = [];
  	$notSort = [];
  	$Sort = [];
  	$QRfirst = '';

  	foreach ($files as $key => $file) 
  	{

  		//Поиск QR и декодирование
  		$scan = new ScanImage( $folder . '/' . $file );
      $text = $scan->text;

      if(!$text)
        echo 'текст не прочитан';

      echo $text;

			if( $text && $QRfirst === '' ){
				if( count($notSort) > 0 ){

					//Есть неотсортированные файлы
					$this->logger->mess('У данного списка файлов отсутствует QR начала паспорта' , $notSort);
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

					$this->logger->mess('У данного списка файлов отсутствует QR конца паспорта' , $Sort);

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
      $this->logger->mess('У данного списка файлов отсутствует QR начала паспорта' , $notSort);
      $notSort = [];

    }

    return $result;
  }
 	/**
  * Получение всех изображений из указанной директории
  */
  protected function getAllImg( $dir )
  {
  	$result = [];

  	if( is_dir( $dir ) )
  	{
  		$imgs = scandir($dir);

	  	foreach ($imgs as $key => $img) {

	  		//Убираем элементы '.' и '..' в массиве
	  		if( $img === '.' || $img === '..' )
	  			continue;

	  		//В финальный массив должны быть добавены только изображения png или jpeg
	  		$mime = mime_content_type($dir . '/' . $img);

	  		if( preg_match('/image\/png|image\/jpeg|image\/jpg/i', $mime) !== 1 ) 
	  		{
	  			$notSort[] = $dir.'/'.$img;
	  			continue;
	  		}

	  		$result[] = $img;

	  	}

      $this->logger->mess( 'В каталоге ' . $dir . ' находятся следующие файлы' , $imgs, 'info');
	  	// Нужно сфофрировать запись в логе о том, что файлы ... не отсортированы по причине того, что не являются jpeg png
	  	if( isset($notSort) ) 
	  		$this->logger->mess( 'Файлы не соответсвуют типу png|jpeg.', $notSort);

      $this->logger->mess( 'Файлы прошли проверку и будут сортироваться' , $result, 'info');      

  	}else 
  	{
      $this->logger->mess( 'Указанной директории не существует', $dir);
  	}

  	return $result;
  }

}
