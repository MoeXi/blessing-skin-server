<?php
/**
 * @Author: printempw
 * @Date:   2016-03-13 11:53:47
 * @Last Modified by:   printempw
 * @Last Modified time: 2016-03-13 14:31:29
 */

interface EncryptInterface
{
    /**
     * Encrypt password, please define it to adapt to other encryption method
     *
     * @param  string $raw_passwd
     * @param  string $username
     * @return string, ecrypted password
     */
    public function encryptPassword($raw_passwd, $username="");

}
