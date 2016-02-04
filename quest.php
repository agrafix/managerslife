<?php
include "include.php";

exit;

function test_parser($title, $syntax, $cards) {
	echo '<h1>PokerParser: '.$title.'</h1>';

	echo '<h2>Syntax: '.$syntax.'</h2>';

	$p = new PokerParser($syntax);

	echo '<h2>Hand:</h2>';
	$deck = array();
	foreach (explode(",", $cards) as $dm) {
		$c = "spades";

		switch($dm{0}) {
			case "s":
				$c = "spades";
				break;

			case "d":
				$c = "diamons";
				break;

			case "c":
				$c = "clubs";
				break;

			case "h":
				$c = "hearts";
				break;
		}

		$cv = $dm{1};
		if($cv == 1) {
			$cv = 10;
		}

		$deck[] = array('color' => $c, 'card' => $cv);
	}

	shuffle($deck);

	foreach ($deck as $d) {
		echo $d['card']." of ".$d['color']." <br />";
	}

	echo '<h2>Check</h2>';
	var_dump($p->check($deck));
	echo ' <i>(Highest-Card-Value: '.$p->getHighestCardValue().')</i>';
}

// pair
test_parser("Highcard", '?', "s3,sj,d3,c4,hq,h7,hk");

// pair
test_parser("Pair", '1{2}', "s3,sj,d3,c4,hq,h7,hk");
test_parser("No Pair", '1{2}', "s2,sj,d3,c4,hq,h7,hk");

// two pair
test_parser("Two Pair", '1{2}2{2}', "s3,sj,d3,c4,hj,h7,hk");
test_parser("No Two Pair", '1{2}2{2}', "s2,sj,d3,c4,hj,h7,hk");

// three of a kind
test_parser("Three of a kind", '1{3}', "s3,s3,d3,c4,hj,h7,hk");
test_parser("No Three of a kind", '1{3}', "s2,s3,d3,c4,hj,h7,hk");

// straight
test_parser("Straight", '?>?>?>?>?', "s3,s8,d7,c9,hj,h10,h6");
test_parser("No Straight", '?>?>?>?>?', "s3,s8,d10,c9,hj,h10,h6");

// flush
test_parser("Flush", 'a{5}', "s3,sj,s3,sj,sj,h7,hk");
test_parser("No Flush", 'a{5}', "c3,dj,s3,sj,sj,h7,hk");

// full house
test_parser("Full House", '1{3}2{2}', "s3,sj,d3,cj,hj,h7,hk");
test_parser("No Full House", '1{3}2{2}', "s5,sj,d3,cj,hj,h7,hk");

// four of a kind
test_parser("For of a Kind", '1{4}', "sj,hj,d3,c4,cj,h7,dj");
test_parser("No For of a Kind", '1{4}', "sj,hk,d3,c4,cj,h7,dj");

// straight flush
test_parser("Straight-Flush", 'a>a>a>a>a', "sk,sq,sj,s10,s9,h7,hq");
test_parser("No Straight-Flush", 'a>a>a>a>a', "sk,ca,s2,s3,s4,h7,hq");

// royal flush
test_parser("Royal-Flush", '[a>a>a>a>a', "sa,sq,sj,sk,s10,h7,hq");
test_parser("No Royal-Flush", '[a>a>a>a>a', "sk,sq,sj,s10,s9,h7,hq");

// dont run this file
exit;

// benchmark config
$configs = array('resources','teleportPoints','premium_price','company_quests','products');

$total = 0;
$count = 0;

foreach ($configs as $cfg) {
	$start = microtime(true);
	Config::getConfig($cfg);
	$stop = microtime(true) - $start;

	$count++;
	$total += $stop;

	echo "getConfig($cfg) took $stop seconds <br />";
}

echo "<h3>AVG: ".($total / $count)."</h3>";

echo "<hr />";

$total = 0;
$count = 0;

foreach ($configs as $cfg) {
	$start = microtime(true);
	Config::getConfig($cfg);
	$stop = microtime(true) - $start;

	$count++;
	$total += $stop;

	echo "getConfig($cfg) [cached] took $stop seconds <br />";
}

echo "<h3>AVG: ".($total / $count)."</h3>";

echo "<hr />";

// dont run this script
exit;

function prod_time_level($level) {
	$m = pow(1.4, $level);

	return ($m > 24*20 ? 24*20 : $m);
}

function filter($a) {
	if (!isset($a["machine"])) {
		return false;
	}

	return true;
}

function cmp($a, $b) {
	if ($a["machine"]["cost"] > $b["machine"]["cost"]) {
		return 1;
	} else if ($a["machine"]["cost"] < $b["machine"]["cost"]) {
		return -1;
	}

	return 0;
}

function fix_needs(&$array, $key) {
	if (is_array($array["needs"][0])) {
		$array["rType"] = 'product';
		return;
	}

	$array["rType"] = 'resource';

	$needs = array();

	foreach ($array["needs"] as $n) {
		$needs[] = array("ress" => $n, "amount" => 1);
	}

	$array["needs"] = $needs;
}

$p_Raw = array_merge(Config::getConfig('resources'), Config::getConfig('products'));

$products = array_filter(array_merge(Config::getConfig('resources'), Config::getConfig('products')), 'filter');

uasort($products, 'cmp');
array_walk($products, 'fix_needs');

$level = 0;
$counter = 0;

$questDef = array();

$aWalk = $products;

foreach ($products as $name => $p) {
	$quest = array();

	// id
	$id = $name.$level;

	$quest["title"] = "";
	$quest["text"] = "";

	// calculate total costs
	$totalCosts = $p['machine']['cost'];
	$proAmount = ceil($p['machine']['prod_per_hour'] * prod_time_level($level));
	$totalCosts += ($proAmount/$p['machine']['prod_per_hour']) * $p['machine']['running_cost'];

	foreach ($p["needs"] as $n) {
		$totalCosts += ($n['amount'] * $p_Raw[$n['ress']]['base_price']) * $proAmount;
	}

	$totalCosts = round($totalCosts);

	$quest["needs"] = array(
		array('type' => $p['rType'], 'name' => $name, 'amount' => $proAmount)
	);

	$quest["time"] = ceil((prod_time_level($level) / 24) + 1);

	$quest["investmentCosts"] = $totalCosts;

	$quest["level"] = $level;

	// get next costs
	$cx = next($aWalk);
	if ($cx !== false) {
		$c = $cx['machine']['cost'];
		foreach ($cx['needs'] as $n) {
			$c += ($n['amount'] * $p_Raw[$n['ress']]['base_price']) * 2500;
		}
		prev($aWalk);
	}
	else {
		$c = $totalCosts * 0.5;
	}

	$cash = $totalCosts + $c;

	$quest["profit"] = $cash - $totalCosts;

	$quest["oncomplete"] = array(
		"xp" => Model_User::XPuntilNextLevel($level),
		"cash" => $cash
	);

	// save
	$questDef[$id] = $quest;

	// level up?
	/*$counter++;
	if ($counter%2 == 0) {
		$level++;
	}*/
	$level++;
	next($aWalk);
}

echo json_encode($questDef);
?>