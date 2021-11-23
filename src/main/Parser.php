<?php


interface Parser
{
    // Returns a map of parsed PDF or throws an exception
    public function parse($path);
}