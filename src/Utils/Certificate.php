<?php

namespace App\Utils;

use App\Entity\Domain;
use App\Entity\User;
use App\Entity\ServerCertificate;
use Symfony\Component\HttpFoundation\Response;
//use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/*
  Clase para gestionar los certificados x509
*/

class Certificate
{
    private $domain;
    private $user;
    private $certData = [];

    const confFile = '../public/custom/openssl.cnf';

    const pkeyConfigArgs = [
        "digest_alg" => "sha512",
        "private_key_bits" => 4096,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ];
    const basicCertConfg = [
        "digest_alg" => "sha256",
        "private_key_bits" => 4096,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
        //'config' => $_SERVER['DOCUMENT_R00T'] . self::confFile,
    ];
    const CAConfigArgs = [
        "x509_extensions" => [
            'nsCertType' => 'sslCA, emailCA, objCA',
            'keyUsage' => 'keyCertSign,cRLSign',
            'basicConstraints'=> 'critical,CA:TRUE',
        ]
    ];

    public function new_private_key(): string
    {
        //$pkey = openssl_pkey_new($pass);
        //$spkac = openssl_spki_new($pkey, $this->commonData['CN']);
        $res = openssl_pkey_new(self::pkeyConfigArgs);

        // Extract the private key from $res to $privKey
        openssl_pkey_export($res, $privKey);

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

    public function exportPKCS12(array $params)
    {
        /*
         openssl_pkcs12_export(
            mixed $x509,
            string &$out,
            mixed $priv_key,
            string $pass,
            array $args = ?
        ): bool
        openssl_pkcs12_export(
           $cert,
           $pfx_contenido,
           $private_key,
           $pfx_password,
           [ 'extracerts' => $extracerts]
        );
        openssl_pkcs12_read($pfx_contenido, $certs, $pfx_password)

         openssl_pkcs12_export_to_file(
    mixed $x509,
    string $filename,
    mixed $priv_key,
    string $pass,
    array $args = ?
): bool
        */
        dump($params);
        $f = $params['filename'];
        unset($params['filename']);
        if (!empty($params['filename'])) {
            openssl_pkcs12_export_to_file(
                $params['certdata']['cert'],
                $params['filename'],
                $params['certdata']['privKey'],
                $params['plainpassword'],
                //[ 'extracerts' => $params['cacertdata']['cert']]
            );
            openssl_pkcs12_read(file_get_contents($params['filename']), $pfxout, $params['plainpassword']);


        } else {

            openssl_pkcs12_export(
                $params['certdata']['cert'],
                $pfx,
                $params['certdata']['privKey'],
                $params['plainpassword'],
                // AÑADIR CA PROVOCA ERROR DESCONOCIDO AL IMPORTAR
                //[ 'extracerts' => $params['cacertdata']['cert']]
            );
            openssl_pkcs12_read($pfx, $pfxout, $params['plainpassword']);
            //file_put_contents($f, $pfx);
        }
        //dd($pfxout);

        return $pfx;

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
        ];

        return $data;
    }

    private function genCsr($dn,  $privKey)
    {
        $csr = openssl_csr_new($dn, $privKey);

        openssl_csr_export($csr, $csrout);

        dump($csrout, $creqOptions, $csrOptions);

        return $csr;
    }

    public static function convertUTCTime2Date($string) {
        // Add a trailing 'Z' to indicate UTC
        $string .= 'Z';

        // Obtener los componentes de la cadena
        $year = intval(substr($string, 0, 2));
        $month = intval(substr($string, 2, 2));
        $day = intval(substr($string, 4, 2));
        $hour = intval(substr($string, 6, 2));
        $minute = intval(substr($string, 8, 2));
        $second = intval(substr($string, 10, 2));

        // Adjust year if necessary (considering 50 years in the past and 50 in the future)
        $year += ($year < 50) ? 2000 : 1900;

        // Create a date string compatible with DateTime
        $date_string = sprintf("%04d-%02d-%02d %02d:%02d:%02d", $year, $month, $day, $hour, $minute, $second);

        // Create a DateTime object
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $date_string);

        // Return DateTime, or else, false
        if ($date) {
            return $date;
        } else {
            return false;
        }
    }

    public function createCACert($form, $contents): array
    {
        $data = $this->extractFormData($form);
        dump($contents);
        if (null!=$contents) {
            $plainPassword = $form['common']['plainPassword']['setkey']->getPlainPassword();
            $crypted = ' ENCRYPTED';
            if (!strpos($contents, $crypted)) {
                $crypted =' RSA';
            }
            $begin = '-----BEGIN' . $crypted . ' PRIVATE KEY-----';
            $result = strpos($contents, $begin);
            if (false===$result) {
                dump($begin, $result);
                return [
                    'error' => 'No se ha podido encontrar la clave privada'
                ];
            }
            $pem_data = substr($contents, strpos($contents, $begin)+strlen($begin));
            $end = '-----END' . $crypted . ' PRIVATE KEY-----';
            $pem_data = substr($pem_data, 0, strpos($pem_data, $end));
            $privKey = $begin. $pem_data . $end;
            if ($crypted) {
                $privKeyObject = openssl_pkey_get_private($privKey,  $plainPassword);
                //openssl_pkey_export($privKeyObject, $privKey, $plainPassword);
                //openssl_x509_export(openssl_pkey_get_public($contents), $certout);
                dump($crypted, $privKeyObject);
                if (!$privKeyObject) {
                    return [
                        'error' => 'La contraseña no corresponde a la clave privada'
                    ];
                }
            }
            $begin = "-----BEGIN CERTIFICATE-----";
            $end   = "-----END CERTIFICATE-----";
            $pem_data = substr($contents, strpos($contents, $begin)+strlen($begin));
            $pem_data = substr($pem_data, 0, strpos($pem_data, $end));
            $certout = $begin. $pem_data . $end;
            $certest = openssl_x509_parse($certout);
            if (!$certest) {
                return [
                    'error' => 'No se pudo encontrar un certificado en el fichero'
                ];
            }
            dump($privKey, $certout, openssl_x509_parse($certout));
            $creqOptions = "Datos importados de fichero externo";
            unset($data['common']);
            unset($data['interval']);
        }

        if (!$certout) {
            $privKey = $this->new_private_key();

            $csr = openssl_csr_new($data['common'], $privKey);

            $creqOptions = self::CAConfigArgs;
            $signcert = openssl_csr_sign($csr, null, $privKey, $data['interval']['duration'], $creqOptions);
            openssl_x509_export($signcert, $certout);

        }

        if (!$plainPassword) {
            // Generamos una contraseña
            $plainPassword = $this->genPass();
        }

        $cryptedPass = $this->cryptPass($plainPassword, $certout);
        $cryptedKey = $this->cryptKey($privKey, $plainPassword);

        $data['certdata'] = array_merge(
            [
                'privKey' => $cryptedKey,
                'cert' => $certout,
                'config' => $creqOptions,
            ],
            $cryptedPass
        );

        //dump($data, "plainPassword: " . $plainPassword);

        //dump("privKey: " . $privKey, $this->decryptKey($cryptedKey, $plainPassword));
        //dd($plainPassword, $this->decryptPass($cryptedPass['strfactor'], $cryptedPass['strdata']));

        return $data;
    }

    public function createClientCert($form)
    {
        return $this->createSignedCert($form, 'client');
    }

    public function createServerCert($form)
    {
        return $this->createSignedCert($form, 'server');
    }

    private function createSignedCert($formData, $type): array
    {
        // Obtenemos el certificado y la clave sin cifrar
        $caCertData = $this->extractCAData($this->domain);
        $data = $this->extractFormData($formData);
        if (null==$data['common']['organizationalUnitName']) {
            unset($data['common']['organizationalUnitName']);
        }
        if ($type=='server') {
            $extensions = 'server_ext';
            $data['common']['commonName'] .= '.' . $this->domain->getName();
        } elseif ($type=='client') {
            $eUser = $data['common']['emailAddress'];
            $data['common']['commonName'] = $eUser->getFullName();
            $data['common']['emailAddress'] = $eUser->getEmail();
            //dd($data);
            $extensions = 'client_ext';
        }

        // Creamos una contraseña para cifrar la clave privada
        $plainPassword=$this->genPass();

        $privKey = $this->new_private_key();
        //dump($data['common']);
        $csr = openssl_csr_new($data['common'], $privKey);
        //openssl_pkey_export($caCertData['privKey'], $cryptedCAKey, $plainPassword);
        //dump($csr, $cryptedCAKey);
        $creqOptions = array_merge(
            self::basicCertConfg,
            ['config' => self::confFile],
            ['x509_extensions' => $extensions]
        );
        //dump(file_get_contents($creqOptions['config']));
        //$signcert = openssl_csr_sign($csr, $caCertData['cert'], [$cryptedCAKey, $plainPassword], $data['interval']['duration'], $creqOptions);
        $signcert = openssl_csr_sign($csr, $caCertData['cert'], $caCertData['privKey'], $data['interval']['duration'], $creqOptions);
        openssl_x509_export($signcert, $certout);
        //dd(openssl_x509_verify($signcert, $caCertData['cert']));
        //dump($creqOptions, $signcert, openssl_x509_parse($certout));

        // Ciframos la contraseña
        $cryptedPass = $this->cryptPass($plainPassword, $certout);
        $cryptedKey = $this->cryptKey($privKey, $plainPassword);
        unset($creqOptions['config']);
        $data['certdata'] = array_merge(
            [
                'privKey' => $cryptedKey,
                'cert' => $certout,
                'config' => $creqOptions,
            ],
            $cryptedPass
        );
        //dump($data);

        //dump($privKey, $this->decryptKey($cryptedKey, $plainPassword));
        //dd($plainPassword, $this->decryptPass($cryptedPass['strfactor'], $cryptedPass['strdata']));

        return $data;

    }

    private function cryptKey($privKey, $key, $cipher="aes-128-gcm"): array
    {
        //$plaintext = "message to be encrypted";
        $original_plaintext="";
        if (in_array($cipher, openssl_get_cipher_methods()))
        {
            $ivlen = openssl_cipher_iv_length($cipher);
            $iv = openssl_random_pseudo_bytes($ivlen);
            $cryptKey = openssl_encrypt($privKey, $cipher, $key, $options=0, $iv, $tag);
            //store $cipher, $iv, and $tag for decryption later
        } else {
            dd(openssl_get_cipher_methods());
        }

        return [
            'cipher' => $cipher,
            'iv' => bin2hex($iv),
            'tag' => bin2hex($tag),
            'cryptKey' => $cryptKey,
        ];
    }

    private function decryptKey(array $cipherdata, $key): string
    {
        //dump($cipherdata, $key);
        $cipher = $cipherdata['cipher'];
        $tag = $cipherdata['tag'];
        $cryptKey = $cipherdata['cryptKey'];
        $iv = $cipherdata['iv'];
        //$privKey = openssl_decrypt($cryptKey, $cipher, $key, $options=0, $iv, hex2bin($tag));
        $privKey = openssl_decrypt($cipherdata['cryptKey'], $cipherdata['cipher'], $key, $options=0, hex2bin($cipherdata['iv']), hex2bin($cipherdata['tag']));

        //dump("decrypted Key: ". $privKey);
        return $privKey;
    }

    private function cryptPass($plainData, $stream): array
    {
        $factor = rand(16,30);
        $strdata = $plainData;
        for ($i=1;$i<=$factor;$i++) {
            $strdata = base64_encode($strdata);
        }
        $strfactor = substr($stream, 20 + $factor, 10);
        for ($i=1;$i<=$factor-9;$i++) {
            $strfactor = base64_encode($strfactor);
        }
        return [
            'strfactor' => substr($strfactor, 0, 8) . dechex($factor) . substr($strfactor, 8),
            'strdata' => $strdata,
        ];
    }

    private function decryptPass($strfactor, $strdata): string
    {
        $factor = hexdec(substr($strfactor, 8, 2));

        for ($i=1;$i<=$factor;$i++) {
            $strdata = base64_decode($strdata);
        }

        return $strdata;
    }

    private function extractCAData(Domain $domain): array
    {
        return $this->extractCertData($domain->getCertData());
    }

    private function extractCertData($certData): array
    {
        //$caKey = $caCertData['certdata']['privKey'];
        $key = $this->decryptPass($certData['certdata']['strfactor'], $certData['certdata']['strdata']);
        $privKey = $this->decryptKey($certData['certdata']['privKey'], $key);
        //dump($caCertData, $key, $caKey);

        return [
            'cert' => $certData['certdata']['cert'],
            'privKey' => $privKey,
        ];
    }

    public function extractX509Data(ServerCertificate $certificate): array
    {
        $certData = $certificate->getCertData();
        $cer = $certData['certdata']['cert'];
        return openssl_x509_parse($cer, false);
    }

    public function genPass(): string
    {
        return bin2hex(random_bytes(20));
    }

    public function setDomain(Domain $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function certDownload(string $category, array $params)
    {
        $stream = "";
        dump($params);
        // Mensaje común predeterminado
        $message = [
            'succes' => 'OK',
            'message' => 'El certificado se ha descargado correctamente',
        ];
        if ($category=='server') {
            list($certificate, $dtype) = $params;
            //$certData = $certificate->getCertData()['certdata'];
            $certData = $this->extractCertData($certificate->getCertData());
            dump($certData);
            $filename = $certificate->getDescription() . '.' . $certificate->getDomain()->getName();
            $filename .= '-' . $dtype . '.pem';
            if ($dtype=='chain') {
                $caCertData = $this->extractCAData($certificate->getDomain());
                $stream .= $caCertData['cert'];
                $stream .= $certData['cert'];
            } else {
                $stream .= $certData['cert'] . $certData['privKey'];
            }
        } elseif ($category=='client') {
                /*'client',
                [
                    'format' => $certificateform->getClickedButton()->getName(),
                    'setkey' => $formData
                ]*/
            $format = strtolower($params['format']);
            //list($format, $userdata) = $params;
            $user = $params['setkey'];
            $filename = $user->getEmail() . '.' . ($format=='pkcs12'?'pfx':'pem');
            $plainPassword = $user->getPlainPassword();
            //dump("format: " . $format, $user, $plainPassword, "filename: " . $filename);
            $certData = $this->extractCertData($user->getCertData());
            $caCertData = $this->extractCAData($user->getDomain());
            if ($format=='pem') {
                $stream = $certData['cert'] . $certData['privKey'];
            } else {
                openssl_pkcs12_export(
                    $certData['cert'],
                    $stream,
                    $certData['privKey'],
                    $plainPassword,
                    // AÑADIR CA PROVOCA ERROR DESCONOCIDO AL IMPORTAR
                    //[ 'extracerts' => $caCertData['cert']]
                );
                //$stream = $pfx;
                /*
                $stream = $this->exportPKCS12(
                    [
                        'certdata' => $certData,
                        'cacertdata' => $caCertData,
                        'plainpassword' => $plainPassword,
                        'filename' => $filename
                    ]
                );*/
            }
            //dd($caCertData, $certData, ($format=='pem'?$stream:''));
        } elseif ($category=='ca') {
            list($user, $dtype) = $params;
            $filename = $user->getDomain()->getName();
            $filename .= '-' . $dtype . '.pem';
            $caCertData = $this->extractCAData($user->getDomain());
            $stream .= $caCertData['cert'];
            if ($dtype=='certkey') {
                $stream .= $caCertData['privKey'];
            }

        } else {
            return [
                'error' => 'Error',
                'message' => 'Categoría incorrecta',
            ];
        }

        return $this->streamDownload($stream, $filename);

        return $message;
    }

    public function streamDownload($output, $filename): Response
    {
            $response = new Response($output);
            //dd($output, $filename);
            // Create the disposition of the file
            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename
            );

            // Set the content disposition
            $response->headers->set('Content-Disposition', $disposition);

            return $response;

    }

    public function __toString()
    {
        return '';
    }

}
