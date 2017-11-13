<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Sunra\PhpSimple\HtmlDomParser as html;
use App\Groups;
use App\User;
use App\Http\Controllers\ParseController as parse;

class TestController extends Controller {
    protected $dayoftheweek = [
            1 => 'Понедельник',
            2 => 'Вторник',
            3 => 'Среда',
            4 => 'Четверг',
            5 => 'Пятница',
            6 => 'Суббота'];
    protected $confirmation_token = '6ebc1af7';
    protected $token = '331d3d055b70a8548a86e36c96d2150a0e60bfe1bee967e0f9f61850918eebf9ffe39273d2ed8d021f905';

    public function getSite() {


        if (!isset($_REQUEST)) {
            return;
        }


//Получаем и декодируем уведомление 
        $data = json_decode(file_get_contents('php://input'));

//Проверяем, что находится в поле "type" 
        
        switch ($data->type) {
//Если это уведомление для подтверждения адреса... 
            case 'confirmation':
//...отправляем строку для подтверждения 
                echo $this->confirmation_token;
                break;

//Если это уведомление о новом сообщении... 
            case 'message_new':
                if (!$this->findUser($data->object->user_id)) {
                    if (!$this->findGroup($data->object->body)) {
                        $this->sendMessage('Извините, но вашей группы нет в базе данных. Попробуйте еще раз.', $data->object->user_id);
                        return "ok";
                    }
                    $group = $this->findGroup($data->object->body);
                    $user_info = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$data->object->user_id}&v=5.0"));
                    $user_name = $user_info->response[0]->first_name;
                    $this->addUser($data->object->user_id, $group, $user_name);
                    $this->sendMessage('Выберете одно из возможных действий :<br> 1) расписание на сегодня<br> 2) расписание на завтра"
                            . "<br><br>
                            Если хочешь получить расписание на определенный день, то напиши название дня в сокращенной форме (пн, вт, ср, чт, пт, сб)', $data->object->user_id);
                    
                } else if($com = $this->findCommand($data->object->body, $this->getUserValue($data->object->user_id))) {
                    $this->sendMessage($com, $data->object->user_id);
                } else {
                    $this->sendMessage("Выберете одно из возможных действий :<br> 1) расписание на сегодня<br> 2) расписание на завтра<br> "
                            . "<br>Если хочешь получить расписание на определенный день, то напиши название дня в сокращенной форме (пн, вт, ср, чт, пт, сб)"
                            , $data->object->user_id);
                }
                echo('ok');
                break;
                
            case 'group_join' :
                $this->sendMessage('Привет. Напиши мне номер своей группы.', $data->object->user_id);
                echo('ok');
                break;
        }
    }

    public function findGroup($group) {
        $res = Groups::where('name', '=', $group)->get();
        if (count($res) == 1) {
            foreach ($res as $g) {
                return $g->id;
            }
        } else
            return false;
    }

    public function addUser($userID, $group, $name) {
        $user = new User;
        $user->user = $userID;
        $user->group_id = $group;
        $user->first_name = $name;
        $user->save();
    }
    
    protected function getUserValue($user_id){
        $res = User::with('groups')->where('user','=',$user_id)->get();
        foreach($res as $u)
            return $u->groups->value;
    }

    protected function findUser($userID) {
        $res = User::where('user', '=', $userID)->get();
        if (count($res) == 1) {
            return true;
        } else
            return false;
    }

    protected function sendMessage($text, $userID) {
        $params = array(
            'message' => $text,
            'user_id' => $userID,
            'access_token' => $this->token,
            'v' => '5.37'
        );
        $url = 'https://api.vk.com/method/messages.send';
        //$get_params = http_build_query($request_params);
        file_get_contents($url, false, stream_context_create(array(
        'http' => array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($params)
        )
    )));
    }
    
    protected function getDayOfTheWeek($offset = false) {
        if ($offset)
            return $this->dayoftheweek[date('w') + 1];
        else
            return $this->dayoftheweek[date('w')];
    }
    
    protected function findCommand($command,$group){
        switch($command){
            case '1' :
                return parse::getAllSite($group,$this->getDayOfTheWeek());
            case '2' :
                return parse::getAllSite($group,$this->getDayOfTheWeek(true));
            case 'пн' :
               return parse::getAllSite($group,$this->dayoftheweek[1]);
            case 'вт' :
               return parse::getAllSite($group,$this->dayoftheweek[2]);
            case 'ср' :
               return parse::getAllSite($group,$this->dayoftheweek[3]);
            case 'чт' :
               return parse::getAllSite($group,$this->dayoftheweek[4]);
            case 'пт' :
               return parse::getAllSite($group,$this->dayoftheweek[5]);
            case 'сб' :
               return parse::getAllSite($group,$this->dayoftheweek[6]);
            default :
                return false;
        }
    }

}
