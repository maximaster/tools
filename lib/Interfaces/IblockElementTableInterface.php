<?php


namespace Maximaster\Tools\Interfaces;


interface IblockElementTableInterface
{
    /**
     * Необходимо в наследнике определить метод, который позволит получить идентификатор инфоблок
     * @return int
     */
    public static function getIblockId();
}