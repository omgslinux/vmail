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

    public function new_CA_key(Domain $domain)
    {
        // # generate aes encrypted private key
        // openssl genrsa -aes256 -out $CANAME.key 4096
        $pkey = openssl_pkey_new('secret password');
        $spkac = openssl_spki_new($pkey, 'testing');

    }

    public static function getFormData($form): array
    {
        $data = $form;
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
            $data['notAfter'] = $end;
        }

        return $data;
    }


}
