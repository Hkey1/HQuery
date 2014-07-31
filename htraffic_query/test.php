<?php
	header('Content-Type: text/html; charset=cp1251');
	error_reporting(E_ALL);
	include_once(dirname(__FILE__).'/config.php');
	
	HQ::setTestQuery('кондиционеры москва');
	HQ::setTestUri('/');
	HQ::setRedirectTestMode(true);
	
	//echo HQ::query().'<br />';
	//echo HQ::query().'<br />';
	//echo HQ::query().'<br />';
	
	echo '<h1>prepare</h1>';
	echo '<pre>';

	echo 'квартиры ';
	print_r(hq::prepare('квартиры'));
	
	echo 'квартиры=>комнаты ';
	print_r(hq::prepare(Array('квартиры'=>'комнаты')));
	
	echo 'кв(а|о)ртиры=>комнаты ';
	print_r(hq::prepare(Array('кв(а|о)ртиры'=>'комнаты')));

	echo 'кв(а|о)ртиры ';
	print_r(hq::prepare(Array('кв(а|о)ртиры')));
	
	echo '!кв(а|о)ртиры ';
	print_r(hq::prepare(Array('!кв(а|о)ртиры')));

	echo '+кв(а|о)ртиры ';
	print_r(hq::prepare(Array('+кв(а|о)ртиры')));
	
	echo '!кв{а|о}ртиры ';
	print_r(hq::prepare(Array('!кв{а|о}ртиры')));

	echo 'ывыв кв{а|о}ртир{ант|}ы бла бла бла ';
	print_r(hq::prepare(Array('ывы кв{а|о}ртир{ант|}ы бла бла бла')));

	echo '+кв{а|о}ртиры ';
	print_r(hq::prepare(Array('+кв{а|о}ртиры')));
	
	echo '</pre>';
	echo '<h1>match</h1>';
	
	echo '<br />Must be true ';
	var_dump(HQ::match(Array(
		'кондиционеры москва'=>true
	)));

	echo '<br />Must be true ';
	var_dump(HQ::match(Array(
		'кондиционер москве'=>true
	)));

	echo '<br />Must be true ';
	var_dump(HQ::match(Array(
		'кондиционер в москве'=>true
	)));

	echo '<br />Must be true ';
	var_dump(HQ::match(Array(
		'кондиционер'=>true
	)));
	
	echo '<br />Must be true ';
	var_dump(HQ::match(Array(
		'москвы'=>true
	)));

	echo '<br />Must be true ';
	var_dump(HQ::match(Array(
		'Москвы'=>true
	)));
	
	echo '<br />Must be false ';
	var_dump(HQ::match(Array(
		'бла'=>true
	)));
	
	echo '<br />Must be false ';
	var_dump(HQ::match(Array(
		'береза'=>true
	)));

	echo '<br />Must be false ';
	var_dump(HQ::match(Array(
		'москва береза'=>true
	)));

	echo '<br />Must be false ';
	var_dump(HQ::match(Array(
		'кондиционер +в москве'=>true
	)));
	
	echo '<h1>Replace</h1>';
	echo hq::replace('Комнаты в Москве недорого!',Array(
		'Комнаты'=>Array('Кондиционеры')
	));
	
	echo '<h1>defineGetParamAnyRef</h1>';
	hq::defineGetParamAnyRef(Array('москва'=>Array('order_by'=>'price')));
	
	print_r(HQ::$definedGetParams);
	
	echo '<h1>redirectFromAnyPageOnce</h1>';
	hq::redirectFromAnyPageOnce(Array(
		'квартиры'=>'/flat.php',
		'москва'=>'/msk.php',
	));
	
	hq::redirectFromAnyPageOnce(Array(
		'москва'=>'?x=1',
	));
	
	echo '<h1>redirectOnce</h1>';
	hq::redirectOnce(Array(
		'/'=>Array('москва'=>'?x=1'),
	));
	hq::redirectOnce(Array(
		'/page'=>Array('москва'=>'?x=1'),
		'/'=>Array('москва'=>'?x=2'),
	));
	
?>