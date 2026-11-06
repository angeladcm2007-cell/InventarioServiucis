<?php
session_start();
error_reporting(0);
include('includes/config.php');
if(strlen($_SESSION['alogin'])=="")
    {   
    header("Location: index.php"); 
    }
    else{

$stid=intval($_GET['stid']);
if (isset($_GET['stid'])!="")
{
   $stid=$_GET['stid'];
    $deleteQuery = "DELETE FROM staff WHERE StudentId=$stid";
   
}

?>

       
<?PHP } ?>
