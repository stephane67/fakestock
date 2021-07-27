<?php

/**
 * Plugin Name: Fake Stock
 * Plugin URI: 
 * Description: Simulate a low product stock to trigger the visitor's purchase. Scarcity is one of the most important trigger to increase sales.
 * Version: 1.0
 * Author: Stéphane
 * Author URI: https://beeondrugs.com/fakestock
 */


/*
SHORTCODE TO USE FAKESTOCK
*/

function shortcode_fakestock($atts = array(), $content = null)
{
    /*
    DATABASE VALUES
    */
    $minimum = abs(get_option('option_minimum'));
    $refresh_rate = get_option('refresh_stock');
    $decrease_speed = get_option('decrease_speed');

    /*
    MAXIMUM STOCK PASSED AS SHORTCODE PARAMETER
    */
    if (ctype_digit($content)) {
        $maximum = $content;
    } else {
        $maximum = $minimum;
    }

    /*
    REFRESH RATE
    */
    $proportional = 1;
    if ($refresh_rate == "week") {
        //case week
        $day = date("l");
        $day_of_week = date('N', strtotime($day));
        $proportional = $day_of_week / 7;
    } else {
        //case month
        $dayOfMonth = (int)date('d', time());
        $proportional = $dayOfMonth / 31;
    }

    /*
    DECREASE SPEED
    */
    if ($decrease_speed == 1) {
        //fast decrease
        $exp = 0.2;
    } elseif ($decrease_speed == 3) {
        //slow decrease
        $exp = 2;
    } else {
        //proportional decrease
        $exp = 1;
    }

    if ($minimum > $maximum) {
        $maximum = $minimum;
    }

    /*
    CALCULATE STOCK
    */
    $stock_available = ($maximum - $minimum) * (1 - pow($proportional, $exp)) + $minimum;
    $stock_availableIntSup = ceil($stock_available);

    //TODO uncomment for debug
    //return $stock_availableIntSup . " refresh rate : " . $refresh_rate . " stock_available : " . $stock_available;

    return $stock_availableIntSup;
}

add_shortcode('fakestock', 'shortcode_fakestock');



/*
ADMIN SETTINGS PAGE
*/

function fakestock_register_settings()
{
    add_option('option_minimum', '1');
    register_setting('fakestock_options_group', 'option_minimum', 'fakestock_callback');
    add_option('refresh_stock', '1');
    register_setting('fakestock_options_group', 'refresh_stock', 'fakestock_callback');
    add_option('decrease_speed', '2');
    register_setting('fakestock_options_group', 'decrease_speed', 'fakestock_callback');
}
add_action('admin_init', 'fakestock_register_settings');

function fakestock_register_options_page()
{
    add_options_page('Fake Stock settings', 'Fake Stock', 'manage_options', 'fakestock', 'fakestock_options_page');
}
add_action('admin_menu', 'fakestock_register_options_page');



/*
OPTION PAGE
*/

function fakestock_options_page()
{
?>
    <div>
        <?php screen_icon(); ?>
        <h2>Fake Stock settings</h2>
        <?php echo get_option('decrease_speed'); ?>
        <?php echo get_option('refresh_stock'); ?>
        <form method="post" action="options.php">
            <?php settings_fields('fakestock_options_group'); ?>
            <table>
                <tr valign="top">
                    <img src="<?php echo plugin_dir_url(__FILE__) . 'img/arrows.svg'; ?>" width="500">
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="decrease_speed">Chosse decrease speed</label></th>
                    <td><input type="number" min="1" max="3" id="decrease_speed" name="decrease_speed" value="<?php echo get_option('decrease_speed'); ?>" /></td>
                    <td>(1 = fast then slow decrease, 2 = proportional decrease, 3 = slow then fast decrease) </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="option_minimum">Choose the minimum stock</label></th>
                    <td><input type="number" min="0" id="option_minimum" name="option_minimum" value="<?php echo get_option('option_minimum'); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="refresh_stock">Choose the refresh rate</label></th>
                    <td>
                        <select id="refresh_stock" name="refresh_stock" value="<?php echo get_option('refresh_stock'); ?>" />
                        <option selected="selected" value="<?php echo get_option('refresh_stock'); ?>"></option>
                        <option value="week">Every week (monday)</option>
                        <option value="month">Every month (first day of the month)</option>
                        </select>
                    </td>
                    <td>(choose when the stock comes back to its maximum again)</td>
                </tr>

            </table>
            <?php submit_button(); ?>
        </form>

        <h3> Exemple of usage</h3>
        You want to show your customers that your number of products available is 22 on Monday, and 1 left on Sunday. You also want the stock to decrease very fast the last days.
        <p>1. Adjust the settings :</p>
        <ul>
            <li>Decrease speed = 3</li>
            <li>Minimum stock = 1</li>
            <li>Refresh rate = Every week</li>
        </ul>
        <br />
        </p>2. Put this shortcode where you want to show the stock :</p>
        </p><b><i>[fakestock]22[/fakestock]</p></b></i>
        <br />
        </p>3. That's it !</p>
    </div>
<?php
} ?>