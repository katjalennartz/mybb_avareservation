<?php
define("IN_MYBB", 1);
//define('THIS_SCRIPT', 'reservierung.php');

// error_reporting ( -1 );
// ini_set ( 'display_errors', true ); 
 
 
require("global.php");      
global $db, $mybb;

//heutiges Datum, formatiert
    $timestamp = time();
    $today = date("d.m.Y",$timestamp);

//Einstellungen holen
	$opt_gaeste = intval($mybb->settings['reservierung_gaeste']);
	$opt_extend = intval($mybb->settings['reservierung_extend']);
	$opt_count = intval($mybb->settings['reservierung_count']);
		
//feld id von avatarperson holen
	$opt_fid=intval($mybb->settings['reservierung_vgl']);
	$feld_id = "fid".$opt_fid;

	
//können gäste avatare reservieren?
if ($mybb->user['uid'] == 0 && $opt_gaeste == 0) {
	$res_avaform = "Gäste können keine Avatare reservieren<br /><br />";
	} else {
	eval("\$res_avaform .= \"".$templates->get("res_avaform")."\";");
}

//Eintragen, wenn auf button geklickt wird
if ($mybb->input['eintragen'] != '') {
//sollen vergebene Avas überprüft werden?
if ($opt_fid != 0) {
$avatar = $db->escape_string($mybb->input['avaperson']);
$avaisfree=$db->query("SELECT username FROM ".TABLE_PREFIX."userfields, ".TABLE_PREFIX."users WHERE ufid = uid AND upper(".$feld_id.") LIKE upper('$avatar')
UNION
SELECT avaperson as username FROM ".TABLE_PREFIX."avares WHERE upper(avaperson) LIKE upper('$avatar')");

$check = $db->fetch_field($avaisfree, "username");
//kommt es zu übereinstimmungen row_cnt nicht 0. 
$row_cnt = mysqli_num_rows($avaisfree);
} // kein vergleich row_cnt immer 0
else {
	$row_cnt = 0;
	$check = "";
}

//array erstellen um Daten zu speichern
$reservierung = array(
	"uid" =>intval($mybb->user['uid']),
	"avaperson"=>$db->escape_string($mybb->input['avaperson']),
	"name"=>$db->escape_string($mybb->input['name']),
	"gender" =>$db->escape_string($mybb->input['gender']),
	"savedate" => date("Y-m-d H:i:s"),
	"gid" => intval($mybb->user['usergroup']),
    ); 
            
//speichern
//gab es übereinstimmungen beim Vergleich zu vergebenen Avataren?
//Dann Fehlermeldung und nicht speichern
if ($check) {
	echo "<script>alert('Das Avatar ist schon vergeben oder reserviert.')</script>		<script> 
	<!--
	window.location.replace('reservierung.php'); 
	//-->
	</script></font>";
} else {
//sonst speichern
	$db->insert_query("avares", $reservierung);
	//header('Location: reservierung.php');
	//exit();
	redirect('reservierung.php');
	}
}
$m_id="";
$m_uid="";

// Ausgabe
// alle männlichen reservierten avas
$males=$db->query("SELECT * FROM ".TABLE_PREFIX."avares WHERE gender LIKE 'male'");
//ergebnis durchgehen
while($get_males = $db->fetch_array($males)){
    $m_id = intval($get_males['id']);
    $m_uid = intval($get_males['uid']);
    $m_ava = htmlspecialchars_uni($get_males['avaperson']);
    $m_name = $get_males['name'];
    $m_gid = intval($get_males['gid']);
    $get_days = $db->simple_select("usergroups","avares","gid='$m_gid'");
    $days = $db->fetch_field($get_days, "avares");
	$m_expire = date('d.m.Y', strtotime($get_males['savedate']. ' + '.$days.' days'));
    $m_counter = intval($get_males['count_extend']);
    
    // wenn kein Gast, dann link zum Profil mit dem Das Avatar gespeichert wurden ist    
        //wenn user, dann link zum Profil
   	if ($m_uid != 0) {
   		$m_username = get_user(intval($m_uid));
        $m_user = "";
        $m_malename = $m_name;
    } else if ($m_uid == 0) {
    //sonst gast.
       	$m_username =$m_name; 
       	$m_malename = $m_name;
    	$m_user ="(Gast)";
    }
    
    $edit='';
//löschen, wenn moderator, oder gleicher user und nicht gast
 if (($mybb->usergroup['canmodcp'] == 1) || ( ($mybb->user['uid'] == $get_males['uid']) &&  $mybb->user['uid'] != 0))  {
        $delete = '<a href="reservierung.php?action=delete&amp;id='.$m_id.'" original-title="löschen"><i class="fas fa-trash-alt"></i></a>';
        eval("\$edit .= \"".$templates->get("res_edit_bit_m")."\";");
        } else {
        $delete = ''; 
        $edit = '';
    }
    
//verlängern 
 if (($mybb->usergroup['canmodcp'] == 1) || 
 	( ($mybb->user['uid'] == $get_males['uid']) 
 		&& ($mybb->user['uid'] != 0)
 		&& ($opt_extend == 1)
 		&& ($m_counter <= $opt_count) )
 	)  {
        $extend = '<a href="reservierung.php?action=extend&amp;id='.$m_id.'" original-title="verlängern"><i class="fas fa-forward"></i></a>';
        } else {
        $extend = ''; 
    }
//nur ausgeben, wenn das Avatar noch nicht abgelaufen ist
if(strtotime($today) <= strtotime($m_expire)) {
	eval("\$avas_male .= \"".$templates->get("res_avamale")."\";");
}
	$m_expire="";
}

$f_id="";
$f_uid="";


//alle weiblichen reservierten avas
$females=$db->query("SELECT * FROM ".TABLE_PREFIX."avares WHERE gender LIKE 'female'");

while($get_females = $db->fetch_array($females)){
    $f_id = intval($get_females['id']);
    $f_uid = intval($get_females['uid']);
    $f_ava = htmlspecialchars_uni($get_females['avaperson']);
    $f_name = htmlspecialchars_uni($get_females['name']);
    $f_gid = intval($get_females['gid']);
    $get_days = $db->simple_select("usergroups","avares","gid='$f_gid'");;
    $days = $db->fetch_field($get_days, "avares");
	$f_expire = date('d.m.Y', strtotime($get_females['savedate']. ' + '.$days.' days'));
    $f_counter = intval($get_females['count_extend']);
    
    //wenn user, dann link zum Profil
   	if ($f_uid != 0) {
   		$f_username = get_user(intval($f_uid));
        $f_user = "";
        $f_femalename = $f_name;
    } else if ($_uid == 0) {
    //sonst gast.
       //	$f_username ="test"; 
    	$f_user ="(Gast)";
    	$f_femalename = $f_name;
    }
    
 $edit ='';   
 
//Button für löschen - nur wenn mod oder user der eingetragen hat - nicht für gäste
 if (($mybb->usergroup['canmodcp'] == 1) || ( ($mybb->user['uid'] == $get_females['uid']) &&  $mybb->user['uid'] != 0))  {
        $delete = '<a href="reservierung.php?action=delete&amp;id='.$f_id.'" original-title="löschen"><i class="fas fa-trash-alt"></i></a>';
        eval("\$edit .= \"".$templates->get("res_edit_bit_f")."\";");

        } else {
        $delete = ''; 
     	$edit ='';
    }
//Button für verlängern - nur wenn mod oder user der eingetragen hat - nicht für gäste und nur wenn es erlaubt ist.
 if (($mybb->usergroup['canmodcp'] == 1) || 
 	( ($mybb->user['uid'] == $get_females['uid']) 
 		&& $mybb->user['uid'] != 0
 		&& $opt_extend == 1
 		&& $f_counter <= $opt_count )
 	)  {
        $extend = '<a href="reservierung.php?action=extend&amp;id='.$f_id.'" original-title="verlängern"><i class="fas fa-forward"></i></a>';
        } else {
        $extend = ''; 
    }

if(isset($mybb->input['edit'])){
	$getrid = intval($mybb->input['getrid']);
	$uid = intval($mybb->input['uid']);
	$username = $db->escape_string($mybb->input['username']);
	$claim = $db->escape_string($mybb->input['claim']);
	$sex = $db->escape_string($mybb->input['sex']);
	
	//sollen vergebene Avas überprüft werden?
	if ($opt_fid != 0) {
		$res_avaisfree=$db->query("SELECT username FROM ".TABLE_PREFIX."userfields, ".TABLE_PREFIX."users WHERE ufid = uid AND upper(".$feld_id.") LIKE upper('".$claim."')
			UNION
			SELECT avaperson as username FROM ".TABLE_PREFIX."avares WHERE upper(avaperson) LIKE upper('".$claim."')");
		$res_check = $db->fetch_field($res_avaisfree, "username");
		//kommt es zu übereinstimmungen row_cnt nicht 0. 
		$res_row_cnt = mysqli_num_rows($res_avaisfree);
	} // kein vergleich row_cnt immer 0
	else {
		$res_row_cnt = 0;
		$res_check = "";
	}
	if ($res_check) {	
	echo "<script>alert('Das Avatar ist schon vergeben oder reserviert.')</script>		<script> 
	<!--
	window.location.replace('reservierung.php'); 
	//-->
	</script></font>";
	} else {
		$db->query("UPDATE ".TABLE_PREFIX."avares SET uid = '".$uid."', name = '".$username."', avaperson = '".$claim."', gender = '".$sex."' WHERE id = '$getrid'");
		redirect('reservierung.php');
	}
}

// Nur Anzeigen, wenn Datum noch nicht abgelaufen
// abgelaufene Reservierungen werden nicht mehr angezeigt, sind aber noch in der Datenbank!
if(strtotime($today) <= strtotime($f_expire)) {
    eval("\$avas_female .= \"".$templates->get("res_avafemale")."\";");
    }
$f_expire="";

}

//löschen 
//eintrag wird aus der Datenbank gelöscht
if(($mybb->input['action'] == "delete") )  {
    if (isset($_REQUEST['id'])) {
        $id = intval($_REQUEST['id']);
    }
    $db->query("DELETE FROM ".TABLE_PREFIX."avares WHERE id='$id'");
    header("Location: reservierung.php");
    exit();
}

//Verlängern
//Hier wird einfach das aktuelle Datum in savedate eingetragen. 
//Also als ob man die Reservierung an diesem Tag eingetragen hätte. Das neue Ablaufdatum wird dann entsprechend der Gruppeneinstellungen berechnet.
if(($mybb->get_input('action') == "extend") )  {
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
    }
	$datum = date("Y-m-d H:i:s");
	$db->query("UPDATE ".TABLE_PREFIX."avares SET savedate = '$datum', count_extend = count_extend+1 WHERE id='$id'");

    header("Location: reservierung.php");
    exit();
} 


//MOD und ADMIN Verwaltung von Reservierungen 
if($mybb->usergroup['canmodcp'] == 1){

//anzeige abgelaufene Reservierungen:
//alle Reservierungen speichern
$get_abgelaufen=$db->query("SELECT * FROM ".TABLE_PREFIX."avares");

//array durchgehen
while($abgelaufen = $db->fetch_array($get_abgelaufen)){
    $a_id = intval($abgelaufen['id']);
    $a_uid = intval($abgelaufen['uid']);
    $a_ava = htmlspecialchars_uni($abgelaufen['avaperson']);
    $a_name = htmlspecialchars_uni($abgelaufen['name']);
    $a_gid = intval($abgelaufen['gid']);
    $get_days = $db->simple_select("usergroups","avares","gid='$a_gid'");;
    $days = $db->fetch_field($get_days, "avares");
	$a_expire = date('d.m.Y', strtotime($abgelaufen['savedate']. ' + '.$days.' days'));
    $a_counter = intval($abgelaufen['count_extend']);
    
    
   	if ($a_uid != 0) {
   		$a_username = get_user(intval($a_uid));
        $a_user = "";
    } else if ($_uid == 0) {
       	$a_username =""; 
    	$a_user ="(Gast)";
    }
    
// Nur anzeigen, wenn Datum abgelaufen
if(strtotime($today) >= strtotime($a_expire)) {
	//wichtig fürs gesamte löschen, wenn abgelaufen in ein assoziatives array speichern
    $abgelaufene[$a_id] = $a_id;
    eval("\$avas_abgelaufen .= \"".$templates->get("res_abgelaufen")."\";");
}
$a_expire="";
}


//if admin delete button
if(($mybb->input['mod_delete'])&&($mybb->usergroup['canmodcp'] == 1))  {

	//das vorherige erstelle array durchlaufen und entsprechend die ids löschen
	foreach ($abgelaufene as $key => $value) { 
    	$db->query("DELETE FROM ".TABLE_PREFIX."avares WHERE id = '$value'");
	}

	header("Location: reservierung.php");
    exit();	  
}

eval("\$delete_tpl = \"".$templates->get("res_delete")."\";");


}

eval("\$menu .= \"".$templates->get("listen_nav")."\";");

eval("\$list_reservierung .= \"".$templates->get("list_reservierung")."\";");
output_page($list_reservierung);

?>