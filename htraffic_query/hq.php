<?php
	//Version 0.1

	//Функция для калбека	
	function HQ_modifyBuffer($str){
		HQ::modifyBuffer($str);
	}
	//Проверка наличия MB
	if(!function_exists('mb_convert_encoding'))
		die('Nead PHP extension MB');
	//Проверка версии PHP
	if(!function_exists('mb_strtoupper'))
		die('PHP version must be > 4.3');

	include_once(dirname(__FILE__).'/parse.php');
	class HQ_PROTO extends HQ_PARSE {
		static function init(){
			//автоопределение mb_overload
			if(hq::$mb_overload===null)
				hq::$mb_overload==!!ini_get('mbstring.func_overload');
		
			//Парcим текущий запрос 
			$q=false;
			if(hq::$_queryOnce===null){
				$q=hq::findQueryOnce();
				if($q){
					hq::setQueryOnce($q);
					setcookie('hq_query', $q, time()+60*60*24*hq::$cookieDays,'/');
				}
			}
			//Грузим запрос из кук
			if(hq::$_query===null){
				if(!$q)
					$q=hq::findQuery();//Смотрим в куках
				if($q)
					hq::setQuery($q);
			}
			//редиректы
			hq::redirectOnce(hq::$redirects);
			hq::defineGetParamAnyRef(hq::$setDefaultGetParams);
			
			//буферизация вывода
			if(hq::$ob)
				ob_start('HQ_modifyBuffer');
		}
		//Закрытие беферизации вывода
		static function ob_end(){
			$content=ob_get_contents(); 
			ob_clean();
			$content=hq::modifyBuffer($content);
			echo $content;
		}
		//Обработка буферизации вывода
		static function modifyBuffer($str){
			//Обрабатываем замены
			$str=hq::replace($str,hq::$replaces);
			
			//Обрабатываем вставки
			$arr=explode('<hqout>',$str);
			for($i=1;i<count($arr);$i++){//для кажого места
				$cur=explode('</hqout>',$str,2);//Находим окончание
				$end=$cur[1];
				$cur=explode('\n',$str);
				$res=Array();
				foreach($cur as $str){//Парсим условия
					$str=explode('=>',trim($str),2);
					if(count($str)===1)
						$res[]=$str[0];
					else
						$res[$str[0]]=$str[1];
				}
				$res=hq::match($res);//Находим
				if($res || $res===0 || $res==='0')
					$arr[$i]=$res.$end;
				else
					$arr[$i]=$end;					
			}
			$str=join('',$arr);
			return $str; 
		}
		//Находим запрос по которому пришел пользователь
		static function findQueryOnce(){//Парcим текущий запрос 
			$ref=false;
			if(isset($_SERVER['HTTP_REFERER'])){
				$ref=$_SERVER['HTTP_REFERER'];
				if(!$ref||count(explode('/',$ref))===1)
					$ref=false;
			}
			$base=false;
			$params=Array();
			if($ref){
				$arr=explode('?',$ref,2);
				if(count($arr)===2){
					$base=$arr[0];
					$arr=explode('&',$arr[1]);
					foreach($arr as $str){
						$str=explode('=',$str,2);
						if(isset($str[1]))
							$params[$str[0]]=$str[1];
					}
					//Яндекс
					$arr=explode('/',$ref);
					$arr=explode('.',$arr[2]);
					if(($arr[0]==='yandex'||$arr[0]==='ya') && strpos($ref,'yandsearch') && isset($params['text']) && $params['text'])
						return hq::Utf8_To_Cp1251(urldecode($params['text']));
				}
				else 
					$ref=false;
			}				
			foreach(hq::$queryDetect as $str => $encoding){
				$arr=hq::simpleGen($str);
				foreach($arr as $str){
					if(strpos($str,'://')){//из реферера
						if($ref){
							$str=explode('?',$str,2);
							if($base===$str[0] && isset($params[$str[1]]) && $params[$str[1]]){
								if(hq::isCp1251($encoding))
									return urldecode($params[$str[1]]);
								else
									return hq::Utf8_To_Cp1251(urldecode($params[$str[1]]));
							}
						}
					}
					else if(isset($_GET[$str]) && $_GET[$str]){//из гет параметра
						if(hq::isCp1251($encoding))
							return $_GET[$str];
						else
							return hq::Utf8_To_Cp1251($_GET[$str]);
					}
				}
			}
		}
		
		//Читаем из кук
		static function findQuery(){
			if(isset($_COOKIE['hq_query']) && $_COOKIE['hq_query'])
				return $_COOKIE['hq_query'];
		}

		//Геттеры и сеттеры Query
		static $_query=null;
		static function setQuery($str){
			hq::$_query=$str;
		}

		static $_queryOnce=null;
		static function setQueryOnce($str){
			hq::$_queryOnce=$str;
		}

		static function query(){
			return hq::$_query; 
		}
		static function queryOnce(){
			return hq::$_queryOnce; 
		}

		//Тестирование
		static $redirectTestMode=false;
		static function setRedirectTestMode($val){
			hq::$redirectTestMode=$val;	
		}
		
		static function setTestQuery($str){
			$str=hq::fromEncoding($str);
			hq::$_query=$str;	
			hq::$_queryOnce=$str;
		}
		
		static $testUri=null;
		static function setTestUri($str){
			hq::$testUri=$str;	
		}
		
		//Сравнивает запросом с ключом 
		static function singleMatch($query,$key){	


			if(is_string($query))
				$query=hq::parseQuery($query);
			if(is_string($key))
				$key=hq::parseKey($key);

				
			//echo ' <br />Q='; 
			//print_r($query);
			//echo ' <br />'; 
			
			//echo ' <br />key='; 
			//print_r($key);
			//echo ' <br />'; 

			
			
			foreach($key as $word){
				if(!isset($query[$word[0]]) && !isset($query[$word[0]]))
					return false;
			}
			return true;
		}
		//Сравнивает запросом с условиями
		static function multiMatch($query,$arr){
			
			if($query===null||$query===false||$query===''){
				if(isset($arr['']))
					return $arr[''];
				return false;
			}
			
			$arr=hq::prepare($arr);
			//print_r($arr);

//			echo ' <br />Q='; 
//			print_r($query);
//			echo ' <br />'; 

			if(is_string($query))
				$query=hq::parseQuery($query);
//			echo ' Q2='; 
//			print_r($query);
//			echo ' <br />'; 

			foreach($arr as $key => $val){
				if(hq::singleMatch($query,$key)){
					return $val;
				}
			}
			return false;
		}
		//Обертки
		static function match($arr){
			//echo 'hq::query='.hq::query();		
			//echo '<br />';		
			
			return hq::multiMatch(hq::query(),$arr);  
			
		}
		static function matchOnce($arr){
			return hq::multiMatch(hq::queryOnce(),$arr);  
		}
		static function out($arr){
			$res=hq::match($arr);
			if($res!==false && $res!==NULL)
				echo $res;
			return $res;
		}
		static function outOnce($arr){
			$res=hq::matchOnce($arr);
			if($res!==false && $res!==NULL)
				echo $res;
			return $res;
		}
		//URL ДО URI
		static function baseURL() {
			$res = 'http';
			if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on")
				$res .= "s";
			$res .= "://".$_SERVER["SERVER_NAME"];
			if (isset($_SERVER["SERVER_PORT"]) && $_SERVER["SERVER_PORT"] != "80" && $_SERVER["SERVER_PORT"])
				$res .=":".$_SERVER["SERVER_PORT"];
			return $res;
		}
		static function URI() {
			return $_SERVER["REQUEST_URI"];
		}
		static function URL() {
			return hq::baseURL().hq::URI();
		}
		static function matchPage($page){
			$cur=hq::URI();
			if(hq::$testUri!==null)
				$cur=hq::$testUri;

			if($page==='*'||$cur===$page)
				return true;
			if($page==='')
				return false;
			$anyStart=false;

			//Начинаеться ли с *
			if($page[0]==='*'){
				$anyStart=true;
				$page[0]=' ';
				$page=trim($page);
			}
			
			//Заканчиваеться ли с *
			$anyEnd=true;
			if(substr($page,-1)==='*'){
				$anyStart=true;
				$page=substr($page,0,-1);
			}
			
			$pos=strpos($cur,$page);
			if($pos===false || (!$anyStart && $pos!==0))
				return false;
			if($pos+strlen($page)!==strlen($cur) && !$anyEnd)
				return false;
			return true;			
		}
		static function redirectOnce($arr){
			foreach($arr as $page => $data){
				if(hq::matchPage($page)){
					if($data===false||$data===null||hq::redirectFromAnyPageOnce($data))
						return;
				}
			}
		}

		static function redirectFromAnyPageOnce($arr){
			$res=hq::matchOnce($arr);
			if(is_string($res)){
				if(isset($res[0]) && $res[0]==='*'){
					$res[0]=' ';
					$res=trim($res);
					hq::URL().$res;
				}
				else if(isset($res[0]) && $res[0]==='?'){
					$url=explode('?',hq::URL(),2);
					$base=$url[0];
					if(isset($url[1])){
						$url=explode('&',$url);
						$params=Array();
						foreach($url as $str){
							$str=explode('=',$str,2);
							if(!isset($str[1]))
								$params[$str[0]]=null;
							else
								$params[$str[0]]=$str[1];
						}
						$res[0]=' ';
						$res=trim($res);
						$res=explode('&',$res);
						foreach($res as $str){
							$str=explode('=',$str,2);
							if(!isset($str[1]))
								$params[$str[0]]=null;
							else if($str[1]==='%unset%')
								unset($params[$str[0]]);
							else
								$params[$str[0]]=$str[1];
						}
						$res=$base.'?';
						$n=0;
						foreach($params as $par => $val){
							if($n!==0)
								$res.='&';
							if($val===null)
								$res.=$par;
							else
								$res.=$par.'='.$val;
							$n++;
						}
					}
					else
						$res=$base.$res;
				}
				else if($res==='' || $res[0]==='/')
					$res=hq::baseURL().$res;
					
				if($res===hq::URL())
					return false;
				if(hq::$redirectTestMode){
					echo "\n<br>\n Redirect To ".$res."\n<br>\n";
					return true;
				}
				header('Location: '.$res);
				exit();
				
			}
			return false;
		}
		static $definedGetParams=Array();
		static function defineGetParamAnyRef($paramName, $arr=null){
			if($arr===null){
				$arr=$paramName;
				$paramName='';
			}
			$res=hq::match($arr);
			if($res){
				$pars=Array();
				if(is_array($res))
					$pars=$res;
				else
					$pars[$paramName]=$res;
				foreach($pars as $name => $val){
					if(!isset($_GET[$name])){
						$_GET[$name]=$val;
						hq::$definedGetParams[$name]=$val;
					}
						
					if(!isset($_REQUEST[$name]))
						$_REQUEST[$name]=$val;
						
					if(isset($GLOBALS['HTTP_GET_VARS']) && !isset($GLOBALS['HTTP_GET_VARS'][$name]))
						$GLOBALS['HTTP_GET_VARS'][$name]=$val;	
				}	
			}
		}
		static function replace($str,$arr){
			$res=Array();
			foreach($arr as $key => $data){
				$cur=hq::match($data);
				
				if($cur || $cur==='')
					$res[$key]=$cur;
			}
			//echo '$res=';
			//print_r($res);
			//print_r(array_values($res));
			//echo "str=$str";

			//return str_replace('Комнаты','Кондиционеры 2334',$str);
			
			return str_replace(array_keys($res),array_values($res),$str);
		}
	} 
?>