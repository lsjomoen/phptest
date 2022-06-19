<?php
    require_once("header.php");

    $strUserID = $_SESSION["UID"];
    if ($strReferer != $strPageURL and $PostVarCount > 0)
    {
        print "<p class=\"Error\">Invalid operation, Bad Reference!!!</p> ";
        exit;
    }

    if (isset($_POST['btnSubmit']))
    {
        $btnSubmit = $_POST['btnSubmit'];
    }
    else
    {
        $btnSubmit = "";
    }

    if ($btnSubmit =="Delete Account")
    {
        $iRegNum = trim($_POST['iUserID']);
        $BeenSubmitted = trim($_POST['BeenSubmitted']);

        if($iRegNum)
        {
            if($BeenSubmitted == "True")
            {
                $strQuery = "Delete from tblInterestMap where iUserID='$iRegNum'";
                if(UpdateSQL($strQuery, "delete"))
                {
                    $strQuery = "Delete from tblUsers where iUserID='$iRegNum';";
                    //print("Querystring: $strQuery<br>\n");
                    if ($dbh->query ($strQuery))
                    {
                        print "Account Deleted successful, please close your browser.<br>\n";
                        require_once("KillSession.php");
                    }
                    else
                    {
                        $strError = "Database update failed. Error (". $dbh->errno . ") " . $dbh->error . "\n";
                        $strError .= "$strQuery\n";
                        error_log($strError);
                        if(EmailText("$SupportEmail","Automatic Error Report", $strError . "\n\n\n" . $strQuery ,"From:$SupportEmail"))
                        {
                            print "<p class=\"Error\">We seem to be experiencing technical difficulties. " .
                            "We have been notified. Please try again later. If you have any " .
                            "questions you can contact us at $SupportEmail.</p>";
                        }
                        else
                        {
                            print "<p class=\"Error\">We seem to be experiencing technical difficulties. " .
                                    "Please send us a message at $SupportEmail with information about " .
                                    "what you were doing.</p>";
                        }
                    }
                }
            }
            else
            {
                print "<center>\n<form method=\"post\">\n";
                print "<p class=\"Error\">Are you sure you want to delete your account? <br>\n";
                print "Just leave this page anyway you please if you do not want to delete it. ";
                print "Otherwise press \"Delete Account\" again.</p>\n";
                print "<input type=\"submit\" value=\"Delete Account\" name=\"btnSubmit\"><br>\n";
                print "<input type=\"hidden\" name=\"BeenSubmitted\" value=\"True\">\n";
                print "<input type=\"hidden\" name=\"iUserID\" value=\"$iRegNum\">\n";
                print "</form>\n</center>\n";
            }
        }
        else
        {
            print("<p class=\"Error\">Registration number seems to have gotten lost in transport. Please try again" .
                        "<br>Feel free to contact us at $SupportEmail if you have questions.</p>\n");
        }
    }

    if ($btnSubmit =="Submit")
    {
        //Saving updates to my profile to database
        require_once 'CleanReg.php';
        $iLevel = $Priv;
        if (!$bSpam)
        {
            require 'UserUpdate.php';
            if ($iUserID)
            {
                $strUID = substr(trim($_POST['txtUID']),0,19);
                $strOUID = substr(trim($_POST['txtOUID']),0,19);
                $Password = substr(trim($_POST['txtPWD']),0,19);
                $PWDConf = substr(trim($_POST['txtPWDConf']),0,19);
                $strUID = str_replace("'","",$strUID);
                CleanReg($strUID);
                CleanReg($strOUID);
                CleanReg($Password);
                cleanreg($PWDConf);
                if ($Password !='' or $strOUID != $strUID)
                {
                    $strQuery = "select count(*) iRowCount from tblUsers where vcUID = '$strUID' and iUserID <> $iUserID";
                    if (!$Result = $dbh->query ($strQuery))
                    {
                        error_log ('Failed to fetch data. Error ('. $dbh->errno . ') ' . $dbh->error);
                        error_log ($strQuery);
                        exit(2);
                    }
                    $Row = $Result->fetch_assoc();
                    $RowCount = $Row['iRowCount'];
                    $i = 1;
                    $strUID2 = $strUID;
                    while ($RowCount>0)
                    {
                        $strUID2 = $strUID.$i;
                        $strQuery = "select count(*) iRowCount from tblUsers where vcUID = '$strUID2' and iUserID <> $iUserID";
                        if (!$Result2 = $dbh->query ($strQuery))
                        {
                                error_log ('Failed to data. Error ('. $dbh->errno . ') ' . $dbh->error);
                                error_log ($strQuery);
                                exit(2);
                        }
                        $Row2 = $Result2->fetch_assoc();
                        $RowCount = $Row2['iRowCount'];
                        $i += 1;
                    }

                    if ($strUID2 == $strUID)
                    {
                        $salt = substr($strUID , 0, 4) ;
                        $PWD = crypt($Password , $salt);
                    }
                    else
                    {
                        $salt = substr($strOUID , 0, 4) ;
                        $PWD = crypt($Password , $salt);
                    }
                    $strQuery="";
                    if ($Password == $PWDConf and $strUID == $strUID2 and $Password != '')
                    {
                        $strQuery = "UPDATE tblUsers SET vcUID = '$strUID', vcPWD = '$PWD' WHERE iUserID = '$iUserID'";
                    }
                    if ($Password =='' and $strOUID != $strUID and $strUID == $strUID2)
                    {
                        print "<p>Please provide password to change your user name.</p>";
                    }
                    if ($Password != $PWDConf and $strUID == $strUID2)
                    {
                        print "<p>Passwords do not match so password and username was not changed.</p>\n";
                    }
                    if ($Password == $PWDConf and $strUID != $strUID2)
                    {
                        print "<p>Requested username is already in use could not be changed, however the password will been changed. " .
                        "To change the username use a different username that is not in use. For example $strUID2 is available.</p>\n";
                        $strQuery = "UPDATE tblUsers SET vcPWD = '$PWD' WHERE iUserID = '$iUserID'";
                    }
                    if ($Password != $PWDConf and $strUID != $strUID2)
                    {
                        print "<p>Passwords do not match so password was not changed. Requested username is already in use could not be changed. " .
                        "To change the username Try again and use a different username that is not in use. " .
                        "For example $strUID2 is available. When changing the password make sure you type the same one twice.</p>\n";
                    }
                    if ($strQuery)
                    {
                        UpdateSQL($strQuery, "update");
                    }
                }
            }
        }
    }

    if ($btnSubmit !="Delete Account")
    {
        $strQuery = "SELECT vcPrivName FROM tblprivlevels where iPrivLevel = $Priv;";
        if (!$Result = $dbh->query ($strQuery))
        {
            error_log ('Failed to fetch data. Error ('. $dbh->errno . ') ' . $dbh->error);
            error_log ($strQuery);
            print "<p class=\"Attn\" align=center>$ErrMsg</p>\n";
            exit(2);
        }
        $Row = $Result->fetch_assoc();
        $PrivName = $Row['vcPrivName'];
        if ($PrivName == '')
        {
            $PrivName = $Priv;
        }

        require 'UserDBVar.php';

        if ($dtUpdated=="")
        {
            print "<p class=\"Error\">This account has not been verified. Please verify the information, " .
                                "make any needed changes, then submit to verify your information.</p>\n";
        }

        print "<p>RegistrationID: $iUserID" ;
        $strQuery = "SELECT vcPrivName FROM tblprivlevels where iPrivLevel = $iPrivLevel;";
        if (!$PrivResult = $dbh->query ($strQuery))
        {
            error_log ('Failed to fetch data. Error ('. $dbh->errno . ') ' . $dbh->error);
            error_log ($strQuery);
            print "<p class=\"Attn\" align=center>$ErrMsg</p>\n";
            exit(2);
        }
        $PrivRow = $PrivResult->fetch_assoc();
        $PrivName = $PrivRow['vcPrivName'];
        if ($PrivName == '')
        {
            $PrivName = $Row['iPrivLevel'];
        }

        print "<p>Authorization level is set to $PrivName</p>\n";
        print "<form method=\"POST\">\n";
        require 'UserRegForm.php';
        print "<tr><td>&nbsp</td></tr>";
        print "<tr>";
        print "<td colspan=2 align=\"center\" >";
        print "You can change your username and password here,<br>\n";
        print "Just make sure you provide password and confirm it when changing your username.";
        print "</td>";
        print "</tr>";
        print "<tr><td align=\"right\" class=\"lbl\">UserName:</td>\n";
        print "<input type=\"hidden\" name=\"txtOUID\" value=\"$strUID\">";
        print "<td><input type=\"text\" name=\"txtUID\" size=\"50\" value=\"$strUID\"><span class=\"Attn\">Required</span></td></tr>\n";
        print "<tr><td align=\"right\" class=\"lbl\">Password:</td>\n";
        print "<td><input type=\"password\" name=\"txtPWD\" size=\"50\"><span class=\"Attn\">Required</span></td></tr>\n";
        print "<tr><td align=\"right\" class=\"lbl\">Confirm Password:</td>\n";
        print "<td><input type=\"password\" name=\"txtPWDConf\" size=\"50\"><span class=\"Attn\">Required</span></td></tr>\n";
        print "<tr><td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Submit\" name=\"btnSubmit\"></td></tr>";
        print "</table></form>\n";

        print "<form method=\"post\">\n";
        print "<input type=\"submit\" value=\"Delete Account\" name=\"btnSubmit\">\n";
        print "<input type=\"hidden\" name=\"BeenSubmitted\" value=\"false\">\n";
        print "<input type=\"hidden\" name=\"iUserID\" size=\"5\" value=\"$iUserID\">\n";
        print "</form>\n";
    }
    require_once("footer.php");
?>
