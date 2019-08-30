<?php
/**
 * Задание 3.
 * 3. Написать на PHP парсер html страницы (на входе url), который на выходе
 * будет отображать количество и название всех используемых html тегов.
 * Использование готовых парсеров и библиотек запрещено. (обязательно
 * использование ООП подхода, демонстрирующее взаимодействие объектов)
 *
 * PHP v7.2
 *
 * Пример запуска: php pars.php "https://php.net/"
 *
 * Автор Черкас Руслан still@itserv.ru
 */

include_once('library/debug.php'); // Библиотека трассировки кода полностью написана мной
include_once("library/pars.php"); // Объект парсера

$log = new TLog();
$log->Start(true)->Begin()->Text("Task 3");

// Создание объекта парсера
$parser = new ForMarketcall\Parser($log);

// Выполненеи работы
if (count($argv)>1 && $argv[1]) {
    if ($parser->load($argv[1])->loaded()) {
         // парсинг
         $parser->pars_simple();
         // вывод перечня тэгов отсортировнных по количеству
         $log->Info()->Text("Tags by count");
         $parser->sortByCount()->dump();
         // вывод перечня тэгов отсортированных по имени
         $log->Info()->Text("Tags by name");
         $parser->sortByName()->dump();
    }
} else {
    $log->Error()->Text("URL not found. Example: php pars_start.php http://php.net\n");
}

$log->End()->Stop();
