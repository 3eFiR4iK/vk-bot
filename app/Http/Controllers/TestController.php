<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiHandler;
use App\Groups;
use App\User;
use App\Http\Controllers\ParseController as parse;
//use App\text\text;

class TestController extends Controller {

    

    public function getSite() {

        if (!isset($_REQUEST)) {
            return;
        }

       
        $handler = new ApiHandler();
        $data = json_decode(file_get_contents('php://input'));
        $handler->parse($data);
        
        //dump(config('text.welcome'));
        
        
        //dump($data);
        //dump();
    }

    

}
