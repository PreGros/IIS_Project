<?php

use \ReflectionClass;

class AutoMapper{

    /** @var array<object> $entities */
    private static array $entities = [];
    
    /**
     * @param object|array $srcObject object or array to be mapped to object
     * @param string|object $dst 
     * when string, class of dst object
     *  - try to retrieve object, that was used for $srcObject mapping
     *  - if fails new object for dst will be created
     * when object, this object will be modified
     * @param ?array $map if set, only properties in this array will be set
     * @param bool $trackEntity if true, srcObject will be saved for remapping
     * @return object mapped object
     */
    public static function map(object|array $srcObject, string|object $dst, ?array $map = null, bool $trackEntity = true): object
    {
        if (is_string($dst)){
            if (!class_exists($dst)){
                throw new InvalidArgumentException("Invalid class name");
            }
            $dst = (is_object($srcObject) ? self::$entities[spl_object_id($srcObject)] : null) ?? new $dst();
        }
        
        $dstRC = new ReflectionClass($dst);
        
        if (is_object($srcObject)){
            $srcRC = new ReflectionClass($srcObject);
            $mapped = self::mapObjectFromGetters($srcRC, $dstRC, $srcObject, $dst, $map);
            self::mapObjectFromProperties($srcRC, $dstRC, $srcObject, $dst, $map, $mapped);

            if ($trackEntity){
                self::$entities[spl_object_id($dst)] = $srcObject;
            }
            return $dst;
        }
        
        self::mapObjectFromArray($dstRC, $dst, $srcObject, $map);
        return $dst;
    }

    private static function mapObjectFromGetters(
        \ReflectionClass $srcRC,
        \ReflectionClass $dstRC,
        object $srcObject,
        object $dstObject,
        ?array $map = null): array
    {
        $mapped = [];
        foreach ($srcRC->getMethods(ReflectionMethod::IS_PUBLIC) as $getter){
            if ($getter->isAbstract() || $getter->isStatic() || !str_starts_with($getter->getShortName(), 'get')){
                continue;
            }
            $property = lcfirst(substr($getter->getShortName(), strlen('get')));
            if (!($map === null || in_array($property, $map))){
                continue;
            }

            $methodName = 'set' . ucfirst($property);
            if ($dstRC->hasMethod($methodName)){
                $dstRC->getMethod($methodName)->invoke($dstObject, $getter->invoke($srcObject));
                $mapped[] = $property;
                continue;
            }

            if ($dstRC->hasProperty($property)){
                $dstRC->getProperty($property)->setValue($dstObject, $getter->invoke($srcObject));
                $mapped[] = $property;
            }
        }
        return $mapped;
    }

    private static function mapObjectFromProperties(
        \ReflectionClass $srcRC,
        \ReflectionClass $dstRC,
        object $srcObject,
        object $dstObject,
        ?array $map = null,
        array $mapped)
    {
        foreach ($srcRC->getProperties() as $property){
            $propName = $property->getName();
            if (in_array($propName, $mapped) || !($map === null || in_array($propName, $map))){
                continue;
            }

            $methodName = 'set' . ucfirst($propName);
            if ($dstRC->hasMethod($methodName)){
                $dstRC->getMethod($methodName)->invoke($dstObject, $property->getValue($srcObject));
                continue;
            }

            if ($dstRC->hasProperty($propName)){
                $dstRC->getProperty($propName)->setValue($dstObject, $property->getValue($srcObject));
            }
        }
    }

    private static function mapObjectFromArray(\ReflectionClass $dstRC, object $dst, array $src, ?array $map)
    {
        foreach ($src as $key => $value){
            if ($map !== null && !in_array($key, $map)){
                continue;
            }

            $methodName = 'set' . ucfirst($key);
            if ($dstRC->hasMethod($methodName)){
                $dstRC->getMethod($methodName)->invoke($dst, $value);
                continue;
            }

            if ($dstRC->hasProperty($key)){
                $dstRC->getProperty($key)->setValue($dst, $value);
            }
        }
    }
}
