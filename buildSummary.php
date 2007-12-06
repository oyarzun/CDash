<?php
/*=========================================================================

  Program:   CDash - Cross-Platform Dashboard System
  Module:    $RCSfile: common.php,v $
  Language:  PHP
  Date:      $Date$
  Version:   $Revision$

  Copyright (c) 2002 Kitware, Inc.  All rights reserved.
  See Copyright.txt or http://www.cmake.org/HTML/Copyright.html for details.

     This software is distributed WITHOUT ANY WARRANTY; without even 
     the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR 
     PURPOSE.  See the above copyright notices for more information.

=========================================================================*/
include("config.php");
include("common.php");

@$buildid = $_GET["buildid"];
@$date = $_GET["date"];

include("config.php");
$db = mysql_connect("$CDASH_DB_HOST", "$CDASH_DB_LOGIN","$CDASH_DB_PASS");
mysql_select_db("$CDASH_DB_NAME",$db);
  
$build_array = mysql_fetch_array(mysql_query("SELECT * FROM build WHERE id='$buildid'"));  
$projectid = $build_array["projectid"];
$date = date("Ymd", strtotime($build_array["starttime"]));
    
$project = mysql_query("SELECT * FROM project WHERE id='$projectid'");
if(mysql_num_rows($project)>0)
  {
  $project_array = mysql_fetch_array($project);
  $svnurl = $project_array["cvsurl"];
  $homeurl = $project_array["homeurl"];
  $bugurl = $project_array["bugtrackerurl"];   
  $projectname = $project_array["name"];  
  }

list ($previousdate, $currenttime, $nextdate) = get_dates($date,$project_array["nightlytime"]);
$logoid = getLogoID($projectid);

$xml = '<?xml version="1.0"?><cdash>';
$xml .= "<title>CDash : ".$projectname."</title>";
$xml .= "<cssfile>".$CDASH_CSS_FILE."</cssfile>";
$xml .="<dashboard>
  <datetime>".date("D, d M Y H:i:s",strtotime($build_array["starttime"]))."</datetime>
  <date>".$date."</date>
  <svn>".$svnurl."</svn>
  <bugtracker>".$bugurl."</bugtracker> 
  <home>".$homeurl."</home>
  <projectid>".$projectid."</projectid> 
  <logoid>".$logoid."</logoid> 
  <projectname>".$projectname."</projectname> 
  <previousdate>".$previousdate."</previousdate> 
  <nextdate>".$nextdate."</nextdate> 
  </dashboard>
  ";
  
  // Build
  $xml .= "<build>";
  $build = mysql_query("SELECT * FROM build WHERE id='$buildid'");
  $build_array = mysql_fetch_array($build); 
  $siteid = $build_array["siteid"];
  $site_array = mysql_fetch_array(mysql_query("SELECT name FROM site WHERE id='$siteid'"));
  $xml .= add_XML_value("site",$site_array["name"]);
  $xml .= add_XML_value("name",$build_array["name"]);
  $xml .= add_XML_value("id",$build_array["id"]);
  $xml .= add_XML_value("time",date("Y-m-d H:i:s T",strtotime($build_array["starttime"]." UTC")));  
  $xml .= add_XML_value("type",$build_array["type"]);
		
		// Find the last submit date
		$buildtype = $build_array["type"];
		$buildname = $build_array["name"];
		$starttime = $build_array["starttime"];
  $previousbuild = mysql_query("SELECT id,starttime FROM build
                             WHERE siteid='$siteid' AND type='$buildtype' AND name='$buildname'
																													AND projectid='$projectid' AND starttime<'$starttime' ORDER BY starttime DESC LIMIT 1");

			if(mysql_num_rows($previousbuild)>0)
					{
					$previousbuild_array = mysql_fetch_array($previousbuild);              
					$lastsubmitbuild = $previousbuild_array["id"];
					$lastsubmitdate = date("Y-m-d H:i:s T",strtotime($previousbuild_array["starttime"]." UTC"));
					}
		else
		  {
				$lastsubmitbuild = 0;
				$lastsubmitdate = 0;
	  	}
		$xml .= add_XML_value("generator",$build_array["generator"]);
		$xml .= add_XML_value("command",$build_array["command"]);
		$xml .= add_XML_value("starttime",date("Y-m-d H:i:s T",strtotime($build_array["starttime"]." UTC")));	
		$xml .= add_XML_value("endtime",date("Y-m-d H:i:s T",strtotime($build_array["endtime"]." UTC")));	
		
		$xml .= add_XML_value("lastsubmitdate",$lastsubmitdate);
		$xml .= add_XML_value("lastsubmitdate",$lastsubmitdate);
		
	
		// Number of errors and warnings
		$builderror = mysql_query("SELECT count(buildid) FROM builderror WHERE buildid='$buildid' AND type='0'");
		$builderror_array = mysql_fetch_array($builderror);
		$nerrors = $builderror_array[0];
		$xml .= add_XML_value("error",$nerrors);
		$buildwarning = mysql_query("SELECT count(buildid) FROM builderror WHERE buildid='$buildid' AND type='1'");
		$buildwarning_array = mysql_fetch_array($buildwarning);
		$nwarnings = $buildwarning_array[0];
			
		$xml .= add_XML_value("nerrors",$nerrors);
		$xml .= add_XML_value("nwarnings",$nwarnings);
		
		
		// Display the errors
		$errors = mysql_query("SELECT * FROM builderror WHERE buildid='$buildid' and type='0'");
  while($error_array = mysql_fetch_array($errors))
    {
    $xml .= "<error>";
    $xml .= add_XML_value("logline",$error_array["logline"]);
    $xml .= add_XML_value("text",$error_array["text"]);
    $xml .= add_XML_value("sourcefile",$error_array["sourcefile"]);
    $xml .= add_XML_value("sourceline",$error_array["sourceline"]);
    $xml .= add_XML_value("precontext",$error_array["precontext"]);
    $xml .= add_XML_value("postcontext",$error_array["postcontext"]);
    $xml .= "</error>";
    }
		
		
		// Display the warnings
		$errors = mysql_query("SELECT * FROM builderror WHERE buildid='$buildid' and type='1'");
  while($error_array = mysql_fetch_array($errors))
    {
    $xml .= "<warning>";
    $xml .= add_XML_value("logline",$error_array["logline"]);
    $xml .= add_XML_value("text",$error_array["text"]);
    $xml .= add_XML_value("sourcefile",$error_array["sourcefile"]);
    $xml .= add_XML_value("sourceline",$error_array["sourceline"]);
    $xml .= add_XML_value("precontext",$error_array["precontext"]);
    $xml .= add_XML_value("postcontext",$error_array["postcontext"]);
    $xml .= "</warning>";
    }
		
  $xml .= "</build>";

  // Update
		$xml .= "<update>";
		
		// Checking for locally modify files
		$updatelocal = mysql_query("SELECT buildid FROM updatefile WHERE buildid='$buildid' AND author='Local User'");						
		$nerrors = mysql_num_rows($updatelocal);
		$nwarnings = 0;
		$xml .= add_XML_value("nerrors",$nerrors);
		$xml .= add_XML_value("nwarnings",$nwarnings);
		
		$update = mysql_query("SELECT buildid FROM updatefile WHERE buildid='$buildid'");
		$nupdates = mysql_num_rows($update);
  $xml .= add_XML_value("nupdates",$nupdates);  
					
		$update = mysql_query("SELECT * FROM buildupdate WHERE buildid='$buildid'");
  $update_array = mysql_fetch_array($update);
  $xml .= add_XML_value("command",$update_array["command"]);
  $xml .= add_XML_value("type",$update_array["type"]);
  $xml .= add_XML_value("starttime",date("Y-m-d H:i:s T",strtotime($update_array["starttime"]." UTC")));
		$xml .= add_XML_value("endtime",date("Y-m-d H:i:s T",strtotime($update_array["endtime"]." UTC")));
		$xml .= "</update>";
		
		
		// Configure
		$xml .= "<configure>";
		$configure = mysql_query("SELECT * FROM configure WHERE buildid='$buildid'");
  $configure_array = mysql_fetch_array($configure);
  
		$nerrors = 0;
		
	 if($configure_array["status"]!=0)
	   {
				$nerrors = 1;
	   }
		
		$nwarnings = 0;
		$xml .= add_XML_value("nerrors",$nerrors);
		$xml .= add_XML_value("nwarnings",$nwarnings);
  

  $xml .= add_XML_value("status",$configure_array["status"]);
  $xml .= add_XML_value("command",$configure_array["command"]);
  $xml .= add_XML_value("output",$configure_array["log"]);
  $xml .= add_XML_value("starttime",date("Y-m-d H:i:s T",strtotime($configure_array["starttime"]." UTC")));
		$xml .= add_XML_value("endtime",date("Y-m-d H:i:s T",strtotime($configure_array["endtime"]." UTC")));
  $xml .= "</configure>";

		// Test
		$xml .= "<test>";
		$nerrors = 0;
		$nwarnings = 0;
		$xml .= add_XML_value("nerrors",$nerrors);
		$xml .= add_XML_value("nwarnings",$nwarnings);
		
		$npass_array = mysql_fetch_array(mysql_query("SELECT count(testid) FROM build2test WHERE buildid='$buildid' AND status='passed'"));
  $npass = $npass_array[0];
		$nnotrun_array = mysql_fetch_array(mysql_query("SELECT count(testid) FROM build2test WHERE buildid='$buildid' AND status='notrun'"));
  $nnotrun = $nnotrun_array[0];
  $nfail_array = mysql_fetch_array(mysql_query("SELECT count(testid) FROM build2test WHERE buildid='$buildid' AND status='failed'"));
  $nfail = $nfail_array[0];
  
		$xml .= add_XML_value("npassed",$npass);
		$xml .= add_XML_value("nnotrun",$nnotrun);  
		$xml .= add_XML_value("nfailed",$nfail); 
		
		
		
		
		
		$xml .= "</test>";
		
		

		
		
  $xml .= "</cdash>";
 

// Now doing the xslt transition
generate_XSLT($xml,"buildSummary");
?>
