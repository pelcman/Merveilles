<?php

require_once __DIR__ . '/../../../../../src/bootstrap.php';

$auth->requireAdmin();

$data = $_REQUEST['data'] ?? '';

$val = array(
	'0' => true, '1' => true, '2' => true, '3' => true, '4' => true,
	'5' => true, '6' => true, '7' => true, '8' => true, '9' => true
);

$length = strlen($data);
$hex = '';
$floor = '';
$map = array();
$mx = 0;
$my = 0;

for ($x = 0; $x < $length; $x++) {
	if (isset($val[$data[$x]])) {
		$hex .= $data[$x];
	} elseif (strlen($hex) > 0) {
		if (!$floor) {
			$floor = (int) $hex;
		} else {
			if ($mx > 63) {
				$mx = 0;
				$my++;
			}
			$map[$mx][$my] = (int) $hex;
			$mx++;
		}
		$hex = '';
	}
}

$levelsDir = __DIR__ . '/../../../levels';
file_put_contents($levelsDir . '/' . $floor . '-1.dat', serialize($map));

$stmt = $db->prepare('DELETE FROM monsters WHERE floor = ?');
$stmt->execute([$floor]);

header('Location: /editor?floor=' . $floor, true, 302);
exit;
