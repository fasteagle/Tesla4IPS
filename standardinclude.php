<?
function FB_getServiceData($fbroot,$descXML,$SCPD,$action)
{
	$xml = @simplexml_load_file($fbroot.'/'.$descXML);
	if ($xml === false)
	{
	   echo "Not found:".$descXML.PHP_EOL;
		return false;
	}
	$xml->registerXPathNamespace('fb', $xml->getNameSpaces(false)[""]);
	$xmlservice = $xml->xpath("//fb:service[fb:SCPDURL='/".$SCPD."']");
	$service['uri'] = (string)$xmlservice[0]->serviceType;
	$service['location'] =$fbroot.(string)$xmlservice[0]->controlURL;
	$service['SCPDURL'] =trim((string)$xmlservice[0]->SCPDURL,"/");
	return $service;
}

function FB_getStateVars ($fbroot,$service,$action)
{
	$xmlDesc = @simplexml_load_file($fbroot.'/'.$service['SCPDURL']);
	if ($xmlDesc === false)
	{
	   echo "Not found:".$service['SCPDURL'].PHP_EOL;
		return false;
	}
	$xmlDesc->registerXPathNamespace('fritzbox', $xmlDesc->getNameSpaces(false)[""]);
	$xmlArgumentList = $xmlDesc->xpath("//fritzbox:actionList/fritzbox:action[fritzbox:name='".$action."']/fritzbox:argumentList/fritzbox:argument");
	$StateVariables=false;
	foreach ($xmlArgumentList as $xmlArgument)
	{
		$xmlStateVariable = $xmlDesc->xpath("//fritzbox:stateVariable[fritzbox:name='".(string)$xmlArgument->relatedStateVariable."']");
		$StateVariables[(string)$xmlArgument->name] =  (string)$xmlStateVariable[0]->dataType;
	}
	return $StateVariables;
}

function FB_SoapAction($service,$action,$parameter=null,$user = false,$pass = false)
{
	$service['noroot'] = true;
	$service['trace'] = false;
	$service['exceptions'] = false;
	if (!($user === false))
	{
		$service['login'] = $user;
		$service['password'] = $pass;
	}
	$client = new SoapClient(null,$service);
	$status = $client->{$action}($parameter);
	if (is_soap_fault($status))
	{
	   return false;
	}
	return $status;
}

function UpdateIPSvar($parent,$ident,$value,$type)
{
	$ident=str_replace(array(".",":","-","_"),array("","","",""),$ident);
	if (!ctype_alnum($ident))
	{
		echo "ERROR: Konnte Variable nicht hinzufügen. Name kann nicht als Ident verwendet werden:".$ident.PHP_EOL;
		return;
	}
	if (is_int($type))
	{
		$ips_type=$type;
	} else {
		switch ($type)
		{
			case "i1":
			case "i2":
			case "i4":
			case "ui1":
			case "ui2":
			case "ui4":
				$ips_type=1;
				break;
			case "boolean":
				$ips_type=0;
				break;
			case "uuid":
			case "dateTime":
			case "string":
				$ips_type=3;
				break;
			default:
				echo "Unbekannter Datentyp:".$type.PHP_EOL;
				return;
				break;
		}
	}
	$var_id = @IPS_GetObjectIDByIdent($ident,$parent);
	if ($var_id === false)
	{
		$var_id = IPS_CreateVariable($ips_type);
		IPS_SetName($var_id,$ident);
		IPS_SetIdent($var_id,$ident);
		IPS_SetParent($var_id,$parent);
	}
	switch ($ips_type)
	{
		case 0:
		   if (GetValueBoolean($var_id) <> (bool)$value)
		   {
		      SetValueBoolean($var_id,(bool)$value);
		   }
			break;
		case 1:
			if (GetValueInteger($var_id) <> (int)$value)
			{
	   		SetValueInteger($var_id,(int)$value);
			}
			break;
		case 2:
			if (GetValueFloat($var_id) <> round((float)$value,2))
			{
	   		SetValueFloat($var_id,round((float)$value,2));
			}
			break;
		case 3:
			if (GetValueString($var_id) <> $value)
			{
	   		SetValueString($var_id,$value);
			}
			break;
	}
}
?>
