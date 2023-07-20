<?php

ini_set('max_execution_time', '0');

function pr($dt) {
	echo '<pre>';
	print_r($dt);
	echo '</pre>';
}
function sortByName($a, $b) {
	return strcmp($a[1], $b[1]);
}

$dt = file_get_contents('location.json');
$dt = json_decode($dt,1);

$province = [];
$city     = [];
$district = [];
$village  = [];

foreach ($dt as $v) {
	$id = explode('.', $v['code']);
	$province[$id[0]] = [intval($id[0]),$v['province']];
	$city[$id[0]][$id[0].$id[1]] = [intval($id[0].$id[1]),$v['city']];
	$district[$id[0].$id[1]][$id[0].$id[1].$id[2]] = [intval($id[0].$id[1].$id[2]),$v['district']];
	$village[$id[0].$id[1].$id[2]][$id[0].$id[1].$id[2].$id[3]] = [intval($id[0].$id[1].$id[2].$id[3]),$v['village'],$v['postal']];
}

// $province = array_values($province);
// usort($province, 'sortByName');
// file_put_contents('api/province.json', json_encode($province));

// foreach ($city as $k => $v) {
// 	$v = array_values($v);
// 	usort($v, 'sortByName');
// 	file_put_contents('api/city/'.$k.'.json', json_encode($v));
// }

// foreach ($district as $k => $v) {
// 	$v = array_values($v);
// 	usort($v, 'sortByName');
// 	file_put_contents('api/district/'.$k.'.json', json_encode($v));
// }

// foreach ($village as $k => $v) {
// 	$v = array_values($v);
// 	usort($v, 'sortByName');
// 	file_put_contents('api/village/'.$k.'.json', json_encode($v));
// }

echo 'SELESAI';