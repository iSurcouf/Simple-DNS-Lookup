<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Simple PHP script to lookup entries of A, AAAA, NS, MX, SOA and TXT records">
        <meta name="author" content="HQWEB">
        <title>Simple DNS Lookup</title>
        <link rel="icon" href="assets/favicon.ico"><!-- https://www.iconperk.com/iconsets/magicons -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet"><!-- Bootstrap core CSS -->
        <link href="assets/css/style.css" rel="stylesheet"><!-- Custom styles for this template -->
    </head>
    <body>
        <div class="container">
            <div class="header clearfix">
                <nav>
                    <ul class="nav nav-pills pull-right">
                        <li role="presentation" ><a href="/">Outils</a></li>
                        <li role="presentation" class="active"><a href="/dns-lookup">DNS Lookup</a></li>
                    </ul>
                </nav>
                <h3 class="text-muted">Simple DNS Lookup</h3>
            </div>
            
            <?php
                // ini_set('display_errors', 1); // Uncomment to display errors
                // ini_set('display_startup_errors', 1); // Uncomment to display errors
                // error_reporting(E_ALL); // Uncomment to display errors
                
                // If domain is included in URL, prefill form with domain or if form is submitted display domain in it
                if( isset($_POST['domain']) )
                    {
                        $value = $_POST['domain'];
                    }
                    else
                    {
                        $value = $_GET['domain'];
                    }
                
                // Parse url to extract host
                $posted_domain = $_POST['domain'];
                $parsed_url = parse_url($posted_domain);
                
                if (array_key_exists('host', $parsed_url))
                    {
                        $domain = $parsed_url['host'];
                    }
                else
                    {
                        $domain = $posted_domain;
                    }
                
                // get records
                $dns_a = dns_get_record($domain, DNS_A);
                $dns_a_ttl = $dns_a[0]['ttl'];
                
                $dns_ns = dns_get_record($domain, DNS_NS);
                $dns_ns_ttl = $dns_ns[0]['ttl'];
                
                $dns_mx = dns_get_record($domain, DNS_MX);
                $dns_mx_ttl = $dns_mx[0]['ttl'];
                
                $dns_soa = dns_get_record($domain, DNS_SOA);
                $dns_soa_ttl = $dns_soa[0]['ttl'];
                $dns_soa_email = explode(".", $dns_soa[0]['rname']);
                $dns_soa_email = $dns_soa_email[0].'@'.$dns_soa_email[1].'.'.$dns_soa_email[2];
                $dns_soa_serial = $dns_soa[0]['serial'];
                $dns_soa_refresh = $dns_soa[0]['refresh'];
                $dns_soa_retry = $dns_soa[0]['retry'];
                $dns_soa_expire = $dns_soa[0]['expire'];
                $dns_soa_minimum_ttl = $dns_soa[0]['minimum-ttl'];
                
                $dns_txt = dns_get_record($domain, DNS_TXT);
                $dns_txt_ttl = $dns_txt[0]['ttl'];
                
                $dns_aaaa = dns_get_record($domain, DNS_AAAA);
                $dns_aaaa_ttl = $dns_aaaa[0]['ttl'];
                
                // Page URL
                $page_url_domain = $_SERVER['HTTP_REFERER'] . "?domain=" . $domain;
            ?>
            
            <div class="jumbotron">
                <form action="./" method="post">
                    <div class="form-group">
                        <input
                            type="search"
                            class="form-control input-lg text-center"
                            name ="domain"
                            id="domain"
                            placeholder="https://www.domain.com/page.html or domain.com"
                            value="<?=$value?>"
                            requirerd
                        >
                        <button type="submit" name="submit" class="btn btn-primary btn-lg">Lookup</button>
                    </div>
                </form>
            </div>
            
            <?php if(isset($_POST['submit'])) { ?> <!-- IF FORM SUBMITTED -->
            
            <div class="row marketing">
                
                <h4>Direct link : <a href="<?=$page_url_domain?>"><?=$page_url_domain?></a></h4>

                <table class="table table-striped table-bordered table-responsive">
                    <thead class="bg-primary">
                        <tr>
                            <th class="text-center">Records</th>
                            <th class="text-center">TTL</th>
                            <th>Entries for <?=$domain?></th>
                        </tr>
                    </thead>
                    
                    <!-- A RECORD -->
                    <tr>
                        
                        <td class="vert-align text-center"><h4><span class="label label-primary">A</span></h4></td>
                        
                        <?php if(empty($dns_a) != null){ ?> <!-- IF NO A RECORD -->
                        
                        <td class="vert-align text-center">NA</td>
                        <td class="warning"><h4>No record</h4></td>
                        
                        <?php } else { ?> <!-- ELSE A RECORD -->
                        
                        <td class="vert-align text-center"><?=$dns_a_ttl?></td>
                        <td class="success">
                            <?php foreach($dns_a as $value)
                                {
                                    echo "<h4>";
                                    $ipapi = file_get_contents('http://ip-api.com/json/' . $value['ip'] . '?fields=4195842'); // https://ip-api.com/docs/api:json#test
                                    $ipapidc = json_decode($ipapi, true);
                                    $country_code_flag = $ipapidc['countryCode']; // Uppercase
                                    echo mb_convert_encoding( '&#' . ( 127397 + ord( $country_code_flag[0] ) ) . ';', 'UTF-8', 'HTML-ENTITIES');
                                    echo mb_convert_encoding( '&#' . ( 127397 + ord( $country_code_flag[1] ) ) . ';', 'UTF-8', 'HTML-ENTITIES');
                                    echo(" " . $ipapidc['countryCode'] . " · " . $value['ip']. " · <small>(<b>ISP</b> " . $ipapidc['isp']. " <b>ORG</b> " . $ipapidc['org']. " <b>AS</b> " . $ipapidc['asname']);
                                    echo ")</small></h4>";
                                }
                            ?>
                        </td>
                        
                        <?php } ?> <!-- ENDIF A RECORD -->
                        
                    </tr>
                    <!-- A RECORD -->
                    
                    
                    <!-- AAAA RECORD -->
                    <tr>
                        
                        <td class="vert-align text-center"><h4><span class="label label-info">AAAA</span></h4></td>
                        
                        <?php if(empty($dns_aaaa) != null){ ?> <!-- IF NO AAAA RECORD -->
                        
                        <td class="vert-align text-center">NA</td>
                        <td class="warning"><h4>No record</h4></td>
                        
                        <?php } else { ?> <!-- ELSE AAAA RECORD -->
                        
                        <td class="vert-align text-center"><?=$dns_aaaa_ttl?></td>
                        <td class="success">
                            <?php foreach($dns_aaaa as $value)
                                {
                                    echo "<h4>";
                                    $ipapi = file_get_contents('http://ip-api.com/json/' . $value['ipv6'] . '?fields=4195842');
                                    $ipapidc = json_decode($ipapi, true);
                                    $country_code_flag = $ipapidc['countryCode']; // Uppercase
                                    echo mb_convert_encoding( '&#' . ( 127397 + ord( $country_code_flag[0] ) ) . ';', 'UTF-8', 'HTML-ENTITIES');
                                    echo mb_convert_encoding( '&#' . ( 127397 + ord( $country_code_flag[1] ) ) . ';', 'UTF-8', 'HTML-ENTITIES');
                                    echo(" ". $ipapidc['countryCode'] . " · " . $value['ipv6']. " ·<small> <b>ISP</b> ". $ipapidc['isp']. " · <b>ORG</b> ". $ipapidc['org']. " · <b>ASNAME</b> ". $ipapidc['asname']);
                                    echo "</small></h4>";
                                }
                            ?>
                        </td>
                        
                        <?php } ?> <!-- ENDIF AAAA NO RECORD -->
                        
                    </tr>
                    <!-- AAAA RECORD -->
                    
                    <!-- NS RECORD -->
                    <tr>
                        
                        <td class="vert-align text-center"><h4><span class="label label-success">NS</span></h4></td>
                        
                        <?php if(empty($dns_ns) != null){ ?> <!-- IF NO NS RECORD -->
                        
                        <td class="vert-align text-center">NA</td>
                        <td class="warning"><h4>No record</h4></td>
                        
                        <?php } else { ?> <!-- ELSE NS RECORD -->
                        
                        <td class="vert-align text-center"><?=$dns_ns_ttl?></td>
                        <td class="success">
                            <?php foreach($dns_ns as $value)
                                {
                                    echo "<h4>";
                                    echo($value['target']);
                                    echo " (";
                                    $ipapi = file_get_contents('http://ip-api.com/json/' . gethostbyname($value['target']) . '?fields=2');
                                    $ipapidc = json_decode($ipapi, true);
                                    $country_code_flag = $ipapidc['countryCode']; // Uppercase
                                    echo mb_convert_encoding( '&#' . ( 127397 + ord( $country_code_flag[0] ) ) . ';', 'UTF-8', 'HTML-ENTITIES');
                                    echo mb_convert_encoding( '&#' . ( 127397 + ord( $country_code_flag[1] ) ) . ';', 'UTF-8', 'HTML-ENTITIES');
                                    echo(" " . $ipapidc['countryCode'] . " · " . gethostbyname($value['target'])); 
                                    echo ")</h4>";
                                }
                            ?>
                        </td>
                        
                        <?php } ?> <!-- ENDIF NS RECORD -->
                        
                    </tr>
                    <!-- NS RECORD -->
                    
                    <!-- MX RECORD -->
                    <tr>
                        
                        <td class="vert-align text-center"><h4><span class="label label-danger">MX</span></h4></td>
                        
                        <?php if(empty($dns_mx) != null){ ?> <!-- IF NO MX RECORD -->
                        
                        <td class="vert-align text-center">NA</td>
                        <td class="warning"><h4>No record</h4></td>
                        
                        <?php } else { ?> <!-- ELSE MX RECORD -->
                        
                        <td class="vert-align text-center"><?=$dns_mx_ttl?></td>
                        <td class="success">
                            <?php foreach($dns_mx as $value)
                                {
                                    echo "<h4>";
                                    echo("[" . $value['pri'] . "] " . $value['target'] . " (");
                                    $ipapi = file_get_contents('http://ip-api.com/json/' . gethostbyname($value['target']) . '?fields=2');
                                    $ipapidc = json_decode($ipapi, true);
                                    $country_code_flag = $ipapidc['countryCode']; // Uppercase
                                    echo mb_convert_encoding( '&#' . ( 127397 + ord( $country_code_flag[0] ) ) . ';', 'UTF-8', 'HTML-ENTITIES');
                                    echo mb_convert_encoding( '&#' . ( 127397 + ord( $country_code_flag[1] ) ) . ';', 'UTF-8', 'HTML-ENTITIES');
                                    echo(" " . $ipapidc['countryCode'] . " · " . gethostbyname($value['target']));
                                    echo ")</h4>";
                                }
                            ?>
                        </td>
                        
                        <?php } ?> <!-- ENDIF MX RECORD -->
                        
                    </tr>
                    <!-- MX RECORD -->
                    
                    <!-- SOA RECORD -->
                    <tr>
                        
                        <td class="vert-align text-center"><h4><span class="label label-warning">SOA</span></h4></td>
                        
                        <?php if(empty($dns_soa) != null){ ?> <!-- IF NO RECORD -->
                        
                        <td class="vert-align text-center">NA</td>
                        <td class="warning"><h4>No record</h4></td>
                        
                        <?php } else { ?> <!-- ELSE SOA RECORD -->
                        
                        <td class="vert-align text-center"><?=$dns_soa_ttl?></td>
                        <td class="success">
                                <h4>Email : <?=$dns_soa_email?></h4>
                                <h4>Serial : <?=$dns_soa_serial?></h4>
                                <h4>Refresh : <?=$dns_soa_refresh?></h4>
                                <h4>Retry : <?=$dns_soa_retry?></h4>
                                <h4>Expire : <?=$dns_soa_expire?></h4>
                                <h4>Minimum TTL : <?=$dns_soa_minimum_ttl?></h4>
                        </td>
                        
                        <?php } ?> <!-- ENDIF SOA RECORD -->
                        
                    </tr>
                    <!-- SOA RECORD -->
                    
                    <!-- TXT RECORD -->
                    <tr>
                        
                        <td class="vert-align text-center"><h4><span class="label label-default">TXT</span></h4></td>
                        
                        <?php if(empty($dns_txt) != null){ ?> <!-- IF NO TXT RECORD -->
                        
                        <td class="vert-align text-center">NA</td>
                        <td class="warning"><h4>No record</h4></td>
                        
                        <?php } else { ?> <!-- ELSE TXT RECORD -->
                        
                        <td class="vert-align text-center"><?php echo($dns_txt[0]['ttl']); ?></td>
                        <td class="success">
                            <?php foreach($dns_txt as $value)
                                {
                                    echo "<h4>";
                                    $dns_txt_value = wordwrap($value['txt'], 80, "<br/>\n", true);
                                    echo $dns_txt_value;
                                    echo "</h4>";
                                }
                            ?>
                        </td>
                        
                        <?php } ?> <!-- ENDIF TXT RECORD -->
                        
                    </tr>
                    <!-- TXT RECORD -->
                    
                </table>
                
            </div>

            <?php } ?> <!-- ENDIF FORM SUBMITTED -->

            <footer class="footer">
                <p  class="text-center">&copy; Simple DNS Lookup - <a href="https://github.com/iSurcouf/Simple-DNS-Lookup">Sourcecode on GitHub</a></p>
            </footer>
            </div> <!-- /container -->
    </body>
</html>