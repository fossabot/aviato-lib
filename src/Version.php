<?php
/*
 * License
 *
 * @author Aviato Soft
 * @copyright 2014-present Aviato Soft. All Rights Reserved.
 * @license GNUv3
 * @version 00.07.03
 * @since  2021-12-14 20:01:10
 *
 */
declare(strict_types = 1);
namespace Avi;

const AVI_MAJOR = '00';

const AVI_MINOR = '07';

const AVI_PATCH = '03';

const AVI_DATE = '2021-12-14 20:01:10';

const AVI_JS_MD5 = '2a27faf53ecccaf688e9db1bde167f3c';

/**
 * Version class
 *
 * @author aviato-vasile
 *
 */
class Version
{


	/**
	 *
	 * @return string formated build version using notation {Major}.{Minor}.{Patch}
	 */
	public static function get()
	{
		return AVI_MAJOR.'.'.AVI_MINOR.'.'.AVI_PATCH;
	}


	/**
	 * Return MD5 hash of aviato.js
	 *
	 * @return string
	 */
	public static function getJsMd5()
	{
		return AVI_JS_MD5;
	}
}
?>