<?
require_once("Standardinclude.php"); //Hilfs-Script

$instanceName="Tesla Control"; //Name der Dummy Instanz für das "Gerät" Tesla



class SimpleTeslaAPI {
    // Quick&Dirty Tesla API - Implementierung in PHP zum Nachvollziehen und weiterprogrammieren,
    // 9.1.2016 (V1.1)
    // von tachy@tff-forum.de


   // !!!!!!!!! Hier Token und vehicle_id von z.B. RemoteS einsetzen !!!!!!!!!
    var $token= "XXXXXXXXXXXXXX";
    var $vehicleID = "XXXXXXXXXXXXXX";
    
    // Namen der anzulegenden VAriablen.
    // Nach der Installation sollten diese nicht mehr verändert werden!
     var $varInnentemperatur = 'Innentemperatur';
	  var $varAussentemperatur = 'Aussentemperatur';
	  var $varTempFahrer = 'TempFahrer';
	  var $varTempBeifahrer = 'TempBeifahrer';
	  var $varKlimaState = 'Klima';

	  var $varName = 'Name';
	  var $varSchiebedach = 'Schiebedach';
	  var $varVerriegelt = 'Verriegelt';
	  var $varVersion = 'Version';
	  
	  
	  var $varHupe = 'Hupe';
 	  var $varLicht = 'Lichthupe';

	  var $varChargeState="Ladestatus";
	  var $varChargeTriggerStart="LadenStarten";
	  var $varChargeTriggerStop="LadenUnterbrechen";
	  var $varChargeLimit="Ladelimit";
	  var $varChargeSOC="SOC";
	  var $varChargePort="Ladeport"; //tbd
	  var $varBatteryHeater="Batterieheizung";
	  var $varTypicalRange="RWIdeal";
	  var $varEstimatedRange="RWTypisch";
	  var $varIdealRange="RWGeschaetzt";

	// Namen für individulle Profile
	  var $profileKM="TESLAKM";
	  var $profileButton="TESLATrigger";


	//Trigger Button Farbe
	var $colTriggerButton="0x0000FF";
	


    //TESLA API Url
    var $url = 'https://owner-api.teslamotors.com/';
    
    var $parentID;
    function SimpleTeslaAPI($parentID){
		$this->parentID=$parentID;
    }

	 //Funktion zum Auslesen der Klima Daten und Schreiben in die Variablen
    function readClimateState2Variable() {
		   $climate=$this->climate_state();
		 	UpdateIPSvar($this->parentID,$this->varInnentemperatur,($climate->{'response'}->{'inside_temp'}),2);
		 	UpdateIPSvar($this->parentID,$this->varAussentemperatur,($climate->{'response'}->{'outside_temp'}),2);
			UpdateIPSvar($this->parentID,$this->varTempFahrer,($climate->{'response'}->{'driver_temp_setting'}),2);
		 	UpdateIPSvar($this->parentID,$this->varTempBeifahrer,($climate->{'response'}->{'passenger_temp_setting'}),2);
			$climateActive=($climate->{'response'}->{'fan_status'}>0);
			echo "Lüftung ist ".$climateActive;
			UpdateIPSvar($this->parentID,$this->varKlimaState,$climateActive,0);

    }
    
     //Funktion zum Auslesen der Fzg Daten und Schreiben in die Variablen
    function readVehicleState2Variable() {
		  	$vehicle_state =$this->vehicle_state();
		   UpdateIPSvar($this->parentID,$this->varName,($vehicle_state->{'response'}->{'vehicle_name'}),3);
			UpdateIPSvar($this->parentID,$this->varSchiebedach,($vehicle_state->{'response'}->{'sun_roof_percent_open'}),1);
			UpdateIPSvar($this->parentID,$this->varVerriegelt,$vehicle_state->{'response'}->{'locked'},0);
			UpdateIPSvar($this->parentID,$this->varVersion,($vehicle_state->{'response'}->{'car_version'}),3);
		}
		
		 function readChargeState2Variable() {
		  	$charge_state =$this->charge_state();
			UpdateIPSvar($this->parentID,$this->varChargeState,($charge_state->{'response'}->{'charging_state'}),3);
			UpdateIPSvar($this->parentID,$this->varChargeLimit,($charge_state->{'response'}->{'charge_limit_soc'}),1);
			UpdateIPSvar($this->parentID,$this->varChargeSOC,($charge_state->{'response'}->{'battery_level'}),1);
			UpdateIPSvar($this->parentID,$this->varBatteryHeater,($charge_state->{'response'}->{'battery_heater_on'}),0);
			UpdateIPSvar($this->parentID,$this->varTypicalRange,($charge_state->{'response'}->{'battery_range'}/0.621371),2);
			UpdateIPSvar($this->parentID,$this->varEstimatedRange,($charge_state->{'response'}->{'est_battery_range'}/0.621371),2);
			UpdateIPSvar($this->parentID,$this->varIdealRange,($charge_state->{'response'}->{'ideal_battery_range'}/0.621371),2);
			

         UpdateIPSvar($this->parentID,$this->varChargeTriggerStop,1,1);
			UpdateIPSvar($this->parentID,$this->varChargeTriggerStart,1,1);
			
			if($charge_state->{'response'}->{'charging_state'}=="Charging"){
     			IPS_SetHidden(IPS_GetVariableIDByName($this->varChargeTriggerStop,$this->parentID),false);
				IPS_SetHidden(IPS_GetVariableIDByName($this->varChargeTriggerStart,$this->parentID),true);
			}else if($charge_state->{'response'}->{'charging_state'}=="Stopped"){
     			IPS_SetHidden(IPS_GetVariableIDByName($this->varChargeTriggerStop,$this->parentID),true);
				IPS_SetHidden(IPS_GetVariableIDByName($this->varChargeTriggerStart,$this->parentID),false);
			}else if($charge_state->{'response'}->{'charging_state'}=="Complete"){
     			IPS_SetHidden(IPS_GetVariableIDByName($this->varChargeTriggerStop,$this->parentID),true);
				IPS_SetHidden(IPS_GetVariableIDByName($this->varChargeTriggerStart,$this->parentID),true);
			}
		}
		
	//Funktion zum Auslesen der Drive Daten und Schreiben in die Variablen
    function readDriveState2Variable() {
		   $drive_state =$this->drive_state();
		  	UpdateIPSvar($this->parentID,'GoogleMaps URL',"\nhttp://maps.google.com/?q=" . $drive_state->{'response'}->{'latitude'} . "," . $drive_state->{'response'}->{'longitude'},3);
	 }
    

    private function curlexec($command,$mode="GET",$params=array()) {
        
		  $url=$this->url;
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

        $params['vehicle_id']=$this->vehicleID;

        if ( $mode == "POST" ) {
            if ( $command == "oauth/token" ) {
                curl_setopt($ch,CURLOPT_URL, $url.$command);
            } else {
                if ( $command == "wake_up" ) {
                    curl_setopt($ch,CURLOPT_URL, $url."api/1/vehicles/".$this->vehicleID."/wake_up");
                } else {
                    curl_setopt($ch,CURLOPT_URL, $url."api/1/vehicles/".$this->vehicleID."/command/".$command);
                }
                curl_setopt($ch,CURLOPT_HTTPHEADER, array("Authorization:Bearer ".$this->token) );
            }
            curl_setopt($ch,CURLOPT_POST, 1);
            curl_setopt($ch,CURLOPT_POSTFIELDS, $params);
        } else {
            if ( $command == "vehicles" ) {
                curl_setopt($ch,CURLOPT_URL, $url."api/1/vehicles");
            } else {
                curl_setopt($ch,CURLOPT_URL, $url."api/1/vehicles/".$this->vehicleID."/data_request/".$command."?".join("&",$params));
            }
            curl_setopt($ch,CURLOPT_HTTPHEADER, array("Authorization:Bearer ".$this->token) );
        }
        $result = curl_exec($ch);
        $rc=curl_getinfo($ch,CURLINFO_HTTP_CODE);

        curl_close($ch);
			echo "\n".$result;
        return json_decode($result);
    }

    //Grundfunktionen
    function vehicles() {
        return $this->curlexec("vehicles","GET");
    }
    function wake_up() {
        return $this->curlexec("wake_up","POST");
    }

    //data-requests
    function charge_state() {
        return $this->curlexec("charge_state","GET");
    }
    function climate_state() {
        return $this->curlexec("climate_state","GET");
    }
    function drive_state() {
        return $this->curlexec("drive_state","GET");
    }
    function gui_settings() {
        return $this->curlexec("gui_settings","GET");
    }
    function vehicle_state() {
        return $this->curlexec("vehicle_state","GET");
    }


    // commands
    function charge_port_door_open() {
        return $this->curlexec("charge_port_door_open","POST");
    }
    function charge_standard() {
        return $this->curlexec("charge_standard","POST");
    }
    function charge_max_range() {
        return $this->curlexec("charge_max_range","POST");
    }
    function set_charge_limit($percent) {
        return $this->curlexec("set_charge_limit","POST",array("percent" => $percent ));
    }
    function charge_start() {
        return $this->curlexec("charge_start","POST");
    }
    function charge_stop() {
        return $this->curlexec("charge_stop","POST");
    }
    function flash_lights() {
        return $this->curlexec("flash_lights","POST");
    }
    function honk_horn() {
        return $this->curlexec("honk_horn","POST");
    }
    function door_unlock() {
        return $this->curlexec("door_unlock","POST");
    }
    function door_lock() {
        return $this->curlexec("door_lock","POST");
    }
    function set_temps($tempDriver, $tempPassenger) {
        return $this->curlexec("set_temps","POST",array("driver_temp" => $tempDriver,"passenger_temp" => $tempPassenger ));
    }
    function auto_conditioning_start() {
        return $this->curlexec("auto_conditioning_start","POST");
    }
    function auto_conditioning_stop() {
        return $this->curlexec("auto_conditioning_stop","POST");
    }
    function sun_roof_control_state($state) {
        return $this->curlexec("sun_roof_control","POST",array("state" => $state ));
    }
    function sun_roof_control_percent($percent) {
        return $this->curlexec("sun_roof_control","POST",array("state" => "move", "percent" => $percent ));
    }
    function remote_start_drive($password) {
        return $this->curlexec("remote_start_drive","POST",array("password" => $password ));
    }

   

    // deprecated
  //  function trunk_open() {
  //      return $this->curlexec("trunk_open","POST",array("which_trunk" => "rear" ));
  //  }
  
  
}



$object = IPS_GetObject($IPS_SELF);
$parentID = $object['ParentID'];

//Installation
if(IPS_GetName($parentID)!=$instanceName){
	//Anlegen der DummyInstanz
	$instanceID = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
	IPS_SetParent($instanceID, $parentID);
	$parentID = $instanceID;
	IPS_SetName($instanceID, $instanceName);
	IPS_SetParent($IPS_SELF, $instanceID);
	IPS_SetName($instanceID, $instanceName);
	
	//Anlegen aller Variablen für die State Werte
	$tesla=new SimpleTeslaAPI($instanceID);
	$tesla->readClimateState2Variable();
	$tesla->readVehicleState2Variable();
	$tesla->readChargeState2Variable();
	
	//Profile anlegen:

	if(IPS_VariableProfileExists($tesla->profileKM)==false){
		IPS_CreateVariableProfile($tesla->profileKM,2);
		IPS_SetVariableProfileText($tesla->profileKM,"","KM");
	}
	
	if(IPS_VariableProfileExists($tesla->profileButton)==false){
		IPS_CreateVariableProfile($tesla->profileButton,1);
		IPS_SetVariableProfileAssociation($tesla->profileButton, 1, "Start", "", 0x008800);
	}

	
	//Setzen der Eigenschaften für die einzelnen Variablen:
	$idVar=IPS_GetVariableIDByName($tesla->varInnentemperatur,$instanceID);
	IPS_SetVariableCustomProfile($idVar,"~Temperature");

	$idVar=IPS_GetVariableIDByName($tesla->varAussentemperatur,$instanceID);
	IPS_SetVariableCustomProfile($idVar,"~Temperature");

	$idVar=IPS_GetVariableIDByName($tesla->varTempFahrer,$instanceID);
	IPS_SetVariableCustomProfile($idVar,"~Temperature");

	$idVar=IPS_GetVariableIDByName($tesla->varTempBeifahrer,$instanceID);
	IPS_SetVariableCustomProfile($idVar,"~Temperature");
	
	$idVar=IPS_GetVariableIDByName($tesla->varKlimaState,$instanceID);
	IPS_SetVariableCustomProfile($idVar,"~Switch");
	IPS_SetVariableCustomAction($idVar,$IPS_SELF);



   $idVar=IPS_GetVariableIDByName($tesla->varName,$instanceID);
	IPS_SetVariableCustomProfile($idVar,"~String");

	$idVar=IPS_GetVariableIDByName($tesla->varSchiebedach,$instanceID);
	IPS_SetVariableCustomProfile($idVar,"~Intensity.100");

   $idVar=IPS_GetVariableIDByName($tesla->varVerriegelt,$instanceID);
	IPS_SetVariableCustomProfile($idVar,"~Lock");

   $idVar=IPS_GetVariableIDByName($tesla->varVersion,$instanceID);
	IPS_SetVariableCustomProfile($idVar,"~String");

   $idVar=IPS_GetVariableIDByName($tesla->varChargeState,$instanceID);
	IPS_SetVariableCustomProfile($idVar,"~String");
	
   $idVar=IPS_GetVariableIDByName($tesla->varChargeLimit,$instanceID);
	IPS_SetVariableCustomProfile($idVar,"~Battery.100");
   $idVar=IPS_GetVariableIDByName($tesla->varChargeSOC,$instanceID);
	IPS_SetVariableCustomProfile($idVar,"~Battery.100");
   $idVar=IPS_GetVariableIDByName($tesla->varBatteryHeater,$instanceID);
	IPS_SetVariableCustomProfile($idVar,"~Switch");
	
	$idVar=IPS_GetVariableIDByName($tesla->varTypicalRange,$instanceID);
	IPS_SetVariableCustomProfile($idVar,$tesla->profileKM); 
   $idVar=IPS_GetVariableIDByName($tesla->varEstimatedRange,$instanceID);
	IPS_SetVariableCustomProfile($idVar,$tesla->profileKM); 
   $idVar=IPS_GetVariableIDByName($tesla->varIdealRange,$instanceID);
	IPS_SetVariableCustomProfile($idVar,$tesla->profileKM); 
	
	//Trigger Buttons erstellen
	UpdateIPSvar($tesla->parentID,$tesla->varHupe,1,1);
	UpdateIPSVarButtonProfil($tesla->varHupe, $instanceID,$tesla->profileButton,$IPS_SELF);
   
   UpdateIPSvar($tesla->parentID,$tesla->varLicht,1,1);
	UpdateIPSVarButtonProfil($tesla->varLicht, $instanceID,$tesla->profileButton,$IPS_SELF);

   UpdateIPSvar($tesla->parentID,$tesla->varChargePort,1,1);
	UpdateIPSVarButtonProfil($tesla->varChargePort, $instanceID,$tesla->profileButton,$IPS_SELF);

	//Button zum Starten/Stoppen des Ladens
	UpdateIPSVarButtonProfil($tesla->varChargeTriggerStop, $instanceID,$tesla->profileButton,$IPS_SELF);
	UpdateIPSVarButtonProfil($tesla->varChargeTriggerStart, $instanceID,$tesla->profileButton,$IPS_SELF);

//ENDE Installationsprozess

}else{
	$tesla=new SimpleTeslaAPI($parentID);
	
	if($IPS_SENDER == "WebFront")
	{
		//	$IPS_VARIABLE, $IPS_VALUE);
		$variableName=IPS_GetName($IPS_VARIABLE);
		if($variableName==$tesla->varHupe){
			$tesla->honk_horn();

		}else if($variableName==$tesla->varKlimaState){
		   $idVarKlimaState=GetValueBoolean(IPS_GetVariableIDByName($tesla->varKlimaState,$parentID));

			if($idVarKlimaState){
				$tesla->auto_conditioning_stop();
				echo "Klima AUS";
				}
			else{
				$tesla->auto_conditioning_start();
				echo "Klima AN";
				}
			sleep(4);
			$tesla->readClimateState2Variable();

		}else if($variableName==$tesla->varLicht){
			$tesla->flash_lights();


		}else if($variableName==$tesla->varVerriegelt){
			echo "TODO Verriegeln on/off";

		}else if($variableName==$tesla->varChargeTriggerStop){
			$tesla->charge_stop();
			sleep(5);
			$tesla->readChargeState2Variable();

		}else if($variableName==$tesla->varChargeTriggerStart){
			$tesla->charge_start();
			sleep(5);
			$tesla->readChargeState2Variable();
		}else if($variableName==$tesla->varChargeLimit){
			echo "TODO Ladelimit setzen";

		}else if($variableName==$tesla->varChargePort){
			$tesla->charge_port_door_open();
		

		}

		

		
	}else{
		$tesla->readClimateState2Variable();
		$tesla->readVehicleState2Variable();
		$tesla->readChargeState2Variable();
	}

	



}
?>
