<?php

//Copyright © 2009,2015,2022  Siggi Bjarnason.
//
//This program is free software: you can redistribute it and/or modify
//it under the terms of the GNU General Public License as published by
//the Free Software Foundation, either version 3 of the License, or
//(at your option) any later version.
//
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.
//
//You should have received a copy of the GNU General Public License
//along with this program.  If not, see <http://www.gnu.org/licenses/>

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function ShowErrHead()
{
  $ROOTPATH = $GLOBALS['ROOTPATH'];
  $HeadImg = $GLOBALS['HeadImg'];
  $CSSName = $GLOBALS['CSSName'];
  $ErrMsg = $GLOBALS['ErrMsg'];
  $ImgHeight = "150";
  $imgname = $ROOTPATH . $HeadImg;
  print "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"\n\"http://www.w3.org/TR/html4/loose.dtd\">";
  print "<HTML>\n<HEAD>\n<title>\nTechnical Difficulties\n</title>\n";
  print "<link href=\"$CSSName\" rel=\"stylesheet\" type=\"text/css\">\n</HEAD>\n";
  print "<body>\n";
  print "<div id=\"left\"></div>";
  print "<div id=\"right\"></div>";
  print "<div id=\"top\"></div>";
  print "<div id=\"bottom\"></div>";
  print "<div class=\"BlacktblHead\">";
  print "<TABLE border=\"0\" cellPadding=\"4\" cellSpacing=\"0\">\n";
  print "<TR>\n";
  print "<TD align=\"center\" vAlign=\"middle\">\n";
  print "<img border=\"0\" src=\"$imgname\" align=\"center\" height=\"$ImgHeight\">\n";
  print "</TD>\n";
  print "</TR>\n";
  print "</TABLE>\n</div>\n</div>\n";
  print "<p class=\"Header1\">Technical Difficulties</p>\n";
  print "<p class=\"Attn\" align=center>$ErrMsg</p>\n";
  exit;
}

function UpdateSQL ($strQuery,$type)
{
  $DefaultDB = $GLOBALS['DefaultDB'];
  $dbh = $GLOBALS['dbh'];
  $SupportEmail = $GLOBALS['SupportEmail'];
  $FromEmail = $GLOBALS['FromEmail'];

  if ($dbh->query ($strQuery))
  {
    $NumAffected = $dbh->affected_rows;
    // print "<p class=\"MainText\">Database $type of $NumAffected record successful<br>\n";
    return TRUE;
  }
  else
  {
    $strError = "Database $type failed. Error (". $dbh->errno . ") " . $dbh->error . "\n";
    If ($dbh->errno =="1451")
    {
      print "\n<p class=\"error\">Unable to delete the selected value as it is still in use in other parts of the system</p>\n";
    }
    else
    {
      print "\n<p class=\"error\">Database $type failed: </p>\n";
      error_log($strError);
      error_log("SQL: $strQuery");
      if(EmailText("$SupportEmail","Automatic Error Report","$strError\n$strQuery",$FromEmail))
      {
        print "<p class=\"error\">We seem to be experiencing technical difficulties. We have been notified. " .
        "Please try again later. Thank you.</p>";
      }
      else
      {
        $strError = str_replace("\n","<br>\n",$strError);
        print "<p class=\"error\">We seem to be experiencing technical difficulties. " .
                "Please send us a message at $SupportEmail with information about " .
                "what you were doing.</p>";
      }
    }
    return FALSE;
  }
}

function CallSP ($strQuery)
{
  $DefaultDB = $GLOBALS['DefaultDB'];
  $dbh = $GLOBALS['dbh'];
  $SupportEmail = $GLOBALS['SupportEmail'];
  $FromEmail = $GLOBALS['FromEmail'];

  if ($dbh->query ($strQuery))
  {
    print "Database update successful<br>\n";
    return TRUE;
  }
  else
  {
    $strError = 'Database update failed. Error ('. $dbh->errno . ') ' . $dbh->error;

    print "\nDatabase update failed: \n";
    error_log($strError);
    error_log("SQL: $strQuery");
    if(EmailText("$SupportEmail","Automatic Error Report","$strError\n$strQuery",$FromEmail))
    {
      print "We seem to be experiencing technical difficulties. We have been notified. " .
      "Please try again later. Thank you.<br>";
    }
    else
    {
      $strError = str_replace("\n","<br>\n",$strError);
      print "We seem to be experiencing technical difficulties. " .
            "Please send us a message at $SupportEmail with information about " .
            "what you were doing.</p>";
    }
    return FALSE;
  }
}

function CallSPNoOut ($strQuery)
{
  $dbh = $GLOBALS['dbh'];

  if ($dbh->query ($strQuery))
  {
      return TRUE;
  }
  else
  {
    $strError = 'Database update failed. Error ('. $dbh->errno . ') ' . $dbh->error . "\n";

    error_log($strError);
    error_log($strQuery);
    return FALSE;
  }
}

function CleanSQLInput ($InVar)
{
  $InVar = str_replace("\\","",$InVar);
  $InVar = str_replace("'","\'",$InVar);
  $InVar = str_replace(";","",$InVar);
  return $InVar;
}

function CleanReg ($InVar)
{
  $InVar = strip_tags($InVar);
  $InVar = str_replace("\\","",$InVar);
  $InVar = str_replace("=","",$InVar);
  $InVar = str_replace('"',"",$InVar);
  $InVar = str_replace("'","",$InVar);
  $InVar = str_replace(";","",$InVar);
  return $InVar;
}

function SpamDetect ($InVar)
{
  $dbh = $GLOBALS['dbh'];
  $SupportEmail = $GLOBALS['SupportEmail'];
  $FromEmail = $GLOBALS['FromEmail'];
  $strRemoteIP = $GLOBALS['strRemoteIP'];
  $strURLRegx = "#(http://)|(a href)#i";
  if (preg_match($strURLRegx,$InVar))
  {
    $InVar = str_replace("'","\'",$InVar);
    $strQuery = "INSERT INTO tblSpamLog (vcIPAddress, vcContent) VALUES ('$strRemoteIP', '$InVar');";
    if (!$dbh->query ($strQuery))
    {
      $strError = 'Database insert failed. Error ('. $dbh->errno . ') ' . $dbh->error . "\n";
      $strError .= "$strQuery\n";
      error_log($strError);
      EmailText("$SupportEmail","Automatic Error Report",$strError,"From:$SupportEmail");
    }
    return TRUE;
  }
  else
  {
    return FALSE;
  }
}

function quarterByDate($date)
{
  return (int)floor(date('m', strtotime($date)) / 3.1) + 1;
}

function QuarterYear($date)
{
  $QNum = quarterByDate($date);
  $YearNum = date('Y', strtotime($date));
  return "Q$QNum $YearNum";
}

function Log_BackTrace ($BackTrace, $msg)
{
  error_log("");
  error_log("$msg starting debug backtrace");
  foreach($BackTrace as $key => $value)
  {
    $Level = intval($key)+1;
    error_log("Stack level $Level");
    foreach($value as $key => $value)
    {
      if (is_array($value))
      {
        $pre = $key;
        foreach($value as $key => $value)
        {
          error_log("  $pre [$key] : $value");
        }
    }
      else
      {
        error_log("$key : $value");
      }
    }
  }
  error_log("$msg ending debug backtrace");
}

function Log_Session ($msg)
{
  $Session=$_SESSION;
  error_log("");
  error_log("$msg start dump of SESSION array");
  foreach($Session as $key => $value)
  {
    if (is_array($value))
    {
      $pre = $key;
      foreach($value as $key => $value)
      {
        error_log("$pre [$key] : $value");
      }
    }
    else
    {
      error_log("$key : $value");
    }
  }
  error_log("$msg ending dump of SESSION array");
}

function Log_Array ($array, $msg)
{
  error_log("");
  error_log("$msg start dump of array");
  foreach($array as $key => $value)
  {
    if (is_array($value))
    {
      $pre = $key;
      foreach($value as $key => $value)
      {
        error_log("$pre [$key] : $value");
      }
    }
    else
    {
      error_log("$key : $value");
    }
  }
  error_log("$msg ending dump of array");
}

function return_bytes($val)
{
  $val = trim($val);
  $last = strtolower($val[strlen($val)-1]);
  switch(intval($last))
  {
    // The 'G' modifier is available since PHP 5.1.0
    case 'g':
      $val *= 1024;
    case 'm':
      $val *= 1024;
    case 'k':
      $val *= 1024;
  }
  return $val;
}

function with_unit($val)
{
  $Units[0]="";
  $Units[1]="KB";
  $Units[2]="MB";
  $Units[3]="GB";
  $Units[4]="TB";
  $val = trim($val);
  $tmp = $val/1024;
  $i=0;
  while ($tmp > 1)
  {
    $tmp = $val/1024;
    if ($tmp > 1)
    {
      $val=$tmp;
      $tmp = $val/1024;
      $i++;
    }
    else
    {
      break;
    }
  }
  return number_format($val, 2) . " " . $Units[$i];
}

function copyemz($file1,$file2)
{
  $contentx=@file_get_contents($file1);
  $openedfile = fopen($file2, "w");
  fwrite($openedfile, $contentx);
  fclose($openedfile);
  if ($contentx === FALSE)
  {
    $status=false;
  }
  else
  {
    $status=true;
  }
  return $status;
}

function codeToMessage($code)
{
  $MaxFileSize = ini_get('upload_max_filesize');
  switch ($code)
  {
    case UPLOAD_ERR_INI_SIZE:
        $message = "The uploaded file exceeds file size limit of $MaxFileSize";
        break;
    case UPLOAD_ERR_FORM_SIZE:
        $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
        break;
    case UPLOAD_ERR_PARTIAL:
        $message = "The uploaded file was only partially uploaded";
        break;
    case UPLOAD_ERR_NO_FILE:
        $message = "No file was uploaded";
        break;
    case UPLOAD_ERR_NO_TMP_DIR:
        $message = "Missing a temporary folder";
        break;
    case UPLOAD_ERR_CANT_WRITE:
        $message = "Failed to write file to disk";
        break;
    case UPLOAD_ERR_EXTENSION:
        $message = "File upload stopped by extension";
        break;
    default:
        $message = "Unknown upload error # $code";
        break;
    }
    return $message;
}

function format_phone_us($phone)
{
  // note: strip out everything but numbers
  $phone = preg_replace("/[^0-9]/", "", $phone);
  $length = strlen($phone);
  switch($length)
  {
    case 0:
        return 'notphone';
    case 7:
        return preg_replace("/([0-9]{3})([0-9]{4})/", "$1-$2", $phone);
        break;
    case 10:
        return preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "($1) $2-$3", $phone);
        break;
    case 11:
        $phone=  substr($phone, 1);
        return preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "($1) $2-$3", $phone);
        break;
    default:
        return $phone;
        break;
  }
}

function StripHTML ($content)
{
  $content = str_replace("<th>","|",$content);
  $content = str_replace("</th>","",$content);
  $content = str_replace("<td>","|",$content);
  $content = str_replace("</td>","",$content);
  $unwanted = ['style','script'];
  foreach ( $unwanted as $tag )
  {
    $content = preg_replace( "/(<$tag>.*?<\/$tag>)/is", '', $content );
  }
  unset( $tag );
  $content = str_replace("\r","",$content);
  $content = strip_tags($content);
  $content = preg_replace("/([\r\n]{4,}|[\n]{2,}|[\r]{2,})/", "\n", $content);
  return trim($content);
}

function SendHTMLAttach ($strHTMLMsg, $FromEmail, $toEmail, $strSubject, $strFileName = "", $strAttach = "", $strAddHeader = "", $strFile2Attach = "")
{

  require_once("PHPMailer/Exception.php");
  require_once("PHPMailer/PHPMailer.php");
  require_once("PHPMailer/SMTP.php");

  $strHTMLMsg = preg_replace("/(<script>.*?<\/script>)/is","",$strHTMLMsg);
  $strHTMLMsg = str_replace("\r","\n",$strHTMLMsg);
  $strHTMLMsg = str_replace("\n\n","\n",$strHTMLMsg);

  $ToParts   = explode("|",$toEmail);
  $FromParts = explode("|",$FromEmail);
  $strTxtMsg = StripHTML($strHTMLMsg);

  // create a new PHPMailer object
  $mail = new PHPMailer();

  // configure an SMTP Settings
  $mail->isSMTP();
  $mail->Host = $GLOBALS['MailHost'];
  $mail->Port = $GLOBALS['MailHostPort'];
  $mail->SMTPAuth = true;
  $mail->Username = $GLOBALS['MailUser'];
  $mail->Password = $GLOBALS['MailPWD'];
  if (strtolower($GLOBALS['UseSSL'])=="true")
  {
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
  }
  else
  {
    if (strtolower($GLOBALS['UseStartTLS'])=="true")
    {
      $mail->SMTPSecure = 'tls';
    }
    else
    {
      $mail->SMTPSecure = "";
    }
  }

  // Construct email message
  $mail->setFrom($FromParts[1], $FromParts[0]);
  $mail->addAddress($ToParts[1], $ToParts[0]);
  $mail->Subject = $strSubject;
  $mail->isHTML(TRUE);
  $mail->Body = $strHTMLMsg;
  $mail->AltBody = $strTxtMsg;

  // add string attachment
  if ($strAttach != "" and $strFileName != "")
  {
    $mail->addStringAttachment($strAttach, $strFileName);
  }

  // Process any custom headers
  if (is_array($strAddHeader))
  {
    foreach ($strAddHeader as $header)
    {
      $mail->addCustomHeader($header);
    }
  }
  else
  {
    if ($strAddHeader != "")
    {
      $mail->addCustomHeader($strAddHeader);
    }
  }

  // Attach file attachment
  if ($strFile2Attach != "")
  {
    $mail->addAttachment($strFile2Attach);
  }

  // send the message

  if(!$mail->send())
  {
    return "Message could not be sent. Mailer Error: " . $mail->ErrorInfo;
  }
  else
  {
    return "Message has been sent";
  }
}

function EmailText($to,$subject,$message,$from)
{
  $strFileName = "";
  $strAttach = "";
  $from = str_replace("<", "|", $from);
  $from = str_replace(">", "", $from);
  $from = str_replace("From:", "", $from);
  if (stripos($from, "|")===FALSE)
  {
    $iPos = stripos($from, "@");
    $name = substr($from, 0,$iPos);
    $from = "$name|$from";
  }
  $to = str_replace("<", "|", $to);
  $to = str_replace(">", "", $to);
  if (stripos($to, "|")===FALSE)
  {
    $iPos = stripos($to, "@");
    $name = substr($to, 0,$iPos);
    $to = "$name|$to";
  }
  $message = str_replace("\n", "<br>\n", $message);
  $response = SendHTMLAttach ($message, $from, $to, $subject, $strFileName, $strAttach);
  if ($response == "Message has been sent")
  {
    return TRUE;
  }
  else
  {
    return FALSE;
  }
}

function FetchKeylessStatic ($arrNames)
{
  # $arrNames is an array of the secret names to be fetched
  # Returns an associated array with the secret name as key and the secret as the value
  # Requires AccessID and Accesskey as environment variables
  $AccessID = getenv("KEYLESSID");
  $AccessKey = getenv("KEYLESSKEY");
  $APIEndpoint = "https://api.akeyless.io";

  $PostData = array();
  $PostData['access-type'] = 'access_key';
  $PostData['access-id'] = "$AccessID";
  $PostData['access-key'] = "$AccessKey";
  $jsonPostData = json_encode($PostData);

  $Service = "/auth";
  $url = $APIEndpoint.$Service;
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonPostData);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl, CURLOPT_HTTPHEADER, array('accept: application/json','Content-Type: application/json'));
  $response = curl_exec($curl);
  curl_close($curl);
  $arrResponse = json_decode($response, TRUE);
  if (array_key_exists("error",$arrResponse))
  {
    error_log("Failed to authenticate to the AKEYLESS system. ".$arrResponse["error"]);
    return $arrResponse;
  }

  $token = $arrResponse["token"];

  $PostData = array();
  $PostData["token"] = $token;
  $PostData["names"] = $arrNames;
  $jsonPostData = json_encode($PostData);

  $Service = "/get-secret-value";
  $url = $APIEndpoint.$Service;
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonPostData);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl, CURLOPT_HTTPHEADER, array('accept: application/json','Content-Type: application/json'));
  $response = curl_exec($curl);
  curl_close($curl);
  return json_decode($response, TRUE);
}

function FetchDopplerStatic ($strProject,$strConfig)
{
  # $strProject is a simple string with the name of the Doppler Project holding your secret
  # $strConfig is a simple string with the name of the configuration to use
  # Returns an associated array with top level key of success, indicating if the fetch was successful or not
  # If success = true, all secrets will be under a top level key of secrets
  # with the secret name as key and the secret as the value
  # If success = false, there will a array of messages under top level key of messages with error messages
  # Requires DopplerKEY as environment variables
  $AccessKey = getenv("DOPPLERKEY");
  $APIEndpoint = "https://api.doppler.com";
  $Service = "/v3/configs/config/secrets";
  $method = "GET";

  $Param = array();
  $Param['project'] = $strProject;
  $Param['config'] = $strConfig;

  $url = $APIEndpoint.$Service . '?' . http_build_query($Param);
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_USERPWD, "$AccessKey:");
  curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_HTTPHEADER, array('accept: application/json'));
  curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
  $response = curl_exec($curl);
  curl_close($curl);
  $arrResponse = json_decode($response, TRUE);
  return json_decode($response, TRUE);
}

function SendTwilioSMS ($msg,$number)
{
  # $msg is a simple string with the message you wish to send
  # $number is the phone number to send it to
  # Returns success/failure
  # Utilizes Doppler for API Key and other info

  $arrSecretValues = FetchDopplerStatic($GLOBALS["DopplerProj"],$GLOBALS["DopplerConf"]);
  if (array_key_exists("secrets",$arrSecretValues))
  {
    $TwilioToken = $arrSecretValues["secrets"]["TWILIO_KEY"]["computed"];
    $FromNumber = $arrSecretValues["secrets"]["TWILIO_NUM"]["computed"];
    $TwilioSID = $arrSecretValues["secrets"]["TWILIO_SID"]["computed"];
  }
  else
  {
    if (array_key_exists("messages",$arrSecretValues))
    {
      $AccessKey = getenv("DOPPLERKEY");
      $strMsg = "There was an issue fetching the secrets from $DopplerProj - $DopplerConf. Key starts with '" . substr($AccessKey,0,12) ."'";
      foreach ($arrSecretValues["messages"] as $msg)
      {
        $strMsg .= "$msg. ";
      }
      return($strMsg);
    }
    else
    {
      return("Unexpected reponse from FetchDopplerStatic" . json_encode($arrSecretValues));
    }
  }

  $APIEndpoint = "https://api.twilio.com/";
  $Service = "2010-04-01/Accounts/$TwilioSID/Messages.json";
  $method = "POST";

  $Param = array();
  $Param['From'] = $FromNumber;
  $Param['Body'] = $msg;
  $Param['To'] = $number;

  $url = $APIEndpoint.$Service;
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_USERPWD, "$TwilioSID:$TwilioToken");
  curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_ENCODING, '');
  curl_setopt($curl, CURLOPT_HTTPHEADER, array('accept: application/json','Content-Type: application/x-www-form-urlencoded'));
  curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
  curl_setopt($curl,CURLOPT_POSTFIELDS,http_build_query($Param));
  $response = curl_exec($curl);
  curl_close($curl);
  $arrResponse = json_decode($response, TRUE);
  return json_decode($response, TRUE);
}

function GenerateRecovery($iUserID)
{
  if (isset($GLOBALS["ConfArray"]["RecoverCodeLen"]) )
  {
      $iCodeLen = $GLOBALS["ConfArray"]["RecoverCodeLen"];
  }
  else
  {
      $iCodeLen = 1;
  }
  $RecovCode = $GLOBALS["TextArray"]["RecovCode"];
  $strRecoveryCode = chunk_split(bin2hex(random_bytes($iCodeLen/2)),4," ");
  print "<p class=\"BlueAttn\">$RecovCode</p>";
  print "<p class=\"BlueAttn\">$strRecoveryCode</p>";
  $strCleanRecovery = str_replace(' ','',$strRecoveryCode);
  $strECode = password_hash($strCleanRecovery, PASSWORD_DEFAULT);
  $strQuery = "update tblUsers set vcRecovery = '$strECode' where iUserID = $iUserID;";
  UpdateSQL ($strQuery, "update");
}

?>