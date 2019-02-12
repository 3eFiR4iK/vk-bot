<?php
namespace App\Http\Controllers;

use \VK\CallbackApi\Server\VKCallbackApiServerHandler; 
use VK\Client\VKApiClient;
use VK\Client\Enums\VKLanguage;
use App\Http\Controllers\CommandController as Commands;
use App\Groups;
use App\User;

class ApiHandler extends VKCallbackApiServerHandler { 
    const SECRET = 'sdfsdln7werlkn'; 
    const GROUP_ID = 152757604; 
    const CONFIRMATION_TOKEN = '6ebc1af7'; 

function confirmation(int $group_id, ?string $secret) { 
        if ($secret === static::SECRET && $group_id === static::GROUP_ID) { 
            echo static::CONFIRMATION_TOKEN; 
        } 
    } 

public function messageNew(int $group_id, ?string $secret, array $object) { 
    $vk = new VKApiClient('5.83',VKLanguage::RUSSIAN);
    $commands = new Commands($object);
    if(isset($object['payload'])){
        $payload = json_decode($object['payload']);
    }
    //dump($object);
    /*
     * проверка на существование пользователя
     */
    //$vk->messages()->send(config('app.VKtoken'),['peer_id'=>'144856310','message'=>'ok','keyboard'=>'{"buttons":[],"one_time":true}']);
    if(isset($payload->command) && !$this->findUser($object['peer_id'])){
        $commands->exeCommand($payload->command);
        echo "ok";
        return;
    }else if (!isset($payload->command) && !$this->findUser($object['peer_id'])){
        $commands->exeCommand('start');
        echo "ok";
        return;
    }
    //---------------------------------------//
    
    /*
     * update группы
     */
    if($this->findGroup($object['text']) && $this->waitStatus($object['peer_id'])){
        $commands->updateGroup(false,$this->findGroup($object['text']));
        //dump($group);
        $commands->exeCommand('update');
        echo "ok";
        return;
    } else if(!$this->findGroup($object['text']) && $this->waitStatus($object['peer_id'])){
        $commands->exeCommand('noFound');
        echo "ok";
        return;
    }
    //----------------------------------------
    
    /*
     * основные команды
     */
    if(isset($payload->command) && $this->findUser($object['peer_id'])){
        $commands->exeCommand($payload->command,$this->getUserValue($object['peer_id']));
        echo "ok";
        return;
    }
    //-----------------------------------------
    
    /*
     * меню
     */
    if(isset($payload->menu) && $this->findUser($object['peer_id'])){
        $commands->exeMenu($payload->menu);
        echo "ok";
        return;
    }
    
    
    
    echo "ok";
    //dump($payload);
    
    
    } 
    
    protected function findGroup($group) {
        $res = Groups::where('name', '=', mb_strtoupper($group))->get();
        //dump($res);
        if (count($res) == 1) {
            foreach ($res as $g) {
                return $g->value;
            }
        } else
            return false;
    }
    
    protected function waitStatus($userID){
        $res = User::where('user', '=', $userID)->first();
        if ($res->status == 1) {
            return true;
        } else
            return false;
    }

    protected function findUser($userID) {
        $res = User::where('user', '=', $userID)->get();
        if (count($res) == 1) {
            return true;
        } else
            return false;
    }
    
    public static function getUserValue($user_id){
        $res = User::with('groups')->where('user', '=', $user_id)->first();
        //dump($res);
        return $res->group_id;
    }
} 