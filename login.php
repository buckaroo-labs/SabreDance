<?php
$pagetitle="Log In";
include "Hydrogen/pgTemplate.php";
?>

<!-- Main content: shift it to the right by 250 pixels when the sidebar is visible -->
<div class="w3-main" style="margin-left:250px">

  <div class="w3-row w3-padding-64">
    <div class="w3-twothird w3-container">

	<?php include "Hydrogen/pgLogin.php"; ?>

    </div>
    <div class="w3-third w3-container">
        <ul>
        <li><a target="_blank" href="server.php">server.php</a></li>
<li><a target="_blank" href="calendarserver.php">calendarserver.php</a></li>
<li><a target="_blank" href="addressbookserver.php">addressbookserver.php</a></li>
<li><a target="_blank" href="reminders.php">reminders.php</a></li>
</ul>

    </div>
  </div>

</div>

<?php include "Hydrogen/elemFooter.php"; ?>
</body></html>




