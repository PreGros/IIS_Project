<?php

use \ReflectionClass;

class AutoMapper{
    
    public static function map(object|array $srcObject, string $dstObjectClassName, array $map = null): object
    {
        if (!class_exists($dstObjectClassName)){
            throw new InvalidArgumentException("Invalid class name");
        }
        $dst = new $dstObjectClassName();
        $dstRC = new ReflectionClass($dst);
        
        if (is_object($srcObject)){
            foreach ((new ReflectionClass($srcObject))->getMethods(ReflectionMethod::IS_PUBLIC) as $getter){
                if (
                    $getter->isAbstract() ||
                    $getter->isStatic() ||
                    !str_starts_with($getter->getShortName(), 'get') ||
                    ($map !== null && !in_array($getter->getShortName(), $map))
                ){
                    continue;
                }

                $methodName = 'set' . substr($getter->getShortName(), strlen('get'));
                if ($dstRC->hasMethod($methodName)){
                    $dstRC->getMethod($methodName)->invoke($dst, $getter->invoke($srcObject));
                }
            }
            return $dst;
        }
        
        foreach ($srcObject as $key => $value){
            if ($map !== null && !in_array($key, $map)){
                continue;
            }

            $methodName = 'set' . ucfirst($key);
            if ($dstRC->hasMethod($methodName)){
                $dstRC->getMethod($methodName)->invoke($dst, $value);
            }
        }

        return $dst;
    }

}
