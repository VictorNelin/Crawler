<?php
require('phpQuery/phpQuery.php');

class ParserCian
{

    public $servername = "";
    public $database = "";
    public $username = "";
    public $password = "";
    public $hidetime = 0;
    public $proxylist = array("157.230.216.214:80", "157.230.140.12:8080", "193.92.85.51:8080", "5.79.113.168:3128", "80.179.157.80:80", "207.180.226.111:80", "68.183.156.72:80", "13.114.159.59:80",);
    public $barrierid = array();

    function ParserCian($servername, $database, $username, $password, $hidetime)
    {
        $this->servername = $servername;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
        $this->hidetime = $hidetime;
    }

    function Checklast()
    {
        $conn = mysqli_connect($this->servername, $this->username, $this->password, $this->database);
        if (!$conn) {
            die("Connection failed: " . mysqli_connect_error() . "<br>");
        }
        $sql = "SELECT * FROM datapars  WHERE numway=1 ORDER BY id DESC LIMIT 1";
        $res = mysqli_query($conn, $sql);
        $row = mysqli_fetch_array($res);
        $this->barrierid[0] = $row['idcian'];
        $sql = "SELECT * FROM datapars  WHERE numway=2 ORDER BY id DESC LIMIT 1";
        $res = mysqli_query($conn, $sql);
        $row = mysqli_fetch_array($res);
        $this->barrierid[1] = $row['idcian'];
        $sql = "SELECT * FROM datapars  WHERE numway=3 ORDER BY id DESC LIMIT 1";
        $res = mysqli_query($conn, $sql);
        $row = mysqli_fetch_array($res);
        $this->barrierid[2] = $row['idcian'];
        $sql = "SELECT * FROM datapars  WHERE numway=4 ORDER BY id DESC LIMIT 1";
        $res = mysqli_query($conn, $sql);
        $row = mysqli_fetch_array($res);
        $this->barrierid[3] = $row['idcian'];
        mysqli_close($conn);
    }

    function ToDb($date, $imgs, $roomcol, $area, $price, $description, $authorname, $address, $allphones, $allundergrounds, $geodata, $idcian, $numway)
    {
        if (!(empty($date) && empty($imgs) && empty($roomcol) && empty($area) && empty($price))) {
            $conn = mysqli_connect($this->servername, $this->username, $this->password, $this->database);
            if (!$conn) {
                die("Connection failed: " . mysqli_connect_error());
            }
            $sql = "INSERT INTO datapars (`id`, `publdate`, `imgs`, `roomcol`, `area`, `price`, `description`, `authorname`, `address`, `allphones`, `allundergrounds`, `geodata`, `idcian`, `numway`) VALUES (NULL,'$date', '$imgs', '$roomcol', '$area', '$price', '$description', '$authorname', '$address',' $allphones', '$allundergrounds', '$geodata', '$idcian', '$numway');";
            if (mysqli_query($conn, $sql)) {
            } else {
                echo "Error:" . $sql . "<br>" . mysqli_error($conn) . "<br>";
            }
            mysqli_close($conn);

        }
    }

    function GetPages($href, $numway)
    {
        echo "Search on page : " . $href . "<br>";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $href);
        curl_setopt($curl, CURLOPT_PROXY, $this->proxylist[array_rand($this->proxylist, 1)]);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 25);
        curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $out = curl_exec($curl);
        curl_close($curl);
        $obj = phpQuery::newDocument($out);
        $elems = $obj->find('a[class*=header]');

        foreach ($elems as $el) {
            $idT = pq($el);

            if ((stristr($idT->attr('href'), 'flat')) && ((stristr($idT->attr('href'), 'rent')) || (stristr($idT->attr('href'), 'sale'))) && !((stristr($idT->attr('href'), 'cat.php'))) && !((stristr($idT->attr('href'), 'favorites')))) {
                echo "Page : " . $idT->attr('href') . "<br>";
                $hreft = substr($idT->attr('href'), 0, -1);
                if (in_array(substr($hreft, strrpos($hreft, '/') + 1), $this->barrierid)) {
                    break;
                } else {
                    $this->GetData($idT->attr('href'), $numway);
                }

            }


            sleep($this->hidetime);
        }
    }

    function GetData($href, $numway)
    {
        echo "Page : " . $href . "<br>";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $href);
        curl_setopt($curl, CURLOPT_PROXY, $this->proxylist[array_rand($this->proxylist, 1)]);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $out = curl_exec($curl);
        curl_close($curl);
        $temp = stristr($out, '\'offer-card\'');
        $dfl = json_decode("{\"full\":" . substr($temp, 15, strpos($temp, '}];') - 13) . '}', false);
        $date = $dfl->{'full'}[47]->{'value'}->{'offerData'}->{'offer'}->{'editDate'};
        $imgs = $dfl->{'full'}[47]->{'value'}->{'offerData'}->{'offer'}->{'photos'};
        $allimgs = array();
        if (isset($imgs)) {
            foreach ($imgs as $data) {
                array_push($allimgs, $data->{'fullUrl'});
            }
        }
        $rooms = $dfl->{'full'}[47]->{'value'}->{'offerData'}->{'offer'}->{'roomsCount'};
        $area = $dfl->{'full'}[47]->{'value'}->{'offerData'}->{'offer'}->{'totalArea'};
        $price = $dfl->{'full'}[47]->{'value'}->{'offerData'}->{'offer'}->{'bargainTerms'}->{'price'};
        $description = $dfl->{'full'}[47]->{'value'}->{'offerData'}->{'offer'}->{'description'};
        $name = $dfl->{'full'}[47]->{'value'}->{'offerData'}->{'agent'}->{'name'};
        if ($name == null) {
            $name = $dfl->{'full'}[47]->{'value'}->{'offerData'}->{'agent'}->{'firstName'} . ' ' . $dfl->{'full'}[47]->{'value'}->{'offerData'}->{'agent'}->{'lastName'};
        }
        $phone = $dfl->{'full'}[47]->{'value'}->{'offerData'}->{'offer'}->{'phones'};
        $allphs = array();

        if (isset($phone)) {
            foreach ($phone as $data) {
                array_push($allphs, $data->{'countryCode'} . $data->{'number'});
            }
        }
        $geo = array($dfl->{'full'}[47]->{'value'}->{'offerData'}->{'offer'}->{'geo'}->{'coordinates'}->{'lng'}, $dfl->{'full'}[47]->{'value'}->{'offerData'}->{'offer'}->{'geo'}->{'coordinates'}->{'lat'});
        $addr = $dfl->{'full'}[47]->{'value'}->{'offerData'}->{'offer'}->{'geo'}->{'address'};
        $address = "";
        if (isset($addr)) {
            foreach ($addr as $data) {
                $address = $address . $data->{'fullName'} . ", ";
            }
        }
        $undegr = $dfl->{'full'}[47]->{'value'}->{'offerData'}->{'offer'}->{'geo'}->{'undergrounds'};
        $allunds = array();

        if (isset($undegr)) {
            foreach ($undegr as $data) {
                array_push($allunds, $data->{'name'} . '-' . $data->{'travelTime'});
            }
        }
        $tempimages = "";
        $tempphs = "";
        $tempunds = "";
        $tempgeo = "";
        foreach ($allimgs as $data) {
            $tempimages = $tempimages . $data . ',';
        }
        $tempimages = substr($tempimages, 0, -1);
        foreach ($allphs as $data) {
            $tempphs = $tempphs . $data . ',';
        }
        $tempphs = substr($tempphs, 0, -1);
        foreach ($allunds as $data) {
            $tempunds = $tempunds . $data . ',';
        }
        $tempunds = substr($tempunds, 0, -1);
        foreach ($geo as $data) {
            $tempgeo = $tempgeo . $data . ',';

        }
        $tempgeo = substr($tempgeo, 0, -1);

        $href = substr($href, 0, -1);
        $idcian = 0;
        $marker = strrpos($href, '/');
        if ($marker)
            $idcian = substr($href, strrpos($href, '/') + 1);
        $this->ToDb($date, $tempimages, $rooms, $area, $price, $description, $name, $address, $tempphs, $tempunds, $tempgeo, $idcian, $numway);

    }

    function Start()
    {
        $this->Checklast();
        for ($i = 1; $i <= 2; $i++) {
            $href = "https://www.cian.ru/cat.php?deal_type=rent&engine_version=2&offer_type=flat&region=1&room1=1&room2=1&room3=1&room4=1&room5=1&room6=1&room9=1&type=4&p=" . $i;
            $this->GetPages($href, 1);
        }
        for ($i = 1; $i <= 2; $i++) {
            $href = "https://www.cian.ru/cat.php?deal_type=sale&engine_version=2&offer_type=flat&region=1&room1=1&room2=1&room3=1&room4=1&room5=1&room6=1&room9=1&type=4&p=" . $i;
            $this->GetPages($href, 2);
        }
        for ($i = 1; $i <= 2; $i++) {
            $href = "https://www.cian.ru/cat.php?deal_type=rent&engine_version=2&offer_type=flat&region=2&room1=1&room2=1&room3=1&room4=1&room5=1&room6=1&room9=1&type=4&p=" . $i;
            $this->GetPages($href, 3);
        }
        for ($i = 1; $i <= 2; $i++) {
            $href = "https://www.cian.ru/cat.php?deal_type=sale&engine_version=2&offer_type=flat&region=2&room1=1&room2=1&room3=1&room4=1&room5=1&room6=1&room9=1&type=4&p=" . $i;
            $this->GetPages($href, 4);
        }

    }

}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('error_reporting', E_ALL);
$parser = new ParserCian("localhost", "hodlyard_parsecia", "hodlyard_user2", "768218df", 10);
$parser->Start();

?>
