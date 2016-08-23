<?php

/**
 * MDNS : My DNS library
 * Implementation based on http://www.faqs.org/rfcs/rfc1035.html
 *
 * @author Alexis Ducastel <alexis@ducasteL.net>
 * @copyright All rights reserved
 */

abstract class MdnsRecord {
	public $raw;
	public $length;
	public $name;
	public $type;
	public $typeRaw;
	public $class;
	public $classRaw;

	//==========================================================================
	// Constructor in private => force factories
	//==========================================================================
	protected function __construct() {}

	//==========================================================================
	// Factories
	//==========================================================================
	public static function createFromRR($dg, $pos) {
		$record = new static;
		$record->parseRR($dg, $pos);
		return $record;
	}
	public static function create($name, $type, $class) {
		$record = new static;
		$record->setAll($name, $type, $class);
		return $record;
	}
	public static function createFromArray($array) {
		$record = new static;
		$record->setFromArray($array);
		return $record;
	}

	//==========================================================================
	// RR Parser
	//==========================================================================
	protected function parseRR($dg, $pos) {
		$rr = substr($dg, $pos);
		$rrName = "";
		$dgPos = 0;

		$inRR = true;
		$namePos = $pos;
		// Extraction du rrName (nom de domaine)
		while (($partLength = ord($dg[$namePos])) > 0) {
			if ($partLength >= 192) {
				$namePos = 256 * ($partLength - 192) + ord($dg[$namePos + 1]);
				$inRR = false;
				$dgPos++;
			} else {
				$rrName .= substr($dg, $namePos + 1, $partLength) . '.';
				if ($inRR) {
					$dgPos += $partLength + 1;
				}

				$namePos += $partLength + 1;
			}
		}
		$dgPos++;

		$rrType = intval(ord($rr[$dgPos]) * 256 + ord($rr[$dgPos + 1]));
		$rrTypeS = MdnsCommon::getTypeFromId($rrType);
		$dgPos += 2;

		$rrClass = intval(ord($rr[$dgPos]) * 256 + ord($rr[$dgPos + 1]));
		$rrClassS = MdnsCommon::getClassFromId($rrClass);
		$dgPos += 2;

		$this->raw = substr($rr, 0, $dgPos);
		$this->length = $dgPos;
		$this->name = $rrName;
		$this->type = $rrTypeS;
		$this->typeRaw = $rrType;
		$this->class = $rrClassS;
		$this->classRaw = $rrClass;
	}

	//==========================================================================
	// Setters
	//==========================================================================
	public function setAll($name, $type, $class) {
		$this->name = $name;
		$this->type = $type;
		$this->typeRaw = MdnsCommon::getIdFromType($type);
		$this->class = $class;
		$this->classRaw = MdnsCommon::getIdFromClass($class);
	}
	public function setFromArray($array) {
		if (isset($array['name'])) {
			$this->name = $array['name'];
		}

		if (isset($array['type'])) {
			$this->type = $array['type'];
			$this->typeRaw = MdnsCommon::getIdFromType($array['type']);
		}
		if (isset($array['class'])) {
			$this->class = $array['class'];
			$this->classRaw = MdnsCommon::getIdFromClass($array['class']);
		}
	}
	public function setFromRR($dg, $pos) {
		$this->parseRR($dg, $pos);
	}

	//==========================================================================
	// Retter (RR maker)
	//==========================================================================
	public function getRR() {
		$rr = '';
		// Encoding name (each part separated with dot, length.part)
		//foreach(explode(".",$this->name) as $p) $rr.=chr(strlen($p)).$p;
		$rr .= self::hn2b($this->name);

		// Type
		$rr .= chr($this->typeRaw / 256) . chr($this->typeRaw % 256);

		// Class
		$rr .= chr($this->classRaw / 256) . chr($this->classRaw % 256);

		return $rr;
	}

	protected static function readHostname($dg, $pos) {
		$name = "";
		$dgPos = 0;

		$isPointer = false;
		$namePos = $pos;
		// Extraction du rrName (nom de domaine)
		while (($partLength = ord($dg[$namePos])) > 0) {
			if ($partLength >= 192) {
				$namePos = 256 * ($partLength - 192) + ord($dg[$namePos + 1]);
				$isPointer = true;
				$dgPos++;
			} else {
				$name .= substr($dg, $namePos + 1, $partLength) . '.';
				if (!$isPointer) {
					$dgPos += $partLength + 1;
				}

				$namePos += $partLength + 1;
			}
		}

		return array(
			'name' => $name,
			'length' => $dgPos,
		);

	}
	protected static function b2hn($dg, $pos) {
		$r = self::readHostname($dg, $pos);
		return $r['name'];
		//protected static function b2hn($d){
		$p = 0;
		$hostname = "";
		while (($l = ord($d[$p])) > 0) {
			$hostname .= substr($d, $p + 1, $l) . '.';
			$p += $l + 1;
		}
		return $hostname;
	}
	protected static function hn2b($hostname) {
		$data = "";
		if ($hostname[strlen($hostname) - 1] == '.') {
			$hostname = substr($hostname, 0, strlen($hostname) - 1);
		}

		foreach (explode(".", $hostname) as $p) {
			$data .= chr(strlen($p)) . $p;
		}

		return $data . chr(0);
	}
	protected static function b2ip($d) {
		$ip = '';
		for ($i = 0; $i < 4; $i++) {
			$ip .= ($i == 0 ? '' : '.') . ord($d[$i]);
		}

		return $ip;
	}
	protected static function ip2b($ip) {
		$data = "";
		foreach (explode(".", $ip) as $pip) {
			$data .= chr($pip);
		}

		return $data;
	}

}
class MdnsRecordQuery extends MdnsRecord {}
class MdnsRecordAnswer extends MdnsRecord {
	public $ttl;
	public $data;

	//==========================================================================
	// Factories
	//==========================================================================
	public static function createAnswer($name, $type, $class, $ttl, $data) {

		$record = new static;
		$record->setAll($name, $type, $class);
		$record->ttl = $ttl;
		$record->data = $data;
		return $record;
	}
	public static function createInA($name, $ip, $ttl = 3600) {
		return self::createAnswer($name, 'A', 'IN', $ttl, $ip);
	}
	public static function createInNs($name, $ns, $ttl = 3600) {
		return self::createAnswer($name, 'NS', 'IN', $ttl, $ns);
	}
	public static function createInCname($name, $alias, $ttl = 3600) {
		return self::createAnswer($name, 'CNAME', 'IN', $ttl, $alias);
	}

	//==========================================================================
	// RR Parser
	//==========================================================================
	protected function parseRR($dg, $pos) {
		parent::parseRR($dg, $pos);
		$rr = substr($dg, $pos);

		$dgPos = $this->length;

		$rrTtl = ord($rr[$dgPos]) * 16777216 + ord($rr[$dgPos + 1]) * 65536 + ord($rr[$dgPos + 2]) * 256 + ord($rr[$dgPos + 3]);
		$dgPos += 4;

		$rrLen = ord($rr[$dgPos]) * 256 + ord($rr[$dgPos + 1]);
		$dgPos += 2;

		$dataPos = $pos + $dgPos;
		$rrData = substr($rr, $dgPos, $rrLen);
		$dgPos += $rrLen;

		$this->length = $dgPos;
		$this->ttl = $rrTtl;

		// Data
		switch ($this->type) {
			case 'A':
				$this->data = self::b2ip($rrData);
				break;
			default:
				$this->data = self::b2hn($dg, $dataPos);
				break;

		}
	}

	//==========================================================================
	// Setters
	//==========================================================================
	public function setFromArray($array) {
		parent::setFromArray($array);
		if (isset($array['ttl'])) {
			$this->ttl = $array['ttl'];
		}

		if (isset($array['data'])) {
			$this->ttl = $array['data'];
		}

	}

	//==========================================================================
	// Retter (RR maker)
	//==========================================================================
	public function getRR() {
		$rr = parent::getRR();

		// TTL
		$rr .= chr($this->ttl / 16777216) . chr($this->ttl / 65536) . chr($this->ttl / 256) . chr($this->ttl % 256);

		// Data
		switch ($this->type) {
			case 'A':
				$data = self::ip2b($this->data);
				break;
			default:
				$data = self::hn2b($this->data);
				break;

		}

		$length = strlen($data);
		$rr .= chr($length / 256) . chr($length % 256);
		$rr .= $data;

		return $rr;
	}
}
class MdnsRecordAnswerAuthority extends MdnsRecordAnswer {}
class MdnsRecordAnswerAdditionnal extends MdnsRecordAnswer {}

class MdnsRequest {
	public $id;
	public $qr = 0;
	public $opCode = 0;
	public $aa = 0;
	public $tc = 0;
	public $rd = 1;
	public $ra = 1;
	public $z = 0;
	public $rcode = 0;
	public $queries = array();
	public $answers = array();
	public $answersAuthority = array();
	public $answersAdditionnal = array();

	public function __construct() {
		$this->id = rand(0, 32767); // 16bit random
	}

	public static function createFromDatagram($datagram) {
		$dg = new static;
		$dg->parseDatagram($datagram);
		return $dg;
	}

	public function createAnswer() {
		$answer = new static;
		foreach ($this as $a => $v) {
			$answer->$a = $this->$a;
		}
		$answer->answers = array();
		$answer->answersAuthority = array();
		$answer->answersAdditionnal = array();
		$answer->qr = 1;
		return $answer;
	}

	public function setErrorNone() {$this->rcode = 0;}
	public function setErrorInternal() {$this->rcode = 2;}
	public function setErrorUnknown() {$this->rcode = 3;}
	public function setErrorNotImplemented() {$this->rcode = 4;}
	public function setErrorRejected() {$this->rcode = 5;}

	/**
	 * Parsing dns datagram
	 * @param string $dg Dns datagram
	 */
	protected function parseDatagram($dg) {
		$datagram = array();

		// datagram id
		$dgId = ord($dg[0]) * 256 + ord($dg[1]);

		$dgFlag = substr($dg, 2, 2);
		$dgFlag_b = str_pad(decbin($dgFlag[0]), 8, "0", STR_PAD_LEFT)
		. str_pad(decbin($dgFlag[1]), 8, "0", STR_PAD_LEFT);

		// Qr : Ce champ permet d’indiquer s’il s’agit d’une requête (0) ou d’une réponse (1).
		$dgQr = intval($dgFlag_b[0]); // 0=query, 1=answer

		// Opcode : Ce champ perme de spécifier le type de requête (4 bits)
		// 0 : Requête standard (Query), 1 : Requête inverse (IQuery),
		// 2 : Statut du serveur (Status),  3-15 : Réservé pour utilisation future
		$dgOpCode = bindec(substr($dgFlag_b, 1, 4));

		// Aa : Ce flag signifie « Authoritative Answer »
		$dgAa = $dgFlag_b[5];

		// TC : Ce champ indique que ce message a été tronqué.
		// Ce flag est positionné à 0 lorsque le protocole TCP est utilisé mais lorsqu'UDP est utilisé
		// ce flag peut être positionné à 1 si la réponse excède  512 octets
		$dgTc = $dgFlag_b[6];

		// Rd : Ce flag permet de demander la récursivité en le mettant à 1
		$dgRd = $dgFlag_b[7];

		// Ra : Ce flag indique que la récursivité est autorisée
		$dgRa = $dgFlag_b[8];

		// Z : Celui-ci est réservé à utilisation future. Il doit être positionné à 0
		$dgZ = bindec(substr($dgFlag_b, 9, 3));

		// Rcode : Ce champ indique le type de réponse (4 bits) :
		// 0: Pas d.erreur, 1: Erreur de format dans la requête,
		// 2: Problème sur serveur, 3: Le nom n.existe pas, 4: Non implémenté, 5: Refus,
		// 6-15: Réservés.
		$dgRcode = bindec(substr($dgFlag_b, 12, 4));

		// Nombre de requetes
		$nbRq = ord($dg[4]) * 256 + ord($dg[5]);

		// Nombre de reponses
		$nbA = ord($dg[6]) * 256 + ord($dg[7]);

		// Nomre de reponses d'autorité
		$nbAa = ord($dg[8]) * 256 + ord($dg[9]);

		// Nomre de reponses additionnelles
		$nbAo = ord($dg[10]) * 256 + ord($dg[11]);

		$this->id = $dgId;
		$this->qr = $dgQr;
		$this->opCode = $dgOpCode;
		$this->aa = $dgAa;
		$this->tc = $dgTc;
		$this->rd = $dgRd;
		$this->ra = $dgRa;
		$this->z = $dgZ;
		$this->rcode = $dgRcode;

		$dgPos = 12;

		// Parsing des RR query
		$this->queries = array();
		for ($i = 0; $i < $nbRq; $i++) {
			//$query=new MdnsRecordQuery(substr($dg,$dgPos));
			$query = MdnsRecordQuery::createFromRR($dg, $dgPos);
			$this->queries[] = $query;
			$dgPos += $query->length;
		}

		// Parsing des RR Answers
		$this->answers = array();
		for ($i = 0; $i < $nbA; $i++) {
			$rr = MdnsRecordAnswer::createFromRR($dg, $dgPos);
			$this->answers[] = $rr;
			$dgPos += $rr->length;
		}

		// Parsing des RR Answers Authority
		$this->answersAuthority = array();
		for ($i = 0; $i < $nbAa; $i++) {
			$rr = MdnsRecordAnswerAuthority::createFromRR($dg, $dgPos);
			$this->answersAuthority[] = $rr;
			$dgPos += $rr->length;
		}

		// Parsing des RR Answers Authority
		$this->answersAdditionnal = array();
		for ($i = 0; $i < $nbAo; $i++) {
			$rr = MdnsRecordAnswerAdditionnal::createFromRR($dg, $dgPos);
			$this->answersAdditionnal[] = $rr;
			$dgPos += $rr->length;
		}
	}

	public function getDatagram() {
		$datagram = '';

		// Datagram Header
		// DG id
		$datagram .= chr($this->id / 256) . chr($this->id % 256);
		if (strlen($datagram) != 2) {
			throw new Exception('invalid datagram id');
		}

		// Flags part 1
		$this->opCode = str_pad(decbin($this->opCode), 4, "0", STR_PAD_LEFT);
		$datagram .= chr(bindec($this->qr . $this->opCode . $this->aa . $this->tc . $this->rd));
		// bit position:      0         1234          5         6         7

		// Flags part 2
		$this->rcode = str_pad(decbin($this->rcode), 4, "0", STR_PAD_LEFT);
		$datagram .= chr(bindec($this->ra . $this->z . $this->rcode));
		// bit position:      0         123      4567

		// Number of queries
		$nbRq = count($this->queries);
		$datagram .= chr($nbRq / 256) . chr($nbRq % 256);

		// Number of answers
		$nbA = count($this->answers);
		$datagram .= chr($nbA / 256) . chr($nbA % 256);

		// Number of authoritative answers
		$nbAa = count($this->answersAuthority);
		$datagram .= chr($nbAa / 256) . chr($nbAa % 256);

		// Number of additionnal answers
		$nbAo = count($this->answersAdditionnal);
		$datagram .= chr($nbAo / 256) . chr($nbAo % 256);

		// Adding RR query
		foreach ($this->queries as $rr) {
			$datagram .= $rr->getRR();
		}

		// Adding RR Answers
		foreach ($this->answers as $rr) {
			$datagram .= $rr->getRR();
		}

		// Adding RR Answers Authority
		foreach ($this->answersAuthority as $rr) {
			$datagram .= $rr->getRR();
		}

		// Adding RR Answers Additionnal
		foreach ($this->answersAdditionnal as $rr) {
			$datagram .= $rr->getRR();
		}

		return $datagram;
	}
}

class MdnsErrorUnknown extends Exception {}
class MdnsErrorNotImplemented extends Exception {}
class MdnsErrorRejected extends Exception {}

abstract class MdnsCommon {
	private static $classes = array(
		'IN' => 1,
		'CS' => 2,
		'CH' => 3,
		'HS' => 4,
	);
	private static $types = array(
		"A" => 1,
		"NS" => 2,
		"CNAME" => 5,
		"SOA" => 6,
		"WKS" => 11,
		"PTR" => 12,
		"HINFO" => 13,
		"MX" => 15,
		"TXT" => 16,
		"RP" => 17,
		"SIG" => 24,
		"KEY" => 25,
		"LOC" => 29,
		"NXT" => 30,
		"AAAA" => 28,
		"CERT" => 37,
		"A6" => 38,
		"AXFR" => 252,
		"IXFR" => 251,
		"ANY" => 255,
	);

	public static function getTypeFromId($id) {return array_search('' . $id, self::$types);}
	public static function getIdFromType($type) {return self::$types[$type];}

	public static function getClassFromId($id) {return array_search('' . $id, self::$classes);}
	public static function getIdFromClass($class) {return self::$classes[$class];}

	public static function str2hex($str) {
		$l = strlen($str);
		$r = '';
		for ($i = 0; $i < $l; $i++) {
			$r .= str_pad(dechex(ord($str[$i])), 2, "0", STR_PAD_LEFT);
		}

		return $r;
	}
}
class MdnsServer {
	private $socket;
	private $debug = false;
	private $callback = null;
	private $ipBinding = null;

	/**
	 * Set IP binding
	 * N.B : Mandatory for Linux, but useless for Windows
	 * @param mixed $ip
	 */
	public function setIpBinding($ip) {
		$this->ipBinding = $ip;
	}

	/**
	 * Set the DNS query callback
	 * callback must accept one array as argument
	 * @param mixed $callback string for function, array($object,"method") for methods
	 */
	public function setCallback($callback) {
		$this->callback = $callback;
	}

	public function handle() {
		set_time_limit(0);

		// Creating Socket
		$this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		if ($this->socket < 0) {
			throw new Exception("Cannot create socket");
		}

		// Binding port and IP
		if (!socket_bind($this->socket, $this->ipBinding, "53")) {
			throw new Exception("Cannot bind port 53");
		}

		// Looping forever
		while (true) {
			$len = socket_recvfrom($this->socket, $buf, 1024 * 4, 0, $ip, $port);
			if ($len > 0) {
				$this->handleQuery($buf, $ip, $port);
			}

		}
	}

	private function handleQuery($buffer, $clientIp, $clientPort) {
		$request = MdnsRequest::createFromDatagram($buffer);
		$response = $request->createAnswer();

		foreach ($request->queries as $query) {

			try {
				$answers = call_user_func($this->callback, $query->name, $query->type, $clientIp);
				if (!is_array($answers)) {
					$answers = array($answers);
				}

				foreach ($answers as $answer) {
					if ($answer instanceof MdnsRecordAnswerAdditionnal) {
						$response->answersAdditionnal[] = $answer;
					} else if ($answer instanceof MdnsRecordAnswerAuthority) {
						$response->answersAuthority[] = $answer;
					} else if ($answer instanceof MdnsRecordAnswer) {
						$response->answers[] = $answer;
					} else {
						$response->answers[] = MdnsRecordAnswer::createAnswer($query->name, $query->type, $query->class, 3600, $answer);
					}

				}
			} catch (MdnsErrorRejected $e) {
				$response->setErrorRejected();
				break;} catch (MdnsErrorNotImplemented $e) {
				$response->setErrorNotImplemented();
				break;} catch (Exception $e) {
				$response->setErrorInternal();
				break;}

		}

		$buffer = $response->getDatagram();
		socket_sendto($this->socket, $buffer, strlen($buffer), 0, $clientIp, $clientPort)
		or die("Error in socket");
	}
}

class MdnsClient {
	private $dns;
	public function __construct($server) {
		$this->dns = $server;
	}
	public function ask($name, $type = 'A', $class = 'IN') {
		$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		$result = socket_connect($sock, $this->dns, 53);

		$request = new MdnsRequest();
		$request->queries[] = MdnsRecordQuery::create($name, $type, $class);

		$msg = $request->getDatagram();
		$len = strlen($msg);
		socket_write($sock, $msg, strlen($msg));
		//socket_sendto($sock, $msg, $len, 0, '192.168.1.7', 11104);
		$buf = socket_read($sock, 4096);
		//socket_recvfrom($sock, $buf, 12, 0, $from, $port);
		socket_close($sock);

		$answer = MdnsRequest::createFromDatagram($buf);

		return array_merge(
			$answer->answers,
			$answer->answersAuthority,
			$answer->answersAdditionnal
		);
	}
}

?>
