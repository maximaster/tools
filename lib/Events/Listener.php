<?php

namespace Maximaster\Tools\Events;

use Maximaster\Tools\Psr4Autoloader;

class Listener extends Psr4Autoloader
{
    protected $prefixes = array();

    /**
     * »нициирует регистрацию всех событий
     */
    public function register()
    {
        $collection = array();
        foreach ($this->prefixes as $namespace => $directoryList)
        {
            foreach ($directoryList as $directory)
                $collection += $this->collect($namespace, $directory);
        }
        foreach ($collection as $handler)
        {
            $sort = $handler['sort'] ? $handler['sort'] : 100;
            $this->listen($handler['moduleName'], $handler['eventType'], $handler['callback'], $sort);
        }
    }

    /**
     * –егистрирует событие с заданными параметрами
     * @param     $moduleId
     * @param     $eventType
     * @param     $callback
     * @param int $sort
     * @return int
     */
    private function listen($moduleId, $eventType, $callback, $sort = 100)
    {
        return AddEventHandler($moduleId, $eventType, $callback, $sort);
    }

    /**
     * Ќа основании пространства имен собирает все обработчики в массив
     * @param $namespace
     * @param $handlersDirectory
     * @return array
     */
    private function collect($namespace, $handlersDirectory)
    {
        $ns = $namespace;
        $collection = array();
        if (!is_dir($handlersDirectory)) return $collection;

        $dirIterator   = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($handlersDirectory));
        $regexIterator = new \RegexIterator($dirIterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);

        foreach ($regexIterator as $file)
        {
            $file = current($file);
            if (!$this->requireFile($file)) continue;

            $relativeClass = str_replace(array($handlersDirectory, '.php'), '', $file);
            list($moduleName, $eventType) = explode('/', $relativeClass);
            if (!$eventType) continue;

            $className = $ns . str_replace('/', '\\', $relativeClass);
            $class = new \ReflectionClass($className);
            foreach ($class->getMethods() as $method)
            {
                if ($method->class == $class->getName())
                    $collection[] = array(
                        'moduleName' => strtolower($moduleName),
                        'eventType' => $eventType,
                        'callback' => array($class->getName(), $method->name)
                    );
            }
        }
        
        return $collection;
    }
}