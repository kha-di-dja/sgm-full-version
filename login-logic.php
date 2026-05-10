<?php
include ("login-connect.php");
if(isset($_POST['submit'])){
$email=$_POST['email'];
$password=$_POST['password'];
$sql="SELECT * FROM login WHERE email='$email' AND password='$password'";
$result=mysqli_query($conn,$sql);
$row=mysqli_fetch_array($result,MYSQLI_ASSOC);
$count=mysqli_num_rows($result);
if($count==1){
    echo "Login successful";
    header("Location:student.php");
}
else{
    echo " Invalid email or password !";
}
}
?>