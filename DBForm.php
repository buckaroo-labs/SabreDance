<?php
//this file is included by index.php if the DB connection requires setup or modification
?>
<form action="index.php" method="post">
DB Name: <input type="text" name="dbname" value="<?php echo $settings['DBName'];?>"><br>
Username: <input type="text" name="dbuser" value="<?php echo $settings['DBUser'];?>"><br>
Password: <input type="text" name="dbpass" value="<?php echo $settings['DBPass'];?>"><br>
DB Host: <input type="text" name="dbhost" value="<?php echo $settings['DBHost'];?>"><br>
<input type="submit" name="submit" value="Submit">  
</form>
