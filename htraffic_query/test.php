<?php
	header('Content-Type: text/html; charset=cp1251');
	error_reporting(E_ALL);
	include_once(dirname(__FILE__).'/config.php');
	
	HQ::setTestQuery('������������ ������');
	HQ::setTestUri('/');
	HQ::setRedirectTestMode(true);
	
	//echo HQ::query().'<br />';
	//echo HQ::query().'<br />';
	//echo HQ::query().'<br />';
	
	echo '<h1>prepare</h1>';
	echo '<pre>';

	echo '�������� ';
	print_r(hq::prepare('��������'));
	
	echo '��������=>������� ';
	print_r(hq::prepare(Array('��������'=>'�������')));
	
	echo '��(�|�)�����=>������� ';
	print_r(hq::prepare(Array('��(�|�)�����'=>'�������')));

	echo '��(�|�)����� ';
	print_r(hq::prepare(Array('��(�|�)�����')));
	
	echo '!��(�|�)����� ';
	print_r(hq::prepare(Array('!��(�|�)�����')));

	echo '+��(�|�)����� ';
	print_r(hq::prepare(Array('+��(�|�)�����')));
	
	echo '!��{�|�}����� ';
	print_r(hq::prepare(Array('!��{�|�}�����')));

	echo '���� ��{�|�}����{���|}� ��� ��� ��� ';
	print_r(hq::prepare(Array('��� ��{�|�}����{���|}� ��� ��� ���')));

	echo '+��{�|�}����� ';
	print_r(hq::prepare(Array('+��{�|�}�����')));
	
	echo '</pre>';
	echo '<h1>match</h1>';
	
	echo '<br />Must be true ';
	var_dump(HQ::match(Array(
		'������������ ������'=>true
	)));

	echo '<br />Must be true ';
	var_dump(HQ::match(Array(
		'����������� ������'=>true
	)));

	echo '<br />Must be true ';
	var_dump(HQ::match(Array(
		'����������� � ������'=>true
	)));

	echo '<br />Must be true ';
	var_dump(HQ::match(Array(
		'�����������'=>true
	)));
	
	echo '<br />Must be true ';
	var_dump(HQ::match(Array(
		'������'=>true
	)));

	echo '<br />Must be true ';
	var_dump(HQ::match(Array(
		'������'=>true
	)));
	
	echo '<br />Must be false ';
	var_dump(HQ::match(Array(
		'���'=>true
	)));
	
	echo '<br />Must be false ';
	var_dump(HQ::match(Array(
		'������'=>true
	)));

	echo '<br />Must be false ';
	var_dump(HQ::match(Array(
		'������ ������'=>true
	)));

	echo '<br />Must be false ';
	var_dump(HQ::match(Array(
		'����������� +� ������'=>true
	)));
	
	echo '<h1>Replace</h1>';
	echo hq::replace('������� � ������ ��������!',Array(
		'�������'=>Array('������������')
	));
	
	echo '<h1>defineGetParamAnyRef</h1>';
	hq::defineGetParamAnyRef(Array('������'=>Array('order_by'=>'price')));
	
	print_r(HQ::$definedGetParams);
	
	echo '<h1>redirectFromAnyPageOnce</h1>';
	hq::redirectFromAnyPageOnce(Array(
		'��������'=>'/flat.php',
		'������'=>'/msk.php',
	));
	
	hq::redirectFromAnyPageOnce(Array(
		'������'=>'?x=1',
	));
	
	echo '<h1>redirectOnce</h1>';
	hq::redirectOnce(Array(
		'/'=>Array('������'=>'?x=1'),
	));
	hq::redirectOnce(Array(
		'/page'=>Array('������'=>'?x=1'),
		'/'=>Array('������'=>'?x=2'),
	));
	
?>