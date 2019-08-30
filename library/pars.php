<?php

/**
 * Объект парсер
 * PHP v7.2
 *
 * Задание 3.
 * 3. Написать на PHP парсер html страницы (на входе url), который на выходе
 * будет отображать количество и название всех используемых html тегов.
 * Использование готовых парсеров и библиотек запрещено. (обязательно
 * использование ООП подхода, демонстрирующее взаимодействие объектов)
 *
 * Автор still@itserv.ru
 */

namespace ForMarketcall;

include_once("debug.php");

class Parser
{
    /**
     * Объект Parser исполняет требования задачи
     * пример создания объекта: $parser = new ForMarketcall\Parser();
     * пример загрузки контента: $parser->load("http://php.net");
     * пример проверки загрузки: $parser->loaded();
     * пример парсинга: $parser->pars();
     * пример сортировки: $parser->sortByCount();
     */
    private $_content = ""; // контент загруженный методом Load
    private $_tags = []; // масиив статистики по тэгам "Name","Count".
    private $_log = null;

    /**
     * Конструктор
     * Требует $log - отладчик из библиотеки debug.php
     */
    public function __construct(&$log)
    {
        $this->_log=$log;
        $this->_log->Info()->Text('Constructor');
        $this->Clear();
    }



    /**
     * Загрузка контента из $url
     */
    public function &load($url)
    {
        $this->_log->Begin();
        @$this->_content = file_get_contents($url);
        $this->_log->End();
        return $this;
    }



    /**
     * Проверка наличия загруженного контента
     */
    public function loaded()
    {
        return $this->_content != "";
    }



    /**
     * сравнение по имени тэга в Parser->Pars()
     */
    function compare($a,$b)
    {
        $result = 0;
        if ($a[0] > $b[0]) {
            $result = 1;
        } else {
            if ($a[0] < $b[0]) {
                $result = -1;
            }
        }
        return $result;
    }



    /**
     * Парсинг просто
     * Для вызова Parser->pars() контент должен быть загружен успешно Parser->load()->loaded()
     */
    public function pars_simple()
    {
        /**
         * основное тело
         */
        $this->_log->Begin();

        if ($this->loaded()) {
            // нормализация текста
            $_content = preg_replace('/\" . *?\"/', "", $this->_content); // очистка двойных кавычек
            $_content = preg_replace("/\' . *?\'/", "", $_content); // очистка одинарнвх кавычек

            // парсинг
            // достали все открывающие тэги
            preg_match_all
            (
                '/<(\w+?)(?:\s|\/>|>)/mi',
                $_content,
                $arrayOfTag,
                PREG_OFFSET_CAPTURE
            );

            // сортировка тэгов
            usort($arrayOfTag[1], array($this,"compare"));

            // очистка массива тэгов
            $this->_tags = array();
            $lastName='unknowntag';
            // последний индекс обработанный
            foreach ($arrayOfTag[1] as $tag)
            {
                $name=$tag[0];
                if ($lastName!=$name) {
                    // встречен новый тэг
                    $this->_log->Info()->Param('New tag', $name);
                    // сохраняем в массив тэгов новый тэг
                    array_push($this->_tags, ["Name"=>$name, "Count"=>1]);
                    $lastName=$name;
                } else {
                    // встречен тэг повторно
                    $this->_tags[count($this->_tags)-1]["Count"]++;
                }
            }
        }
        $this->_log->End();
        return $this;
    }



    /**
     * Очистка объекта и приведение его в исходное состояние
     */
    public function clear()
    {
        $this->_log->Begin();
        $_content = "";
        $_tags = [];
        $this->_log->End();
        return $this;
    }



    /**
     * Вывод статистики
     */
    public function dump()
    {
        $this->_log->Begin();
        foreach ($this->_tags as $tag)
        {
            $this->_log->Debug();
            $this->_log->Param($tag["Name"], $tag["Count"]);
        }
        $this->_log->End();
        return $this;
    }



    /**
     * сравнение по количеству
     */
    private function cmp_count($a,$b)
    {
        if ($a["Count"] < $b["Count"])
        {
            $result = 1;
        } else {
            if ($a["Count"] > $b["Count"]) {
                $result = -1;
            } else {
                $result = 0;
            }
        }
        return $result;
    }



    /**
     * Cортировка по количеству
     */
    public function sortByCount()
    {
        /**
         * основное тело метода
         */
        $this->_log->Begin();
        usort($this->_tags, array($this,"cmp_count"));
        $this->_log->End();
        return $this;
    }



    /**
     * Cортировка по имени
     */
    private function cmp_name($a,$b)
    {
        if ($a["Name"] > $b["Name"]) {
            $result = 1;
        } else {
            if ($a["Name"] < $b["Name"]) {
                $result = -1;
            } else {
                $result = 0;
            }
        }
        return $result;
    }



    public function sortByName()
    {
        /**
         * сравнение по количеству в Parser->SortByName
         */
        $this->_log->Begin();
        usort($this->_tags, array($this, "cmp_name"));
        $this->_log->End();
        return $this;
    }



    /**
     * Cортировка по имени
     */
    public function getTags()
    {
        return $this->_tags;
    }
}
