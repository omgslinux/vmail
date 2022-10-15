<?php

namespace App\Utils;

#use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class PassEncoder implements PasswordHasherInterface
{
    const SPECIALCHARS = '~!@#$%^&*_-+=`|\(){}[]:;"\'<>,.?/';

  	/**
  	 * Parameters used for crypt: const name, salt prefix, len of random salt, postfix
  	 *
  	 * @var array
  	 */
  	private $crypt_params = array(	//
  		'crypt' => array('', 2, ''),
  		'ext_crypt' => array('_J9..', 4, ''),
  		'md5_crypt' => array('$1$', 8, '$'),
  		'blowfish_crypt' => array('$2a$12$', 22, ''),	// $2a$12$ = 2^12 = 4096 rounds
  		'sha256_crypt' => array('$5$', 16, '$'),	// no "round=N$" --> default of 5000 rounds
  		'sha512_crypt' => array('$6$', 16, '$'),	// no "round=N$" --> default of 5000 rounds
  	);

  	private	$random_char =
      [
  			'0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f',
  			'g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v',
  			'w','x','y','z','A','B','C','D','E','F','G','H','I','J','K','L',
  			'M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'
  		]
    ;

    private $cryptType;
    private $saltPrefix;

    public function __construct($type='sha512_crypt')
    {
        $this->setType($type);
    }

    public function setType($type)
    {
        $this->cryptType=$type;
        //$this->setSalt($type);
    }

    public function encodePassword($raw, $saltPrefix=null)
    {
        // Implementamos nuestro encoder personalizado
        if (null==$saltPrefix) $saltPrefix=$this->getSaltPrefix($this->cryptType).md5(md5($raw));
        //die(dump($raw, $this->cryptType, "salt: ".$this->getSaltPrefix($this->cryptType),$saltPrefix));
		return crypt($raw, $saltPrefix);
        //return hash('sha1', $salt . $raw);
    }

    public function isPasswordValid($encoded, $raw, $salt=null)
    {
				//list($const, $prefix, $len, $postfix) = self::$crypt_params[$type];
        //if (is_null($salt)) $salt=$this->getSalt();
        return $encoded === $this->encodePassword($raw, $salt);
    }

    public function getSaltPrefix($type)
    {
        if (is_null($this->saltPrefix)) {
            $this->checkType($type);
        }

        return $this->saltPrefix;
    }

    public function setSaltPrefix($prefix)
    {
        $this->saltPrefix=$prefix;

        return $this;
    }

    private function checkType($type)
    {
        //$salt='';
				list($prefix, $chars, $suffix) = $this->crypt_params[$type];
        // return $this->randomstring($chars).$suffix;
        //$salt=$prefix;
        switch (strtolower($type)) {
          case 'sha512_crypt':
			      if (!(defined('CRYPT_SHA512') && constant('CRYPT_SHA512') == 1)) {
                return $this->checkType('sha256_crypt');
            }
            break;
          case 'sha256_crypt':
            if (!(defined('CRYPT_SHA256') && constant('CRYPT_SHA256') == 1)) {
                return $this->checkType('md5_crypt');
            }
            break;
          case 'md5_crypt':
            if (!(defined('CRYPT_MD5') && constant('CRYPT_MD5') == 1)) {
                return $this->checkType('ext_crypt');
            }
            break;
          case 'ext_crypt':
            if (!(defined('CRYPT_EXT_DES') && constant('CRYPT_EXT_DES') == 1)) {
                return $this->checkType('crypt');
            }
            break;
          case 'crypt':
            if (!(defined('CRYPT_STD_DES') && constant('CRYPT_STD_DES') == 1)) {
                return false;
            }
            break;

          }
        $this->setSaltPrefix($prefix);
        $this->type=$type;

        return $type;
    }

    public function getRandomString($size, $use_specialchars=false)
    {
      // we need special chars
      if ($use_specialchars)
      {
        $random_char = array_merge($this->random_char, str_split(str_replace('\\', '', self::SPECIALCHARS)), $random_char);
      }

      // use cryptographically secure random_int available in PHP 7+
      $func = function_exists('random_int') ? 'random_int' : 'mt_rand';

      $s = '';
      for ($i=0; $i < $size; $i++)
      {
        $s .= $random_char[$func(0, count($random_char)-1)];
      }
      return $s;
    }

    public function hash(string $plainPassword): string
    {
        // Tiene que devolver la $plainPassword encriptada
        return $this->encodePassword($plainPassword);
    }

    public function verify(string $hashedPassword, string $plainPassword): bool
    {
        // Tiene que comparar la plain y la hashed y devolver true o false
        return $hashedPassword === $this->encodePassword($plainPassword);
    }

    public function needsRehash(string $encoded): bool
    {
        return false;
    }

}
