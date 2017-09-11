<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="keywords" content="" />
        <meta name="description" content="" />
        <title>Linked Account Shipchung</title>
    </head>
    <body>
<?php
    require_once("shipchung.client.php");

    $shipchungclient = new ShipchungClient();
    $shipchungclient->client_id = "demo";
    //$shipchungclient->client_id = "page365_access";
    //$shipchungclient->client_secret = "page365.vn@477786913079296";    
    $authorize_url = $shipchungclient->get_url_authorize();
?>
<div class="row mgt-15">
            <div class="col-lg-8 col-md-7 col-xs-12">
                <p class="clr-red"><strong><font><font>Delivery - Earning Money (CoD)</font></font></strong></p>
                <ul class="list-policy-payment">
                    <li><strong><font><font>Stimulation purchase:</font></font></strong><font><font> 100% buyer like CoD. </font><font>The seller may be reduced if sales do not accept this form.</font></font></li>
                    <li><strong><font><font>Free</font></font></strong><font><font> Free Free CoD internation be returned if the buyer refuses delivery</font></font></li>
                    <li><strong><font><font>Custody a short amount of time:</font></font></strong><font><font> 1 week Payment for goods / time on 6th</font></font></li>
                    <li><strong><font><font>Network-wide shipping:</font></font></strong><font><font> Go to each county / district in 63 provinces / cities nationwide</font></font></li>
                    <li><strong><font><font>Convenience:</font></font></strong><font><font> Check or Online lookup cruise</font></font></li>

                </ul>
                <p class="clr-red  mgt-15"><strong><font><font>Transport services of ShipChung</font></font></strong></p>
                <ul class="list-policy-payment">
                    <li><strong><font><font>Full free:</font></font></strong><font><font> Apply Online all forms of payment (credit card, bank account, for NganLuong)</font></font></li>
                    <li><strong><font><font>Increase sales:</font></font></strong><font><font> Online payment not accept rejection convenience customers prefer.</font></font></li>
                    <li><strong><font><font>Reduce costs:</font></font></strong><font><font> Percentage of buyers canceled orders and delivery is very low</font></font></li>
                    <li><strong><font><font>Get cash NOW row:</font></font></strong><font><font> Immediately after payment Customers</font></font></li>
                    <li><strong><font><font>Convenient &amp; Simple:</font></font></strong><font><font> Over 30 collection method</font></font></li>
                    <li><strong><font><font>Buyers trust:</font></font></strong><font><font> Bank 100% insurance amount</font></font></li>
                    <li><strong><font><font>Protection seller:</font></font></strong><font><font> Limiting charge back risk</font></font></li>

                </ul>
            </div>
            <div class="col-lg-4 col-md-5 col-xs-12">


                    <a id="sc-integration" class="btn btn-default btn-sm btn-block"><span class="icon-no-sc"></span><font><font>Links ShipChung account </font></font></a>
                    <a href="https://www.nganluong.vn/?portal=nganluong&amp;page=user_register"><font><font>register</font></font></a><font><font> if no account ShipChung

                </font></font>
            </div>
        </div>



        <div>
            <h2><a href="<?php echo $authorize_url; ?>"> Link Account Shipchung </a></h2>
        </div>

    </body>

    </html>

