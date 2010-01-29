<?php
# Copyright 2007 CardBoardFish
# http://www.cardboardfish.com/
# See readme.txt for terms of use.

/**
 * reviewed and improved by Ania <anna.warzecha@gmail.com>
 */
class SendSMS 
{
    protected $sDestAddr;
    protected $sSourceAddr;
    protected $sSourceAddrTon;
    protected $sMessage;
    protected $sDataCodingScheme;
    protected $sDeliveryRecipient;
    protected $sUserDataHeader;
    protected $sUserReference;
    protected $sValidityPeriod;
    protected $sDelayUntil;
    protected $sLocalTime;
    protected $bRetry;
    protected $sErrstr = "";
    protected $sErrCode;
    private $sUsername;
    private $sPassword;

    /**
	 * Base directory where the language directories can be found.
	 */
	private $sBaseDir;
	
	/**
	 * The name of the template directory below the language directory
	 */
	private $sTemplateDir;
	
	/**
	 * Holds the content of the sms template (input file). Each array entry
	 * represents a single line of the file
	 */
	private $aTemplate;
	
    public function __construct($psUsername, $psPassword) 
    {
		if($psUsername == '' || $psPassword == '') 
		{
			$this->sErrstr = "Username/Pass hasn't been set";
			throw new Exception($this->sErrstr);
		}
		$this->sUsername 	= $psUsername;
		$this->sPassword 	= $psPassword;

		$this->bRetry = true;
    }
   
	/**
	 * Sets the destination addresses.
	 */
    public function setDA ($psDestAddr) 
    {
        if ($psDestAddr == "") 
        {
            $this->sDestAddr = "";
            return true;
        }
        $paDests = explode(",", $psDestAddr);
        $aDests = array();
        foreach ($paDests as $psDest) 
        {
            preg_match("/(\+|00)?([1-9]\d{7,15})/", $psDest, $aMatches);
            if ($aMatches[2] != "") 
            {
                array_push ($aDests, $aMatches[2]);
            } 
            else 
            {
                $this->sDestAddr = "";
                $this->sErrstr = "Destination not recognised.";
                throw new Exception($this->sErrstr);
            }
        }
        $this->sDestAddr = implode(",",$aDests);
    }
	
    /**
     * Sets the source address.
     */
    public function setSA ($psSourceAddr) 
    {
        if ($psSourceAddr == "") 
        {
            $this->sSourceAddr = "";
        }

        preg_match("/^(\d{1,16}|.{1,11})$/", $psSourceAddr, $aMatches);
        if ($aMatches[1] != "") 
        {
            $this->sSourceAddr = urlencode($psSourceAddr);
        } 
        else 
        {
            $this->sErrstr = "Source address not recognised.";
            throw new Exception($this->sErrstr);
        }
    }
	
    /**
     * Sets the sMessage.
     */
    public function setMSG ($psMessage) 
    {
        $this->sMessage = $psMessage;
    }
	
    /**
     * Sets the source type of the number.
     */
    public function setST ($psSourceAddrTon) 
    {
        if ($psSourceAddrTon == "") 
        {
            $this->sSourceAddrTon = "";
        } 
        else 
        {
            preg_match("/^[105]$/", $psSourceAddrTon, $aMatches);
            if ($aMatches[0] != "") 
            {
                $this->sSourceAddrTon = $psSourceAddrTon;
            } 
            else 
            {
                $this->sErrstr = "Source type of number must be 1, 0 or 5.";
                throw new Exception($this->sErrstr);
            }
        }
    }
	
    /**
     * Sets the data coding scheme.
     */
    public function setDC ($psDataCodingScheme) 
    {
        $psDataCodingScheme = "" . $psDataCodingScheme;

        if ($psDataCodingScheme == "") 
        {
            $this->sDataCodingScheme = "";
        } 
        else 
        {
            preg_match("/^[0124567]$/", $psDataCodingScheme, $aMatches);
            if ($aMatches[0] != "") 
            {
                $this->sDataCodingScheme = $psDataCodingScheme;
            } 
            else 
            {
                $this->sErrstr = "Data coding scheme must be one of:\n\t0 - Flash\n\t1 - Normal (default)\n\t2 - Binary\n\t4 - UCS2\n\t5 - Flash UCS2\n\t6 - Flash GSM\n\t7 - Normal GSM\n";
                throw new Exception($this->sErrstr);
            }
        }
    }

    /**
     * Sets the delivery receipt request field.
     */
    public function setDR ($psDeliveryRecipient) 
    {
        if ($psDeliveryRecipient == "") 
        {
            $this->sDeliveryRecipient = "";
        } 
        else 
        {
            preg_match("/^[012]$/", $psDeliveryRecipient, $aMatches);
            if ($aMatches[0] != "") 
            {
                $this->sDeliveryRecipient = $psDeliveryRecipient;
            } 
            else 
            {
                $this->sErrstr = "Delivery receipt request must be 0, 1 or 2.";
                throw new Exception($this->sErrstr);
            }
        }
    }

    /**
     * Sets the user data header.
     */
    public function setUD ($psUserDataHeader) 
    {
        if ($psUserDataHeader == "") 
        {
            $this->sUserDataHeader = "";
        } 
        else 
        {
            preg_match("/^[0-9a-fA-F]{1,17}$/", $psUserDataHeader, $aMatches);
            if ($aMatches[0] != "") 
            {
                $this->sUserDataHeader = $psUserDataHeader;
            } 
            else 
            {
                $this->sErrstr = "User header data invalid.";
                throw new Exception($this->sErrstr);
            }
        }
    }
    
	/**
	 * Sets the user reference.
	 */
    public function setUR ($psUserReference) 
    {
        if ($psUserReference == "") 
        {
            $this->sUserReference = "";
        } 
        else 
        {
            preg_match("/^\w{1,16}$/", $psUserReference, $aMatches);
            if ($aMatches[0] != "") 
            {
                $this->sUserReference = $psUserReference;
            } 
            else 
            {
                $this->sErrstr = "User reference invalid. Must be 1-16 chars: " . $psUserReference;
                throw new Exception($this->sErrstr);
            }
        }
    }
    
    /**
     * Sets the validy period.
     */
    public function setVP ($psValidityPeriod) 
    {
        if ($psValidityPeriod == "") 
        {
            $this->sValidityPeriod = "";
        } 
        else 
        {
            preg_match("/^\d+$/", $psValidityPeriod, $aMatches);
            if ($aMatches[0] != "" && $aMatches[0] > 0 && $aMatches[0] <= 10080) 
            {
                $this->sValidityPeriod = $psValidityPeriod;
            } 
            else 
            {
                $this->sErrstr = "Validity period must be a number between 0 and 10080.";
                throw new Exception($this->sErrstr);
            }
        }
    }

    /**
     * Sets the time at which to send the sMessage.
     */
    public function setDU ($psDelayUntil) 
    {
        if ($psDelayUntil == "") 
        {
            $this->sDelayUntil = "";
        } 
        else 
        {
            preg_match("/^\d{10}$/", $psDelayUntil, $aMatches);
            if ($aMatches[0] != "") {
                $this->sDelayUntil = $psDelayUntil;
                $this->setLT("");
            } 
            else 
            {
                $this->sErrstr = "Delay Until must be a 10 digit UCS timestamp.";
                throw new Exception($this->sErrstr);
            }
        }
    }

    /**
     * Specifies the local time.
     */
    public function setLT ($psLocalTime) 
    {

        if ($psLocalTime == "") 
        {
            if ($this->sDelayUntil != "") 
            {
                $this->sLocalTime = time();
            } 
            else 
            {
                $this->sLocalTime = "";
            }
        } 
        else 
        {
            preg_match("/^\d{10}$/", $psLocalTime, $aMatches);
            if ($aMatches[0] != "") 
            {
                $this->sLocalTime = $psLocalTime;
            } 
            else 
            {
                $this->sErrstr = "Local Time must be a 10 digit UCS timestamp.";
                throw new Exception($this->sErrstr);
            }
        }
    }
	
    /*
     * converts characters for SMS use
     */
	private function GSMEncode ($sToEncode) 
	{
	    $aGSMChars = array (
	        "\x0A" => "\x0A",
	        "\x0D" => "\x0D",
	        "\x24" => "\x02",
	        "\x40" => "\x00",
	        "\x13" => "\x13",
	        "\x10" => "\x10",
	        "\x19" => "\x19",
	        "\x14" => "\x14",
	        "\x1A" => "\x1A",
	        "\x16" => "\x16",
	        "\x18" => "\x18",
	        "\x12" => "\x12",
	        "\x17" => "\x17",
	        "\x15" => "\x15",
	        "\x5B" => "\x1B\x3C",
	        "\x5C" => "\x1B\x2F",
	        "\x5D" => "\x1B\x3E",
	        "\x5E" => "\x1B\x14",
	        "\x5F" => "\x11",
	        "\x7B" => "\x1B\x28",
	        "\x7C" => "\x1B\x40",
	        "\x7D" => "\x1B\x29",
	        "\x7E" => "\x1B\x3D",
	        "\x80" => "\x1B\x65",
	        "\xA1" => "\x40",
	        "\xA3" => "\x01",
	        "\xA4" => "\x1B\x65",
	        "\xA5" => "\x03",
	        "\xA7" => "\x5F",
	        "\xBF" => "\x60",
	        "\xC0" => "\x41",
	        "\xC1" => "\x41",
	        "\xC2" => "\x41",
	        "\xC3" => "\x41",
	        "\xC4" => "\x5B",
	        "\xC5" => "\x0E",
	        "\xC6" => "\x1C",
	        "\xC7" => "\x09",
	        "\xC8" => "\x45",
	        "\xC9" => "\x1F",
	        "\xCA" => "\x45",
	        "\xCB" => "\x45",
	        "\xCC" => "\x49",
	        "\xCD" => "\x49",
	        "\xCE" => "\x49",
	        "\xCF" => "\x49",
	        "\xD0" => "\x44",
	        "\xD1" => "\x5D",
	        "\xD2" => "\x4F",
	        "\xD3" => "\x4F",
	        "\xD4" => "\x4F",
	        "\xD5" => "\x4F",
	        "\xD6" => "\x5C",
	        "\xD8" => "\x0B",
	        "\xD9" => "\x55",
	        "\xDA" => "\x55",
	        "\xDB" => "\x55",
	        "\xDC" => "\x5E",
	        "\xDD" => "\x59",
	        "\xDF" => "\x1E",
	        "\xE0" => "\x7F",
	        "\xE1" => "\x61",
	        "\xE2" => "\x61",
	        "\xE3" => "\x61",
	        "\xE4" => "\x7B",
	        "\xE5" => "\x0F",
	        "\xE6" => "\x1D",
	        "\xE7" => "\x63",
	        "\xE8" => "\x04",
	        "\xE9" => "\x05",
	        "\xEA" => "\x65",
	        "\xEB" => "\x65",
	        "\xEC" => "\x07",
	        "\xED" => "\x69",
	        "\xEE" => "\x69",
	        "\xEF" => "\x69",
	        "\xF0" => "\x64",
	        "\xF1" => "\x7D",
	        "\xF2" => "\x08",
	        "\xF3" => "\x6F",
	        "\xF4" => "\x6F",
	        "\xF5" => "\x6F",
	        "\xF6" => "\x7C",
	        "\xF8" => "\x0C",
	        "\xF9" => "\x06",
	        "\xFA" => "\x75",
	        "\xFB" => "\x75",
	        "\xFC" => "\x7E",
	        "\xFD" => "\x79" 
	    );
	
	    # using the NO_EMPTY flag eliminates the need for the shift pop correction
	    $aChars = preg_split("//", $sToEncode, -1, PREG_SPLIT_NO_EMPTY);
	    $sToReturn = "";
	    foreach ($aChars as $sChar) 
	    {
	        preg_match("/[A-Za-z0-9!\/#%&\"=\-'<>\?\(\)\*\+\,\.;:]/", $sChar, $aMatches);
	        if (isset($aMatches[0])) 
	        {
	            $sToReturn .= $sChar;
	        } 
	        else 
	        {
	            if (!isset($aGSMChars[$sChar])) 
	            {
	                $sToReturn .= "\x20";
	            } 
	            else 
	            {
	                $sToReturn .= $aGSMChars[$sChar];
	            }
	        }
	    }
	    return $sToReturn;
	}

	/**
	 * Sends the sMessage using previously set params to manu destinations
	 * 
	 * @return array
	 */
	public function sendSMS() 
	{
	    $aDAs = explode(",", $this->sDestAddr);
	    $iDAs = count($aDAs);
	    $aBatches = array_chunk($aDAs, 10);
	    $aReplies = array();
	    foreach ($aBatches as $aBatch) 
	    {
	        $aBatchda = implode(",", $aBatch);
	        $aBatchReplies = $this->sendSMSObject();
	        if (!$aBatchReplies) 
	        {
	            if ($this->sErrCode == -15) 
	            {
	                $this->sErrCode = 0;
	                $aBatchReplies = array();
	                for ($i = 0; $i < count($aBatch); $i++) 
	                {
	                    array_push ($aBatchReplies, "-15");
	                }
	            }
	            else 
	            {
	                return false;
	            }
	        }
	        $aValFreq = array_count_values($aBatchReplies);
	        if (isset($aValFreq['-20']) && $aValFreq['-20'] > 0) 
	        {
	            $aRetryBatch = array();
	            for ($i = 0; $i < count($aBatch); $i++) 
	            {
	                if ($aBatchReplies[$i] == '-20') 
	                {
	                    $aRetryBatch[] = $aBatch[$i];
	                }
	            }
	            $sRB = implode(",", $aRetryBatch);
	            $this->setDA($sRB);
	            $sRetryReplies = $this->sendSMSObject();
	            for ($i = 0; $i < count($aBatch); $i++) 
	            {
	                if ($aBatchReplies[$i] == '-20') 
	                {
	                    $aBatchReplies[$i] = array_shift($sRetryReplies);
	                }
	            }
	        }
	        $aReplies = array_merge($aReplies, $aBatchReplies);
	    }
	
	    return $aReplies;
	
	}
	
	private function includeif ($sExisting, $sPrefix) 
	{
	    if ($sExisting == "") 
	    {
	        return "";
	    } 
	    else 
	    {
	        return $sPrefix . $sExisting;
	    }
	}

	/**
	 * sends single sMessage using previousy set params
	 */
	public function sendSMSObject()
	{
	    $sUsername = urlencode($this->sUsername);
	    $sPassword = urlencode($this->sPassword);
		$sSystemtype = "H";
	    $sDCS = $this->sDataCodingScheme;
	    if ($sDCS == "" || $sDCS == 1) 
	    {
	        $sDCS = 6;
	        $sMsg = urlencode($this->GSMEncode($this->sMessage));
	    } 
	    else if ($sDCS == 0) 
	    {
	        $sDCS = 7;
	        $sMsg = urlencode($this->GSMEncode($this->sMessage));
	    } 
	    else 
	    {
	        $sMsg = urlencode($this->sMessage);
	    }
	
	    $sRequest = "http://sms1.cardboardfish.com:9001/HTTPSMS?S={$sSystemtype}&UN=${sUsername}&P=${sPassword}&DA={$this->sDestAddr}&SA={$this->sSourceAddr}&M=${sMsg}";
	    if (!$this->sSourceAddrTon) 
	    {
	        preg_match("/\w/", $this->sSourceAddr, $aMatches);
	        if ($aMatches) 
	        {
	            $this->setST("5");
	        }
	    }
	    $sRequest .= $this->includeif ($this->sSourceAddrTon, "&ST=");
	    $sRequest .= $this->includeif ($sDCS, "&DC=");
	    $sRequest .= $this->includeif ($this->sDeliveryRecipient, "&DR=");
	    $sRequest .= $this->includeif ($this->sUserReference, "&UR=");
	    $sRequest .= $this->includeif ($this->sUserDataHeader, "&UD=");
	    $sRequest .= $this->includeif ($this->sValidityPeriod, "&VP=");
	    $sRequest .= $this->includeif ($this->sDelayUntil, "&DU=");
	    $sRequest .= $this->includeif ($this->sLocalTime, "&LT=");
	    $oCurlHandler = curl_init($sRequest);
	
	    if (!$oCurlHandler) 
	    {
	        $this->sErrstr = "Could not connect to server.";
	        throw new Exception($this->sErrstr);
	    }
	    curl_setopt($oCurlHandler, CURLOPT_RETURNTRANSFER, TRUE);
	    $serverresponse = curl_exec($oCurlHandler);
	
	    if (!$serverresponse) 
	    {
	        $code = curl_getinfo($oCurlHandler, CURLINFO_HTTP_CODE);
	        $this->sErrstr = "HTTP error: $code\n";
	        throw new Exception($this->sErrstr);
	    }
	
	    preg_match("/(OK.*)\r$/", $serverresponse, $aMatches);
	    if (!isset($aMatches[0])) 
	    {
	        $code = curl_getinfo($oCurlHandler, CURLINFO_HTTP_CODE);
	        if ($code == 400) 
	        {
	            $this->sErrstr = "(Server) Bad request.";
	        } 
	        else if ($code == 401) 
	        {
	            $this->sErrstr = "(Server) Invalid username / password.";
	        } 
	        else if ($code == 402) 
	        {
	            $this->sErrstr = "(Server) Credit too low, payment required.";
	        } 
	        else if ($code == 503) 
	        {
	            $this->sErrCode = -15;
	            $this->sErrstr = "(Server) Destination not recognised.";
	        } 
	        else if ($code == 500) 
	        {
	            if ($this->bRetry) 
	            {
	                try 
	                {
	                	$this->bRetry = false;
	                	return new SendSMS($this);
	                }
	                catch (Exception $e)
	                {
	                    $this->sErrstr = "(Server) Error, retry failed.";
	                } 
	            } 
	            else 
	            {
	                $this->sErrstr = "(Server) Error, retry failed.";
	            }
	        }
	        throw new Exception($this->sErrstr);
	    }
	
	    $sResponse = $aMatches[1];
	    preg_match("/^OK((\s\-?\d+)+)(\sUR:.+)?/", $sResponse, $aMatches);
	    $aNumber = explode(" ", $aMatches[1]);
	
	    # Drop the dead entry
	    array_shift($aNumber);
	    $sToReturn = array();
	    foreach ($aNumber as $iId) 
	    {
	        $sToReturn[] = $iId;
	    }
	    return $sToReturn;
	}
	
	##############################
	# template methods
	##############################
	/**
	 * Sets sMessage template according to specified language, template name
	 * ad additional params
	 * 
	 * @param $psLang the language to use in the sMessage
	 * @param $psTemplateName a string prepared with placeholders
	 * @param $paValues	an associated array where the keys should have
	 * 	the same name as the placeholders that should be replaced with array values
	 * 
	 * @return string
	 */
    public function setsMessageTemplate($psLang, $psTemplateName, 
    	array $paValues = array()) 
    {
    	$sFile = $this->sBaseDir.'/'.$psLang.'/'.$this->sTemplateDir.'/'.
		            $psTemplateName;
		if(!file_exists($sFile))
		{
			$this->sErrstr = "sMessage template file not found!";
			throw new Exception($this->sErrstr);
		}
		
		// fetch file content. prepare and parse the template
		$sFileContents = file_get_contents($sFile);
		return $this->prepareTemplateFromString($sFileContents, $paValues);
	}
	
	/**
	 * Replaces the placeholders in a string with values from a given array.
	 * 
	 * @param $psTemplate a string prepared with placeholders
	 * @param $paValues	the values to replace the placeholders with
	 * @return string
	 */
	public function prepareTemplateFromString($psTemplateContent,
		array $paValues = array())
	{
		// Split the string into its lines to iterate over
		$this->aTemplate = explode("\n", $psTemplateContent);
		if(0 == count($this->aTemplate))
		{
			$this->sErrstr = "Template is empty!";
			throw new Exception($this->sErrstr);
		}
		return $this->parseTemplate($paValues);
	}
	
	/**
	 * Parses the template and replaces placeholders with given parameters.
	 *
	 * @param $paValues a list of values to replace
	 * @return string
	 */
	private function parseTemplate(array $paValues = array())
	{
		// Replace placeholders with real values
		foreach($this->aTemplate as $sKey => $sValue)
		{
			// Replace template variable with corresponding content
			$sValue = preg_replace('/\[([A-Za-z0-9|_-]+)\]/e',
				"isset(\$paValues['\\1']) ? \$paValues['\\1'] : ''", $sValue);
			if($this->aTemplate[$sKey] != $sValue)
			{
				$this->aTemplate[$sKey] = $sValue;
			}
		}
		return implode(' ',$this->aTemplate);
	}
}
?>
