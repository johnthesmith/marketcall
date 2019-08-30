<?php
/**
 * Задание 2.
 *
 * Имеется массив числовых значений [4, 5, 8, 9, 1, 7, 2]. В распоряжении
 * есть функция array_swap(&$arr, $num) { … } которая меняем местами элемент на
 * позиции $num и элемент на 0 позиции.  Т.е. при выполнении array_swap([3, 6,
 * 2], 2) на выходе получим [2, 6, 3].
 * Написать код, сортирующий массив по возрастанию, используя только функцию
 * array_swap.
*/


/**
 * исполнение задачи
 */
$workArray = [4,5,6,8,9,1,7,2]; // массив пример
print_r('Task 2.');
print_r($workArray);
sort_array_by_swap($workArray);
print_r($workArray);


/**
 * реализация функции array_swap
 */
function array_swap(&$arr, $num)
{
    $Transit = $arr[$num];
    $arr[$num] = $arr[0];
    $arr[0] = $Transit;
    return true;
}


/**
 * выбор минимального индекса c минимальным значением
 * в массиве $AArray рзмером $ASize начиная с позиции $APosition
 */
function get_min_index_from_position(&$array, $position, $size)
{
    $result = -1;
    $currentMin = $array[$position];
    for ($i=$position; $i<$size; $i++)
    {
        if ($array[$i] <= $currentMin)
        {
            $currentMin = $array[$i];
            $result = $i;
        }
    }
    return $result;
}



/**
 * функция сортировки массива $AArray
 * c примененеием функции array_swap
 */
function sort_array_by_swap(&$array)
{
    // количество итераций в качестве результата
    $countIteration = 0;
    // определяем размер массива
    $size = count($array);
    for ($i=0; $i<$size; $i++)
    {
        $minIndex = get_min_index_from_position($array, $i, $size);
        if ($array[$i]>$array[$minIndex])
        {
            // тройное перемещение через нулевой элемент согласно условию задачи
            array_swap($array, $i);
            array_swap($array, $minIndex);
            array_swap($array, $i);
            $countIteration++;
        }
    }
    // возвращение результата
    return $countIteration;
}
