<?php
if(isset($_POST['submit']))
{
include('../dbcon.php');
    include('../notification_helper.php');
    $rollno=$_POST['rollno'];
    $class=$_POST['class'];
    $hindi1=$_POST['hindi1'];
    $english1=$_POST['english1'];
    $math1=$_POST['math1'];
    $physics1=$_POST['physics1'];
    $chemestry1=$_POST['chemestry1'];
    $hindi2=$_POST['hindi2'];
    $english2=$_POST['english2'];
    $math2=$_POST['math2'];
    $physics2=$_POST['physics2'];
    $chemestry2=$_POST['chemestry2'];
    
    $sql="UPDATE `user_mark` SET  `u_hindi1` = '$hindi1', `u_english1` = '$english1', `u_math1` = '$math1', `u_physics1` = '$physics1', `u_chemestry1` = '$chemestry1', `u_hindi2` = '$hindi2', `u_english2` = '$english2', `u_math2` = '$math2', `u_physics2` = '$physics2', `u_chemestry` = '$chemestry2' WHERE `u_rollno` = '$rollno' AND `u_class` = '$class'";
    
    $run=mysqli_query($con,$sql);
    if($run)
    {
        $notificationMessage = notify_parent_after_marks(
            $con,
            $class,
            $rollno,
            array(
                'hindi1' => (int)$hindi1,
                'english1' => (int)$english1,
                'math1' => (int)$math1,
                'physics1' => (int)$physics1,
                'chemestry1' => (int)$chemestry1,
                'hindi2' => (int)$hindi2,
                'english2' => (int)$english2,
                'math2' => (int)$math2,
                'physics2' => (int)$physics2,
                'chemestry2' => (int)$chemestry2
            ),
            'Exam marks updated'
        );
        ?>
        <script>
        alert('Data Updated  Succesfully\n<?php echo addslashes($notificationMessage); ?>');
        window.open('updatemark_form.php?sid=<?php echo $rollno; ?>', '_self');
             </script>
       
       
        <?php
    }
    else
    {
        echo "Error";
    }
}
?>