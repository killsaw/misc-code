<?php

class QifTypes
{
    const HEADER    = '!Type:CCard';
    const DELIMITER = '^';
    const DATE      = 'D';
    const AMOUNT    = 'T';
    const PAYEE     = 'P';
    const NUMBER    = 'N';
    const ADDRESS   = 'A';
}

class QifReader
{
	public static function parseQifFile($file)
	{
		$idx = 0;
		$trans = array();
		$lines = file($file);
		
		foreach($lines as $l) {
			$l = trim($l);
			
			// Skip empties.
			if (empty($l) {
				continue;
			}
		
			$code = $l[0];
			$data = substr($l, 1);
		
			switch($code) {
				case QifTypes::HEADER:
					break;
				case QifTypes::DELIMITER:
					$trns[++$idx] = array();
					break;
				case QifTypes::DATE:
					$trans[$idx]['date'] = $data;
					break;
				case QifTypes::AMOUNT:
					$trans[$idx]['amount'] = floatval($data);
					break;
				case QifTypes::PAYEE:
					$trans[$idx]['payee'] = $data;
					break;
				case QifTypes::NUMBER:
					$trans[$idx]['number'] = $data;
					break;
				case QifTypes::ADDRESS:
					$trans[$idx]['address'] = $data;
					break;
			}
		}
		
		return $trans;
	}
}
