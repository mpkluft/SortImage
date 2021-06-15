<?php
namespace sort;

Class ScanImage {

	protected $weight;
	protected $height;
	protected $file;
	protected $orig;
	protected $type;
	protected $coord;

	public $text = false;

	public function __construct( $file ) {
		$this->file = $file;
		$this->text = $this->scan();
	}

	protected function scan()
	{

		$this->getImgParam();

		$this->getCoordinate();
		//Добавить разрезание фото

		echo 'Сканируем'. $this->file . '<br>';

		$qrcode = new \Zxing\QrReader( $this->file );
    return $qrcode->text();
	}

	protected function getImgParam()
	{

		$info = getimagesize( $this->file );
    $this->weight = $info[0];
    $this->height = $info[1];

		$mime = mime_content_type( $this->file );

		switch ($mime) {

			case 'image/png':
				$orig = imagecreatefrompng( $this->file );
				$type = '.png';
			break;

			case 'image/jpeg':
				$orig = imagecreatefromjpeg( $this->file );
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
		/*
		//Рекурсивный вызов функции, временно отключен для данной задачи
		$cnt *= 4;
		for ($i=0; $i < $cnt; $i++) { 
			if( $i > 0 ){

				if( $x_cord + $x_canv < $w )
				{	
					$x_cord += $x_canv;
				}else if( $y_cord + $y_canv < $h )
				{
					$x_cord = 0;
					$y_cord += $y_canv;
				} 
			
			}
			$resArr = array_merge($resArr, cutTheImage($w/2, $h/2, $cnt, $x_cord, $y_cord));
		}
		*/
		//Должна возвращать массив с координатамии вида [ [x_canv, y_canv, x_cord, y_cord] ]
		//return $resArr;

		$this->coord = $resArr;
	}
}
