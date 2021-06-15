<?php
namespace sort;

Class SortImage extends Sort
{
	const SORTING = true;
	const FREE = false;

  protected $env = '';

	private $state = '';
	private $logMess = [];
  public $logger = '';

	public function __construct() {
		parent::__construct();
    $this->logger = new Logger( $this->env );
	}

  public function sorting()
  {

  	$folders = $this->getFolder();

  	$this->showPath();

  	foreach ($folders as $folder) {
  		//Получаем все фото из папки
  		$imgs = $this->getAllImg($folder);
  		//Запускаем сортировку и перемещение для данной папки
  		$this->chekImage($imgs, $folder);
  	}

  }
  /**
  * Перемещение файлов
  */
  protected function movingImg( $filesArr, $text, $folder )
  {
    $dir = $text;
    if ( !file_exists($dir) )
    {
      mkdir($dir, 0777, true);
    }

    foreach ($filesArr as $file) {
      # code...
      rename($folder . '/' . $file, $text . '/' . $file);
    }

  }
 	/**
  * Сканирование изображений, возвращает наборы файлов, которые нужно переместить
  */
  protected function chekImage( $files, $folder )
  {

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

					//переносим все изображения в новую директорию
          //$this->movingImg( $Sort, $text, $folder);

					$this->logger->mess('Файлы успешно перенесены' , $Sort);
					//Обнуляем массив Sort

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

  }
 	/**
  * Получение всех изображений из указанной директории
  */
  protected function getAllImg( $dir )
  {
  	$result = [];
  	$notSort = [];

  	if( is_dir( $dir ) )
  	{
  		$imgs = scandir($dir);

	  	foreach ($imgs as $key => $img) {

	  		//Убираем элементы '.' и '..' в массиве
	  		if( $img === '.' || $img === '..' )
	  			continue;

	  		//В финальный массив должны быть добавены только изображения png или jpeg
	  		$mime = mime_content_type($dir . '/' . $img);

	  		if( preg_match('/image\/png|image\/jpeg/i', $mime) !== 1 ) 
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
