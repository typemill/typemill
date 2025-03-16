<?php 

namespace Typemill\Models;

class StorageWrapper
{
	public $object;

	public function __construct(string $classname)
	{
        if (!class_exists($classname))
        {
            throw new \RuntimeException(sprintf('Callable class %s does not exist', $classname));
        }
        $this->object = new $classname();
	}

	function __call($method, $args)
	{
		if(!method_exists($this->object, $method))
		{
            throw new \RuntimeException(sprintf('Callable method %s does not exist', $method));			
		}

    	# Invoke original method on our proxied object
    	return call_user_func_array([$this->object, $method], $args);
  	}
}