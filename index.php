<?php
namespace sort;

require __DIR__ . "/vendor/autoload.php";
/**
* Класс Sort , который обеспечивает создание только по одному эксземпляру
* объектов каждого подкасса
*/
require_once('Sort.php');
/**
* Класс SortImage - класс-контроллер, который производит
* сортировку изображений по QR коду
*/
require_once('SortImage.php');
/**
* Класс CutImage - Разрезание фото для поиска QR
*/
require_once('ScanImg.php');
/**
* Класс Logger - Вывод информации на экран или в файл
*/
require_once('Logger.php');

echo 'Сортировка фото</br></br>';

$s1 = SortImage::getInstance('photo');
$s1->sorting();
