<?php
/**
 * automatische Avatarreservierung  - by risuena
 */
// error_reporting ( -1 );
// ini_set ( 'display_errors', true ); 
 
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}


function reservierung_info()
{
	return array(
		"name"			=> "Reservierung",
		"description"	=> "automatische Reservierung von Avataren",
		"website"		=> "http://lslv.de",
		"author"		=> "risuena",
		"authorsite"	=> "http://lslv.de",
		"version"		=> "1.0",
		"compatibility" => "*"
	);
}


function reservierung_install()
{
  global $db;
  

      $db->write_query("ALTER TABLE `".TABLE_PREFIX."usergroups` ADD `avares` INT(10) NOT NULL DEFAULT '0';");
      //$db->write_query('UPDATE '.TABLE_PREFIX.'usergroups SET avares = 1 where avares= 0');
    //  $cache->update_usergroups();
     
  //erstellen der Tabelle
      $db->write_query("CREATE TABLE `".TABLE_PREFIX."avares` (
    `id` INT(20) NOT NULL AUTO_INCREMENT,
  	`uid` int(20),
  	`avaperson` varchar(200),
  	`name` varchar(200),
  	`gender` varchar(200),
  	`savedate` date,
  	`gid` int(11),
  	`count_extend` int(11) NOT NULL DEFAULT '0',
  	PRIMARY KEY (`id`)
	) ENGINE=InnoDB;");	
	
	//Einstellungen 
	$setting_group = array(
      'name' => 'reservierung',
      'title' => 'Avatarreservierung',
      'description' => 'Einstellungen für die Avatarreservierung',
      'disporder' => 5, // The order your setting group will display
      'isdefault' => 0
  );
  $gid = $db->insert_query("settinggroups", $setting_group);

	//dürfen Gäste Avatare reservieren?	
	$setting_array = array(
      'reservierung_gaeste' => array(
          'title' => 'Gäste?',
          'description' => 'Dürfen Gäste Avatare reservieren?',
          'optionscode' => 'yesno',
          'value' => '1', // Default
          'disporder' => 1
      ),
      //Dürfen Reservierungen verlängert werden?
      'reservierung_extend' => array(
          'title' => 'Verlängern',
          'description' => 'Dürfen registrierte Mitglieder Reservierungen verlängern?',
          'optionscode' => 'yesno',
          'value' => '1', // Default
          'disporder' => 2
      ),
      //wenn ja wie lange?
    'reservierung_count' => array(
          'title' => 'Wie oft darf eine Reservierung verlängert werden?',
          'description' => '0 bei kein mal',
          'optionscode' => 'text',
          'value' => '3', // Default
          'disporder' => 3
      ),

      //avatare vergleichen mit vergebenen?
          'reservierung_vgl' => array(
          'title' => 'Vergleich mit vergebenen Avataren?',
          'description' => 'Wenn vergebene Avatare über eigene Profilfelder gespeichert werden, könnt ihr hier die ID des Feldes eintragen. Dann wird überprüft, ob das Avatar schon vergeben ist. Ansonsten hier den Wert 0 stehen lassen.',
          'optionscode' => 'text',
          'value' => '0', // Default
          'disporder' => 4
      ),
            //avatare vergleichen mit vergebenen?
          'reservierung_alert' => array(
          'title' => 'Eine Benachrichtigung auf der Indexseite anzeigen, wenn die Reservierung ausläuft?',
          'description' => '0 wenn nicht gewünscht, sonst Anzahl der Tage angeben bevor die Reservierung ausläuft.',
          'optionscode' => 'text',
          'value' => '0', // Default
          'disporder' => 5
      ),
        //Wird der Accountswitcher benutzt, Benachrichtigung übergreifend anzeigen?
          'reservierung_as' => array(
          'title' => 'Wird der Accountswitcher genutzt?',
          'description' => 'Wenn ja, werden die Benachrichtigungen übergreifend angezeigt.',
          'optionscode' => 'yesno',
          'value' => '0', // Default
          'disporder' => 6
      ),
  );
  

  
  foreach($setting_array as $name => $setting)
  {
      $setting['name'] = $name;
      $setting['gid'] = $gid;

      $db->insert_query('settings', $setting);
  }
   rebuild_settings(); 

}

function reservierung_is_installed()
{
    global $db;
    if($db->table_exists("avares"))
    {
        return true;
    }
    return false;
}

function reservierung_uninstall()
{
	global $db;
	if($db->field_exists("id", "avares"))
	{
		$db->drop_table("avares");
	}
		  // Einstellungen entfernen
  $db->delete_query('settings', "name IN ('reservierung_gaeste', 'reservierung_extend', 'reservierung_count')");
  $db->delete_query('settinggroups', "name = 'reservierung'");
 $db->query("ALTER TABLE ".TABLE_PREFIX."usergroups DROP avares");
  rebuild_settings();
}

function reservierung_activate()
{
	global $db;

	$insert_array = array(
		'title'		=> 'list_reservierung',
		'template'	=> $db->escape_string('
		<html>
   		<head>
        <title> Avatarreservierungen </title>
        {$headerinclude}
    	</head>
    <body>
        {$header}

<br /><br />
<center>

	{$res_avaform}
	
<table width="85%">
    <tr>
		<td colspan="3" align="center"><b>Männliche Avatare</b></td>
    </tr>
      <tr>
        <td width="33%"><b>Avatarperson</b></td>
        <td width="33%"><b>Spieler</b></td>
        <td width="33%"><b>läuft aus</b></td>
    </tr>
    {$avas_male}
	</table>
	<br />
<table width="85%">	
        <tr>
        <td colspan="3" align="center"><b>Weibliche Avatare</b></td>
    </tr>
      <tr>
        <td width="33%"><b>Avatarperson</b></td>
        <td width="33%"><b>Spieler</b></td>
        <td width="33%"><b>läuft aus</b></td>
    </tr>
    {$avas_female}
    </table>
		
		{$delete_tpl}
		
</center>
        {$footer}
    </body>
</html>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);
	
		$insert_array = array(
		'title'		=> 'res_avafemale',
		'template'	=> $db->escape_string('	<tr>
        <td valign="top">{$f_ava}</td>
        <td valign="top">{$f_name}<br>
            {$f_user}</td>
        <td valign="top">{$f_expire}<br />
            {$edit} - {$delete} - {$extend}
    </tr>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);
	
		$insert_array = array(
		'title'		=> 'res_avamale',
		'template'	=> $db->escape_string('<tr>
        <td valign="top">{$m_ava}</td>
        <td valign="top">{$m_name}<br>
            {$m_user}</td>
        <td valign="top">{$m_expire}<br />
            {$edit} - {$delete} - {$extend}
    </tr>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	
	$db->insert_query("templates", $insert_array);
	
			$insert_array = array(
		'title'		=> 'res_avaform',
		'template'	=> $db->escape_string('<form action="" method="post"> 
	<table>
		<tr>
			<td><b>Avatarperson: </b></td>
			<td><input type="input" name="avaperson" value="avaperson"/> </td>
		</tr>
		<tr>
			<td><b>Spielername: </b></td>
			<td><input type="input" name="name" value="name"/><br></td>
		</tr>
		<tr>
			<td rowspan="2"><b>Geschlecht</b></td>
			<td><input type="radio" name="gender" value="female"/> Female</td>
		</tr>
		<tr>
			<td><input type="radio" name="gender" value="male"/> Male</td>
		</tr>
		<tr>
			<td colspan="2" align="center"><input type="submit" name="eintragen" id="eintragen" value="eintragen"/></td>
		</tr>
	</table>
</form><br /><br />'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	
	
	$db->insert_query("templates", $insert_array);
	
	$insert_array = array(
		'title'		=> 'res_message',
		'template'	=> $db->escape_string('<div style="width: 100%; height:30px; margin:auto auto; text-align:center;background-color:#f1f1f1;color:#DF3A01;font-size:12px;  display: flex;  align-items: center;  justify-content: center;  border: 1px solid #000000;"> <b>Deine Reservierung läuft bald aus.</b></div>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	
	$db->insert_query("templates", $insert_array);
	
	$insert_array = array(
		'title'		=> 'res_delete',
		'template'	=> $db->escape_string('<br/>
    <hr/><br/> 
    <b>Admin/Modtool:</b> <br/>
     Alle ausgelaufenen Reservierungen aus der Datenbank löschen. <br/><br />

<table width="85%">
    <tr>
		<td colspan="3" align="center"><b> abgelaufene Reservierungen: </b>    </td>
    </tr>
      <tr>
        <td width="33%"><b>Avatarperson</b></td>
        <td width="33%"><b>Spieler</b></td>
        <td width="33%"><b>läuft aus</b></td>
    </tr>
{$avas_abgelaufen}
	</table>
<br/>

<b>Achtung</b>, das Löschen kann nicht rückgängig gemacht werden.<br/>
    <form action="" method="post"><input type="submit" name="mod_delete" value="ausgelaufene löschen"/></form><br />'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	
	$db->insert_query("templates", $insert_array);
	
		$insert_array = array(
		'title'		=> 'res_abgelaufen',
		'template'	=> $db->escape_string('	<tr>
        <td valign="top">{$a_ava}</td>
        <td valign="top">{$a_name}<br>
            {$a_user}</td>
        <td valign="top">{$a_expire} 
    </tr>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	
	$db->insert_query("templates", $insert_array);

$insert_array = array(
		'title'		=> 'res_edit_bit_m',
		'template'	=> $db->escape_string('<style>.infopop { position: fixed; top: 0; right: 0; bottom: 0; left: 0; background: hsla(0, 0%, 0%, 0.5); z-index: 1; opacity:0; -webkit-transition: .5s ease-in-out; -moz-transition: .5s ease-in-out; transition: .5s ease-in-out; pointer-events: none; } .infopop:target { opacity:1; pointer-events: auto; } .infopop > .pop { background: #aaaaaa; width: 300px; position: relative; margin: 10% auto; padding: 5px; z-index: 3; } .closepop { position: absolute; right: -5px; top:-5px; width: 100%; height: 100%; z-index: 2; }</style>
<div id="popinfo{$get_males[\'id\']}" class="infopop">
  <div class="pop"><form method="post" action="">
  <input type=\'hidden\' value=\'{$m_id}\' name=\'getrid\'>
  <table width=\'100%\' class=\'trow1\'><tr><td colspan=\'2\'><h2>Reservierung editieren</h2></td></tr>
 <tr><td class="trow1"><b>Username</b></td><td class="trow1"><input type="text" name="username" id="username" value="{$m_malename}" class="textbox" /></td></tr>
	   <tr><td class="trow1"><b>Userid</b></td><td class="trow1"><input type="text" name="uid" id="uid" value="{$m_uid}" class="textbox" /></td></tr>

	<tr><td class="trow1"><b>Reservierung</b></td><td class="trow1"><input type="text" name="claim" id="claim" value="{$m_ava}" class="textbox" /></td></tr>
	<tr><td class="trow1"><b>Geschlecht</b></td><td class="trow1">
	<select name="sex"><option selected value="{$get_males[\'gender\']}"><i>männlich</i></option>
<option value="female">weiblich</option></select></td></tr>
<tr><td colspan=\'2\' align=\'center\'><input type="submit" name="edit" value="Reservierung editieren" id="submit" class="button"></td></tr></table>
	  </form>
		</div><a href="#closepop" class="closepop"></a>
</div>

<a href="#popinfo{$get_males[\'id\']}">[e]</a>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
);
	
	$db->insert_query("templates", $insert_array);

$insert_array = array(
		'title'		=> 'res_edit_bit_f',
		'template'	=> $db->escape_string('<style>.infopop { position: fixed; top: 0; right: 0; bottom: 0; left: 0; background: hsla(0, 0%, 0%, 0.5); z-index: 1; opacity:0; -webkit-transition: .5s ease-in-out; -moz-transition: .5s ease-in-out; transition: .5s ease-in-out; pointer-events: none; } .infopop:target { opacity:1; pointer-events: auto; } .infopop > .pop { background: #aaaaaa; width: 300px; position: relative; margin: 10% auto; padding: 5px; z-index: 3; } .closepop { position: absolute; right: -5px; top:-5px; width: 100%; height: 100%; z-index: 2; }</style>
<div id="popinfo{$get_females[\'id\']}" class="infopop">
  <div class="pop"><form method="post" action="">
  <input type=\'hidden\' value=\'{$f_id}\' name=\'getrid\'>
  <table width=\'100%\' class=\'trow1\'><tr><td colspan=\'2\'><h2>Reservierung editieren</h2></td></tr>
 <tr><td class="trow1"><b>Username</b></td><td class="trow1"><input type="text" name="username" id="username" value="{$f_femalename}" class="textbox" /></td></tr>
	   <tr><td class="trow1"><b>Userid</b></td><td class="trow1"><input type="text" name="uid" id="uid" value="{$f_uid}" class="textbox" /></td></tr>

	<tr><td class="trow1"><b>Reservierung</b></td><td class="trow1"><input type="text" name="claim" id="claim" value="{$f_ava}" class="textbox" /></td></tr>
	<tr><td class="trow1"><b>Geschlecht</b></td><td class="trow1">
	<select name="sex"><option selected value="{$get_females[\'gender\']}"><i>weiblich</i></option>
	<option value="male">männlich</option></td></tr>
<tr><td colspan=\'2\' align=\'center\'><input type="submit" name="edit" value="Reservierung editieren" id="submit" class="button"></td></tr></table>
	  </form>
		</div><a href="#closepop" class="closepop"></a>
</div>

<a href="#popinfo{$get_females[\'id\']}">[e]</a>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
);
	
	$db->insert_query("templates", $insert_array);
	include  MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("header", "#".preg_quote('{$modnotice}')."#i", '{$modnotice}{$ava_message}');

}

function reservierung_deactivate()
{
global $db;
	include  MYBB_ROOT."/inc/adminfunctions_templates.php";
	$db->delete_query("templates", "title IN('list_reservierung','res_avamale','res_avafemale', 'res_avaform', 'res_message', 'res_delete', 'res_abgelaufen', 'res_edit_bit_f', 'res_edit_bit_m')");
	find_replace_templatesets("header", "#".preg_quote('{$ava_message}')."#i", '');
}


//wieviele tage darf welche Gruppe das Ava reservieren?
//Einstellungen im Admin CP 
$plugins->add_hook("admin_formcontainer_end", "reservierung_edit_group");
function reservierung_edit_group()
{
	global $run_module, $form_container, $lang, $form, $mybb, $user;
		//$lang->load("users_permissions");
	if($run_module == 'user' && !empty($lang->users_permissions) &&  $form_container->_title == $lang->users_permissions){     

        $reservierung_options = array();
        $reservierung_options[] = 'Wieviele Tage darf diese Gruppe ein Avatar reservieren?<br/>'.$form->generate_text_box("avares", $mybb->input['avares'], array("avares" => 'avares'));
        $form_container->output_row("<b>Avatarreservierung</b>", "<div class=\"group_settings_bit\">".implode("</div><div class=\"group_settings_bit\">", $reservierung_options)."</div>");
	}
	
}

$plugins->add_hook('admin_user_groups_edit_commit', 'reservierung_edit_group_do');
function reservierung_edit_group_do()
{
    global $updated_group, $run_module, $form_container, $lang, $form, $mybb;                       
    $updated_group['avares'] = $mybb->get_input('avares', MyBB::INPUT_INT);
} 


//Benachrichtung wenn Reservierung ausläuft
$plugins->add_hook('global_intermediate', 'reservierung_alert');
function reservierung_alert(){
global $db, $mybb, $templates, $ava_message;   
   
//accountswitcher?
$opt_as=intval($mybb->settings['reservierung_as']);
//wieviele tage
$opt_alert_days=intval($mybb->settings['reservierung_alert']);

//welcher user ist online
$this_user = intval($mybb->user['uid']);
$reservation = 0;

if ($opt_as == 1) {
//für den fall nicht mit hauptaccount online
$as_uid = intval($mybb->user['as_uid']);

// suche alle angehangenen accounts
if($as_uid == 0) { 
	$get_all_uids = $db->query("SELECT uid FROM ".TABLE_PREFIX."users WHERE (as_uid = $this_user) OR (uid = $this_user)");
} else if ($as_uid != 0) {
//id des users holen wo alle angehangen sind 
	$get_all_uids = $db->query("SELECT uid FROM ".TABLE_PREFIX."users WHERE (as_uid = $as_uid) OR (uid = $this_user)");
}
// gehe alle angehängten accounts durch und schaue ob es eine reservierung gibt
	while($get_avares = $db->fetch_array($get_all_uids)){
	$uid = intval($get_avares['uid']);
	$get_avas=$db->query("SELECT * FROM ".TABLE_PREFIX."avares WHERE (uid = $uid)");
	
	while($get_message= $db->fetch_array($get_avas)){
		$savedate = $get_message['savedate'];
		$gid = intval($get_message['gid']);	
		$get_days = $db->simple_select("usergroups","avares","gid='$gid'");
   		$days = $db->fetch_field($get_days, "avares");
		$expiredate = date('d.m.Y', strtotime($get_message['savedate']. ' + '.$days.' days'));
  
		$date = new DateTime($expiredate);
		$now = new DateTime();
		$difference = $date->diff($now)->format("%d");
		
		if($difference <= $opt_alert_days){
			$reservation++ ;
		}
		}

	}

if (($reservation > 0) && ($mybb->user['uid'] != 0)) {
	eval("\$ava_message = \"".$templates->get("res_message")."\";");

} else{
	$ava_message ='';
}
}

}

//Teambenachrichtung wenn Reservierung ausgelaufen ist
$plugins->add_hook('global_intermediate', 'reservierung_teamalert');
function reservierung_teamalert(){
global $db, $mybb, $templates, $ava_message;   

if($mybb->usergroup['canmodcp'] == 1){
	
	//alle Reservierungen speichern
$get_teamabgelaufen=$db->query("SELECT * FROM ".TABLE_PREFIX."avares");

//array durchgehen
while($teamabgelaufen = $db->fetch_array($get_teamabgelaufen)){
    $ta_id = intval($teamabgelaufen['id']);
    $ta_uid = intval($teamabgelaufen['uid']);
    $ta_ava = htmlspecialchars_uni($teamabgelaufen['avaperson']);
    $ta_name = htmlspecialchars_uni($teamabgelaufen['name']);
    $ta_gid = intval($teamabgelaufen['gid']);
    $get_days = $db->simple_select("usergroups","avares","gid='$ta_gid'");;
    $days = $db->fetch_field($get_days, "avares");
	$ta_expire = date('d.m.Y', strtotime($teamabgelaufen['savedate']. ' + '.$days.' days'));
    $ta_counter = intval($teamabgelaufen['count_extend']);
    
// Nur anzeigen, wenn Datum abgelaufen
if(strtotime($today) >= strtotime($ta_expire)) {
	//wichtig fürs gesamte löschen, wenn abgelaufen in ein assoziatives array speichern
    $teamabgelaufene[$ta_id] = $ta_id;
    eval("\$ava_teammessage .= \"".$templates->get("res_message_team")."\";");
}
$ta_expire="";
}


}
}


?>