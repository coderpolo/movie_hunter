<?php
require_once('class.phpmailer.php');
include('class.smtp.php');
include('config.php');
ini_set("display_errors", "On");

function logOnTmp($notice, $infoObj = null) {
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



$movie_list = get_moive_list();


function generate_url($movie_name)
{
    $dytt_url = "http://s.dydytt.net/plus/search.php/?kwtype=0&keyword=".iconv('UTF-8','GBK',$movie_name) ;

    $dysf_url = "http://www.dysfz.net/key/".urlencode($movie_name)."/";

    $urls = array('dytt'=>$dytt_url,
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
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER=>'1',
        )
    );

    $content = curl_exec($curl);
    filter_html($site,$movie_name,$url,$content);
}

function filter_html($site_name,$movie_name,$url,$html_content)
{
    $conf = get_conf();

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
                    postmail($conf['to'],$movie_name,$url);
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
                    postmail($conf['to'],$movie_name,$url);
                }
            }

        default:
            break;

    }

}

function postmail($to,$subject = '',$body = ''){

    $mail             = new PHPMailer();
    $conf = get_conf();
//    $body            = eregi_replace("[\]",'',$body);

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
    logOnTmp("send mail success");

    echo "Message sent!恭喜，邮件发送成功！";
    return true;
}

logOnTmp("start scan...");
foreach ($movie_list as $movie_name)
{
    $urls = generate_url($movie_name);
    foreach ($urls as $site=>$url)
    {
        search_movie($site,mb_convert_encoding($movie_name,"GBK"),$url);
    }
}
logOnTmp("stop scan...");

?>