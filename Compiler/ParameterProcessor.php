<?php

namespace Kutny\AutowiringBundle\Compiler;

use ReflectionClass;
use ReflectionParameter;
use Symfony\Component\DependencyInjection\Reference;

class ParameterProcessor
{

    public function getParameterValue(ReflectionParameter $parameter, array $classes, $serviceId)
    {
        $parameterClass = $parameter->getClass();

        if ($parameterClass) {
            $value = $this->processParameterClass($parameterClass, $parameter, $classes);
        } else {
            if ($parameter->isDefaultValueAvailable()) {
                $value = $parameter->getDefaultValue();
            } else {
                $message = 'Class ' . $parameter->getDeclaringClass()->getName() . ' (service: ' . $serviceId . '), parameter $' . $parameter->getName();

                throw new CannotResolveParameterException($message);
            }
        }

        return $value;
    }

    private function processParameterClass(ReflectionClass $parameterClass, ReflectionParameter $parameter, $classes)
    {
        $class = $parameterClass->getName();

        if ($class === 'Symfony\Component\DependencyInjection\Container') {
            return new Reference('service_container');
        }

        if (isset($classes[$class])) {
            if (count($classes[$class]) === 1) {
                $value = new Reference($classes[$class][0]);
            } else {
                $serviceNames = implode(', ', $classes[$class]);
                $message = 'Multiple services of ' . $class . ' defined (' . $serviceNames . '), class used in ' . $parameter->getDeclaringClass()->getName();

                throw new MultipleServicesOfClassException($message);
            }
        } else {
            if ($parameter->isDefaultValueAvailable()) {
                $value = $parameter->getDefaultValue();
            } else {
                throw new ServiceNotFoundException('Service not found for ' . $class . ' used in ' . $parameter->getDeclaringClass()->getName());
            }
        }

        return $value;
    }
}
