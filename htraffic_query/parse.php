<?php
	//Функции касающиеся подготовки ключей и их сравнения с запросом 

	include_once(dirname(__FILE__).'/libs.php');
	class HQ_PARSE extends HQ_LIBS {
	
		// Генерирует варианты одного типа синтаксиса () или {}
		// Возвращает массив вариантов 
		static function simpleGen($str,$startSymb='(',$endSymb=')'){
			$arr=explode($startSymb, $str,  2);
			if(count($arr)===1){

				return $arr;
			}
			
			$start=$arr[0];
			$arr=explode($endSymb, $arr[1], 2);
			$end=$arr[1];
			
			$arr=explode('|',$arr[0]);
			
			$res=Array();
			foreach($arr as $cur)
				$res=array_merge($res,hq::simpleGen($start.$cur.$end,$startSymb,$endSymb));
			return $res;
		}
		
		// Генерирует варианты обоих типов синтаксиса () и {}
		// Возвращает ассоциативный массив
		static function fullGen($str){
			static $cache=Array();
			if(isset($cache[$str]))
				return $cache[$str];

			$arr=hq::simpleGen($str);
			
			$res=Array();
			foreach($arr as $cur){
				$t=hq::simpleGen($cur,'{','}');
				$res[$t[0]]=$t;
			}
			$cache[$str]=$res;

			return $res;
		}
		
		static function toLower($str){
			//$str=str_replace(['ё','Ё'],['е','Е'],$str);
			$str=mb_strtolower($str,'cp1251');
			return $str;
		}

		// Препроцессинг списка ключей
		static function prepare($arr){
			if(!is_array($arr)){
				if(is_string($arr))
					$arr=Array($arr);
				else
					die('Error must be string or Array');
			}
			$res=Array();
			foreach($arr as $i => $val){
				if(!is_string($i) && !is_string($val))
					die('Error must be string');
				if(is_numeric($i)){//("value")
					
					$val=hq::fromEncoding($val);
					hq::checkKey($val);
					$ta=hq::fullGen($val);
					//print_r($ta);
					foreach($ta as $str => $data)
						foreach($data as $key)
							$res[hq::toLower($key)]=hq::dropMinusAndVskl(HQ::toEncoding($str));
				}
				else {//"key"=>"value"
					$i=hq::fromEncoding($i);
					hq::checkKey($i);
					$ta=hq::fullGen($i);
					//print_r($ta);
					foreach($ta as $str => $data)
						foreach($data as $key)
							$res[hq::toLower($key)]=$val;
				}				
			}
			return $res;
		}
		static function byteLen($str){
			if(hq::$mb_overload){
				return mb_substr($str, 'cp1251');
			}else
				return strlen($str);
		}
		static function byteAt($str,$i){
			if(hq::$mb_overload){
				return mb_substr($str , $i, 1, 'cp1251');
			}else
				return $str[$i];
		}
		static function dropMinusAndVskl($str){
			return str_replace(['!','+'],['',''],$str);
		}
		static function dropSyntax($str){
			static $cache=Array();
			$str=str_replace(['ё','Ё'],['е','Е'],$str);
			if(isset($cache[$str]))
				return $cache[$str];
				
			$res='';
			$len=hq::$mb_overload ? hq::byteLen($str) : strlen($str);
			for($i=0;$i<$len;$i++){
				$symb=hq::$mb_overload ? hq::byteAt($str,$i) : $str[$i];
				if(isset(hq::$symbWhiteList[$symb])
				&&!isset(hq::$syntaxSymbs[$symb]))
					$res.=$symb;
			}
			$cache[$str]=$res;
			return $res;
		}	
		// Проверка на плохие символы
		static function checkKey($str){
			static $cache=Array();
			if(isset($cache[$str])||hq::$noCheck)
				return;
			$len=hq::$mb_overload ? hq::byteLen($str) : strlen($str);
			for($i=0;$i<$len;$i++){
				$symb=hq::$mb_overload ? hq::byteAt($str,$i) : $str[$i];
				if(!isset(hq::$symbWhiteList[$symb]))
					die('Bad Encoding. Or bad symbol `'.$symb.'` at '.$str);	
			}
			$cache[$str]=1;
		}
		static function dropPlus($str){
			if($str[0]==='+'){
				$str[0]=' ';
				$str=trim($str);
			}
			return $str;
		}
		static function morf($str){
			static $cache=Array();
			if($str[0]==='!')
				return $str;
	
			if(isset($cache[$str]))
				return $cache[$str];
			$res=null;
			
			if(!hq::$noMorf)
				$res=MorfFindGroup(hq::Cp1251_To_Utf8($str));
			//echo 'omo_id('.$str.')='.$res.' ';
				
			static $stemmer = null;
			if($stemmer ===null)
				$stemmer = new LinguaStemRu();		
			
			$res=$res ? $res : $stemmer->stem_word($str);
			
			$cache[$str]=$res;
			return $res;
		}
		static function convert_encoding($str, $in, $out){
			//mb_convert_encoding($str ,$to_encoding $from_encoding);
			$str=mb_convert_encoding($str ,$out, $in);
			return $str;
		}
		static function Cp1251_To_Utf8($str){
			return hq::convert_encoding($str, 'cp1251', 'utf-8');
		}
		static function Utf8_To_Cp1251($str){
			return hq::convert_encoding($str, 'utf-8','cp1251');
		}
		static function isCp1251($encoding){
			return !$encoding || strpos($encoding,'1251')!==false;
		}
		
		//Конвертирует кодировку ввода во внутрениюю кодировку (цп1251) 
		static function fromEncoding($str){
			return hq::isCp1251(hq::$encoding) ? $str : hq::Utf8_To_Cp1251($str);
		}
		//Конвертирует внутренююю кодировку во внешнюю
		static function toEncoding($str){
			return hq::isCp1251(hq::$encoding) ? $str : hq::Cp1251_To_Utf8($str);
		}
		
		//преобразует запрос в индекс слов
		static function parseQuery($str){
			static $cache=Array();
			$str=hq::toLower($str);
			$str=hq::dropSyntax($str);
			if(isset($cache[$str]))
				return $cache[$str];
			$index=Array();
			$arr=explode(' ',$str);
			foreach($arr as $cur) if($cur!==''){
				$index[hq::morf($cur)]=1;
				$index['!'.$cur]=1;
			}
			$cache[$str]=$index;
			return $index;
		}

		//преобразует ключ в список слов с морфологией
		static function parseKey($str){
			static $cache=Array();
			if(isset($cache[$str]))
				return $cache[$str];

			$words=Array();
			$arr=explode(' ',$str);
			foreach($arr as $cur) if($cur!==''){
				if(!isset(hq::$stopWords[$cur])){
					$cur=hq::dropPlus($cur);
					if($cur!=='')
						$words[]=[hq::morf($cur),'!'.hq::dropSyntax($cur)];
				}
			}
			$cache[$str]=$words;
			return $words;
		}
		
	} 
?>