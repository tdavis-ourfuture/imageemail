<?php


$conf = parse_ini_file("config.ini",TRUE);
$validstates = array('HI','NY','AL','HI','CO','NJ','ME','MO','TN','LA','PA','NY','NJ','OH','FL','MI','NH','NV','AL','LA','MD','TX','NH','MT','CA','UT','CA','AR','SD','DE','CA','NM','GA','OR','MO','OH','MO','IA','VA','CA','FL','TX','FL','OH','KS','KY','NC','IN','IN','WV','PA','CA','WA','WI','NV','IL','IN','CA','SC','VT','AR','SC','NY','TN','AL','GU','FL','TX','UT','NC','GA','IL','OK','LA','MN','FL','KS','IA','FL','WY','GA','OH','AL','AK','IL','CO','MA','PA','CA','MI','ND','TN','AL','IN','NY','CT','OR','AZ','MI','OH','KY','OK','IN','CA','IL','CA','CA','MI','UT','WA','MD','DE','IN','DE','OH','GA','VI','SC','IN','NC','OK','MS','ND','MI','TN','IL','ID','AL','ID','WY','MD','ME','CA','MA','NY','NY','FL','VA','WV','TX','MO','CA','TX','OK','TX','MN','KY','CA','MO','MO','TX','TX','CA','PA','FL','NY','TN','CT','PA','TN','IN','LA','MS','LA','UT','CO','VA','LA','CA','PA','TX','DE','RI','MI','MN','AR','DE','PA','TX','NY','GA','CA','AR','ND','CA','TX','NJ','IL','VA','GA','OR','CO','MA','CT','FL','WA','MI','CT','TX','NM','CA','ND','PA','CA','TN','IL','SC');


$statename = strtoupper($_GET['state']);

if (strlen($statename)!=2  || !in_array($statename, $validstates) ) 
{

/**
 * If no state abbreviation is passed to the script, then we will try to geolocate the user using GeoIP.
 * You must have the geoip extension installed and a free GeoLite City Edition or commercial GeoIP City Edition
 * for this too work.  
 * 
 * See http://www.php.net/manual/en/geoip.setup.php
 */
	if (extension_loaded('geoip') ) {  
		$record = geoip_record_by_name($_SERVER['REMOTE_ADDR']);
		$statename = $record['region'];
		if ($statename=='DC')  {$statename='MD';} //  I choses to make DC into Maryland for testing purposes.  U
	}
	if (empty($statename) {  
		die('No valid state found.');
	}
}



/*  Find the senator using the included database. */

$mysqli = new mysqli($conf['db']['host'],$conf['db']['user'], $conf['db']['pass'], $conf['db']['database']);

$query = "SELECT 
			concat('Senator',' ',if (nickname!='',nickname,firstname),' ',lastname) senator, govtrack_id,
			state_name, party, gender, lastname, concat(if (nickname!='',nickname,firstname),' ',lastname) informal_name
 			FROM atf2.legislators a,
			state_lookup b
			where a.state = b.state_abbr
			and title='Sen'
			AND state = '$statename' and in_office=1 order by rand() limit 1;";  //  Order by rand() limit 1 gets a random senator

$result = $mysqli->query($query);
$row = $result->fetch_assoc();

	$senator = $row['senator'];

	$informal_name = $row['informal_name'];

	if (strlen($senator)>20) { $senator = 'Senator '.$row['lastname']; }  //  Use title and last name for Senators with long names.

	$state = $row['state_name'];
	$party = $row['party'];

	$gender_mod = ($row['gender'] =='M') ? 'him' : 'her';


	$user_name = strtoupper($_GET['first_name'].", are you with us?");
	$photo_leg = 'photo_legis/'.$row['govtrack_id'].'-200px.jpeg'; 



    $im_leg = @imagecreatefromjpeg($photo_leg);
    $arrow_point = @imagecreatefrompng('png/arrowpoint.png');
	imagealphablending($arrow_point, true);
	imagesavealpha($arrow_point, true);


	$imgname = 'png/email_call_graphic.png';

	if ($party=='D')
	{
		$imgname='png/email_call_graphic_d.png';
		$message = $informal_name." has led the effort to protect:";

	}
	else 
	{

		$message = $informal_name." has led the effort to cut:";

	}

    $im = @imagecreatefrompng($imgname);
    $fontsize = 30;
	$sx=imagesx($im);
	$sy=imagesx($im);

	$px=$sx/2.0;
	$py = $sy/2.0;


	header('Content-Type: image/png');


/*  USER NAME */

	$font = $conf['font']['titlefont'];
	$white = imagecolorallocate($im, 255, 255, 255);
	$black = imagecolorallocate($im, 0, 0, 0);
	$red = imagecolorallocate($im, 194, 2, 15);
	$blue = imagecolorallocate($im, 0, 93, 170);

	$box = @imageTTFBbox($fontsize,0,$font,$user_name);

	$textwidth = abs($box[4] - $box[0]);
	$textheight = abs($box[5] - $box[1]);

	$xpos = ($px - ($textwidth/2)) - 10;
	$ypos = 45;

	// Add the text
	imagettftext($im, $fontsize, 0, $xpos, $ypos, $white, $font, $user_name);

/*  STATE NAME */

    $fontsize = 34;

     if (strlen($state)>10) { $fontsize = 31;  }

    	$font=$conf['font']['textfont'];


	$box = @imageTTFBbox($fontsize,0,$font,strtoupper($state).',');

	$textwidth = abs($box[4] - $box[0]);
	$textheight = abs($box[5] - $box[1]);

  	if (strlen($state)>10)
     {
     	$fontsize = 31;
 		$xpos = ($px - ($textwidth/2)) - 60;

     }
     else
     {
     		$xpos = ($px - ($textwidth/2)) - 80;

     }
	$ypos = 110;
	// Add the text
	imagettftext($im, $fontsize, 0, $xpos, $ypos, $white, $font, strtoupper($state).',');



/*  CALL SO AND SO  */

    $fontsize = 29;

	$font = $conf['font']['titlefont'];
	$box = @imageTTFBbox($fontsize,0,$font,'CALL '.strtoupper($senator));
	$textwidth = abs($box[4] - $box[0]);
	$textheight = abs($box[5] - $box[1]);
	$xpos = ($px - ($textwidth/2)) - 70;
	$ypos = 250;
		$color = ($party =='D') ? $blue : $red;

	imagettftext($im, $fontsize, 0, $xpos, $ypos, $color, $font, 'CALL '.strtoupper($senator));


/*  Call to action */
 	$cta = "We need you to to call ".$row['informal_name']." now and\ntell ".$gender_mod." not to cut these vital programs.";
/*  GENDER MODIFIER  */

    $fontsize = 25;

	$font = $conf['font']['titlefont'];
	imagettftext($im, $fontsize, 0, 167, 581, $white, $font, $gender_mod);


/*  bottom rocker  */

    $fontsize = 29;

	$font = $conf['font']['titlefont'];
	$box = @imageTTFBbox($fontsize,0,$font,'CALL '.strtoupper($informal_name).' TODAY');
	$textwidth = abs($box[4] - $box[0]);
	$textheight = abs($box[5] - $box[1]);
	$xpos = ($px - ($textwidth/2)) - 10;
	$ypos = 723;

	imagettftext($im, $fontsize, 0, $xpos, $ypos, $white, $font, 'CALL '.strtoupper($informal_name).' TODAY');



/*  Message  */

    $fontsize = 24;

	$font = $conf['font']['titlefont'];
	$box = @imageTTFBbox($fontsize,0,$font,$message);
	$textwidth = abs($box[4] - $box[0]);
	$textheight = abs($box[5] - $box[1]);
	$xpos = 22;
	$ypos = 305;

	imagettftext($im, $fontsize, 0, $xpos, $ypos, $white, $font, $message);



/*  CTA  */

    $fontsize = 24;

	$font = $conf['font']['titlefont'];
	$box = @imageTTFBbox($fontsize,0,$font,$cta);
	$textwidth = abs($box[4] - $box[0]);
	$textheight = abs($box[5] - $box[1]);
	$xpos = ($px - ($textwidth/2)) - 10;
	$ypos = 488;
	$color = ($party =='D') ? $white : $black;

	imagettftext($im, $fontsize, 0, 40, $ypos, $color, $font,$cta);


	imagecopymerge($im, $im_leg, 410, 61, 0, 14, 184, 186, 100);
	imagealphablending($im, true);
	imagealphablending($arrow_point, true);


	imagecopy($im, $arrow_point, 391, 192, 0,0, imagesx($arrow_point), imagesy($arrow_point));

	
	imagepng($im);

	imagedestroy($im_leg);
	imagedestroy($im);