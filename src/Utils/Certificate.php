<?php

namespace App\Utils;

use App\Dto\CertCommonDto;
use App\Dto\CertIntervalDto;
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

    const CONFFILE = '../public/custom/openssl.cnf';

    const PKEYCONFIGARGS = [
        "digest_alg" => "sha512",
        "private_key_bits" => 4096,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ];
    const BASICCERTCONFIG = [
        "digest_alg" => "sha256",
        "private_key_bits" => 4096,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ];
    const CACONFIGARGS = [
        "x509_extensions" => [
            'nsCertType' => 'sslCA, emailCA, objCA',
            'keyUsage' => 'keyCertSign,cRLSign',
            'basicConstraints'=> 'critical,CA:TRUE',
        ]
    ];

    public function addToIndex($certout): array
    {
        $cert = openssl_x509_parse($certout, false);
        //dump($cert);
        $key = $cert['subject']['commonName'] . '|' . $cert['serialNumber'] . '|' . $cert['hash'];
        return [$key => [
            'status' => 'V',
            'validTo' => $cert['validTo'],
            'serialNumber' => $cert['serialNumber'],
            'name' => $cert['name'],
            ]
        ];
    }

    private function dumpIndex(Domain $domain)
    {
        /*        The 6 fields are:

        0) Entry type. May be "V" (valid), "R" (revoked) or "E" (expired).
        Note that an expired may have the type "V" because the type has
        not been updated. 'openssl ca updatedb' does such an update.
        1) Expiration datetime.
        2) Revokation datetime. This is set for any entry of the type "R".
        3) Serial number.
        4) File name of the certificate. This doesn't seem to be used,
        ever, so it's always "unknown".
        5) Certificate subject name.
        */

        $caCertData = $domain->getCertData();
        $indexData = $caCertData['index'];
        $crlData = "";
        foreach ($indexData as $key => $value) {
            $crlData .= "\t" . $value['status'] .
                "\t" . $value['validTo'] .
                "\t" . (!empty($value['revokeTime'])??"") .
                "\t" . $value['serialNumber'] .
                "\t" . "unknown" .
                "\t" . $value['name']
                ;
        }
        $filename = '../public/crl/' . $domain->getName() . '_index.txt';
        file_put_contents($filename, $crlData);
    }

    public function newPrivateKey(): string
    {
        $res = openssl_pkey_new(self::PKEYCONFIGARGS);

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
                [ 'extracerts' => [$params['cacertdata']['cert']]]
            );
            openssl_pkcs12_read($pfx, $pfxout, $params['plainpassword']);
            //file_put_contents($f, $pfx);
        }
        //dd($pfxout);

        return $pfx;
    }

    private function extractFormCommonData($commonData): array|CertCommonDto
    {
        return $commonData;
    }

    private function extractFormIntervalDataDTO($dto): array|CertIntervalDto
    {
        $intervalData = $dto;
        $interval = $dto->getInterval();
        if ($interval->d != "0" || $interval->m != "0" || $interval->y != "0") {
            $end = new \DateTime($dto->getNotBefore()->format('Y-m-d'));
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
            $intervalData->setNotAfter($end);
        }

        $duration = $end->diff($dto->getNotBefore());
        $intervalData->setDuration($duration->format('%a'));

        return $intervalData;
    }

    private function extractFormIntervalData($form): array
    {
        $intervalData = $form;
        $interval = $form['interval'];
        $end = new \DateTime();
        if ($interval->d != "0" || $interval->m != "0" || $interval->y != "0") {
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
        //$commonData = $this->extractFormCommonData($form['common']);
        //$intervalData = $this->extractFormIntervalData($form['interval']);
/*
        $data = [
            'common' => $this->extractFormCommonData($form['common']), //commonData,
            'interval' => $this->extractFormIntervalData($form['interval']), //$intervalData,
        ];
*/
        $data = [
            'common' => $this->extractFormCommonData($form->getCommon()->toArray()), //commonData,
            'interval' => $this->extractFormIntervalData($form->getInterval()->toArray()), //$intervalData,
        ];

        return $data;
    }

    private function genCsr($dn, $privKey)
    {
        $csr = openssl_csr_new($dn, $privKey);

        openssl_csr_export($csr, $csrout);

        return $csr;
    }

    public static function convertUTCTime2Date($string)
    {
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
        $certout="";
        if (null!=$contents) {
            if (is_array($data['common']['plainPassword'])) {
                $u = $data['common']['plainPassword']['setkey'];
                $plainPassword = $u->getPlainPassword();
            } else {
                $plainPassword = $data['common']['plainPassword'];
            }
            //$plainPassword = $form->getCommon()->getPlainPassword()['setkey']->getPlainPassword();
            $crypted = ' ENCRYPTED';
            if (!strpos($contents, $crypted)) {
                $crypted =' RSA';
            }
            $begin = '-----BEGIN' . $crypted . ' PRIVATE KEY-----';
            $result = strpos($contents, $begin);
            if (false===$result) {
                //dump($begin, $result);
                return [
                    'error' => 'No se ha podido encontrar la clave privada'
                ];
            }
            $pem_data = substr($contents, strpos($contents, $begin)+strlen($begin));
            $end = '-----END' . $crypted . ' PRIVATE KEY-----';
            $pem_data = substr($pem_data, 0, strpos($pem_data, $end));
            //dump($plainPassword);
            $privKey = $begin. $pem_data . $end;
            if ($crypted) {
                $privKeyObject = openssl_pkey_get_private($privKey, $plainPassword);
                //dump($crypted, $privKeyObject);
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
            //dump($privKey, $certout, openssl_x509_parse($certout));
            $creqOptions = "Datos importados de fichero externo";
            unset($data['common']);
            unset($data['interval']);
        }

        if (!$certout) {
            $privKey = $this->newPrivateKey();
            if (is_array($data['common']['plainPassword'])) {
                $u = $data['common']['plainPassword']['setkey'];
                $plainPassword = $u->getPlainPassword();
            } else {
                $plainPassword = $data['common']['plainPassword'];
            }
            unset($data['common']['plainPassword']);
            //$data->getCommon()->setPlainPassword(null);
            //dump($data->getCommon());
            $common=[];
            foreach ($data['common'] as $dk => $dv) {
                if (null!=$dv&&!is_array($dv)) {
                    $common[$dk] = $dv;
                //} else {
                //    $common[$dk] = ' ';
                }
            }
            dump($data, $common, $plainPassword);
            //$csr = openssl_csr_new($data['common'], $privKey);
            $csr = openssl_csr_new($common, $privKey);

            $creqOptions = self::CACONFIGARGS;
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
        $data['serial'] = 100;

        //dump($data, "plainPassword: " . $plainPassword);

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
            /*
            dump($data['common']);
            $eUser = $data['common']['emailAddress'];
            $data['common']['commonName'] = $eUser->getFullName();
            $data['common']['emailAddress'] = $eUser->getEmail();
            $userData = [
                "emailAddress" => $data['common']['emailAddress']
            ];*/
            $extensions = 'client_ext';
        }
        $userData["commonName"]= $data['common']['commonName'];

        //$data['common']['organizationName'] = $caCertData['organizationName'];

        // Creamos una contraseña para cifrar la clave privada
        $plainPassword=$this->genPass();

        $privKey = $this->newPrivateKey();
        $dnRaw = $userData + $data['common']['subject'];
        $dn = [];
        foreach ($data['common']['subject'] as $key => $value) {
            if (is_string($value)) {
                // Forzamos la conversión a UTF-8 y eliminamos caracteres no válidos
                $dn[$key] = mb_convert_encoding($value, 'UTF-8', 'auto');
            }
        }
        //dd($dnRaw, $dn);

        $csr = openssl_csr_new($dn, $privKey);
        $serial = 100;
        if (!empty($this->domain->getCertData()['serial'])) {
            $serial = intval($this->domain->getCertData()['serial']);
        }
        //dump($csr, $caCertData, $serial);
        $creqOptions = array_merge(
            self::BASICCERTCONFIG,
            ['config' => self::CONFFILE],
            ['x509_extensions' => $extensions]
        );
        $signcert = openssl_csr_sign(
            $csr,
            $caCertData['cert'],
            $caCertData['privKey'],
            $data['interval']['duration'],
            $creqOptions,
            $serial
        );
        openssl_x509_export($signcert, $certout);
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
                'serial' => $serial,
            ],
            $cryptedPass
        );
        //dump($data);

        return $data;
    }

    private function cryptKey($privKey, $key, $cipher = "aes-128-gcm"): array
    {
        $original_plaintext="";
        if (in_array($cipher, openssl_get_cipher_methods())) {
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
        $cipher = $cipherdata['cipher'];
        $tag = $cipherdata['tag'];
        $cryptKey = $cipherdata['cryptKey'];
        $iv = $cipherdata['iv'];
        $privKey = openssl_decrypt(
            $cipherdata['cryptKey'],
            $cipherdata['cipher'],
            $key,
            $options=0,
            hex2bin($cipherdata['iv']),
            hex2bin($cipherdata['tag'])
        );

        //dump("decrypted Key: ". $privKey);
        return $privKey;
    }

    private function cryptPass($plainData, $stream): array
    {
        $factor = rand(16, 30);
        $strdata = $plainData;
        for ($i=1; $i<=$factor; $i++) {
            $strdata = base64_encode($strdata);
        }
        $strfactor = substr($stream, 20 + $factor, 10);
        for ($i=1; $i<=$factor-9; $i++) {
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

        for ($i=1; $i<=$factor; $i++) {
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
        $key = $this->decryptPass($certData['certdata']['strfactor'], $certData['certdata']['strdata']);
        $privKey = $this->decryptKey($certData['certdata']['privKey'], $key);
        //dump($certData, $key, $privKey);

        return [
            'cert' => $certData['certdata']['cert'],
            'privKey' => [$privKey, $key],
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

    public function manageClientForm($form)
    {
        $formData = $form->getData();
        $this->util->setDomain($formData['domain']);
        $user = $formData['common']['emailAddress'];
        $certData = $this->createClientCert($formData);
        $indexData = $this->addToIndex($certData['certdata']['cert']);
        //dd($certData, $indexData);
        $user->setCertData($certData);
        $userRepo->add($user, true);
        $this->repo->updateCAIndex($domain, $indexData);
        $this->addFlash('success', 'Se creo el certificado');

    }

    public function setDomain(Domain $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function certDownload(string $category, array $params)
    {
        $stream = "";
        // Mensaje común predeterminado
        $message = [
            'succes' => 'OK',
            'message' => 'El certificado se ha descargado correctamente',
        ];
        if ($category=='server') {
            list($certificate, $dtype) = $params;
            $certData = $this->extractCertData($certificate->getCertData());
            //dump($certData);
            $filename = $certificate->getDescription() . '.' . $certificate->getDomain()->getName();
            $filename .= '-' . $dtype . '.pem';
            if ($dtype=='chain') {
                $caCertData = $this->extractCAData($certificate->getDomain());
                $stream .= $caCertData['cert'];
                $stream .= $certData['cert'];
            } else {
                $stream .= $certData['cert'] . $certData['privKey'][0];
            }
        } elseif ($category=='client') {
            $format = strtolower($params['format']);
            //list($format, $userdata) = $params;
            $user = $params['setkey'];
            $filename = $user->getEmail() . '.' . ($format=='pkcs12'?'pfx':'pem');
            $plainPassword = $user->getPlainPassword();
            $certData = $this->extractCertData($user->getCertData());
            $caCertData = $this->extractCAData($user->getDomain());
            if ($format=='pem') {
                //dd($caCertData, $certData);
                $stream = $certData['cert'] . $certData['privKey'][0];
            } else {
                $caResource = openssl_x509_read($caCertData['cert']);
                openssl_pkcs12_export(
                    $certData['cert'],
                    $stream,
                    $certData['privKey'],
                    $plainPassword,
                    // AÑADIR CA PROVOCA ERROR DESCONOCIDO AL IMPORTAR
                    //[ 'extracerts' => [$caCertData['cert']]]
                    [ 'extracerts' => [$caResource]]
                );
            }
            //dd($caCertData, $certData, ($format=='pem'?$stream:''));
        } elseif ($category=='ca') {
            list($domain, $dtype) = $params;
            $filename = $domain->getName();
            $filename .= '_CA-' . $dtype . '.pem';
            $caCertData = $this->extractCAData($domain);
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
    }

    public function streamDownload($output, $filename): Response
    {
            $response = new Response($output);
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
