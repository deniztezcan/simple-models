<?php

namespace DenizTezcan\SimpleModels;

class Collection 
{
	public array $attributes;

	public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function set($key, $value)
    {
        $this->attributes[$key] = $value;
    }
    
    public function get($key)
    {
        return $this->attributes[$key];
    }

    public function all()
    {
    	return $this->attributes;
    }

    public function remove($key)
    {
        unset($this->attributes[$key]);
    }

    public function isset($key){
        if(isset($this->attributes[$key])){
            return true;
        }else{
            return false;
        }
    }

    public function count()
    {
    	return count($this->attributes);
    }
}