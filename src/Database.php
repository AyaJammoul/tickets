<?php

class Database {

    private static $instance = null;

    private static $host = 'localhost';
    private static $user = 'medgo_userhelpdesk';
    private static $password = '%e}A(lffQSOc';
    private static $db = 'medgo_helpdesk';
    
    public static function getInstance(){
        if(self::$instance == null){
            self::$instance = new mysqli(self::$host, self::$user, self::$password, self::$db);
        }
        return self::$instance;
    } 
    
}