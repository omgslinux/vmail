<?php

namespace App\Utils;

use App\Entity\Domain;
use App\Entity\User;

/*
  Clase para gestionar los certificados x509
*/

class Certificate
{
    private $domain;
    private $user;
    private $certData = [];

    const pkeyConfigArgs = [
        "digest_alg" => "sha512",
        "private_key_bits" => 4096,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ];
    const CAConfigArgs = [
        "x509_extensions" => [
            'nsCertType' => 'sslCA, emailCA, objCA',
            'keyUsage' => 'keyCertSign,cRLSign',
            'basicConstraints'=> 'critical,CA:TRUE',
        ]
    ];
    const ClientConfigArgs = [
        "x509_extensions" => [
            'nsCertType' => 'client, email',
            'extendedKeyUsage' => 'clientAuth',
            'keyUsage' => 'digitalSignature, keyEncipherment, dataEncipherment, keyAgreement',
            'basicConstraints'=> 'critical,CA:FALSE',
        ]
    ];
    const ServerConfigArgs = [
        "x509_extensions" => [
            'nsCertType' => 'server',
            'subjectAltName' => 'DNS:CN <Update with CN when creating certificate>',
            'extendedKeyUsage' => 'serverAuth',
            'keyUsage' => 'digitalSignature, nonRepudiation, keyEncipherment, keyAgreement',
            'basicConstraints'=> 'critical,CA:FALSE',
        ]
    ];

    public function new_private_key($pass)
    {
        // # generate aes encrypted private key
        // openssl genrsa -aes256 -out $CANAME.key 4096
        //$pkey = openssl_pkey_new($pass);
        //$spkac = openssl_spki_new($pkey, $this->commonData['CN']);



        // Create the private and public key
        $config =
        $res = openssl_pkey_new(self::pkeyConfigArgs);

        // Extract the private key from $res to $privKey
        openssl_pkey_export($res, $privKey);

        // Extract the public key from $res to $pubKey
        $pubKey = openssl_pkey_get_details($res);
        $pubKey = $pubKey["key"];

        return $privKey;

    }

    private function pem2der($pem_data)
    {
       $begin = "CERTIFICATE-----";
       $end   = "-----END";
       $pem_data = substr($pem_data, strpos($pem_data, $begin)+strlen($begin));
       $pem_data = substr($pem_data, 0, strpos($pem_data, $end));
       $der = base64_decode($pem_data);
       return $der;
    }

    private function der2pem($der_data): string
    {
       $pem = chunk_split(base64_encode($der_data), 64, "\n");
       $pem = "-----BEGIN CERTIFICATE-----\n".$pem."-----END CERTIFICATE-----\n";
       return $pem;
    }

    public function exportPKCS12()
    {
        /*
        openssl_pkcs12_export(
   $cert,
   $pfx_contenido,
   $private_key,
   $pfx_password,
   [ 'extracerts' => $extracerts]
);
openssl_pkcs12_read($pfx_contenido, $certs, $pfx_password)

        */
    }

    private function extractFormCommonData($commonData): array
    {
        return $commonData;
    }

    private function extractFormIntervalData($form): array
    {
        $intervalData = $form;
        $interval = $form['interval'];
        if ($interval->d != "0" || $interval->m!="0"|| $interval->y!="0") {
            //dump("Hay que calcular la fecha de fin");
            $end = new \DateTime($form['notBefore']->format('Y-m-d'));
            $str="";
            if ($interval->d!="0") {
                $str=$interval->d . ' day ';
            }
            if ($interval->m!="0") {
                $str .= $interval->m .' months ';
            }
            if ($interval->y!="0") {
                $str .=$interval->y .' years';
            }
            $end->add(\DateInterval::createFromDateString($str));
            $intervalData['notAfter'] = $end;
        }

        $duration = $end->diff($form['notBefore']);
        $intervalData['duration'] = $duration->format('%a');

        return $intervalData;
    }

    private function extractFormData($form): array
    {
        $commonData = $this->extractFormCommonData($form['common']);
        $intervalData = $this->extractFormIntervalData($form['interval']);

        $data = [
            'common' => $commonData,
            'interval' => $intervalData,
            'plainPassword' => ($form['plainPassword']??null)
        ];

        return $data;
    }

    public function createCACert($form): array
    {
        $data = $this->extractFormData($form);
        $privKey = $this->new_private_key($data['plainPassword']);
        //openssl req -new -x509 -config $CACONF -days 3650 -key $CAKEYFILE -$CASIGALG -nodes -out $CAPEM -outform PEM
        /*
        $SSLcnf = array('config' => '/usr/local/nessy2/share/ssl/openssl.cnf',
        'encrypt_key' => true,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
        'digest_alg' => 'sha1',
        'x509_extensions' => 'v3_ca',
        'private_key_bits' => $someVariable // ---> bad
        'private_key_bits' => (int)$someVariable // ---> good
        'private_key_bits' => 512 // ---> obviously good
        );



        To set the "basicConstraints" to  "critical,CA:TRUE", you have to define configargs, but in the openssl_csr_sign() function !

That's my example of code to sign a "child" certificate :

$CAcrt = "file://ca.crt";
$CAkey = array("file://ca.key", "myPassWord");

$clientKeys = openssl_pkey_new();
$dn = array(
    "countryName" => "FR",
    "stateOrProvinceName" => "Finistere",
    "localityName" => "Plouzane",
    "organizationName" => "Ecole Nationale d'Ingenieurs de Brest",
    "organizationalUnitName" => "Enib Students",
    "commonName" => "www.enib.fr",
    "emailAddress" => "ilovessl@php.net"
);
$csr = openssl_csr_new($dn, $clientPrivKey);

$configArgs = array("x509_extensions" => "v3_req");
$cert = openssl_csr_sign($csr, $CAcrt, $CAkey, 100, $configArgs);

openssl_x509_export_to_file($cert, "childCert.crt");

Then if you want to add some more options, you can edit the "/etc/ssl/openssl.cnf" ssl config' file (debian path), and add these after the [ v3_req ] tag.
        */
        $csr = openssl_csr_new($data['common'], $privKey);

        $signcert = openssl_csr_sign($csr, null, $privKey, $data['interval']['duration'], self::CAConfigArgs);
        openssl_x509_export($signcert, $certout);
        //openssl_pkey_export($privkey, $pkeyout, "mypassword")
        $factor = rand(16,30);
        $strpw = $data['plainPassword'];
        for ($i=1;$i<=$factor;$i++) {
            $strpw = base64_encode($strpw);
        }
        $strfactor = substr($certout, 20 + $factor, 10);
        for ($i=1;$i<=$factor-9;$i++) {
            $strfactor = base64_encode($strfactor);
        }
        $data['certdata'] = [
            'privKey' => $privKey,
            'cert' => $certout,
            'factor' => substr($strfactor, 0, 8) . dechex($factor) . substr($strfactor, 8),
            'strpw' => $strpw,
        ];
        return $data;
    }

    public function createClientCert($form)
    {
        $data = $this->extractFormData($form);
        dd($data);
    }

    public function createServerCert($form)
    {
        $data = $this->extractFormData($form);
        dd($data);
    }


}
