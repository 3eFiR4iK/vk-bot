<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \VK\CallbackApi\Server\VKCallbackApiServerHandler; 
use App\Http\Controllers\ParseController as parse;
use VK\Client\VKApiClient;
use VK\Client\Enums\VKLanguage;
use App\Groups;
use App\User;

class CommandController extends Controller {
    protected $dayoftheweek = [
        0 => 'Воскресенье',
        1 => 'Понедельник',
        2 => 'Вторник',
        3 => 'Среда',
        4 => 'Четверг',
        5 => 'Пятница',
        6 => 'Суббота',];
    
    protected $data = array();
    
    function __construct(array $object) {
        $this->data = $object;
    }
    
    public function newUser(){
        $vk = new VKApiClient('5.83',VKLanguage::RUSSIAN);
        $group = $this->findGroup($this->data['peer_id']);
        //dump($group);
        $user_info = $vk->users()->get(config('app.VKtoken'),array('user_id' => $this->data['peer_id'],'fields' => 'first_name' ));
        //dump($user_info);
        $user = new User;
        $user->user = $user_info[0]['id'];
        $user->first_name = $user_info[0]['first_name'];
        $user->status = 1;
        $user->save();
        //$vk->messages()->send(config('app.VKtoken'),['peer_id'=>$this->data['peer_id'],'message'=>'ok','keyboard'=> json_encode(config('keyboard.main'),JSON_UNESCAPED_UNICODE)]);  
    }
    
    protected function getDayOfTheWeek($offset = false) {
        if ($offset)
            return $this->dayoftheweek[date('w') + 1];
        else
            return $this->dayoftheweek[date('w')];
        
    }
    
    public function exeMenu($menu){
        $vk = new VKApiClient('5.83',VKLanguage::RUSSIAN);
        
        switch ($menu){
            case 'main':
                $vk->messages()->send(config('app.VKtoken'),['peer_id'=>$this->data['peer_id'],'message'=> "ok",'keyboard'=> json_encode(config('keyboard.menu'),JSON_UNESCAPED_UNICODE)]);
                break;
            case 'back':
                $vk->messages()->send(config('app.VKtoken'),['peer_id'=>$this->data['peer_id'],'message'=> "ok",'keyboard'=> json_encode(config('keyboard.main'),JSON_UNESCAPED_UNICODE)]);
                break;
        }
    }

    public function exeCommand($command, $group='') {
        $vk = new VKApiClient('5.83',VKLanguage::RUSSIAN);
        
        switch ($command) {
            case 'start': 
                $vk->messages()->send(config('app.VKtoken'),['peer_id'=>$this->data['peer_id'],'message'=> config('text.welcome')]);  
                $this->newUser(); 
                break;
            case 'update':
                $vk->messages()->send(config('app.VKtoken'),['peer_id'=>$this->data['peer_id'],'message'=> config('text.update'),'keyboard'=> json_encode(config('keyboard.main'),JSON_UNESCAPED_UNICODE)]);
                break;
            case 'noFound':
                $vk->messages()->send(config('app.VKtoken'),['peer_id'=>$this->data['peer_id'],'message'=> config('text.noFound')]);
                break;
            case 'changeGroup':
                $this->updateGroup(true);
                return $vk->messages()->send(config('app.VKtoken'),['peer_id'=>$this->data['peer_id'],'keyboard'=> json_encode(config('keyboard.noKey'),JSON_UNESCAPED_UNICODE),'message'=> config('text.changeGroup')]);
                break;
            case '1' :
                return $vk->messages()->send(config('app.VKtoken'),['peer_id'=>$this->data['peer_id'],'keyboard'=> json_encode(config('keyboard.main'),JSON_UNESCAPED_UNICODE),'message'=> parse::getAllSite($group, $this->getDayOfTheWeek())]);
            case '2' :
                return $vk->messages()->send(config('app.VKtoken'),['peer_id'=>$this->data['peer_id'],'keyboard'=> json_encode(config('keyboard.main'),JSON_UNESCAPED_UNICODE),'message'=> parse::getAllSite($group, $this->getDayOfTheWeek(true))]);;
            case 'пн' :
                return parse::getAllSite($group, $this->dayoftheweek[1]);
            case 'вт' :
                return parse::getAllSite($group, $this->dayoftheweek[2]);
            case 'ср' :
                return parse::getAllSite($group, $this->dayoftheweek[3]);
            case 'чт' :
                return parse::getAllSite($group, $this->dayoftheweek[4]);
            case 'пт' :
                return parse::getAllSite($group, $this->dayoftheweek[5]);
            case 'сб' :
                return parse::getAllSite($group, $this->dayoftheweek[6]);
            default :
                return false;
        } 
    }
    
    
    public function updateGroup(bool $change,$group=0){
        $user = User::where('user', '=', $this->data['peer_id'])->first();
        if($change){
        $user->status = 1;   
        } else {
        $user->group_id = $group;
        $user->status = 0; 
        }
        $user->save();
    }
    
    public static function getUserValue($user_id){
        $res = User::with('groups')->where('user', '=', $user_id)->first();
        //dump($res);
        return $res->groups->value;
    }
    
     protected function findGroup($group) {
        $res = Groups::where('name', '=', $group)->get();
        if (count($res) == 1) {
            foreach ($res as $g) {
                return $g->id;
            }
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
}
