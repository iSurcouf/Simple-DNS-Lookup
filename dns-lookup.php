<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
        <meta name="description" content="">
        <meta name="author" content="HQWEB">
        <link rel="icon" href="https://www.hqweb.com/images/favicon.ico">

        <title>Simple DNS Lookup</title>

        <!-- Custom styles for this template -->
        <link href="style.css" rel="stylesheet">

        <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
        <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
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

            <div class="jumbotron">
                <form action="" method="post">
                    <div class="form-group">
                        <input class="form-control input-lg text-center" name ="domain" type="text" placeholder="https://www.domain.com/page.html or domain.com" requirerd>
                        <button type="submit" name="submit" class="btn btn-primary btn-lg">Lookup</button>
                    </div>
                </form>
            </div>

            <div class="row marketing">

                <?php

                $posted_domain = $_POST['domain'];
                $parsed_url = parse_url($posted_domain);                
                    if(isset($_POST['submit']))
                            {
                                if (array_key_exists('host', $parsed_url)) {
                                    $domain = $parsed_url['host'];
                                }
                                else {
                                    $domain = $posted_domain;
                                }
                               
                                $dns_a = dns_get_record($domain, DNS_A);
                                $dns_ns = dns_get_record($domain, DNS_NS);
                                $dns_mx = dns_get_record($domain, DNS_MX);
                                $dns_soa = dns_get_record($domain, DNS_SOA);
                                $dns_txt = dns_get_record($domain, DNS_TXT);
                                $dns_aaaa = dns_get_record($domain, DNS_AAAA);
                                $dns_all = dns_get_record($domain, DNS_ALL);

                ?>

                <h3>Result for <?php echo($domain); ?></h3>

                <table class="table table-striped table-bordered table-responsive">
                    <thead class="bg-primary">
                            <td class="text-center">Records</td>
                            <td class="text-center">TTL</td>
                            <td>Entries</td>
                    </thead>
                    
                    <!-- A RECORD -->
                    <tr>
                        <td class="vert-align text-center"><h4><span class="label label-primary">A</span></h4></td>
                            <?php $result_a = empty($dns_a); if($result_a != null){ ?> <!-- IF NO RECORD -->
                        <td class="vert-align text-center">NA</td>
                        <td class="warning"><h4>No record</h4></td>
                            <?php } else { ?> <!-- ELSE NO RECORD -->
                        <td class="vert-align text-center"><?php echo($dns_a[0]['ttl']); ?></td>
                        <td class="success">
                            <?php 
                            foreach($dns_a as $value)
                                {
                                    ?>	<h4>
                                            <?php
                                                echo($value['ip']);
                                            ?>
                                        </h4>
                        <?php } ?>
                        </td>
                            <?php } ?> <!-- END NO RECORD -->
                    </tr>
                    <!-- A RECORD -->
                    
                    <!-- AAAA RECORD -->
                    <tr>
                        <td class="vert-align text-center"><h4><span class="label label-info">AAAA</span></h4></td>
                            <?php $result_aaaa = empty($dns_aaaa); if($result_aaaa != null){ ?> <!-- IF NO RECORD -->
                        <td class="vert-align text-center">/</td>
                        <td class="warning"><h4>No record</h4></td>
                            <?php } else { ?> <!-- ELSE NO RECORD -->
                        <td class="vert-align text-center"><?php echo($dns_aaaa[0]['ttl']); ?></td>
                        <td class="success">
                            <?php 
                            foreach($dns_aaaa as $value)
                                {
                                    ?><h4>
                                        <?php  echo($value['ipv6']); ?>
                                    </h4>
                                    <?php } ?>
                        </td>
                            <?php } ?> <!-- END NO RECORD -->
                    </tr>
                    <!-- AAAA RECORD -->
                    
                    <!-- NS RECORD -->
                    <tr>
                        <td class="vert-align text-center"><h4><span class="label label-success">NS</span></h4></td>
                            <?php $result_ns = empty($dns_ns); if($result_ns != null){ ?> <!-- IF NO RECORD -->
                        <td class="vert-align text-center">/</td>
                        <td class="warning"><h4>No record</h4></td>
                            <?php } else { ?> <!-- ELSE NO RECORD -->
                        <td class="vert-align text-center"><?php echo($dns_ns[0]['ttl']); ?></td>
                        <td class="success">
                            <?php 
                            foreach($dns_ns as $value)
                                {
                                    ?><h4>
                                        <?php  echo($value['target']); ?>
                                        (<?php echo(gethostbyname($value['target'])) ?>)
                                    </h4>
                                    <?php } ?>
                        </td>
                            <?php } ?> <!-- END NO RECORD -->
                    </tr>
                    <!-- NS RECORD -->
                    
                    <!-- MX RECORD -->
                    <tr>
                        <td class="vert-align text-center"><h4><span class="label label-danger">MX</span></h4></td>
                            <?php $result_mx = empty($dns_mx); if($result_mx != null){ ?> <!-- IF NO RECORD -->
                        <td class="vert-align text-center">NA</td>
                        <td class="warning"><h4>No record</h4></td>
                            <?php } else { ?> <!-- ELSE NO RECORD -->
                        <td class="vert-align text-center"><?php echo($dns_mx[0]['ttl']); ?></td>
                        <td class="success">
                            <?php 
                            foreach($dns_mx as $value)
                                {
                                    ?><h4>
                                        [<?php  echo($value['pri']); ?>] 
                                        <?php  echo($value['target']); ?>
                                        (<?php echo(gethostbyname($value['target'])) ?>)
                                    </h4>
                                    <?php } ?>
                        </td>
                            <?php } ?> <!-- END NO RECORD -->
                    </tr>
                    <!-- MX RECORD -->
                    
                    <!-- SOA RECORD -->
                    <tr>
                        <td class="vert-align text-center"><h4><span class="label label-warning">SOA</span></h4></td>
                            <?php $result_soa = empty($dns_soa); if($result_soa != null){ ?> <!-- IF NO RECORD -->
                        <td class="vert-align text-center">NA</td>
                        <td class="warning"><h4>No record</h4></td>
                            <?php } else { ?> <!-- ELSE NO RECORD -->
                        <td class="vert-align text-center"><?php echo($dns_soa[0]['ttl']); ?></td>
                        <td class="success">
                                <h4>Email : <?php $email = explode(".", $dns_soa[0]['rname']); echo($email[0].'@'.$email[1].'.'.$email[2]); ?></h4>
                                <h4>Serial : <?php echo($dns_soa[0]['serial']); ?></h4>
                                <h4>Refresh : <?php echo($dns_soa[0]['refresh']); ?></h4>
                                <h4>Retry : <?php echo($dns_soa[0]['retry']); ?></h4>
                                <h4>Expire : <?php echo($dns_soa[0]['expire']); ?></h4>
                                <h4>Minimum TTL : <?php echo($dns_soa[0]['minimum-ttl']); ?></h4>
                        </td>
                            <?php } ?> <!-- END NO RECORD -->
                    </tr>
                    <!-- SOA RECORD -->
                    
                    <!-- TXT RECORD -->
                    <tr>
                        <td class="vert-align text-center"><h4><span class="label label-default">TXT</span></h4></td>
                            <?php $result_txt = empty($dns_txt); if($result_txt != null){ ?> <!-- IF NO RECORD -->
                        <td class="vert-align text-center">NA</td>
                        <td class="warning"><h4>No record</h4></td>
                            <?php } else { ?> <!-- ELSE NO RECORD -->
                        <td class="vert-align text-center"><?php echo($dns_txt[0]['ttl']); ?></td>
                        <td class="success">
                            <?php 
                            foreach($dns_txt as $value)
                                {
                                    ?><h4>
                                        <?php  echo($value['txt']); ?>
                                    </h4>
                                    <?php } ?>
                        </td>
                            <?php } ?> <!-- END NO RECORD -->
                    </tr>
                    <!-- TXT RECORD -->
                    
                </table>
            </div>

            <?php } ?>

            <footer class="footer">
                <p  class="text-center">&copy; Simple DNS Lookup</p>
            </footer>
            </div> <!-- /container -->
    </body>
</html>
