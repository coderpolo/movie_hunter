<?php

    function get_moive_list()
    {
        return array("movie_name");
    }

    function get_conf()
    {
        return array('CharSet'=>"GBK",
            'IsSMTP'=>true,
            'SMTPDebug'=>true,
            'SMTPAuth'=>true,
            'SMTPSecure'=>"ssl",
            'Host'=>'smtp.163.com',
            'Port'=>465,
            'Username'=>'account_name',
            'Password'=>'pwd',
            'FromMail'=>'**@**.com',
            'FromName'=>'movie_hunter',
            'AltBody'=>'To view the message, please use an HTML compatible email viewer!',
            'to'=>'**@**.com',
        );
    }




