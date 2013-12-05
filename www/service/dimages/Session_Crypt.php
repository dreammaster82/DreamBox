<?php
  
class session_crypt
{
	var $iv =  '12345623577332927465733020238473';
	var $key =  '12345623577332927465733020238473';

	var $mcrypt;

	function session_crypt() 
	{
		$this->mcrypt = mcrypt_module_open('rijndael-256', '', 'ofb', '');
		//print_r($this->mcrypt);
	}

	function encode($original) 
	{
		//mcrypt_generic_init($this->mcrypt, $this->key256, $this->iv);
		//$encoded = mcrypt_generic($this->mcrypt, $this->pack($original));

		//mcrypt_generic_deinit($this->mcrypt);

		//return base64_encode($encoded);

		/* Создать IV и определить длину keysize */
		//$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($this->mcrypt), MCRYPT_DEV_RANDOM);
		$ks = mcrypt_enc_get_key_size ($this->mcrypt);

		/* Создать ключ */
		$key = substr(md5 ($this->key), 0, $ks);

		/* Инициализировать шифрование */
		mcrypt_generic_init ($this->mcrypt, $key, $this->iv);

		/* Шифровать данные */
		$encrypted = mcrypt_generic ($this->mcrypt, $original);
		$this->destruct();

		return $encrypted;
	}

	function decode($encrypted) 
	{
		//mcrypt_generic_init($this->mcrypt, $this->key256, $this->iv);
		//$decrypted = @mdecrypt_generic($this->mcrypt, base64_decode($crypted));
		//mcrypt_generic_deinit($this->mcrypt);
		//return @$this->unpack($decrypted);

		/* Создать IV и определить длину keysize */
		//$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($this->mcrypt), MCRYPT_DEV_RANDOM);
		$ks = mcrypt_enc_get_key_size ($this->mcrypt);

		/* Создать ключ */
		$key = substr(md5 ($this->key), 0, $ks);

		/* Инициализировать модуль шифрования для дешифрования */
		mcrypt_generic_init ($this->mcrypt, $key, $this->iv);

		/* Дешифровать шифрованную строку */
		$decrypted = mdecrypt_generic ($this->mcrypt, $encrypted);

		/* Закрыть дескриптор дешифрования и закрыть модуль */
		mcrypt_generic_deinit ($this->mcrypt);

		$this->destruct();

		return $decrypted;
	}
    
	function destruct() 
	{
		mcrypt_module_close($this->mcrypt);
	}
}

?>