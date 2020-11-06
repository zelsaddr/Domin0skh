<?php
/**
 * @package Dominos
 * @author <MazterinDev>
 */
date_default_timezone_set('Asia/Jakarta');
require('./ModulKu.php');

class Dominos extends ModulKu {
    private $user_id;
    private $notelp;
    private $email;
    private $name;
    private $password;
    private $area = [];
    private $payment_method = [];
    private $store_location = [];
    private $selected_store = [];
    private $otp_code = 0;
    private $site = [
        'https://spambox.xyz/'
    ];

    public function __construct(){
        // print_r($this->test_pay());
        echo "# Getting Configuration... "; 
        $config = $this->get_configuration();
        if($config == true){
            echo "[SUCCESS]".PHP_EOL;
            echo "# Getting Email... | Server : ";
            $random = $this->site[array_rand($this->site)];
            $sitez = $random;
            $get_email   = $this->get_email($sitez);
            $this->name  = $this->get_random_name();
            $this->email = $get_email['current_email'];
            $this->password = $this->generateRandomString(8);
            $this->notelp = "0896".rand(0000, 9999).rand(0000, 9999);
            $this->cls();
            echo "`======== `User Information` ========`".PHP_EOL;
            echo "\t\~\ First Name  : ".$this->name['first_name'].PHP_EOL;
            echo "\t\~\ Last Name   : ".$this->name['last_name'].PHP_EOL;
            echo "\t\~\ Email       : ".$this->email.PHP_EOL;
            echo "\t\~\ Password    : ".$this->password.PHP_EOL;
            echo "\t\~\ No Telpon   : ".$this->notelp.PHP_EOL;
            $regis = $this->register_app($this->email, $this->password, $this->notelp);
            if($regis == true){
                echo "# Try to activating account... ";
                $activate = $this->activate_account($this->email, $this->otp_code);
                if($activate){
                    echo "[SUCCESS]".PHP_EOL;
                    sleep(2);
                    $this->cls();
                    echo "`======== `Choose Area` ========`".PHP_EOL;
                    foreach($this->area as $area_id){
                        echo "\t".$area_id['id'].".] ".$area_id['area_name'].PHP_EOL;
                        $last = $area_id['id'];
                    }
                    echo "[>] Select area (".$this->area[0]['id']."-".$last.") : "; $selected_area = trim(fgets(STDIN));
                    $get_store = $this->get_store($selected_area);
                    echo "[#] Trying to get store data... ";
                    if($get_store){
                        echo "[SUCCESS]".PHP_EOL;
                        $this->cls();
                        echo "`======== `Choose Location` ========`".PHP_EOL;
                        foreach ($this->store_location as $store){
                            echo "\t".$store['no'].".] ".$store['store_title']."\n";
                            echo "\t\tStore Address : ".str_replace("\n", "", $store['store_address']).PHP_EOL;
                        }
                        echo "[>] Select Store : "; $selected_store = trim(fgets(STDIN));
                        $this->cls();
                        foreach ($this->store_location as $s){
                            if($s['no'] == $selected_store){
                                $this->selected_store['store_code']     = $s['store_code'];
                                $this->selected_store['store_address']  = $s['store_address'];
                                $this->selected_store['store_title']    = $s['store_title'];
                                $this->selected_store['longitude']      = $s['longitude'];
                                $this->selected_store['latitude']       = $s['latitude'];
                                break;
                            }
                        }
                        echo "[`] Selected Store  : ".$this->selected_store['store_title'].PHP_EOL;
                        echo "[=]  Payment Menu  [=]".PHP_EOL;
                        echo "   1.] Gopay".PHP_EOL;
                        echo "   2.] DANA Wallet".PHP_EOL;
                        echo "[>] Select Payment : "; $pay = trim(fgets(STDIN));
                        ($pay == 1) ? $payment = "snapbin" : $payment = "snapmigs";
                        $order = $this->test_pay($this->selected_store, $payment);
                        if(!$order){
                            echo "[+] Gagal Order".PHP_EOL;
                        }else{
                            echo "[+] Email : ".$this->email.PHP_EOL;
                            echo "[+] Password : ".$this->password.PHP_EOL; 
                            echo "[+] Track Code : ".$this->notelp.PHP_EOL;
                            echo "[+] ".$order['message'].PHP_EOL;
                            echo "[+] Order ID".$order['data']['order_id'].PHP_EOL;
                            echo "[+] Payment URL : ".$order['data']['redirect_url'].PHP_EOL;
                        }
                    }else{
                        echo "[FAILED]".PHP_EOL;
                        print_r($get_store);
                    }

                }else{
                    echo "[FAILED]".PHP_EOL;
                    print_r($activate);
                }
            }
        }else{
            echo "[FAILED]".PHP_EOL;
            print_r($config);
        }
    }
    private function cls(){
        print("\033[2J\033[;H");
    }
    private function headers_app(){
        $headers    = array();
        $headers[]  = "token: lER2MLyGC6Go3rNdE7diPVf0umanUuTf8KhVwPB9ViyZJldnsqFhmViQisdcW6s4";
        $headers[]  = "device-type: android";
        $headers[]  = "Accept: application/json";
        $headers[]  = "Host: www.dominos.co.id";
        $headers[]  = "Connection: Keep-Alive";
        $headers[]  = "User-Agent: okhttp/3.12.0";
        return $headers; 
    }
    private function get_configuration(){
        $headers    = $this->headers_app();
        $curl       = $this->curl("https://www.dominos.co.id/infdominos/api/getConfiguration", 0, $headers);
        $decode     = json_decode($curl[1], true);
        if($decode['status'] == "success"){
            foreach ($decode['data']['area_id']['data'] as $area_id){
                $this->area[] = array(
                    "id"        => $area_id['id'],
                    "area_name" => $area_id['area_name_idn'],
                    "area_slug" => $area_id['area_slug']
                );
            }
            $i = 1;
            foreach ($decode['data']['payment_methods']['data'] as $payment){
                $this->payment_method[] = array(
                    "id"    => $i++,
                    "label" => $payment['label'],
                    "value" => $payment['value']
                );
            }
            return true;
        }else{
            return $decode;
        }
    }
    private function register_app($email, $password, $notelp){
        $headers    = $this->headers_app();
        $headers[]  = 'language: in';
        $headers[]  = 'Content-Type: application/x-www-form-urlencoded';
        $post_data  = http_build_query(array(
            "email"     => $email,
            "password"  => $password,
            "password_confirmation" => $password,
            "prefix"    => "No",
            "firstname" => $this->name['first_name'],
            "lastname"  => $this->name['last_name'],
            "birthdate" => urlencode(rand(1,29)."/".rand(10,12)."/".rand(1998, 2002)),
            "contact"   => "",
            "contact_number" => "".$notelp."",
            "contact_type"   => "m",
            "chknewsletter"  => "false",
            "contact_ext"    => "No"
        ));
        $post   = $this->curl("https://www.dominos.co.id/infdominos/api/register", $post_data, $headers);
        $decode = json_decode($post[1], true);
        if($decode['status'] == 'success'){
            $this->save("saved_".$this->name['first_name'].".txt", print_r($decode, true));
            $this->otp_code = $decode['data']['confirmation'];
            $this->user_id  = $decode['data']['entity_id'];
            return true;
        }else{
            return false;
        }
    }
    private function activate_account($email, $otp){
        $headers    = $this->headers_app();
        $headers[]  = 'language: in';
        $headers[]  = 'Content-Type: application/x-www-form-urlencoded';
        $post_data  = http_build_query(array(
            "email" => $email,
            "activation_code" => $otp
        ));
        $post = $this->curl("https://www.dominos.co.id/infdominos/api/customerActivation", $post_data, $headers);
        $decode = json_decode($post[1], true);
        if($decode['status'] == "success"){
            return true;
        }else{
            return $decode;
        }
    }
    private function get_store($area_id){
        $headers = $this->headers_app();
        $curl   = $this->curl("https://www.dominos.co.id/infdominos/api/getStore?area_id=".$area_id."&store_name=", 0, $headers);
        $decode = json_decode($curl[1], true);
        $i = 1;
        if($decode['status'] == "success"){
            foreach($decode['data'] as $store){
                $this->store_location[] = array(
                    "no"            => $i++,
                    "store_code"    => $store['store_mapping_code'],
                    "store_title"   => $store['store_title_idn'],
                    "store_address" => $store['store_address_idn'],
                    "longitude"     => $store['store_location_long'],
                    "latitude"      => $store['store_location_lat'],
                );
            }
            return true;
        }else{
            return $decode;
        }
    }
    private function test_pay(array $store_selected, $payment_method){
        $headers    = $this->headers_app();
        $headers[]  = 'language: in';
        $headers[]  = 'Content-Type: application/x-www-form-urlencoded';
        $post_data  = http_build_query(array(
            "survey_address_id" => $store_selected['store_address'],
            "mobile"            => 1,
            "items"             => '[{"sku":"NEW1000","qty":1,"options":"{\"20926\":\"MCCHTP06\"}","parent_sku":"NEW1000","coupon_code":""}]',
            "service_method"    => "carryout_carryout",
            "store_code"        => $store_selected['store_code'],
            "firstname"         => $this->name['first_name'],
            "lastname"          => $this->name['last_name'],
            "email"             => $this->email,
            "contact_number"    => $this->notelp,
            "contact_ext"       => "",
            "contact_type"      => "m",
            "payment_code"      => $payment_method,
            "order_source"      => "mobile",
            "delivery_time"     => "now",
            "remarks"           => "",
            "longitude"         => $store_selected['longitude'],
            "latitude"          => $store_selected['latitude'],
            "substreet"         => "null null",
            "affiliate_vendor"  => "",
            "deeplink_url"      => "dominos://status/ru",
            "customer_id"       => $this->user_id,
            "session_id"        => "".$this->gen_uuid()."",
            "fav_order"         => 0,
            "address_id"        => "",
            "unique_token"      => "",
            "checksum"          => ""
        ));
        $curl = $this->curl("https://www.dominos.co.id/infdominos/api/placeOrderNew", $post_data, $headers);
        $decode = json_decode($curl[1], true);
        if($decode['status'] == "success"){
            return $decode;
        }else{
            return false;
        }
    }
}

(new Dominos);