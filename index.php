<?php

// ini_set('display_errors', 1); // Uncomment to display errors
// ini_set('display_startup_errors', 1); // Uncomment to display errors
// error_reporting(E_ALL); // Uncomment to display errors


// Function to validate domain
function isValidDomain($domain) {
	// Regex pattern to match a valid domain format
	$domainRegex = '/^(?!:\/\/)(?:[a-zA-Z0-9-]+\.)+(?:[a-zA-Z]{2,})(?:\/)?$/';
	return preg_match($domainRegex, $domain);
}

// Function to extract primary domain from a URL
function extractPrimaryDomain($url) {
	// Define regex pattern to extract primary domain
	$pattern = '/(?:[a-z0-9-]+\.)+([a-z]{2,})/i';
	
	// Perform regex match
	if (preg_match($pattern, $url, $matches)) {
		return $matches[0];
	} else {
		return ''; // Return empty string if no match found
	}
}


function getDNS($domain, $type) {
	$records = @dns_get_record($domain, $type);
	return $records ? $records : [];
}

function fetchIpApiInfo($ip) {
	$ipapi = @file_get_contents('http://ip-api.com/json/' . $ip . '?fields=countryCode,isp,org,asname');
	return $ipapi ? json_decode($ipapi, true) : null;
}

function checkForbiddenDomain($domain, $forbiddenDomains) {
	foreach ($forbiddenDomains as $forbiddenDomain) {
		if ($domain === $forbiddenDomain || strpos($domain, '.' . $forbiddenDomain) !== false) {
			$forbidden_reason = "Sorry, the domain you provided is not allowed.";
			break;
		}
	}

	return [
		'forbidden' => isset($forbidden_reason),
		'reason' => isset($forbidden_reason) ? $forbidden_reason : null
	];
}

function generateFlagEmoji($countryCode) {
	return mb_convert_encoding('&#' . (127397 + ord($countryCode[0])) . ';', 'UTF-8', 'HTML-ENTITIES') . mb_convert_encoding('&#' . (127397 + ord($countryCode[1])) . ';', 'UTF-8', 'HTML-ENTITIES');
}

function generateHTML($DNS, $ipKey, $mx = 0) {
	$r = '';
	foreach ($DNS as $v) {
		$r .= "<h4>";
		$i = fetchIpApiInfo($v[$ipKey]);
		$r .= generateFlagEmoji($i['countryCode']) . " " . $i['countryCode'] . " · ";
		if ($mx) $r .= "[" . $v['pri'] . "] ";
		$r .= $v[$ipKey] . " · <small>(<b>ISP</b> " . $i['isp'] . " <b>ORG</b> " . $i['org'] . " <b>AS</b> " . $i['asname'] . ")</small></h4>";
	}
	return $r;
}

function generateDNSHtml($records, $type, $addLineBreak=1) {
	$html = '';
	foreach ($records as $record) {
		$html .= "<h4>";

		// Check for specific keys and include them in the output based on the type
		foreach ($record as $key => $value) {
			if ($key !== 'host' && $key !== 'ttl' && $key !== 'cpu') {
				// Handle the Rname field separately
				if ($key === 'rname') {
					// Split the Rname into username and domain parts
					$rname_parts = explode('.', $value, 2);
					// Check if the Rname has two parts
					if (count($rname_parts) === 2) {
						// Concatenate the username and domain with '@'
						$rname = $rname_parts[0] . '@' . $rname_parts[1];
						$html .= ucfirst($key) . ": " . htmlspecialchars($rname, ENT_QUOTES, 'UTF-8') . " ";
					} else {
						// If unable to split, treat it as a regular string
						$html .= ucfirst($key) . ": " . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . " ";
					}
				} elseif (is_array($value)) {
					$html .= "<br/>" . ucfirst($key) . ": ";
					foreach ($value as $item) {
						$html .= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') . ", ";
					}
					$html = rtrim($html, ", ") . " ";
				} else {
					if ($key == 'class' && $value = 'IN') {
						// Handle the class if needed
					} else {
						$html .= ucfirst($key) . ": " . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . " ";
					}
				}
				
				if ($addLineBreak) {
					$html .= "<br/>";
				}
			}
		}

		$html .= "</h4>";
	}
	return $html;
}

$thisDomain = htmlspecialchars($_SERVER["HTTP_HOST"], ENT_QUOTES, "UTF-8");

// If domain is included in URL, prefill form with domain or if form is submitted display domain in it
$posted_domain = isset($_POST["domain"]) ? $_POST["domain"] : (isset($_GET["domain"]) ? $_GET["domain"] : '');

// Parse URL to extract host
$parsed_url = @parse_url($posted_domain);
$domain = isset($parsed_url["host"]) ? $parsed_url["host"] : $posted_domain;
//$domain = array_key_exists("host", $parsed_url) ? $parsed_url["host"] : $posted_domain;

// If domain still not found, handle it as needed
if ($domain) {
	$validDomain = isValidDomain($domain);
	$primaryDomain = extractPrimaryDomain($posted_domain);
	$validPrimary = isValidDomain($primaryDomain);
}

// Page URL : check if "?domain=" is in the URL to adapt http_referer content
$page_url_domain = strpos($_SERVER["REQUEST_URI"], "?domain=") !== false ? $_SERVER['HTTP_X_FORWARDED_PROTO'] . "://" . $thisDomain . $_SERVER["REQUEST_URI"] : $_SERVER['HTTP_X_FORWARDED_PROTO'] . "://" . $thisDomain . $_SERVER["REQUEST_URI"] . "?domain=" . $posted_domain;

$forbiddenDomains = [$_SERVER['HTTP_HOST'],];
$forbidden_domain = checkForbiddenDomain($domain, $forbiddenDomains);

// Force $formSubmitted to be false if the domain is forbidden
$formSubmitted = isset($_POST['submit']) && !$forbidden_domain['forbidden'];

//

// Get DNS records and TTLs
$dnsTypes = ["A" => DNS_A, "AAAA" => DNS_AAAA, "CNAME" => DNS_CNAME, "MX" => DNS_MX, "NS" => DNS_NS, "SOA" => DNS_SOA, "TXT" => DNS_TXT, "ANY" => DNS_ANY, "CAA" => DNS_CAA,
//These have not been tested, issues may occur but nothing should go wrong!
"HINFO" => DNS_HINFO, "PTR" => DNS_PTR, "SRV" => DNS_SRV, "NAPTR" => DNS_NAPTR, "A6" => DNS_A6, //"ALL" => DNS_ALL
];

$dnsRecords = [];
foreach ($dnsTypes as $key => $type) {
	$records = getDNS($domain, $type);
	$dnsRecords[$key] = $records;
}

$dns_a_html = generateHTML($dnsRecords['A'], 'ip');

$dns_aaaa_html = generateHTML($dnsRecords['AAAA'], 'ipv6');

$dns_ns_html = generateHTML($dnsRecords['NS'], 'target');

$dns_mx_html = generateHTML($dnsRecords['MX'], 'target', 1);

$dns_cname_html = generateHTML($dnsRecords['CNAME'], 'target');

$dns_soa_html = generateDNSHtml($dnsRecords['SOA'], DNS_SOA, 1);


$dns_txt_html = '';
foreach ($dnsRecords['TXT'] as $value) {
	$dns_txt_html .= "<h4>";
	$dns_txt_html .= wordwrap($value['txt'], 80, "<br/>\n", true);
	$dns_txt_html .= "</h4>";
}

$dns_caa_html = generateDNSHtml($dnsRecords['CAA'], DNS_CAA);
$dns_any_html = generateDNSHtml($dnsRecords['ANY'], DNS_ANY);
$dns_hinfo_html = generateDNSHtml($dnsRecords['HINFO'], DNS_HINFO);
$dns_ptr_html = generateDNSHtml($dnsRecords['PTR'], DNS_PTR);
$dns_srv_html = generateDNSHtml($dnsRecords['SRV'], DNS_SRV);
$dns_naptr_html = generateDNSHtml($dnsRecords['NAPTR'], DNS_NAPTR);
$dns_a6_html = generateDNSHtml($dnsRecords['A6'], DNS_A6);

?>


<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="description" content="Simple PHP script to lookup entries of A, AAAA, NS, CNAME, MX, SOA and TXT records">
		<meta name="author" content="HQWEB">
		<title>Simple DNS Lookup</title>
		<link rel="icon" href="assets/favicon.ico">
		<!-- https://www.iconperk.com/iconsets/magicons -->
		<link href="assets/css/bootstrap.min.css" rel="stylesheet">
		<!-- Bootstrap core CSS -->
		<link href="assets/css/style.css" rel="stylesheet">
		<style>
	.center-container {
	  display: flex;
	  justify-content: center;
	  align-items: center;
	}
		</style>
		<!-- Custom styles for this template -->
		<script>
			function updateAction() {
				var domainInput = document.getElementById('domain').value;
				// Encode the domain input to handle special characters properly
				var encodedDomain = encodeURIComponent(domainInput);
				// Append the domain parameter to the action URL
				document.forms[0].action = window.location.pathname + '?domain=' + encodedDomain;
			}
		</script> <?php include("../../pages/analytics.include.php");?>
	</head>
	<body>
		<div class="container">
			<div class="header clearfix">
				<nav>
					<ul class="nav nav-pills pull-right">
						<!--<li role="presentation" ><a href="/">Outils</a></li>-->
						<li role="presentation" class="active">
							<a href="/dns-lookup">DNS Lookup</a>
						</li>
					</ul>
				</nav>
				<h3 class="text-muted">Simple DNS Lookup</h3>
			</div>
			<div class="jumbotron">
				<form action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" method="post" onsubmit="updateAction()">
					<div class="form-group">
						<input type="search" class="form-control input-lg text-center" name="domain" id="domain" 
						placeholder="https://www.domain.com/page.html or domain.com" value="<?php echo $posted_domain; ?>" required>
						<button type="submit" name="submit" class="btn btn-primary btn-lg">Lookup</button>
					</div>
				</form>
			</div> <?php if($formSubmitted) { ?>
			<!-- IF FORM SUBMITTED -->
			<div class="row marketing">
				<h4>Direct link : <a href="<?php echo $page_url_domain; ?>"> <?php echo $page_url_domain; ?> </a>
				</h4>
				<table class="table table-striped table-bordered table-responsive">
					<thead class="bg-primary">
						<tr>
							<th class="text-center">Records</th>
							<th class="text-center">TTL</th>
							<th>Entries for <?php echo $domain; ?> </th>
						</tr>
					</thead>
					<!-- A RECORD -->
					<tr>
						<td class="vert-align text-center">
							<h4>
								<span class="label label-primary">A</span>
							</h4>
						</td> <?php if(empty($dnsRecords['A']) != null){ ?>
						<!-- IF NO A RECORD -->
						<td class="vert-align text-center">NA</td>
						<td class="warning">
							<h4>No record</h4>
						</td> <?php } else { ?>
						<!-- ELSE A RECORD -->
						<td class="vert-align text-center"> <?php echo $dnsRecords['A'][0]["ttl"]?> </td>
						<td class="success"> <?php echo $dns_a_html;?> </td> <?php } ?>
						<!-- ENDIF A RECORD -->
					</tr>
					<!-- A RECORD -->
					
					<!-- AAAA RECORD -->
					<tr>
						<td class="vert-align text-center">
							<h4>
								<span class="label label-info">AAAA</span>
							</h4>
						</td> <?php if(empty($dnsRecords['AAAA']) != null){ ?>
						<!-- IF NO AAAA RECORD -->
						<td class="vert-align text-center">NA</td>
						<td class="warning">
							<h4>No record</h4>
						</td> <?php } else { ?>
						<!-- ELSE AAAA RECORD -->
						<td class="vert-align text-center"> <?php echo $dnsRecords['AAAA'][0]["ttl"]?> </td>
						<td class="success"> <?php echo $dns_aaaa_html;
							?> </td> <?php } ?>
						<!-- ENDIF AAAA NO RECORD -->
					</tr>
					<!-- AAAA RECORD -->
					
					<!-- NS RECORD -->
					<tr>
						<td class="vert-align text-center">
							<h4>
								<span class="label label-success">NS</span>
							</h4>
						</td> <?php if(empty($dnsRecords['NS']) != null){ ?>
						<!-- IF NO NS RECORD -->
						<td class="vert-align text-center">NA</td>
						<td class="warning">
							<h4>No record</h4>
						</td> <?php } else { ?>
						<!-- ELSE NS RECORD -->
						<td class="vert-align text-center"> <?php echo $dnsRecords['NS'][0]["ttl"]?> </td>
						<td class="success"> <?php echo $dns_ns_html;
							?> </td> <?php } ?>
						<!-- ENDIF NS RECORD -->
					</tr>
					<!-- NS RECORD -->
					
					<!-- MX RECORD -->
					<tr>
						<td class="vert-align text-center">
							<h4>
								<span class="label label-danger">MX</span>
							</h4>
						</td> <?php if(empty($dnsRecords['MX']) != null){ ?>
						<!-- IF NO MX RECORD -->
						<td class="vert-align text-center">NA</td>
						<td class="warning">
							<h4>No record</h4>
						</td> <?php } else { ?>
						<!-- ELSE MX RECORD -->
						<td class="vert-align text-center"> <?php echo $dnsRecords['MX'][0]["ttl"]?> </td>
						<td class="success"> <?php echo $dns_mx_html;
							?> </td> <?php } ?>
						<!-- ENDIF MX RECORD -->
					</tr>
					<!-- MX RECORD -->
					
					<!-- CNAME RECORD -->
					<tr>
						<td class="vert-align text-center">
							<h4>
								<span class="label label-default">CNAME</span>
							</h4>
						</td> <?php if(empty($dnsRecords['CNAME']) != null){ ?>
						<!-- IF NO CNAME RECORD -->
						<td class="vert-align text-center">NA</td>
						<td class="warning">
							<h4>No record</h4>
						</td> <?php } else { ?>
						<!-- ELSE CNAME RECORD -->
						<td class="vert-align text-center"> <?php echo $dnsRecords['CNAME'][0]["ttl"]?> </td>
						<td class="success"> <?php 
							
							echo $dns_cname_html;
							?> </td> <?php } ?>
						<!-- ENDIF CNAME RECORD -->
					</tr>
					<!-- CNAME RECORD -->
					
					<!-- SOA RECORD -->
					<tr>
						<td class="vert-align text-center">
							<h4>
								<span class="label label-warning">SOA</span>
							</h4>
						</td> <?php if(empty($dnsRecords['SOA']) != null){ ?>
						<!-- IF NO RECORD -->
						<td class="vert-align text-center">NA</td>
						<td class="warning">
							<h4>No record</h4>
						</td> <?php } else { ?>
						<!-- ELSE SOA RECORD -->
						<td class="vert-align text-center"> <?php echo $dnsRecords['SOA'][0]["ttl"]?> </td>
						<td class="success"> <?php echo $dns_soa_html;?> </td> <?php } ?>
						<!-- ENDIF SOA RECORD -->
					</tr>
					<!-- SOA RECORD -->
					
					<!-- TXT RECORD -->
					<tr>
						<td class="vert-align text-center">
							<h4>
								<span class="label label-default">TXT</span>
							</h4>
						</td> <?php if(empty($dnsRecords['TXT']) != null){ ?>
						<!-- IF NO TXT RECORD -->
						<td class="vert-align text-center">NA</td>
						<td class="warning">
							<h4>No record</h4>
						</td> <?php } else { ?>
						<!-- ELSE TXT RECORD -->
						<td class="vert-align text-center"> <?php echo($dnsRecords['TXT'][0]['ttl']); ?> </td>
						<td class="success"> <?php echo $dns_txt_html;
							?> </td> <?php } ?>
						<!-- ENDIF TXT RECORD -->
					</tr>
					<!-- TXT RECORD -->
					
					<!-- CAA RECORD -->
					<tr>
						<td class="vert-align text-center">
							<h4>
								<span class="label label-default">CAA</span>
							</h4>
						</td> <?php if(empty($dnsRecords['CAA']) != null){ ?>
						<!-- IF NO CAA RECORD -->
						<td class="vert-align text-center">NA</td>
						<td class="warning">
							<h4>No record</h4>
						</td> <?php } else { ?>
						<!-- ELSE CAA RECORD -->
						<td class="vert-align text-center"> <?php echo($dnsRecords['CAA'][0]['ttl']); ?> </td>
						<td class="success"> <?php echo $dns_caa_html; ?> </td> <?php } ?>
						<!-- ENDIF CAA RECORD -->
					</tr>
					<!-- CAA RECORD -->
					
					<!-- ANY RECORD -->
					<tr>
						<td class="vert-align text-center">
							<h4>
								<span class="label label-default">ANY</span>
							</h4>
						</td> <?php if(empty($dnsRecords['ANY']) != null){ ?>
						<!-- IF NO ANY RECORD -->
						<td class="vert-align text-center">NA</td>
						<td class="warning">
							<h4>No record</h4>
						</td> <?php } else { ?>
						<!-- ELSE ANY RECORD -->
						<td class="vert-align text-center"> <?php echo($dnsRecords['ANY'][0]['ttl']); ?> </td>
						<td class="success"> <?php echo $dns_any_html; ?> </td> <?php } ?>
						<!-- ENDIF ANY RECORD -->
					</tr>
					<!-- ANY RECORD -->
					
					<!-- HINFO RECORD -->
					<tr>
						<td class="vert-align text-center">
							<h4>
								<span class="label label-default">HINFO</span>
							</h4>
						</td> <?php if(empty($dnsRecords['HINFO']) != null){ ?>
						<!-- IF NO HINFO RECORD -->
						<td class="vert-align text-center">NA</td>
						<td class="warning">
							<h4>No record</h4>
						</td> <?php } else { ?>
						<!-- ELSE HINFO RECORD -->
						<td class="vert-align text-center"> <?php echo($dnsRecords['HINFO'][0]['ttl']); ?> </td>
						<td class="success"> <?php echo $dns_hinfo_html; ?> </td> <?php } ?>
						<!-- ENDIF HINFO RECORD -->
					</tr>
					<!-- HINFO RECORD -->
					
					<!-- PTR RECORD -->
					<tr>
						<td class="vert-align text-center">
							<h4>
								<span class="label label-primary">PTR</span>
							</h4>
						</td> <?php if(empty($dnsRecords['PTR']) != null){ ?>
						<!-- IF NO PTR RECORD -->
						<td class="vert-align text-center">NA</td>
						<td class="warning">
							<h4>No record</h4>
						</td> <?php } else { ?>
						<!-- ELSE PTR RECORD -->
						<td class="vert-align text-center"> <?php echo($dnsRecords['PTR'][0]['ttl']); ?> </td>
						<td class="success"> <?php echo $dns_ptr_html; ?> </td> <?php } ?>
						<!-- ENDIF PTR RECORD -->
					</tr>
					<!-- PTR RECORD -->
					
					<!-- SRV RECORD -->
					<tr>
						<td class="vert-align text-center">
							<h4>
								<span class="label label-default">SRV</span>
							</h4>
						</td> <?php if(empty($dnsRecords['SRV']) != null){ ?>
						<!-- IF NO SRV RECORD -->
						<td class="vert-align text-center">NA</td>
						<td class="warning">
							<h4>No record</h4>
						</td> <?php } else { ?>
						<!-- ELSE SRV RECORD -->
						<td class="vert-align text-center"> <?php echo($dnsRecords['SRV'][0]['ttl']); ?> </td>
						<td class="success"> <?php echo $dns_srv_html; ?> </td> <?php } ?>
						<!-- ENDIF SRV RECORD -->
					</tr>
					<!-- SRV RECORD -->
					
					<!-- NAPTR RECORD -->
					<tr>
						<td class="vert-align text-center">
							<h4>
								<span class="label label-default">NAPTR</span>
							</h4>
						</td> <?php if(empty($dnsRecords['NAPTR']) != null){ ?>
						<!-- IF NO NAPTR RECORD -->
						<td class="vert-align text-center">NA</td>
						<td class="warning">
							<h4>No record</h4>
						</td> <?php } else { ?>
						<!-- ELSE NAPTR RECORD -->
						<td class="vert-align text-center"> <?php echo($dnsRecords['NAPTR'][0]['ttl']); ?> </td>
						<td class="success"> <?php echo $dns_naptr_html; ?> </td> <?php } ?>
						<!-- ENDIF NAPTR RECORD -->
					</tr>
					<!-- NAPTR RECORD -->
					
					<!-- A6 RECORD -->
					<tr>
						<td class="vert-align text-center">
							<h4>
								<span class="label label-default">A6</span>
							</h4>
						</td> <?php if(empty($dnsRecords['A6']) != null){ ?>
						<!-- IF NO A6 RECORD -->
						<td class="vert-align text-center">NA</td>
						<td class="warning">
							<h4>No record</h4>
						</td> <?php } else { ?>
						<!-- ELSE A6 RECORD -->
						<td class="vert-align text-center"> <?php echo($dnsRecords['A6'][0]['ttl']); ?> </td>
						<td class="success"> <?php echo $dns_a6_html; ?> </td> <?php } ?>
						<!-- ENDIF A6 RECORD -->
					</tr>
					<!-- A6 RECORD -->
					
				</table>
			</div> <?php } elseif($forbidden_domain['forbidden']){?>
				<div class="center-container">
<div style="max-width: 500px;">
<div class="alert alert-danger" role="alert">
  <h4 class="alert-heading">Access Denied!</h4>
  <p><?php echo $forbidden_domain['reason']; ?> Please try again with a different domain.</p>
  <hr>
  <p class="mb-0">If you believe this is an error, please contact support.</p>
</div>
</div>
				</div>
			<?php } ?>
			<!-- ENDIF FORM SUBMITTED -->
			<footer class="footer">
				<p class="text-center">&copy; Simple DNS Lookup - <a href="https://github.com/dehlirious/Simple-DNS-Lookup">Sourcecode on GitHub</a>
				</p>
			</footer>
		</div>
		<!-- /container -->
	</body>
</html>
