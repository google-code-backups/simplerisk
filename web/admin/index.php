<?
        /* This Source Code Form is subject to the terms of the Mozilla Public
         * License, v. 2.0. If a copy of the MPL was not distributed with this
         * file, You can obtain one at http://mozilla.org/MPL/2.0/. */

        // Include required functions file
        require_once('../includes/functions.php');
        require_once('../includes/authenticate.php');

        // Session handler is database
        session_set_save_handler('db_open', 'db_close', '_read', '_write', '_destroy', '_clean');

        // Start the session
        session_start('SimpleRisk');

        // Check for session timeout or renegotiation
        session_check();

	// Default is no alert
	$alert = false;

        // Check if access is authorized
        if ($_SESSION["admin"] != "1")
        {
                header("Location: /");
                exit(0);
        }

        // Check if the risk level update was submitted
        if (isset($_POST['update_risk_levels']))
        {
		// There is an alert message
		$alert = true;

                $high = (int)$_POST['high'];
                $medium = (int)$_POST['medium'];
                $low = (int)$_POST['low'];
                $risk_model = (int)$_POST['risk_models'];

                // Check if all values are integers
                if (is_int($high) && is_int($medium) && is_int($low) && is_int($risk_model))
                {
                        // Check if low < medium < high
                        if (($low < $medium) && ($medium < $high))
                        {
                                // Update the risk level
                                update_risk_levels($high, $medium, $low);

                		// Audit log
                		$risk_id = 1000;
                		$message = "Risk level scoring was modified by the \"" . $_SESSION['user'] . "\" user.";
                		write_log($risk_id, $_SESSION['uid'], $message);

				// TODO: This message will never be seen because of the alert condition for the risk model.
				$alert_message = "The configuration was updated successfully.";
                        }
			// Otherwise, there was a problem
			else $alert_message = "Your LOW risk needs to be less than your MEDIUM risk which needs to be less than your HIGH risk.";

                        // Risk model should be between 1 and 5
                        if ((1 <= $risk_model) && ($risk_model <= 5))
                        {
                                // Update the risk model
                                update_risk_model($risk_model);

                                // Audit log
                                $risk_id = 1000;
                                $message = "The risk formula was modified by the \"" . $_SESSION['user'] . "\" user.";
                                write_log($risk_id, $_SESSION['uid'], $message);

				$alert_message = "The configuration was updated successfully.";
                        }
			// Otherwise, there was a problem
			else $alert_message = "The risk formula submitted was an invalid value.";
                }
        }
?>

<!doctype html>
<html>
  
  <head>
    <script src="/js/jquery.min.js"></script>
    <script src="/js/bootstrap.min.js"></script>
    <title>SimpleRisk: Enterprise Risk Management Simplified</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta content="text/html; charset=UTF-8" http-equiv="Content-Type">
    <link rel="stylesheet" href="/css/bootstrap.css">
    <link rel="stylesheet" href="/css/bootstrap-responsive.css"> 
    <style type="text/css">.text-rotation {display: block; -webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg);}</style>
  </head>
  
  <body>
    <? if ($alert) echo "<script>alert(\"" . $alert_message . "\");</script>" ?>
    <title>SimpleRisk: Enterprise Risk Management Simplified</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta content="text/html; charset=UTF-8" http-equiv="Content-Type">
    <link rel="stylesheet" href="/css/bootstrap.css">
    <link rel="stylesheet" href="/css/bootstrap-responsive.css">
    <link rel="stylesheet" href="/css/divshot-util.css">
    <link rel="stylesheet" href="/css/divshot-canvas.css">
    <div class="navbar">
      <div class="navbar-inner">
        <div class="container">
          <a class="brand" href="http://code.google.com/p/simplerisk/">SimpleRisk</a>
          <div class="navbar-content">
            <ul class="nav">
              <li>
                <a href="/index.php">Home</a> 
              </li>
              <li>
                <a href="/management/index.php">Risk Management</a> 
              </li>
              <li>
                <a href="/reports/index.php">Reporting</a> 
              </li>
              <li class="active">
                <a href="/admin/index.php">Configure</a>
              </li>
            </ul>
          </div>
<?
if ($_SESSION["access"] == "granted")
{
          echo "<div class=\"btn-group pull-right\">\n";
          echo "<a class=\"btn dropdown-toggle\" data-toggle=\"dropdown\" href=\"#\">".$_SESSION['name']."<span class=\"caret\"></span></a>\n";
          echo "<ul class=\"dropdown-menu\">\n";
          echo "<li>\n";
          echo "<a href=\"/account/profile.php\">My Profile</a>\n";
          echo "</li>\n";
          echo "<li>\n";
          echo "<a href=\"/logout.php\">Logout</a>\n";
          echo "</li>\n";
          echo "</ul>\n";
          echo "</div>\n";
}
?>
        </div>
      </div>
    </div>
    <div class="container-fluid">
      <div class="row-fluid">
        <div class="span3">
          <ul class="nav  nav-pills nav-stacked">
            <li class="active">
              <a href="/admin/index.php">Configure Risk Formula</a> 
            </li>
            <li>
              <a href="/admin/review_settings.php">Configure Review Settings</a>
            </li>
            <li>
              <a href="/admin/add_remove_values.php">Add and Remove Values</a> 
            </li>
            <li>
              <a href="/admin/user_management.php">User Management</a> 
            </li>
            <li>
              <a href="/admin/custom_names.php">Redefine Naming Conventions</a> 
            </li>
            <li>
              <a href="/admin/audit_trail.php">Audit Trail</a>
            </li>
            <li>
              <a href="/admin/extras.php">Extras</a>
            </li>
            <li>
              <a href="/admin/announcements.php">Announcements</a>
            </li>
            <li>
              <a href="/admin/about.php">About</a>        
            </li>
          </ul>
        </div>
        <div class="span9">
          <div class="row-fluid">
            <div class="span12">
              <div class="hero-unit">
                <h4>My Classic Risk Formula Is:</h4>

                <form name="risk_levels" method="post" action="">
                <p>RISK = <? create_dropdown("risk_models", get_setting("risk_model")) ?></p>

                <? $risk_levels = get_risk_levels(); ?>

                <p>I consider HIGH risk to be anything greater than: <input type="text" name="high" size="2" value="<? echo $risk_levels[2]['value']; ?>" /></p>
                <p>I consider MEDIUM risk to be less than above, but greater than: <input type="text" name="medium" size="2" value="<? echo $risk_levels[1]['value']; ?>" /></p>
                <p>I consider LOW risk to be less than above, but greater than: <input type="text" name="low" size="2" value="<? echo $risk_levels[0]['value']; ?>" /></p>

                <input type="submit" value="Update" name="update_risk_levels" />

                </form>

                <? create_risk_table(); ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>

</html>
