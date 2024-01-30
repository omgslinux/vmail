<?php

namespace App\Utils;

use App\Entity\Domain;
use App\Entity\User;
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
        'config' => 'file://' . self::confFile,
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
    const ServerExtensionsArgs0 = [
        'nsCertType' => 'server',
        'subjectAltName' => 'DNS:CN <Update with CN when creating certificate>',
        'extendedKeyUsage' => 'serverAuth',
        'keyUsage' => 'digitalSignature, nonRepudiation, keyEncipherment, keyAgreement',
        'basicConstraints'=> 'critical,CA:FALSE',
    ];
    const ServerExtensionsArgs = [
        'nsCertType' => 'server',
        'extendedKeyUsage' => 'serverAuth',
        'keyUsage' => 'digitalSignature, nonRepudiation, keyEncipherment, keyAgreement',
        'basicConstraints'=> 'critical,CA:FALSE',
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

    public function createCACert($form): array
    {
        $data = $this->extractFormData($form);
        $privKey = $this->new_private_key();

        $csr = openssl_csr_new($data['common'], $privKey);

        $creqOptions = self::CAConfigArgs;
        $signcert = openssl_csr_sign($csr, null, $privKey, $data['interval']['duration'], $creqOptions);
        openssl_x509_export($signcert, $certout);

        // Generamos una contraseña
        $plainPassword = $this->genPass();
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
        $data = $this->extractFormData($form, 'client');
    }

    public function createServerCert($form)
    {
        return $this->createSignedCert($form, 'server');
    }

    private function createSignedCert($form, $type): array
    {
        // Obtenemos el certificado y la clave sin cifrar
        $caCertData = $this->extractCAData($this->domain);
        $data = $this->extractFormData($form);
        if ($type=='server') {
            $extensions = 'v3_req';
            $data['common']['commonName'] .= '.' . $this->domain->getName();
        } elseif ($type=='client') {
            $extensions = 'ext_client';
            $data['common']['emailAddress'] .= '@' . $this->domain->getName();
        }

        $privKey = $this->new_private_key();
        $csr = openssl_csr_new($data['common'], $privKey);

        $creqOptions = [];
        //$creqOptions = self::basicCertConfg;
        $creqOptions['x509_extensions'] = $extensions;
        //dump($caCertData, $creqOptions);
        $signcert = openssl_csr_sign($csr, $caCertData['cert'], $caCertData['privKey'], $data['interval']['duration'], $creqOptions);
        openssl_x509_export($signcert, $certout);

        // Creamos una contraseña para cifrar la clave privada
        $plainPassword=$this->genPass();
        // Ciframos la contraseña
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
        $caCertData = $domain->getCertData();
        //$caKey = $caCertData['certdata']['privKey'];
        $key = $this->decryptPass($caCertData['certdata']['strfactor'], $caCertData['certdata']['strdata']);
        $caKey = $this->decryptKey($caCertData['certdata']['privKey'], $key);
        //dump($caCertData, $key, $caKey);

        return [
            'cert' => $caCertData['certdata']['cert'],
            'privKey' => $caKey,
        ];
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
        if ($category=='server') {
            list($certificate, $dtype) = $params;
            $certData = $certificate->getCertData()['certdata'];
            //dump($certData);
            $filename = $certificate->getDescription() . '.' . $certificate->getDomain()->getName();
            $filename .= '-' . $dtype . '.pem';
            if ($dtype=='chain') {
                $caCertData = $this->extractCAData($certificate->getDomain());
                $stream .= $caCertData['cert'];
                $stream .= $certData['cert'];
            } else {
                $plainPassword = $this->decryptPass($certData['strfactor'], $certData['strdata']);
                $privKey = $this->decryptKey($certData['privKey'], $plainPassword);
                $stream .= $certData['cert'];
                $stream .= $privKey;
            }
            $message = [
                'succes' => 'OK',
                'message' => 'El certificado se ha descargado correctamente',
            ];
        } elseif ($category=='client') {

        } elseif ($category=='ca') {

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

}
