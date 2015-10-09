<?php

namespace test\fixtures;

class Curl
{
    public function doGet($name)
    {
        return "Hello {$name}";
    }
}
