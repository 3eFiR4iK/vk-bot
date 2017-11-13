<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use phpQuery as html;
use App\Groups;

class ParseController extends Controller {

    
    
    public static function getAllSite($group, $day) {

        $site = file_get_contents("http://rasp.guap.ru/?g=" . $group);
        $result = html::newDocument($site)->find('div.result');
        //dump($result);
        $week = self::getWeek(html::newDocument($site));
        // dump($week);
        $result->find('div.study > span > b.'.$week)->parents()->remove();
        $find = false;
        $text = '';
        foreach ($result->children() as $e) {
            if (pq($e) == '<h3>' . $day . '</h3>') {
                $text = '&#128197;';
                $find = true;
            }
            if ($find == true) {
                $expl = explode('>', pq($e));
               // dump($expl);
                if ($expl[0] == '<h3' && $expl[1] != $day . '</h3') {
                    $find = false;
                    break;
                }
                if ($expl[0] == '<h4')
                    $text = $text . '<br>&#8986;';
                $text = $text . ' ' . pq($e)->text() . ' <br> ';
            }
        }
        
        return self::deleteEmptyLessons($text);
    }
    
    protected static function deleteEmptyLessons($str) {
        $arr = explode('<br>', $str);
        //dump($str);
        //$i=0;
        foreach ($arr as $k => $v){
             if($v == " "){
                $arr[$k]="<br>";
            } else {
               $arr[$k]=$arr[$k]."<br>"; 
            }
            if(stristr($v,'пара') && $arr[$k+1] == ' '){
                unset($arr[$k]);
            }
           
        }
        //dump($arr);
        return implode('', $arr);
    }
    
    protected static function getDayOfTheWeek($offset = false) {
        $dayoftheweek = [
            1 => 'Понедельник',
            2 => 'Вторник',
            3 => 'Среда',
            4 => 'Четверг',
            5 => 'Пятница',
            6 => 'Суббота'];
        if ($offset)
            return $dayoftheweek[date('w') + 1];
        else
            return $dayoftheweek[date('w')];
    }

    public function updateGroups() {
        $site = file_get_contents("http://rasp.guap.ru/");
        $site = html::newDocument($site)->find('select[name=ctl00$cphMain$ctl05] > option');
        foreach ($site as $e) {
            if (pq($e)->text() != '- нет -') {
                $group = new Groups;
                $group->name = pq($e)->text();
                $group->value = pq($e)->attr('value');
                $group->save();
            }
        }
        echo $site;
    }

    protected static function getWeek($site) {
        $week = $site->find('div.rasp > p > em')->attr('class');
        if ($week == 'up')
            return 'dn';
        else
            return 'up';
    }

}
