<?php
require_once('class.phpmailer.php');
include('class.smtp.php');
ini_set("display_errors", "On");

function get_config($key)
{
    $str = file_get_contents("./config.json");
    return json_decode($str,true)[$key];
}

function _log($notice, $infoObj = null) {
    //如果不是字符串,则先将其转换为json字符串
    if ($infoObj != null && !is_string($infoObj)) {
        $infoObj = json_encode($infoObj);
    }

    $log_path = "/tmp/mylog";

    file_put_contents($log_path, date('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND);
    file_put_contents($log_path, __FILE__ . __LINE__ . PHP_EOL, FILE_APPEND);
    file_put_contents($log_path, $notice . PHP_EOL, FILE_APPEND);
    if ($infoObj != null) {
        file_put_contents($log_path, $infoObj . PHP_EOL, FILE_APPEND);
    }
    file_put_contents($log_path, PHP_EOL, FILE_APPEND);

}


function generate_url($movie_name)
{
    $dytt_url = "http://s.dydytt.net/plus/search.php/?kwtype=0&keyword=".iconv('UTF-8','GBK',$movie_name) ;

    $dysf_url = "http://www.dysfz.net/key/".urlencode($movie_name)."/";

    $urls = array(  'dytt'=>$dytt_url,
        'dysf'=>$dysf_url);
    return $urls;
}

function search_movie($site,$movie_name,$url)
{
    error_reporting(E_ALL);
    date_default_timezone_set('Asia/Shanghai');//设定时区东八区

    $curl = curl_init($url);

    curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            //ENCODING为空表示接受所有允许的编码
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER=>'1',
        )
    );
    $content = curl_exec($curl);
    return filter_html($site,$movie_name,$url,$content);
}

function filter_html($site_name,$movie_name,$url,$html_content)
{
    switch ($site_name)
    {
        case "dytt":
            $html_content = mb_convert_encoding($html_content,'UTF-8','GBK');
            $record_pos = strpos($html_content,"条记录");

            if ($record_pos)
            {
                $count =  intval(substr($html_content,$record_pos-1,1));
                if ($count>0)
                {
                    return true;
                }
            }

            break;
        case "dysf":
            $record_pos = strpos($html_content,"部相关电影");
            if ($record_pos)
            {
                $count = intval(substr($html_content,$record_pos-1,1));
                if ($count>0)
                {
                    return true;
                }
            }

        default:
            break;
            return false;
    }

}

function postmail($to,$subject = '',$body = ''){

    $mail             = new PHPMailer();
    $conf = get_config("mail");

    $mail->CharSet=$conf['CharSet'];
    $mail->IsSMTP=$conf['IsSMTP'];
    $mail->SMTPDebug=1;
    $mail->SMTPAuth=true;
    $mail->SMTPSecure=$conf['SMTPSecure'];
    $mail->Host=$conf['Host'];
    $mail->Port=$conf['Port'];
    $mail->Username   = $conf['Username'];
    $mail->Password   = $conf['Password'];
    $mail->SetFrom($conf['FromMail'],$conf['FromName']);
    //    $mail->AddReplyTo('xxx@xxx.xxx','who');
    $mail->Subject    = $subject;
    $mail->AltBody    = $conf['AltBody'];
    $mail->MsgHTML($body);
    $mail->AddAddress($to, 'movie hunter');
    $mail->Mailer = "smtp";

    if(!$mail->Send()) {
        echo 'Mailer Error: ' . $mail->ErrorInfo;
        return false;
    }
    _log("send mail success");

    echo "Message sent!恭喜，邮件发送成功！";
    return true;
}

_log("start scan...");

$_movie_list=get_config("movie");
foreach ($_movie_list as $user =>$movies)
{
    foreach ($movies as $movie)
    {
        $urls = generate_url($movie);
        foreach ($urls as $site=>$url)
        {
            $is_find = search_movie($site,mb_convert_encoding($movie,"GBK"),$url);
            if ($is_find)
            {
                postmail($user,mb_convert_encoding($movie,"GBK"),$url);
            }
            else
            {
                echo "暂未找到相关资源\n";
            }

        }
    }
}

_log("stop scan...");


?>